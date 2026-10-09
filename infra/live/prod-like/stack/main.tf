# prod-like, layer 1 of 2 (stack; storage follows in P4-06). Apply with `make tf-apply env=prod-like`.
# Local state (ADR-0011): terraform.tfstate here, git-ignored, holds secrets.

terraform {
  required_version = ">= 1.16.0"

  required_providers {
    docker = {
      source  = "kreuzwerker/docker"
      version = "4.6.0"
    }
  }
}

# infra/tf.sh mounts the host's Docker socket into the tools container
provider "docker" {
  host = "unix:///var/run/docker.sock"
}

variable "image_tag" {
  description = "Image tag to run (infra/tf.sh passes the short SHA of HEAD unless tag=… is given)."
  type        = string
}

variable "app_env" {
  type = string
}

variable "proxy_port" {
  type = number
}

variable "app_url" {
  type = string
}

module "stack" {
  source = "../../../modules/stack"

  env        = "prod-like"
  app_env    = var.app_env
  image_tag  = var.image_tag
  proxy_port = var.proxy_port
  app_url    = var.app_url
  config_dir = "${path.root}/../../../../docker"
}

output "url" {
  value = module.stack.url
}

output "containers" {
  value = module.stack.containers
}
