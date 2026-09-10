#!/usr/bin/env bash

set -Eeuo pipefail

readonly script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly project_root="$(cd -- "${script_dir}/.." && pwd)"
readonly evidence_file="${WPPA_PACKAGE_EVIDENCE:-${project_root}/coverage/package-install.json}"

composer_bin="${COMPOSER_BINARY:-composer}"
php_bin="${WPPA_PHP_BINARY:-php}"

if ! command -v "${composer_bin}" >/dev/null 2>&1; then
    printf 'Composer is required for the package-install smoke test.\n' >&2
    exit 2
fi
if ! command -v "${php_bin}" >/dev/null 2>&1; then
    printf 'PHP CLI is required for the package-install smoke test.\n' >&2
    exit 2
fi
if ! command -v unzip >/dev/null 2>&1; then
    printf 'unzip is required for the package-install smoke test.\n' >&2
    exit 2
fi

composer_version="$("${composer_bin}" --version --no-ansi 2>&1)"
readonly composer_version

readonly temporary_root="${TMPDIR:-/tmp}"
work_dir="$(mktemp -d "${temporary_root%/}/wppa-package-smoke.XXXXXX")"
readonly work_dir
readonly archive_dir="${work_dir}/archive"
readonly package_dir="${work_dir}/package"
readonly resolver_dir="${work_dir}/resolver"
readonly consumer_dir="${work_dir}/consumer"
readonly archive_file="${archive_dir}/wppostable-package.zip"

cleanup() {
    local status="$?"
    trap - EXIT

    case "${work_dir}" in
        "${temporary_root%/}"/wppa-package-smoke.*)
            rm -rf -- "${work_dir}"
            ;;
        *)
            printf 'Refusing to remove unexpected temporary path: %s\n' "${work_dir}" >&2
            status=2
            ;;
    esac

    exit "${status}"
}
trap cleanup EXIT

mkdir -p \
    "${archive_dir}" \
    "${package_dir}" \
    "${resolver_dir}" \
    "${consumer_dir}" \
    "$(dirname -- "${evidence_file}")"

"${composer_bin}" \
    --no-interaction \
    --no-ansi \
    --working-dir="${project_root}" \
    archive \
    --format=zip \
    --file=wppostable-package \
    --dir="${archive_dir}"

if [[ ! -s "${archive_file}" ]]; then
    printf 'Composer did not create the expected package archive: %s\n' "${archive_file}" >&2
    exit 1
fi

unzip -q "${archive_file}" -d "${package_dir}"

if [[ ! -f "${package_dir}/composer.json" || ! -d "${package_dir}/src" ]]; then
    printf 'Generated package archive is missing composer.json or src/.\n' >&2
    exit 1
fi

"${php_bin}" -r '
$packageDirectory = $argv[1];
$outputFile = $argv[2];
$configuration = array(
    "name" => "wppostable/package-install-smoke",
    "type" => "project",
    "repositories" => array(
        array("packagist.org" => false),
        array(
            "type" => "path",
            "url" => $packageDirectory,
            "options" => array(
                "symlink" => false,
                "versions" => array("hokoo/wppostable" => "0.0.0"),
            ),
        ),
    ),
    "require" => array("hokoo/wppostable" => "0.0.0"),
    "minimum-stability" => "stable",
    "prefer-stable" => true,
);
$json = json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (false === $json || false === file_put_contents($outputFile, $json . PHP_EOL)) {
    fwrite(STDERR, "Cannot write isolated consumer composer.json.\n");
    exit(1);
}
' "${package_dir}" "${resolver_dir}/composer.json"

export COMPOSER_HOME="${work_dir}/composer-home"
export COMPOSER_NO_INTERACTION=1

(
    cd "${resolver_dir}"
    "${composer_bin}" update --no-dev --prefer-dist --no-progress --no-ansi
)

cp "${resolver_dir}/composer.json" "${resolver_dir}/composer.lock" "${consumer_dir}/"

(
    cd "${consumer_dir}"
    "${composer_bin}" install --no-dev --prefer-dist --no-progress --no-ansi --classmap-authoritative
)

"${php_bin}" \
    "${project_root}/tests/package/install-smoke.php" \
    "${consumer_dir}" \
    "${archive_file}" \
    "${evidence_file}" \
    "${composer_version}"
