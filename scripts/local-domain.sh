#!/usr/bin/env bash

set -Eeuo pipefail

readonly script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly project_root="$(cd -- "${script_dir}/.." && pwd)"
readonly env_example="${project_root}/.env.localdev.example"
readonly action="${1:-check}"
env_input="${LOCALDEV_ENV_FILE:-.env.localdev}"

if [[ "${env_input}" = /* ]]; then
	env_file="${env_input}"
else
	env_file="${project_root}/${env_input}"
fi

if [[ -f "${env_file}" ]]; then
	set -a
	# shellcheck disable=SC1090
	source "${env_file}"
	set +a
else
	set -a
	# shellcheck disable=SC1090
	source "${env_example}"
	set +a
fi

: "${WP_HOST:=wppostable.local}"
: "${COMPOSE_PROJECT_NAME:=wppostable}"

if [[ ! "${WP_HOST}" =~ ^[A-Za-z0-9][A-Za-z0-9.-]*[A-Za-z0-9]$ ]] || [[ "${WP_HOST}" != *.* ]]; then
	printf 'WP_HOST must be a plain hostname with at least one dot: %s\n' "${WP_HOST}" >&2
	exit 2
fi

if [[ ! "${COMPOSE_PROJECT_NAME}" =~ ^[A-Za-z0-9][A-Za-z0-9_.-]*$ ]]; then
	printf 'Invalid COMPOSE_PROJECT_NAME: %s\n' "${COMPOSE_PROJECT_NAME}" >&2
	exit 2
fi

readonly managed_marker="# ${COMPOSE_PROJECT_NAME} local-dev"
readonly managed_entry=$'127.0.0.1\t'"${WP_HOST}"$'\t'"${managed_marker}"

usage() {
	cat <<'USAGE'
Usage: scripts/local-domain.sh check|add|remove

On WSL2, the action applies to both WSL and Windows so a Windows-hosted browser
can resolve the local site. Adding or removing an entry may request sudo/UAC.
USAGE
}

is_wsl() {
	[[ -r /proc/sys/kernel/osrelease ]] && grep -qiE '(microsoft|wsl)' /proc/sys/kernel/osrelease
}

hosts_file_has_mapping() {
	local hosts_file="$1"
	[[ -r "${hosts_file}" ]] || return 1
	awk -v expected_host="${WP_HOST}" '
		{
			line = $0
			sub(/\r$/, "", line)
			sub(/[[:space:]]*#.*/, "", line)
			gsub(/^[[:space:]]+|[[:space:]]+$/, "", line)
			if (line == "") next
			count = split(line, fields, /[[:space:]]+/)
			if (fields[1] != "127.0.0.1") next
			for (field_index = 2; field_index <= count; field_index++) {
				if (fields[field_index] == expected_host) found = 1
			}
		}
		END { exit(found ? 0 : 1) }
	' "${hosts_file}"
}

append_managed_entry() {
	local hosts_file="$1"
	local entry="${managed_entry}"

	if [[ -s "${hosts_file}" && -n "$(tail -c 1 "${hosts_file}")" ]]; then
		entry=$'\n'"${entry}"
	fi

	if [[ -w "${hosts_file}" ]]; then
		printf '%s\n' "${entry}" >> "${hosts_file}"
		return
	fi
	if ! command -v sudo >/dev/null 2>&1; then
		printf 'sudo is required to update %s.\n' "${hosts_file}" >&2
		return 1
	fi
	printf '%s\n' "${entry}" | sudo tee -a "${hosts_file}" >/dev/null
}

remove_managed_entry() {
	local hosts_file="$1"
	local temporary_file
	temporary_file="$(mktemp)"

	awk -v expected_entry="${managed_entry}" '
		{
			line = $0
			sub(/\r$/, "", line)
			if (line != expected_entry) print $0
		}
	' "${hosts_file}" > "${temporary_file}"

	if cmp -s "${hosts_file}" "${temporary_file}"; then
		rm -f "${temporary_file}"
		return
	fi

	if [[ -w "${hosts_file}" ]]; then
		tee "${hosts_file}" < "${temporary_file}" >/dev/null
	elif command -v sudo >/dev/null 2>&1; then
		sudo tee "${hosts_file}" < "${temporary_file}" >/dev/null
	else
		rm -f "${temporary_file}"
		printf 'sudo is required to update %s.\n' "${hosts_file}" >&2
		return 1
	fi
	rm -f "${temporary_file}"
}

windows_hosts_file() {
	local windows_system_dir
	command -v powershell.exe >/dev/null 2>&1 || return 1
	command -v wslpath >/dev/null 2>&1 || return 1
	windows_system_dir="$(powershell.exe -NoProfile -NonInteractive -Command '[Environment]::SystemDirectory' 2>/dev/null | tr -d '\r')"
	[[ -n "${windows_system_dir}" ]] || return 1
	wslpath -u "${windows_system_dir}\\drivers\\etc\\hosts"
}

run_elevated_windows_action() {
	local windows_action="$1"
	local powershell_body encoded_body powershell_launcher encoded_launcher

	command -v iconv >/dev/null 2>&1 || {
		printf 'iconv is required to update the Windows hosts file from WSL2.\n' >&2
		return 1
	}
	command -v base64 >/dev/null 2>&1 || {
		printf 'base64 is required to update the Windows hosts file from WSL2.\n' >&2
		return 1
	}

	powershell_body="$(cat <<'POWERSHELL'
$ErrorActionPreference = 'Stop'
$action = '__ACTION__'
$hostName = '__HOST__'
$marker = '# __PROJECT__ local-dev'
$entry = "127.0.0.1`t$hostName`t$marker"
$hostsPath = Join-Path $env:SystemRoot 'System32\drivers\etc\hosts'
$lines = @(Get-Content -LiteralPath $hostsPath -ErrorAction Stop)

function Has-LoopbackMapping([string] $line) {
    $data = (($line -split '#', 2)[0]).Trim() -split '\s+'
    return $data.Count -ge 2 -and $data[0] -eq '127.0.0.1' -and $data[1..($data.Count - 1)] -contains $hostName
}

if ($action -eq 'add') {
    if (-not ($lines | Where-Object { Has-LoopbackMapping $_ })) {
        Add-Content -LiteralPath $hostsPath -Value $entry -Encoding Ascii
    }
} elseif ($action -eq 'remove') {
    $updated = @($lines | Where-Object { $_.Trim() -ne $entry })
    [System.IO.File]::WriteAllLines($hostsPath, $updated, [System.Text.Encoding]::ASCII)
} else {
    throw "Unsupported action: $action"
}

ipconfig.exe /flushdns | Out-Null
POWERSHELL
)"
	powershell_body="${powershell_body//__ACTION__/${windows_action}}"
	powershell_body="${powershell_body//__HOST__/${WP_HOST}}"
	powershell_body="${powershell_body//__PROJECT__/${COMPOSE_PROJECT_NAME}}"
	encoded_body="$(printf '%s' "${powershell_body}" | iconv -f UTF-8 -t UTF-16LE | base64 | tr -d '\r\n')"

	powershell_launcher="$(cat <<POWERSHELL
\$arguments = @('-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass', '-EncodedCommand', '${encoded_body}')
\$process = Start-Process -FilePath 'powershell.exe' -Verb RunAs -ArgumentList \$arguments -Wait -PassThru
exit \$process.ExitCode
POWERSHELL
)"
	encoded_launcher="$(printf '%s' "${powershell_launcher}" | iconv -f UTF-8 -t UTF-16LE | base64 | tr -d '\r\n')"

	printf 'Windows administrator access is required for %s. Accept the UAC prompt.\n' "${WP_HOST}"
	powershell.exe -NoProfile -NonInteractive -EncodedCommand "${encoded_launcher}" >/dev/null
}

flush_native_dns_cache() {
	if [[ "$(uname -s)" == "Darwin" ]]; then
		dscacheutil -flushcache 2>/dev/null || true
		if command -v sudo >/dev/null 2>&1; then
			sudo killall -HUP mDNSResponder 2>/dev/null || true
		fi
	fi
}

check_domain() {
	local status=0

	if hosts_file_has_mapping /etc/hosts; then
		printf 'Host environment: %s resolves to 127.0.0.1.\n' "${WP_HOST}"
	else
		printf 'Missing mapping in /etc/hosts: 127.0.0.1 %s\n' "${WP_HOST}" >&2
		status=1
	fi

	if is_wsl; then
		local windows_file
		if windows_file="$(windows_hosts_file)" && hosts_file_has_mapping "${windows_file}"; then
			printf 'Windows browser environment: %s resolves to 127.0.0.1.\n' "${WP_HOST}"
		else
			printf 'Missing Windows hosts mapping: 127.0.0.1 %s\n' "${WP_HOST}" >&2
			status=1
		fi
	fi
	return "${status}"
}

add_domain() {
	if ! hosts_file_has_mapping /etc/hosts; then
		append_managed_entry /etc/hosts
	fi

	if is_wsl; then
		local windows_file
		if ! windows_file="$(windows_hosts_file)"; then
			printf 'Cannot locate the Windows hosts file from WSL2.\n' >&2
			return 1
		fi
		if ! hosts_file_has_mapping "${windows_file}"; then
			run_elevated_windows_action add
		fi
	fi

	flush_native_dns_cache
	check_domain
}

remove_domain() {
	remove_managed_entry /etc/hosts

	if is_wsl; then
		local windows_file
		if windows_file="$(windows_hosts_file)" && grep -Fq "${managed_entry}" "${windows_file}"; then
			run_elevated_windows_action remove
		fi
	fi

	flush_native_dns_cache
	printf 'Removed hosts entries managed for Compose project %s.\n' "${COMPOSE_PROJECT_NAME}"
}

case "${action}" in
	check) check_domain ;;
	add) add_domain ;;
	remove) remove_domain ;;
	-h|--help|help) usage ;;
	*) usage >&2; exit 2 ;;
esac
