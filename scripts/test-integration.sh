#!/usr/bin/env bash

set -Eeuo pipefail

readonly script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly project_root="$(cd -- "${script_dir}/.." && pwd)"
readonly compose_file="${project_root}/tests/integration/docker-compose.yml"
readonly artifact_root="${WPPA_IT_ARTIFACTS:-${project_root}/tests/integration/artifacts}"

profile="${1:-all}"

usage() {
	cat <<'USAGE'
Usage: scripts/test-integration.sh [minimum|latest|all]

Profiles:
  minimum  WordPress 6.0 on PHP 7.4
  latest   Latest stable WordPress on PHP 8.4
  all      Run minimum, then latest (default)

Environment:
  WPPA_IT_MINIMUM_CORE_VERSION  Override the minimum WordPress version.
  WPPA_IT_MINIMUM_CLI_IMAGE     Override the minimum WP-CLI image.
  WPPA_IT_LATEST_CORE_VERSION   Override the latest WordPress version.
  WPPA_IT_LATEST_CLI_IMAGE      Override the latest WP-CLI image.
  WPPA_IT_DB_IMAGE              Override the MariaDB image.
  WPPA_IT_ARTIFACTS             Override the diagnostic artifact directory.
  KEEP_INTEGRATION_ENV=1        Keep this run's uniquely named Docker resources.
USAGE
}

case "${profile}" in
	minimum|latest)
		;;
	all)
		"$0" minimum
		"$0" latest
		exit 0
		;;
	-h|--help)
		usage
		exit 0
		;;
	*)
		usage >&2
		exit 2
		;;
esac

case "${profile}" in
	minimum)
		readonly core_version="${WPPA_IT_MINIMUM_CORE_VERSION:-6.0}"
		readonly cli_image="${WPPA_IT_MINIMUM_CLI_IMAGE:-wordpress:cli-php7.4}"
		readonly expected_wp_prefix="6.0"
		readonly expected_php_prefix="7.4"
		;;
	latest)
		readonly core_version="${WPPA_IT_LATEST_CORE_VERSION:-latest}"
		readonly cli_image="${WPPA_IT_LATEST_CLI_IMAGE:-wordpress:cli-php8.4}"
		readonly expected_wp_prefix=""
		readonly expected_php_prefix="8.4"
		;;
esac

readonly run_nonce="$(date -u +%Y%m%dT%H%M%SZ)-$$-${RANDOM}"
readonly run_id="${profile}-${run_nonce}"
readonly compose_project="wppait${profile}$$${RANDOM}"
readonly artifact_dir="${artifact_root}/${run_id}"
readonly command_log="${artifact_dir}/commands.log"

mkdir -p "${artifact_dir}"
: > "${command_log}"

dc() {
	WPPA_IT_PROFILE="${profile}" \
	WPPA_IT_WP_CLI_IMAGE="${cli_image}" \
	docker compose \
		-f "${compose_file}" \
		-p "${compose_project}" \
		"$@"
}

wp_run() {
	dc exec -T wp-cli php -d memory_limit=512M /usr/local/bin/wp \
		--allow-root \
		--path=/var/www/html \
		"$@"
}

run_logged() {
	printf '+ ' | tee -a "${command_log}"
	printf '%q ' "$@" | tee -a "${command_log}"
	printf '\n' | tee -a "${command_log}"
	"$@" 2>&1 | tee -a "${command_log}"
}

collect_diagnostics() {
	dc logs --no-color > "${artifact_dir}/compose.log" 2>&1 || :
	dc exec -T wp-cli sh -c \
		'test ! -f /var/www/html/wp-content/debug.log || cat /var/www/html/wp-content/debug.log' \
		> "${artifact_dir}/wp-debug.log" 2>&1 || :
}

cleanup() {
	local status="$?"
	local cleanup_status=0
	trap - EXIT

	set +e
	collect_diagnostics
	if [[ "${KEEP_INTEGRATION_ENV:-0}" != "1" ]]; then
		dc down --volumes --remove-orphans >> "${command_log}" 2>&1 || cleanup_status="$?"
	else
		printf 'Kept Docker project %s for investigation.\n' "${compose_project}" \
			| tee -a "${command_log}"
	fi
	set -e

	if (( cleanup_status != 0 )); then
		printf 'Could not remove Docker project %s; diagnostics: %s\n' \
			"${compose_project}" "${artifact_dir}" >&2
		if (( status == 0 )); then
			status="${cleanup_status}"
		fi
	fi

	if (( status != 0 )); then
		printf 'Integration profile %s failed; diagnostics: %s\n' "${profile}" "${artifact_dir}" >&2
	fi

	exit "${status}"
}
trap cleanup EXIT

run_logged dc up -d --wait db wp-cli
run_logged wp_run core download --version="${core_version}" --locale=en_US --force
run_logged wp_run config create \
	--dbname=wordpress \
	--dbuser=wordpress \
	--dbpass=wordpress \
	--dbhost=db:3306 \
	--skip-check \
	--force
run_logged wp_run db check --skip-ssl
run_logged wp_run config set WP_DEBUG true --raw
run_logged wp_run config set WP_DEBUG_LOG true --raw
run_logged wp_run config set WP_DEBUG_DISPLAY false --raw
run_logged wp_run core install \
	--url="http://${compose_project}.test" \
	--title="wpPostAble Integration" \
	--admin_user=admin \
	--admin_password=integration-password \
	--admin_email=admin@example.test \
	--skip-email

run_logged dc exec -T wp-cli sh -eu -c '
	mkdir -p /var/www/html/wp-content/mu-plugins
	ln -s /workspace/tests/fixtures/wppostable-local.php \
		/var/www/html/wp-content/mu-plugins/wppostable-local.php
'

actual_wp_version="$(wp_run core version)"
actual_php_version="$(dc exec -T wp-cli php -r 'echo PHP_VERSION;')"

if [[ -n "${expected_wp_prefix}" && "${actual_wp_version}" != "${expected_wp_prefix}"* ]]; then
	printf 'Expected WordPress %s.x, got %s.\n' "${expected_wp_prefix}" "${actual_wp_version}" >&2
	exit 1
fi
if [[ "${actual_php_version}" != "${expected_php_prefix}"* ]]; then
	printf 'Expected PHP %s.x, got %s.\n' "${expected_php_prefix}" "${actual_php_version}" >&2
	exit 1
fi

{
	printf 'profile=%s\n' "${profile}"
	printf 'compose_project=%s\n' "${compose_project}"
	printf 'wordpress=%s\n' "${actual_wp_version}"
	printf 'php=%s\n' "${actual_php_version}"
	printf 'wordpress_request=%s\n' "${core_version}"
	printf 'wp_cli_image=%s\n' "${cli_image}"
	printf 'db_image=%s\n' "${WPPA_IT_DB_IMAGE:-mariadb:10.11}"
} > "${artifact_dir}/environment.txt"

printf '+ wp eval-file /workspace/tests/integration/wp-lifecycle.php\n' | tee -a "${command_log}"
wp_run eval-file /workspace/tests/integration/wp-lifecycle.php \
	2> "${artifact_dir}/lifecycle.stderr" \
	| tee "${artifact_dir}/lifecycle.json" \
	| tee -a "${command_log}"

if ! grep -Eq '"status"[[:space:]]*:[[:space:]]*"pass"' "${artifact_dir}/lifecycle.json"; then
	printf 'Lifecycle did not report pass; see %s.\n' "${artifact_dir}" >&2
	exit 1
fi

collect_diagnostics
if grep -En 'PHP (Fatal error|Parse error|Warning)' \
	"${artifact_dir}/lifecycle.stderr" \
	"${artifact_dir}/wp-debug.log"; then
	printf 'PHP fatal/parse/warning diagnostics found in %s.\n' "${artifact_dir}" >&2
	exit 1
fi

printf 'Integration profile %s passed (WordPress %s, PHP %s). Evidence: %s\n' \
	"${profile}" \
	"${actual_wp_version}" \
	"${actual_php_version}" \
	"${artifact_dir}"
