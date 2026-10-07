#!/bin/bash
# Kept for backwards compatibility: `make up` is the canonical entry point
# (generates env on first run, then builds and starts the stack).
set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if ! command -v make >/dev/null 2>&1; then
    echo ">> [ERROR] 'make' is required (e.g. sudo apt install make)." >&2
    exit 1
fi

exec make -C "$ROOT_DIR" up
