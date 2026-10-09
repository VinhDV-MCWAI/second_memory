# Shared image for the pnpm workspace apps (nextjs-fe, nextjs-docs).
# Build context is the repository root so installs use the single root pnpm-lock.yaml:
#   docker build -f docker/nextjs/Dockerfile --build-arg APP=nextjs-fe --target production .
# Context filtering: docker/nextjs/Dockerfile.dockerignore

ARG NODE_IMAGE=node:24.21.0-alpine3.24

# ============ Base: pnpm pinned by the root package.json "packageManager" ============
FROM ${NODE_IMAGE} AS base
RUN apk add --no-cache libc6-compat
# Shared corepack cache so the unprivileged dev user finds the pinned pnpm without a download
ENV NEXT_TELEMETRY_DISABLED=1 \
  COREPACK_HOME=/usr/local/share/corepack
WORKDIR /repo
COPY package.json pnpm-workspace.yaml ./
RUN corepack enable && corepack install

# ============ Dependencies (only the selected app and its workspace deps) ============
FROM base AS deps
ARG APP
COPY pnpm-lock.yaml ./
COPY ${APP}/package.json ./${APP}/
RUN --mount=type=cache,id=pnpm,target=/root/.local/share/pnpm/store \
  pnpm install --frozen-lockfile --filter "./${APP}"

# ============ Builder ============
FROM deps AS builder
ARG APP
COPY ${APP} ./${APP}
# Same-origin API path: the browser calls /api on whatever host serves the page (the proxy routes
# it to php-fpm), so one image tag runs in every environment (ADR-0011). Only nextjs-fe reads it.
ARG NEXT_PUBLIC_API_URL=/api
ENV NODE_ENV=production \
  NEXT_PUBLIC_API_URL=${NEXT_PUBLIC_API_URL}
RUN pnpm --filter "./${APP}" build

# ============ Production runner (Next.js standalone output) ============
FROM ${NODE_IMAGE} AS production
ARG APP
RUN apk add --no-cache wget \
  && addgroup --system --gid 1001 nodejs \
  && adduser --system --uid 1001 nextjs
ENV NODE_ENV=production \
  NEXT_TELEMETRY_DISABLED=1 \
  HOSTNAME=0.0.0.0 \
  PORT=3456
WORKDIR /app
# In a workspace the standalone bundle mirrors the repo layout: <root>/<app>/server.js
COPY --from=builder --chown=nextjs:nodejs /repo/${APP}/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /repo/${APP}/.next/static ./${APP}/.next/static
COPY --from=builder /repo/${APP}/public ./${APP}/public
WORKDIR /app/${APP}
USER nextjs
EXPOSE 3456
CMD ["node", "server.js"]

# ============ Development (source is bind-mounted at /repo/<app>) ============
FROM deps AS development
ARG APP
RUN apk add --no-cache wget bash \
  && mkdir -p "/repo/${APP}/.next" \
  && chown node:node /repo "/repo/${APP}" "/repo/${APP}/.next"
# Dependencies come from the lockfile at build time (`--filter` one app), so pnpm's
# run-time check would reinstall the whole workspace into the root-owned node_modules
# before every script. A lockfile change means rebuilding the image instead.
ENV NODE_ENV=development \
  pnpm_config_verify_deps_before_run=false
WORKDIR /repo/${APP}
# The image's `node` user has UID 1000, the usual host UID, so the bind-mounted source and
# the .next volume stay writable without root
USER node
EXPOSE 6543
CMD ["pnpm", "dev"]
