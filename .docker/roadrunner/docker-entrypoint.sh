#!/bin/sh
set -e

# RoadRunner Docker Entrypoint Script
# Handles environment variable substitution and initialization

# Run any initialization scripts in docker-entrypoint.d
if [ -d /docker-entrypoint.d ]; then
    for f in /docker-entrypoint.d/*.sh; do
        if [ -f "$f" ] && [ -x "$f" ]; then
            echo "Running $f"
            "$f"
        fi
    done
fi

# Ensure opcache directory exists (already created in Dockerfile with proper permissions)
mkdir -p /tmp/opcache 2>/dev/null || true

# Environment-based configuration
if [ "${APP_ENV:-prod}" = "dev" ]; then
    echo "Development mode: Using .rr.dev.yaml configuration"
    RR_CONFIG="${RR_CONFIG:-.rr.dev.yaml}"
else
    echo "Production mode: Using .rr.yaml configuration"
    RR_CONFIG="${RR_CONFIG:-.rr.yaml}"
fi

# Export for RoadRunner
export RR_CONFIG

# If first argument is 'rr', use it directly
if [ "$1" = "rr" ]; then
    # Check if -c flag is already provided
    if echo "$@" | grep -q "\-c "; then
        exec "$@"
    else
        # Add config file if not specified
        shift
        exec rr serve -c "${RR_CONFIG}" "$@"
    fi
fi

# If first argument looks like a flag, assume it's for RoadRunner
if [ "${1#-}" != "$1" ]; then
    exec rr serve -c "${RR_CONFIG}" "$@"
fi

# Otherwise, execute the command as-is (allows running php, bash, etc.)
exec "$@"
