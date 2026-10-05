# ============ Stage 1: Dependencies ============
FROM node:22-alpine AS deps

WORKDIR /app

# Install libc6-compat for Alpine
RUN apk add --no-cache libc6-compat

# Enable pnpm
RUN corepack enable && corepack prepare pnpm@latest --activate

# Copy package files
COPY package.json pnpm-lock.yaml* ./

# Allow native packages to run their build scripts in Docker context
# (onlyBuiltDependencies in root package.json is not available here)
RUN --mount=type=cache,id=pnpm,target=/root/.local/share/pnpm/store \
  pnpm config set ignore-scripts true && \
  pnpm install --frozen-lockfile && \
  pnpm rebuild @parcel/watcher @swc/core esbuild msw sharp unrs-resolver

# ============ Stage 2: Builder ============
FROM node:22-alpine AS builder

WORKDIR /app

# Enable pnpm
RUN corepack enable && corepack prepare pnpm@latest --activate

# Copy dependencies from deps stage
COPY --from=deps /app/node_modules ./node_modules

# Copy application source
COPY . .

# Set environment for build
ENV NEXT_TELEMETRY_DISABLED=1
ENV NODE_ENV=production

# Build Next.js application
RUN pnpm build

# ============ Stage 3: Production Runner ============
FROM node:22-alpine AS production

WORKDIR /app

# Install wget for health checks
RUN apk add --no-cache wget

# Set environment
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
ENV HOSTNAME=0.0.0.0
ENV PORT=3456

# Create non-root user
RUN addgroup --system --gid 1001 nodejs && \
  adduser --system --uid 1001 nextjs

# Copy necessary files from builder
COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Switch to non-root user
USER nextjs

# Expose port
EXPOSE 3456

# Start the application
CMD ["node", "server.js"]

# ============ Stage 4: Development (Optimized for Hot Reload) ============
FROM node:22-alpine AS development

WORKDIR /app

# Install system dependencies for development
RUN apk add --no-cache wget bash libc6-compat

# Enable pnpm (we'll use it if pnpm-lock.yaml exists, otherwise npm)
RUN corepack enable && corepack prepare pnpm@latest --activate

# Set environment variables
ENV NODE_ENV=development
ENV NEXT_TELEMETRY_DISABLED=1
ENV HOSTNAME=0.0.0.0
ENV PORT=6543

# Copy package files first for better layer caching
COPY package.json package-lock.json* pnpm-lock.yaml* ./

# Install dependencies in the image (not at runtime)
# This makes container startup much faster
# Use pnpm if pnpm-lock.yaml exists, otherwise use npm with--legacy-peer-deps
RUN if [ -f pnpm-lock.yaml ]; then \
      echo "Using pnpm..." && \
      pnpm config set ignore-scripts true && \
      pnpm install --frozen-lockfile && \
      pnpm add -D @next/swc-linux-x64-musl && \
      pnpm rebuild @parcel/watcher @swc/core esbuild msw sharp unrs-resolver; \
    elif [ -f package-lock.json ]; then \
      echo "Using npm with legacy peer deps..." && \
      npm ci --legacy-peer-deps && \
      npm install --save-dev @next/swc-linux-x64-musl; \
    else \
      echo "Using npm..." && \
      npm install --legacy-peer-deps && \
      npm install --save-dev @next/swc-linux-x64-musl; \
    fi

# Note: Source code will be mounted as volume at runtime
# This allows hot-reload without rebuilding the image

# Expose port
EXPOSE 6543

# Start Next.js development server
# Use npm by default (can be overridden in docker-compose)
CMD ["npm", "run", "dev"]