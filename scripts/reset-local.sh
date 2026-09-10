#!/usr/bin/env bash

set -Eeuo pipefail

readonly script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly project_root="$(cd -- "${script_dir}/.." && pwd)"
env_input="${LOCALDEV_ENV_FILE:-.env.localdev}"

if [[ "${env_input}" = /* ]]; then
	env_file="${env_input}"
else
	env_file="${project_root}/${env_input}"
fi
readonly env_file

cd "${project_root}"

if [[ ! -f "${env_file}" ]]; then
	printf 'No localdev env file found at %s; refusing to guess which Compose project to reset.\n' "${env_file}" >&2
	exit 1
fi

set -a
# shellcheck disable=SC1090
source "${env_file}"
set +a

readonly project_name="${COMPOSE_PROJECT_NAME:-wppostable}"

if [[ "${1:-}" != "--yes" ]]; then
	printf 'This removes the %s Compose resources and generated local-dev files.\n' "${project_name}"
	if [[ ! -t 0 ]]; then
		printf 'Run again with --yes to confirm this project-scoped reset.\n' >&2
		exit 1
	fi
	read -r -p "Type '${project_name}' to continue: " confirmation
	if [[ "${confirmation}" != "${project_name}" ]]; then
		printf 'Reset cancelled.\n'
		exit 0
	fi
fi

if [[ -d "${project_root}/local-dev" ]]; then
	docker compose --env-file "${env_file}" run --rm --no-deps --user root --entrypoint sh php \
		-c 'find /srv/web -mindepth 1 -delete'
fi

docker compose --env-file "${env_file}" down --volumes --remove-orphans
rmdir "${project_root}/local-dev" 2>/dev/null || true

printf 'Reset complete for Compose project %s. The localdev env file was preserved.\n' "${project_name}"
