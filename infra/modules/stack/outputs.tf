output "url" {
  description = "Entry point of the environment."
  value       = "http://localhost:${var.proxy_port}"
}

output "network" {
  description = "Docker network of the environment."
  value       = docker_network.this.name
}

output "containers" {
  description = "Container names."
  value = concat(
    [for c in [docker_container.postgres, docker_container.redis, docker_container.minio, docker_container.php, docker_container.nextjs, docker_container.docs, docker_container.proxy] : c.name],
    [for c in docker_container.worker : c.name],
  )
}
