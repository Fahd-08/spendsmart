#!/bin/sh
# Bouwt de coverage-driver PCOV voor de PHP van XAMPP (macOS) en zet hem in tools/pcov.so.
# Eenmalig nodig; vereist de Xcode Command Line Tools (xcode-select --install).
set -e

PHP_CONFIG=/Applications/XAMPP/xamppfiles/bin/php-config
VERSION=1.0.12
TOOLS_DIR="$(cd "$(dirname "$0")" && pwd)"
WORK_DIR="$(mktemp -d)"

cd "$WORK_DIR"
curl -sSL -o pcov.tgz "https://pecl.php.net/get/pcov-$VERSION.tgz"
tar xzf pcov.tgz
cd "pcov-$VERSION"

# XAMPP-PHP is x86_64. Een minimale libSystem-stub voorkomt een linkerfout met nieuwe macOS-SDK's.
cat > libSystem.tbd <<'TBD'
--- !tapi-tbd
tbd-version:     4
targets:         [ x86_64-macos ]
install-name:    '/usr/lib/libSystem.B.dylib'
current-version: 1351
...
TBD

cc -arch x86_64 -O2 -fPIC -shared -nostdlib libSystem.tbd -Wl,-undefined,dynamic_lookup \
    -DCOMPILE_DL_PCOV=1 -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1 \
    $($PHP_CONFIG --includes) pcov.c -o "$TOOLS_DIR/pcov.so"

rm -rf "$WORK_DIR"
echo "Klaar: $TOOLS_DIR/pcov.so"
