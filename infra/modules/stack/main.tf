# One Terraform-managed environment on the local Docker engine (ADR-0011): network, volumes and the app
# containers. Images are built outside Terraform (ci/build.sh); configs are read from docker/,
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
    site     = "${local.name}-public-site"
    minio    = "${local.name}-minio"
    reverb   = "${local.name}-reverb"
  }

  # Buckets and the backend's MinIO user, as in docker/.env (ADR-0014)
  bucket_official = "media-official"
  bucket_temp     = "media-temp"
  minio_iam_user  = "backend-user"

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
    # The admin app treats a request as signed in when this cookie exists (SESSION_COOKIE_NAME in
    # nextjs-fe/src/shared/config/constant.ts); without it Laravel derives the name from APP_NAME
    "SESSION_COOKIE=laravel_session",
    "CACHE_STORE=redis",
    # Background jobs and live notifications (ADR-0014): queue on Redis, Reverb in its own container
    "QUEUE_CONNECTION=redis",
    "BROADCAST_CONNECTION=reverb",
    "REVERB_APP_ID=second-memory",
    "REVERB_APP_KEY=${var.reverb_app_key}",
    "REVERB_APP_SECRET=${random_password.reverb.result}",
    "REVERB_HOST=${local.hosts.reverb}",
    "REVERB_PORT=8080",
    "REVERB_SCHEME=http",
    "REVERB_SERVER_HOST=0.0.0.0",
    "REVERB_SERVER_PORT=8080",
    # Files (ADR-0014): the API talks to MinIO on the network and signs browser URLs for the proxy's host
    "AWS_ACCESS_KEY_ID=${local.minio_iam_user}",
    "AWS_SECRET_ACCESS_KEY=${random_password.minio_iam.result}",
    "AWS_DEFAULT_REGION=us-east-1",
    "AWS_BUCKET=${local.bucket_official}",
    "AWS_BUCKET_OFFICIAL=${local.bucket_official}",
    "AWS_BUCKET_TEMP=${local.bucket_temp}",
    "AWS_ENDPOINT=http://${local.hosts.minio}:9000",
    "AWS_PUBLIC_ENDPOINT=${var.app_url}",
    "AWS_USE_PATH_STYLE_ENDPOINT=true",
  ]

  # queue:work, schedule:work and the Reverb server run the API image with another command (dev: ml-queue …)
  workers = {
    queue = {
      command = ["php", "artisan", "queue:work", "redis", "--sleep=1", "--timeout=900", "--max-time=3600"]
      health  = ["CMD-SHELL", "grep -q artisan /proc/1/cmdline"]
    }
    scheduler = {
      command = ["php", "artisan", "schedule:work"]
      health  = ["CMD-SHELL", "grep -q artisan /proc/1/cmdline"]
    }
    reverb = {
      command = ["php", "artisan", "reverb:start", "--host=0.0.0.0", "--port=8080"]
      health  = ["CMD", "php", "-r", "exit(@fsockopen('127.0.0.1', 8080) ? 0 : 1);"]
    }
  }

  # Compose's proxy config with the dev upstream names (ml-php:9000 …) swapped for this environment's
  nginx_site = replace(file("${var.config_dir}/nginx/default.conf"), "/\\bml-(php|public-site|nextjs|reverb|minio):/", "${local.name}-$1:")
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

resource "random_password" "reverb" {
  length  = 32
  special = false
}

resource "random_password" "minio_root" {
  length  = 32
  special = false
}

resource "random_password" "minio_iam" {
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
  for_each = toset(["postgres", "redis", "minio"])
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
    # Ships `mc` for docker/minio/create-buckets.sh; the server runs sm-minio (same base, ci/build.sh)
    minio = "pgsty/minio:RELEASE.2026-08-04T00-00-00Z"
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

# Runs once per API image / config change; php starts only after it exits 0. Migrations, then a
# permission row per admin route (ADR-0013), then (seed_root) the root owner so the environment can be
# logged into. Every step is idempotent.
resource "docker_container" "migrate" {
  name   = "${local.name}-migrate"
  image  = "sm-api:${var.image_tag}"
  memory = var.memory_mb.migrate
  env    = local.laravel_env
  command = ["sh", "-c", join(" && ", concat(
    ["php artisan migrate --force --no-interaction", "php artisan permission:sync-routes"],
    var.seed_root ? ["php artisan db:seed --class=RootAccountSeeder --force --no-interaction"] : [],
  ))]
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

resource "docker_container" "minio" {
  name    = local.hosts.minio
  image   = "sm-minio:${var.image_tag}"
  restart = "unless-stopped"
  memory  = var.memory_mb.minio

  env = [
    "MINIO_ROOT_USER=sm-admin",
    "MINIO_ROOT_PASSWORD=${random_password.minio_root.result}",
  ]
  # No console port: the environment only publishes the proxy
  command = ["server", "/data"]

  volumes {
    volume_name    = docker_volume.data["minio"].name
    container_path = "/data"
  }

  networks_advanced {
    name = docker_network.this.id
  }

  healthcheck {
    test         = ["CMD", "curl", "-f", "http://localhost:9000/minio/health/live"]
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

# Buckets, lifecycle rules and the backend user, with Compose's script (idempotent)
resource "docker_container" "minio_init" {
  name       = "${local.name}-minio-init"
  image      = docker_image.upstream["minio"].image_id
  user       = "1000:1000"
  memory     = var.memory_mb.minio_init
  entrypoint = ["/bin/sh", "/minio/create-buckets.sh"]
  must_run   = false
  attach     = true
  logs       = true

  env = [
    "MINIO_ENDPOINT=http://${local.hosts.minio}:9000",
    "MINIO_ROOT_USER=sm-admin",
    "MINIO_ROOT_PASSWORD=${random_password.minio_root.result}",
    "MINIO_BUCKET_OFFICIAL=${local.bucket_official}",
    "MINIO_BUCKET_TEMP=${local.bucket_temp}",
    "MINIO_IAM_USER=${local.minio_iam_user}",
    "MINIO_IAM_PASSWORD=${random_password.minio_iam.result}",
    # mc keeps its config under $HOME/.mc; the unprivileged user has no home
    "HOME=/tmp",
  ]

  dynamic "upload" {
    for_each = toset(["create-buckets.sh", "ilm-history.json", "ilm-temp.json"])
    content {
      file    = "/minio/${upload.value}"
      content = file("${var.config_dir}/minio/${upload.value}")
    }
  }

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

  depends_on = [docker_container.minio]

  lifecycle {
    postcondition {
      condition     = self.exit_code == 0
      error_message = "MinIO setup failed: docker logs ${local.name}-minio-init"
    }
  }
}

resource "docker_container" "worker" {
  for_each = local.workers

  name    = "${local.name}-${each.key}"
  image   = "sm-api:${var.image_tag}"
  restart = "unless-stopped"
  memory  = var.memory_mb[each.key]
  env     = local.laravel_env
  command = each.value.command

  networks_advanced {
    name = docker_network.this.id
  }

  healthcheck {
    test         = each.value.health
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

  depends_on = [docker_container.migrate, docker_container.redis, docker_container.minio_init]
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

  depends_on = [docker_container.migrate, docker_container.redis, docker_container.minio_init]
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
    test         = ["CMD-SHELL", "wget --quiet --tries=1 --spider http://localhost:6543/admin/login || exit 1"]
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

resource "docker_container" "site" {
  name    = local.hosts.site
  image   = "sm-public-site:${var.image_tag}"
  restart = "unless-stopped"
  memory  = var.memory_mb.site
  # Server-side calls to the public API go through this environment's proxy, not dev's ml-nginx
  env = [
    "PORT=4321",
    "HOST=0.0.0.0",
    "API_INTERNAL_URL=http://${local.name}-proxy:8080/api",
  ]

  networks_advanced {
    name = docker_network.this.id
  }

  healthcheck {
    test         = ["CMD-SHELL", "wget --quiet --tries=1 --spider http://127.0.0.1:4321/healthz || exit 1"]
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

  depends_on = [docker_container.php, docker_container.nextjs, docker_container.site, docker_container.worker, docker_container.minio]

  lifecycle {
    precondition {
      condition     = !strcontains(local.nginx_site, "ml-")
      error_message = "docker/nginx/default.conf has a dev upstream (ml-…) the name swap does not cover."
    }
  }
}
