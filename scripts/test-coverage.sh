#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd -- "${SCRIPT_DIR}/.." && pwd)"

cd "${PROJECT_DIR}"

if [[ ! -f vendor/bin/phpunit ]]; then
    printf 'PHPUnit is not installed. Run composer install first.\n' >&2
    exit 1
fi

PHP_BIN="$(command -v php || true)"

if [[ -z "${PHP_BIN}" ]]; then
    printf 'PHP CLI is required to run coverage.\n' >&2
    exit 1
fi

PHP_ARGS=()
COVERAGE_ENV=()

if "${PHP_BIN}" -r 'exit(extension_loaded("xdebug") ? 0 : 1);'; then
    COVERAGE_ENV=(env XDEBUG_MODE=coverage)
elif "${PHP_BIN}" -r 'exit(extension_loaded("pcov") ? 0 : 1);'; then
    PHP_ARGS=(-d pcov.enabled=1 -d "pcov.directory=${PROJECT_DIR}/src")
else
    EXTENSION_DIR="$("${PHP_BIN}" -r 'echo ini_get("extension_dir");')"
    SHLIB_SUFFIX="$("${PHP_BIN}" -r 'echo PHP_SHLIB_SUFFIX;')"
    XDEBUG_EXTENSION="${EXTENSION_DIR}/xdebug.${SHLIB_SUFFIX}"

    if [[ ! -f "${XDEBUG_EXTENSION}" ]]; then
        printf 'No coverage driver found. Enable Xdebug or PCOV for PHP CLI.\n' >&2
        exit 1
    fi

    PHP_ARGS=(-d "zend_extension=${XDEBUG_EXTENSION}" -d xdebug.mode=coverage)
    COVERAGE_ENV=(env XDEBUG_MODE=coverage)
fi

mkdir -p coverage

"${COVERAGE_ENV[@]}" "${PHP_BIN}" "${PHP_ARGS[@]}" vendor/bin/phpunit \
    --testsuite unit \
    --coverage-text \
    --coverage-clover coverage/clover.xml

"${PHP_BIN}" scripts/check-coverage.php coverage/clover.xml coverage/summary.json
