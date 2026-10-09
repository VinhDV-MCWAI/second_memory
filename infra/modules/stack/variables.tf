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
  description = "Tag of the sm-api / sm-nextjs-fe / sm-nextjs-docs images built by infra/build-images.sh (short git SHA)."
  type        = string
}

variable "docs_enabled" {
  description = "Create the public docs container. Off until the docs app builds a production image (P4-03)."
  type        = bool
  default     = true
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

variable "memory_mb" {
  description = "Memory limit per container in MB (ADR-0011 budget: ≤ 1.5 GB per environment)."
  type        = map(number)
  default = {
    postgres = 384
    redis    = 96
    php      = 384
    migrate  = 256
    nextjs   = 256
    docs     = 192
    proxy    = 64
  }
}
