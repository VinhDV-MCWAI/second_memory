# Shared image for the pnpm workspace apps (nextjs-fe, nextjs-docs).
# Build context is the repository root so installs use the single root pnpm-lock.yaml:
#   docker build -f docker/nextjs/Dockerfile --build-arg APP=nextjs-fe --target production .
# Context filtering: docker/nextjs/Dockerfile.dockerignore

ARG NODE_IMAGE=node:22-alpine

# ============ Base: pnpm pinned by the root package.json "packageManager" ============
FROM ${NODE_IMAGE} AS base
RUN apk add --no-cache libc6-compat
ENV NEXT_TELEMETRY_DISABLED=1
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
ENV NODE_ENV=production
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
RUN apk add --no-cache wget bash
ENV NODE_ENV=development
WORKDIR /repo/${APP}
EXPOSE 6543
CMD ["pnpm", "dev"]
