#!/usr/bin/env bash
# Apply OPcache environment variables for PHP 8.4 optimization
#
# Supported environment variables:
#   PHP_OPCACHE_ENABLE (default: 1)
#   PHP_OPCACHE_ENABLE_CLI (default: 0)
#   PHP_OPCACHE_VALIDATE_TIMESTAMPS (default: 1)
#   PHP_OPCACHE_REVALIDATE_FREQ (default: 2)
#   PHP_OPCACHE_JIT (default: tracing for FPM, function for CLI)
#   PHP_OPCACHE_JIT_BUFFER_SIZE (default: 128M)
#   PHP_OPCACHE_MEMORY_CONSUMPTION (default: 256)

set -euo pipefail

OPCACHE_INI="/usr/local/etc/php/conf.d/zz-opcache-dynamic.ini"

echo "[OPcache] Applying PHP 8.4 OPcache configuration..."

# Create dynamic OPcache configuration
cat > "${OPCACHE_INI}" <<EOF
; Dynamic OPcache configuration - generated from environment variables
; File: ${OPCACHE_INI}
; Generated: $(date -Iseconds)

[opcache]
opcache.enable=${PHP_OPCACHE_ENABLE:-1}
opcache.enable_cli=${PHP_OPCACHE_ENABLE_CLI:-0}
opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS:-1}
opcache.revalidate_freq=${PHP_OPCACHE_REVALIDATE_FREQ:-2}
opcache.memory_consumption=${PHP_OPCACHE_MEMORY_CONSUMPTION:-256}
opcache.jit=${PHP_OPCACHE_JIT:-tracing}
opcache.jit_buffer_size=${PHP_OPCACHE_JIT_BUFFER_SIZE:-128M}
EOF

# Log the applied configuration
echo "[OPcache] Configuration applied:"
echo "  - opcache.enable=${PHP_OPCACHE_ENABLE:-1}"
echo "  - opcache.enable_cli=${PHP_OPCACHE_ENABLE_CLI:-0}"
echo "  - opcache.jit=${PHP_OPCACHE_JIT:-tracing}"
echo "  - opcache.jit_buffer_size=${PHP_OPCACHE_JIT_BUFFER_SIZE:-128M}"

# Check for Xdebug compatibility warning
if [[ "${XDEBUG_MODE:-off}" != "off" && "${PHP_OPCACHE_JIT:-tracing}" != "off" ]]; then
    echo "[OPcache] WARNING: Xdebug is enabled with JIT. Setting JIT to 'off' for compatibility."
    sed -i 's/opcache.jit=.*/opcache.jit=off/' "${OPCACHE_INI}"
fi
