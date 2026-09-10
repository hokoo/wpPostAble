#!/usr/bin/env bash

set -Eeuo pipefail

readonly script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly project_root="$(cd -- "${script_dir}/.." && pwd)"
readonly env_example="${project_root}/.env.localdev.example"
env_input="${LOCALDEV_ENV_FILE:-.env.localdev}"

if [[ "${env_input}" = /* ]]; then
	env_file="${env_input}"
else
	env_file="${project_root}/${env_input}"
fi
readonly env_file

cd "${project_root}"

if ! command -v docker >/dev/null 2>&1 || ! docker compose version >/dev/null 2>&1; then
	printf 'Docker Compose v2 is required.\n' >&2
	exit 1
fi

if [[ ! -f "${env_file}" ]]; then
	cp "${env_example}" "${env_file}"
	printf 'Created %s from .env.localdev.example.\n' "${env_file}"
fi

set -a
# shellcheck disable=SC1090
source "${env_file}"
set +a

: "${WP_HOST:=wppostable.local}"
: "${WP_HTTP_PORT:=8089}"
: "${WP_DEBUG:=1}"
: "${WP_TITLE:=wpPostAble Local}"
: "${WP_ADMIN_USER:=admin}"
: "${WP_ADMIN_PASSWORD:?Set WP_ADMIN_PASSWORD in ${env_file}}"
: "${WP_ADMIN_EMAIL:?Set WP_ADMIN_EMAIL in ${env_file}}"

if [[ ! "${WP_HTTP_PORT}" =~ ^[0-9]+$ ]] || (( WP_HTTP_PORT < 1 || WP_HTTP_PORT > 65535 )); then
	printf 'WP_HTTP_PORT must be an integer from 1 to 65535.\n' >&2
	exit 1
fi

if [[ "${WP_HOST}" == *://* || "${WP_HOST}" == */* || "${WP_HOST}" == *:* ]]; then
	printf 'WP_HOST must contain a hostname only, without a scheme, port, or path.\n' >&2
	exit 1
fi

wp_url="http://${WP_HOST}"
if [[ "${WP_HTTP_PORT}" != "80" ]]; then
	wp_url="${wp_url}:${WP_HTTP_PORT}"
fi
readonly wp_url

dc() {
	docker compose --env-file "${env_file}" "$@"
}

mkdir -p "${project_root}/local-dev"

printf 'Building and starting db + PHP for %s...\n' "${COMPOSE_PROJECT_NAME:-wppostable}"
dc up -d --build --wait --remove-orphans db php

dc exec -T --user root php sh -eu -c '
	chown -R wodby:wodby /srv/web
	install -d -o wodby -g wodby /srv/web/wp-content
'

dc exec -T php sh -eu -c '
	cd /srv/web

	if [ ! -f wp-load.php ]; then
		printf "Downloading WordPress core into /srv/web...\n"
		wp core download --force
	fi

	if [ ! -f wp-config.php ]; then
		printf "Creating wp-config.php...\n"
		wp config create \
			--dbname="${DB_NAME}" \
			--dbuser="${DB_USER}" \
			--dbpass="${DB_PASSWORD}" \
			--dbhost="${DB_HOST}:${DB_PORT}" \
			--dbcharset=utf8mb4 \
			--skip-check
	fi

	wp config set DB_NAME "${DB_NAME}" --type=constant --quiet
	wp config set DB_USER "${DB_USER}" --type=constant --quiet
	wp config set DB_PASSWORD "${DB_PASSWORD}" --type=constant --quiet
	wp config set DB_HOST "${DB_HOST}:${DB_PORT}" --type=constant --quiet

	case "${WP_DEBUG}" in
		1|true|TRUE|yes|YES|on|ON)
			wp config set WP_DEBUG true --raw --type=constant --quiet
			wp config set WP_DEBUG_LOG /var/log/php/error.log --type=constant --quiet
			wp config set WP_DEBUG_DISPLAY true --raw --type=constant --quiet
			;;
		*)
			wp config set WP_DEBUG false --raw --type=constant --quiet
			wp config set WP_DEBUG_DISPLAY false --raw --type=constant --quiet
			;;
	esac
'

# The library currently has no runtime dependencies, but Composer's generated
# autoloader is the consumer contract exercised by the local fixture.
dc exec -T --workdir /workspace php composer dump-autoload --no-interaction

dc exec -T --user root php sh -eu -c '
	link_path=/srv/web/wp-content/mu-plugins/wppostable-local.php
	target_path=/workspace/tests/fixtures/wppostable-local.php

	mkdir -p "$(dirname "${link_path}")"
	if [ -L "${link_path}" ]; then
		if [ "$(readlink "${link_path}")" != "${target_path}" ]; then
			rm "${link_path}"
		fi
	elif [ -e "${link_path}" ]; then
		printf "Refusing to replace non-symlink path: %s\n" "${link_path}" >&2
		exit 1
	fi
	if [ ! -L "${link_path}" ]; then
		ln -s "${target_path}" "${link_path}"
		chown -h wodby:wodby "${link_path}"
	fi
'

if ! dc exec -T php wp --url="${wp_url}" core is-installed >/dev/null 2>&1; then
	printf 'Installing WordPress at %s...\n' "${wp_url}"
	dc exec -T php wp core install \
		--url="${wp_url}" \
		--title="${WP_TITLE}" \
		--admin_user="${WP_ADMIN_USER}" \
		--admin_password="${WP_ADMIN_PASSWORD}" \
		--admin_email="${WP_ADMIN_EMAIL}" \
		--skip-email
else
	printf 'WordPress is already installed; preserving its content.\n'
	dc exec -T php wp option update home "${wp_url}" --quiet
	dc exec -T php wp option update siteurl "${wp_url}" --quiet
fi

dc exec -T php wp eval '
	if ( ! class_exists( "WpPostAbleLocalItem" ) || ! post_type_exists( WpPostAbleLocalItem::POST_TYPE ) ) {
		fwrite( STDERR, "wpPostAble local fixture was not loaded.\n" );
		exit( 1 );
	}
'

printf 'Starting nginx...\n'
dc up -d --wait --remove-orphans nginx

if ! LOCALDEV_ENV_FILE="${env_file}" "${script_dir}/local-domain.sh" check; then
	printf '\nLocal-domain mapping is incomplete. Run `make hosts-add`, then retry.\n' >&2
fi

printf '\nWordPress is ready: %s\n' "${wp_url}"
