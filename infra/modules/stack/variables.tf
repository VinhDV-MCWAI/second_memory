variable "env" {
  description = "Environment name (ADR-0011). Prefixes every Docker object (sm-<env>-…) and sets the sm.env label."
  type        = string

  validation {
    condition     = contains(["staging", "prod-like"], var.env)
    error_message = "env must be staging or prod-like (dev stays on Compose)."
  }
}

variable "app_env" {
  description = "Laravel APP_ENV."
  type        = string
}

variable "image_tag" {
  description = "Tag of the sm-api / sm-nextjs-fe / sm-public-site / sm-minio images built by ci/build.sh (short git SHA)."
  type        = string
}

variable "proxy_port" {
  description = "Host port of the proxy, the only published port (HTTP until P4-08 adds TLS)."
  type        = number
}

variable "app_url" {
  description = "Public URL of the environment (Laravel APP_URL)."
  type        = string
}

variable "config_dir" {
  description = "Path to the repo's docker/ directory: nginx, PostgreSQL and Redis configs are shared with Compose."
  type        = string
}

variable "reverb_app_key" {
  description = "Reverb app key. Public (browsers send it), baked into the admin image: keep it equal to NEXT_PUBLIC_REVERB_APP_KEY in docker/nextjs/Dockerfile."
  type        = string
  default     = "second-memory"
}

variable "seed_root" {
  description = "Create the root owner (root / 12345678, RootAccountSeeder) so a local demo can log in. Never for a real production."
  type        = bool
  default     = false
}

variable "memory_mb" {
  description = "Memory limit per container in MB (ADR-0011 budget ≤ 1.5 GB; ~2 GB since MinIO and the workers joined, CD-01)."
  type        = map(number)
  default = {
    postgres   = 384
    redis      = 96
    php        = 384
    migrate    = 256
    nextjs     = 256
    site       = 128
    proxy      = 64
    minio      = 256
    minio_init = 64
    queue      = 192
    scheduler  = 128
    reverb     = 128
  }
}
