# Playwright runner for the E2E journey (P3-15). Build context is the repo root so the install
# uses the single root pnpm-lock.yaml:
#   docker build -f e2e/Dockerfile -t ml-e2e .
# The image tag must match the @playwright/test version in e2e/package.json (browsers are baked in).
FROM mcr.microsoft.com/playwright:v1.63.0-noble

# Dependencies come from the lockfile at build time, so pnpm must not reinstall before scripts
ENV COREPACK_HOME=/usr/local/share/corepack \
  pnpm_config_verify_deps_before_run=false \
  CI=1
WORKDIR /repo
COPY package.json pnpm-workspace.yaml ./
RUN corepack enable && corepack install
COPY pnpm-lock.yaml ./
COPY e2e/package.json ./e2e/
RUN --mount=type=cache,id=pnpm,target=/root/.local/share/pnpm/store \
  pnpm install --frozen-lockfile --filter ./e2e
COPY e2e ./e2e

WORKDIR /repo/e2e
CMD ["sh", "-c", "pnpm typecheck && pnpm test"]
