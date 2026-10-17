# Public site (public-site/, Astro, ADR-0019). Build context is the repository root so installs use
# the single root pnpm-lock.yaml:
#   docker build -f docker/public-site/Dockerfile --target production .
# Context filtering: docker/public-site/Dockerfile.dockerignore

ARG NODE_IMAGE=node:24.21.0-alpine3.24

# ============ Base: pnpm pinned by the root package.json "packageManager" ============
FROM ${NODE_IMAGE} AS base
ENV COREPACK_HOME=/usr/local/share/corepack \
  ASTRO_TELEMETRY_DISABLED=1
WORKDIR /repo
COPY package.json pnpm-workspace.yaml ./
RUN corepack enable && corepack install && pnpm --version

# ============ Dependencies (this app only) ============
FROM base AS deps
COPY pnpm-lock.yaml ./
COPY public-site/package.json ./public-site/
RUN --mount=type=cache,id=pnpm,target=/root/.local/share/pnpm/store \
  pnpm install --frozen-lockfile --filter ./public-site

# ============ Development (source is bind-mounted at /repo/public-site) ============
FROM deps AS development
RUN apk add --no-cache wget \
  && mkdir -p /repo/public-site/.astro \
  && chown node:node /repo /repo/public-site /repo/public-site/.astro /repo/public-site/node_modules
# Dependencies come from the lockfile at build time; a lockfile change means rebuilding the image
ENV NODE_ENV=development \
  pnpm_config_verify_deps_before_run=false
WORKDIR /repo/public-site
# UID 1000 like the usual host user, so the bind-mounted source stays writable; Vite writes its
# dependency cache in node_modules/.vite
USER node
EXPOSE 4321
CMD ["pnpm", "dev"]

# ============ Builder ============
FROM deps AS builder
COPY public-site ./public-site
ENV NODE_ENV=production
RUN pnpm --filter ./public-site build

# ============ Production runner (Astro Node adapter, standalone server) ============
FROM ${NODE_IMAGE} AS production
RUN apk add --no-cache wget
ENV NODE_ENV=production \
  HOST=0.0.0.0 \
  PORT=4321
WORKDIR /app
# The production build bundles every dependency into dist/ (astro.config.mjs, ssr.noExternal)
COPY --from=builder /repo/public-site/dist ./dist
USER node
EXPOSE 4321
CMD ["node", "dist/server/entry.mjs"]
