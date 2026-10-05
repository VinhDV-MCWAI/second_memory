#!/bin/bash
set -e

echo "🚀 Starting Next.js Development Server..."
echo "📍 Server will be available at http://localhost:${PORT:-6543}"
echo ""

# Dependencies are pre-installed in the Docker image
# Only check for updates if package.json has changed since last run

# Detect package manager
if [ -f "pnpm-lock.yaml" ]; then
  PKG_MANAGER="pnpm"
  LOCK_FILE="pnpm-lock.yaml"
elif [ -f "package-lock.json" ]; then
  PKG_MANAGER="npm"
  LOCK_FILE="package-lock.json"
else
  PKG_MANAGER="npm"
  LOCK_FILE="package.json"
fi

echo "📦 Using package manager: $PKG_MANAGER"

# Create a checksum file location
CHECKSUM_FILE="/tmp/.package-checksum"

# Calculate current package files checksum
if [ -f "$LOCK_FILE" ]; then
  CURRENT_CHECKSUM=$(md5sum package.json $LOCK_FILE 2>/dev/null | md5sum | cut -d' ' -f1)
  
  # Check if checksum file exists and compare
  if [ -f "$CHECKSUM_FILE" ]; then
    STORED_CHECKSUM=$(cat "$CHECKSUM_FILE")
    
    if [ "$CURRENT_CHECKSUM" != "$STORED_CHECKSUM" ]; then
      echo "📦 Dependencies changed, updating..."
      if [ "$PKG_MANAGER" = "pnpm" ]; then
        pnpm install --frozen-lockfile
      else
        npm ci --legacy-peer-deps
      fi
      echo "$CURRENT_CHECKSUM" > "$CHECKSUM_FILE"
      echo "✅ Dependencies updated successfully"
    else
      echo "✅ Dependencies are up to date"
    fi
  else
    # First run, save checksum
    echo "$CURRENT_CHECKSUM" > "$CHECKSUM_FILE"
    echo "✅ Dependencies already installed in image"
  fi
fi

echo ""
echo "🎯 Starting Next.js dev server with hot-reload enabled..."

# Execute the main command
exec "$@"
