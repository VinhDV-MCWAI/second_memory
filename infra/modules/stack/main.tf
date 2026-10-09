# One Terraform-managed environment on the local Docker engine (ADR-0011): network, volumes and the app
# containers. Images are built outside Terraform (infra/build-images.sh); configs are read from docker/,
# the same files Compose mounts, and uploaded into the containers.

locals {
  name   = "sm-${var.env}"
  labels = { "sm.env" = var.env }

  db_name = "second_memory"
  db_user = "sm_app"

  hosts = {
    postgres = "${local.name}-postgres"
    redis    = "${local.name}-redis"
    php      = "${local.name}-php"
    nextjs   = "${local.name}-nextjs"
    docs     = "${local.name}-nextjs-docs"
  }

  # Same variable names as Compose / .env.example; only the values differ
  laravel_env = [
    "APP_NAME=Second Memory",
    "APP_ENV=${var.app_env}",
    "APP_DEBUG=false",
    "APP_KEY=base64:${random_bytes.app_key.base64}",
    "APP_URL=${var.app_url}",
    "LOG_CHANNEL=stderr",
    "LOG_LEVEL=info",
    "DB_CONNECTION=pgsql",
    "DB_HOST=${local.hosts.postgres}",
    "DB_PORT=5432",
    "DB_DATABASE=${local.db_name}",
    "DB_USERNAME=${local.db_user}",
    "DB_PASSWORD=${random_password.db.result}",
    "REDIS_CLIENT=predis",
    "REDIS_HOST=${local.hosts.redis}",
    "REDIS_PORT=6379",
    "REDIS_PASSWORD=${random_password.redis.result}",
    "SESSION_DRIVER=redis",
    "CACHE_STORE=redis",
    "QUEUE_CONNECTION=sync",
  ]

  # Compose's proxy config with the dev upstream names (ml-php:9000 …) swapped for this environment's
  nginx_site = replace(file("${var.config_dir}/nginx/default.conf"), "/\\bml-(php|nextjs-docs|nextjs):/", "${local.name}-$1:")
}

# Secrets are generated and kept in state until SOPS + age replace them (P4-09)
resource "random_password" "db" {
  length  = 32
  special = false
}

resource "random_password" "redis" {
  length  = 32
  special = false
}

resource "random_bytes" "app_key" {
  length = 32
}

resource "docker_network" "this" {
  name = local.name

  dynamic "labels" {
    for_each = local.labels
    content {
      label = labels.key
      value = labels.value
    }
  }
}

resource "docker_volume" "data" {
  for_each = toset(["postgres", "redis"])
  name     = "${local.name}-${each.key}"

  dynamic "labels" {
    for_each = local.labels
    content {
      label = labels.key
      value = labels.value
    }
  }
}

resource "docker_image" "upstream" {
  for_each = {
    postgres = "postgres:16.15-alpine3.24"
    redis    = "redis:7.4.11-alpine3.21"
    proxy    = "nginxinc/nginx-unprivileged:1.25.5-alpine3.19"
  }
  name         = each.value
  keep_locally = true
}

resource "docker_container" "postgres" {
  name    = local.hosts.postgres
  image   = docker_image.upstream["postgres"].image_id
  restart = "unless-stopped"
  memory  = var.memory_mb.postgres

  env = [
    "POSTGRES_DB=${local.db_name}",
    "POSTGRES_USER=${local.db_user}",
    "POSTGRES_PASSWORD=${random_password.db.result}",
    "POSTGRES_INITDB_ARGS=--encoding=UTF8 --locale=en_US.UTF-8",
    "PGDATA=/var/lib/postgresql/data/pgdata",
  ]
  # Dev's tuning scaled to the environment's memory budget
  command = [
    "postgres",
    "-c", "shared_buffers=96MB",
    "-c", "effective_cache_size=256MB",
    "-c", "max_connections=50",
    "-c", "work_mem=4MB",
    "-c", "random_page_cost=1.1",
    "-c", "hba_file=/etc/postgresql/pg_hba.conf",
  ]

  upload {
    file    = "/etc/postgresql/pg_hba.conf"
    content = file("${var.config_dir}/postgres/pg_hba.conf")
  }

  volumes {
    volume_name    = docker_volume.data["postgres"].name
    container_path = "/var/lib/postgresql/data"
  }

  networks_advanced {
    name = docker_network.this.id
  }

  healthcheck {
    test         = ["CMD-SHELL", "pg_isready -U ${local.db_user} -d ${local.db_name}"]
    interval     = "10s"
    timeout      = "5s"
    retries      = 5
    start_period = "10s"
  }
  wait         = true
  wait_timeout = 120

  dynamic "labels" {
    for_each = local.labels
    content {
      label = labels.key
      value = labels.value
    }
  }
}

resource "docker_container" "redis" {
  name    = local.hosts.redis
  image   = docker_image.upstream["redis"].image_id
  restart = "unless-stopped"
  memory  = var.memory_mb.redis

  env = ["REDIS_PASSWORD=${random_password.redis.result}"]
  # Exec form so the image entrypoint drops to the redis user; maxmemory fits the container limit
  command = [
    "redis-server", "/usr/local/etc/redis/redis.conf",
    "--requirepass", random_password.redis.result,
    "--maxmemory", "64mb",
  ]

  upload {
    file    = "/usr/local/etc/redis/redis.conf"
    content = file("${var.config_dir}/redis/redis.conf")
  }

  volumes {
    volume_name    = docker_volume.data["redis"].name
    container_path = "/data"
  }

  networks_advanced {
    name = docker_network.this.id
  }

  healthcheck {
    test         = ["CMD-SHELL", "redis-cli --no-auth-warning -a \"$REDIS_PASSWORD\" ping | grep -q PONG"]
    interval     = "10s"
    timeout      = "3s"
    retries      = 5
    start_period = "5s"
  }
  wait = true

  dynamic "labels" {
    for_each = local.labels
    content {
      label = labels.key
      value = labels.value
    }
  }
}

# Runs the migrations once per API image / config change; php starts only after it exits 0
resource "docker_container" "migrate" {
  name     = "${local.name}-migrate"
  image    = "sm-api:${var.image_tag}"
  memory   = var.memory_mb.migrate
  env      = local.laravel_env
  command  = ["php", "artisan", "migrate", "--force", "--no-interaction"]
  must_run = false
  attach   = true
  logs     = true

  networks_advanced {
    name = docker_network.this.id
  }

  dynamic "labels" {
    for_each = local.labels
    content {
      label = labels.key
      value = labels.value
    }
  }

  depends_on = [docker_container.postgres]

  lifecycle {
    postcondition {
      condition     = self.exit_code == 0
      error_message = "php artisan migrate failed: docker logs ${local.name}-migrate"
    }
  }
}

resource "docker_container" "php" {
  name    = local.hosts.php
  image   = "sm-api:${var.image_tag}"
  restart = "unless-stopped"
  memory  = var.memory_mb.php
  env     = local.laravel_env

  networks_advanced {
    name = docker_network.this.id
  }

  healthcheck {
    test         = ["CMD", "php", "-r", "exit(@fsockopen('127.0.0.1', 9000) ? 0 : 1);"]
    interval     = "15s"
    timeout      = "5s"
    retries      = 3
    start_period = "10s"
  }
  wait = true

  dynamic "labels" {
    for_each = local.labels
    content {
      label = labels.key
      value = labels.value
    }
  }

  depends_on = [docker_container.migrate, docker_container.redis]
}

resource "docker_container" "nextjs" {
  name    = local.hosts.nextjs
  image   = "sm-nextjs-fe:${var.image_tag}"
  restart = "unless-stopped"
  memory  = var.memory_mb.nextjs
  # Port the shared nginx config expects for the admin app
  env = ["PORT=6543", "HOSTNAME=0.0.0.0"]

  networks_advanced {
    name = docker_network.this.id
  }

  healthcheck {
    test         = ["CMD-SHELL", "wget --quiet --tries=1 --spider http://localhost:6543/ || exit 1"]
    interval     = "15s"
    timeout      = "5s"
    retries      = 5
    start_period = "15s"
  }
  wait         = true
  wait_timeout = 120

  dynamic "labels" {
    for_each = local.labels
    content {
      label = labels.key
      value = labels.value
    }
  }
}

resource "docker_container" "docs" {
  name    = local.hosts.docs
  image   = "sm-nextjs-docs:${var.image_tag}"
  restart = "unless-stopped"
  memory  = var.memory_mb.docs
  # Server-side calls to the public API go through this environment's proxy, not dev's ml-nginx
  env = [
    "PORT=3457",
    "HOSTNAME=0.0.0.0",
    "API_INTERNAL_URL=http://${local.name}-proxy:8080/api",
  ]

  networks_advanced {
    name = docker_network.this.id
  }

  healthcheck {
    test         = ["CMD-SHELL", "wget --quiet --tries=1 --spider http://localhost:3457/docs || exit 1"]
    interval     = "15s"
    timeout      = "5s"
    retries      = 5
    start_period = "15s"
  }
  wait         = true
  wait_timeout = 120

  dynamic "labels" {
    for_each = local.labels
    content {
      label = labels.key
      value = labels.value
    }
  }
}

resource "docker_container" "proxy" {
  name    = "${local.name}-proxy"
  image   = docker_image.upstream["proxy"].image_id
  restart = "unless-stopped"
  memory  = var.memory_mb.proxy

  upload {
    file    = "/etc/nginx/nginx.conf"
    content = file("${var.config_dir}/nginx/nginx.conf")
  }

  upload {
    file    = "/etc/nginx/conf.d/default.conf"
    content = local.nginx_site
  }

  ports {
    internal = 8080
    external = var.proxy_port
  }

  networks_advanced {
    name = docker_network.this.id
  }

  healthcheck {
    test         = ["CMD", "wget", "--quiet", "--tries=1", "--spider", "http://localhost:8080/health"]
    interval     = "15s"
    timeout      = "5s"
    retries      = 3
    start_period = "5s"
  }
  wait = true

  dynamic "labels" {
    for_each = local.labels
    content {
      label = labels.key
      value = labels.value
    }
  }

  depends_on = [docker_container.php, docker_container.nextjs, docker_container.docs]

  lifecycle {
    precondition {
      condition     = !strcontains(local.nginx_site, "ml-")
      error_message = "docker/nginx/default.conf has a dev upstream (ml-…) the name swap does not cover."
    }
  }
}
