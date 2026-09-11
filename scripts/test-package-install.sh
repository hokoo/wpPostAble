#!/usr/bin/env bash

set -Eeuo pipefail

readonly script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly project_root="$(cd -- "${script_dir}/.." && pwd)"
readonly evidence_file="${WPPA_PACKAGE_EVIDENCE:-${project_root}/coverage/package-install.json}"
readonly hosted_archive_input="${WPPA_HOSTED_PACKAGE_ARCHIVE:-}"
readonly hosted_target_sha="${WPPA_HOSTED_TARGET_SHA:-}"

composer_bin="${COMPOSER_BINARY:-composer}"
php_bin="${WPPA_PHP_BINARY:-php}"

fail() {
    printf 'Package install smoke failed: %s\n' "$1" >&2
    exit 1
}

if ! command -v "${composer_bin}" >/dev/null 2>&1; then
    printf 'Composer is required for the package-install smoke test.\n' >&2
    exit 2
fi
if ! command -v "${php_bin}" >/dev/null 2>&1; then
    printf 'PHP CLI is required for the package-install smoke test.\n' >&2
    exit 2
fi
if ! command -v git >/dev/null 2>&1; then
    printf 'Git is required for the hosted-package archive smoke test.\n' >&2
    exit 2
fi
if ! command -v unzip >/dev/null 2>&1; then
    printf 'unzip is required for the package-install smoke test.\n' >&2
    exit 2
fi

if [[ -n "${hosted_archive_input}" || -n "${hosted_target_sha}" ]]; then
    [[ -n "${hosted_archive_input}" && -n "${hosted_target_sha}" ]] || \
        fail 'WPPA_HOSTED_PACKAGE_ARCHIVE and WPPA_HOSTED_TARGET_SHA must be supplied together'
    [[ -f "${hosted_archive_input}" && ! -L "${hosted_archive_input}" ]] || \
        fail 'hosted package archive is missing or is a symlink'
    [[ "${hosted_target_sha}" =~ ^[0-9a-f]{40}$ ]] || \
        fail 'hosted package target must be a lowercase full commit SHA'
fi

composer_version="$("${composer_bin}" --version --no-ansi 2>&1)"
readonly composer_version
git_target_sha="$(git -C "${project_root}" rev-parse --verify 'HEAD^{commit}')"
readonly git_target_sha
[[ "${git_target_sha}" =~ ^[0-9a-f]{40}$ ]] || fail 'cannot resolve the current Git commit'
if [[ -n "${hosted_target_sha}" && "${hosted_target_sha}" != "${git_target_sha}" ]]; then
    fail 'hosted package target does not match the checked-out commit'
fi

readonly temporary_root="${TMPDIR:-/tmp}"
work_dir="$(mktemp -d "${temporary_root%/}/wppa-package-smoke.XXXXXX")"
readonly work_dir
readonly archive_dir="${work_dir}/archives"
readonly composer_archive_file="${archive_dir}/composer-archive.zip"
readonly git_archive_file="${archive_dir}/git-archive.zip"

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

mkdir -p "${archive_dir}" "$(dirname -- "${evidence_file}")"

git -C "${project_root}" archive \
    --format=zip \
    --output="${git_archive_file}" \
    "${git_target_sha}"

[[ -s "${git_archive_file}" ]] || fail "Git did not create ${git_archive_file}"

"${composer_bin}" \
    --no-interaction \
    --no-ansi \
    --working-dir="${project_root}" \
    archive \
    --format=zip \
    --file=composer-archive \
    --dir="${archive_dir}"

[[ -s "${composer_archive_file}" ]] || fail "Composer did not create ${composer_archive_file}"

artifact_labels=(git_hosted_equivalent composer_archive)
artifact_files=("${git_archive_file}" "${composer_archive_file}")
artifact_targets=("${git_target_sha}" "${git_target_sha}")

if [[ -n "${hosted_archive_input}" ]]; then
    readonly hosted_archive_file="${archive_dir}/github-hosted.zip"
    cp -- "${hosted_archive_input}" "${hosted_archive_file}"
    [[ -s "${hosted_archive_file}" ]] || fail 'copied hosted package archive is empty'
    artifact_labels+=(github_hosted)
    artifact_files+=("${hosted_archive_file}")
    artifact_targets+=("${hosted_target_sha}")
fi

component_evidence_files=()

resolve_package_root() {
    local extraction_dir="$1"
    local entries=()
    local entry

    if [[ -f "${extraction_dir}/composer.json" && -d "${extraction_dir}/src" ]]; then
        printf '%s\n' "${extraction_dir}"
        return
    fi

    while IFS= read -r -d '' entry; do
        entries+=("${entry}")
    done < <(find "${extraction_dir}" -mindepth 1 -maxdepth 1 -print0)

    [[ "${#entries[@]}" -eq 1 && -d "${entries[0]}" ]] || \
        fail 'archive must contain the package directly or under exactly one root directory'
    [[ -f "${entries[0]}/composer.json" && -d "${entries[0]}/src" ]] || \
        fail 'archive root directory is missing composer.json or src/'

    printf '%s\n' "${entries[0]}"
}

verify_archive() {
    local artifact_label="$1"
    local archive_file="$2"
    local target_sha="$3"
    local artifact_dir="${work_dir}/${artifact_label}"
    local extraction_dir="${artifact_dir}/extracted"
    local resolver_dir="${artifact_dir}/resolver"
    local consumer_dir="${artifact_dir}/consumer"
    local composer_home="${artifact_dir}/composer-home"
    local component_evidence="${artifact_dir}/evidence.json"
    local package_dir

    mkdir -p "${extraction_dir}" "${resolver_dir}" "${consumer_dir}" "${composer_home}"
    unzip -q "${archive_file}" -d "${extraction_dir}"
    package_dir="$(resolve_package_root "${extraction_dir}")"

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

    (
        cd "${resolver_dir}"
        COMPOSER_HOME="${composer_home}" COMPOSER_NO_INTERACTION=1 \
            "${composer_bin}" update --no-dev --prefer-dist --no-progress --no-ansi
    )

    cp "${resolver_dir}/composer.json" "${resolver_dir}/composer.lock" "${consumer_dir}/"

    (
        cd "${consumer_dir}"
        COMPOSER_HOME="${composer_home}" COMPOSER_NO_INTERACTION=1 \
            "${composer_bin}" install --no-dev --prefer-dist --no-progress --no-ansi --classmap-authoritative
    )

    "${php_bin}" \
        "${project_root}/tests/package/install-smoke.php" \
        "${consumer_dir}" \
        "${archive_file}" \
        "${component_evidence}" \
        "${composer_version}" \
        "${artifact_label}" \
        "${target_sha}"

    component_evidence_files+=("${component_evidence}")
}

for artifact_index in "${!artifact_labels[@]}"; do
    verify_archive \
        "${artifact_labels[${artifact_index}]}" \
        "${artifact_files[${artifact_index}]}" \
        "${artifact_targets[${artifact_index}]}"
done

"${php_bin}" -r '
$outputFile = array_pop($argv);
array_shift($argv);
$artifacts = array();
$targetCommit = null;
foreach ($argv as $componentFile) {
    $component = json_decode((string) file_get_contents($componentFile), true);
    if (!is_array($component) || "pass" !== ($component["status"] ?? null)) {
        fwrite(STDERR, "Cannot aggregate package-install evidence.\n");
        exit(1);
    }
    $source = $component["artifact"]["source"] ?? null;
    $componentTarget = $component["artifact"]["target_commit"] ?? null;
    if (!is_string($source) || isset($artifacts[$source])) {
        fwrite(STDERR, "Package-install evidence has a missing or duplicate artifact source.\n");
        exit(1);
    }
    if (null === $targetCommit) {
        $targetCommit = $componentTarget;
    }
    if ($targetCommit !== $componentTarget) {
        fwrite(STDERR, "Package-install artifacts do not share one target commit.\n");
        exit(1);
    }
    $artifacts[$source] = $component;
}
if (!isset($artifacts["composer_archive"], $artifacts["git_hosted_equivalent"])) {
    fwrite(STDERR, "Both Composer and Git archive evidence are required.\n");
    exit(1);
}
$summary = array(
    "schema_version" => 2,
    "status" => "pass",
    "package" => "hokoo/wppostable",
    "target_commit" => $targetCommit,
    "artifact_count" => count($artifacts),
    "artifacts" => $artifacts,
);
$json = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (false === $json || false === file_put_contents($outputFile, $json . PHP_EOL)) {
    fwrite(STDERR, "Cannot write combined package-install evidence.\n");
    exit(1);
}
' "${component_evidence_files[@]}" "${evidence_file}"

printf 'Package install smoke passed for %s: %s\n' \
    "$(IFS=,; printf '%s' "${artifact_labels[*]}")" \
    "${evidence_file}"
