# Tools image for the Terraform environments (ADR-0011): Terraform only runs here, never on the host.
# SOPS + age join in P4-09. Build and run through infra/tf.sh.
FROM hashicorp/terraform:1.16.3

# One-shot CLI, nothing to probe
HEALTHCHECK NONE

# infra/tf.sh runs it as the host user (state and .terraform/ stay the developer's) with the
# Docker socket's group added; providers live in each root's .terraform/ (bind mount)
ENV HOME=/tmp \
  TF_IN_AUTOMATION=1
USER 1000:1000
WORKDIR /work
