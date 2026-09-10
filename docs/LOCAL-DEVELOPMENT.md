# Local development

The local-development harness runs WordPress behind nginx with a PHP-FPM
container and a MySQL database. The repository is mounted at `/workspace`, and
WordPress itself is generated in the ignored `local-dev/` directory.

Run all commands in this guide from the repository root.

## Prerequisites

- Docker Engine or Docker Desktop with the Compose v2 command
  (`docker compose`).
- GNU Make and Bash.
- Enough local resources to run MySQL, PHP-FPM, and nginx containers.
- Network access during the first setup and test run to pull container images,
  download WordPress, and install Composer packages. Later runs can reuse local
  Docker and Composer caches, but operations involving `latest` versions may
  still access the network.
- Permission to update the host machine's hosts file if the named local URL is
  used. This can require `sudo` or an administrator/UAC prompt.

Run the initial `make setup` from an interactive terminal. If the mapping is
missing, a headless shell without passwordless `sudo` stops at `hosts-add`
before setup begins; run `make hosts-add` interactively or provision the exact
managed mapping first.

The hostname helper edits `/etc/hosts`. When it detects WSL2, it also attempts
to edit the Windows hosts file so a Windows-hosted browser can resolve the
site; that path requires `powershell.exe`, `wslpath`, `iconv`, and `base64`.

## Configure the environment

Create the local-only environment file before the first setup, especially if
you want to change the hostname or port:

```bash
cp .env.localdev.example .env.localdev
```

`.env.localdev` is ignored by Git. It is separate from any repository `.env`
file and should contain only local-development settings. Because the setup
scripts source it as Bash, keep values shell-safe and quote values containing
spaces.

The available settings are:

| Variable | Purpose | Example default |
| --- | --- | --- |
| `COMPOSE_PROJECT_NAME` | Names and isolates this Compose stack and its volumes. Changing it creates a separate stack. | `wppostable` |
| `WP_HOST` | Browser hostname only, without scheme, port, or path. The hosts helper also requires at least one dot. | `wppostable.local` |
| `WP_BIND_ADDRESS` | Host address on which nginx publishes its port. Keep loopback binding for a local-only site. | `127.0.0.1` |
| `WP_HTTP_PORT` | Published HTTP port, from 1 through 65535. | `8089` |
| `WP_DEBUG` | Enables WordPress debug mode and PHP log routing when set to `1`, `true`, `yes`, or `on` (case variants accepted by the setup script). | `1` |
| `WP_TITLE` | Site title used during the first WordPress installation. | `wpPostAble Local` |
| `WP_ADMIN_USER` | Administrator username used during the first installation. | `admin` |
| `WP_ADMIN_PASSWORD` | Administrator password used during the first installation. | `admin` |
| `WP_ADMIN_EMAIL` | Administrator email used during the first installation. | `admin@wppostable.local` |
| `NGINX_IMAGE` | nginx image used by the persistent stack. | `nginx:1.31.5-alpine` |
| `PHP_BASE_IMAGE` | Base image used to build the local PHP container. | `wodby/wordpress-php:8.4-4.80.9` |
| `MYSQL_IMAGE` | MySQL image used by the persistent stack. | `mysql:8.0.46` |
| `PHP_EXTENSIONS_DISABLE` | PHP extensions disabled for normal local work. | `xdebug,xhprof,spx` |
| `DOCKER_LOG_MAX_SIZE` | Maximum size of each Docker JSON log file. | `10m` |
| `DOCKER_LOG_MAX_FILE` | Number of rotated Docker JSON log files. | `3` |
| `DB_NAME` | Local WordPress database name. | `wordpress` |
| `DB_USER` | Local WordPress database user. | `wordpress` |
| `DB_PASSWORD` | Local WordPress database password. | `wordpress-local` |
| `DB_ROOT_PASSWORD` | Local MySQL root password. | `wordpress-root-local` |

The example credentials are for a loopback-bound disposable development site.
Change them before setup if you change `WP_BIND_ADDRESS` or otherwise expose
the stack beyond the local machine.

To use a differently named environment file, pass it to Make consistently:

```bash
LOCALDEV_ENV_FILE=.env.my-localdev make setup
LOCALDEV_ENV_FILE=.env.my-localdev make up
```

## First setup

```bash
make setup
```

`make setup` performs the following reconciliation:

1. Adds or verifies the `127.0.0.1` mapping for `WP_HOST`.
2. Creates `.env.localdev` from the example if it does not exist.
3. Builds and starts the database and PHP services and waits for health checks.
4. Downloads WordPress into `local-dev/` when Core is absent and creates or
   updates `wp-config.php` with the configured database and debug settings.
5. Generates the Composer autoloader in `/workspace/vendor`.
6. Installs the local fixture as an mu-plugin. It registers the `wppa_item`
   post type and the `wp wppostable smoke` command.
7. Installs WordPress only when it is not already installed. On subsequent
   setup runs it preserves the database and content, updating only `home` and
   `siteurl` to match the configured URL.
8. Starts nginx and waits for all service health checks.

With the example settings, open:

```text
http://wppostable.local:8089
```

The URL is `http://<WP_HOST>:<WP_HTTP_PORT>`; port `80` is omitted. Sign in at
`/wp-admin/` with the administrator credentials chosen before the first
installation.

If setup reports an incomplete hostname mapping, inspect or apply it with:

```bash
make hosts-check
make hosts-add
```

Adding the mapping can request `sudo` or UAC. Remove only the entry managed for
this Compose project with:

```bash
make hosts-remove
```

## Start, stop, and persistence

After the initial setup, start the existing site with:

```bash
make up
```

Inspect service state with:

```bash
make ps
```

Stop and remove the running containers and network while preserving the site:

```bash
make down
```

`make down` preserves all normal local state:

- WordPress files in `local-dev/`;
- the MySQL `mysql-data` named volume;
- the PHP `php-logs` named volume;
- `.env.localdev`;
- the managed hosts-file entry.

Running `make setup` again is also non-destructive to an installed WordPress
database and its content.

Rebuild the PHP image after changing its Dockerfile or base image:

```bash
make php.build
```

## Reset the local site

> **Warning:** `make reset` is destructive. It deletes the generated
> `local-dev/` WordPress files and the Compose project's named volumes,
> including the WordPress database and PHP logs. The deleted site content is
> not recoverable from this harness unless you made an external backup.

The interactive reset asks you to type the current `COMPOSE_PROJECT_NAME`:

```bash
make reset
```

The reset is scoped by `.env.localdev`, preserves that environment file, and
does not remove the hosts-file entry. Use `make hosts-remove` separately if the
hostname is no longer needed. For explicitly non-interactive automation, the
underlying equivalent is:

```bash
./scripts/reset-local.sh --yes
```

After a reset, run `make setup` to create a fresh site.

## WordPress and container tools

Run WP-CLI in the persistent WordPress site by passing its arguments through
`ARGS`:

```bash
make wp ARGS="core version"
make wp ARGS="post list --post_type=wppa_item"
```

Run the real-WordPress smoke check:

```bash
make smoke
```

The smoke command creates a `wppa_item`, exercises create/save/reload, metadata,
parameters, and publishing, then deletes its fixture record. A passing run does
not intentionally leave test posts behind.

Open interactive shells with:

```bash
make shell
make nginx-shell
make db-shell
```

`make shell` is an alias of `make php-shell`. The database shell connects to
the configured WordPress database as the MySQL root user.

Follow container logs, selecting a service when needed:

```bash
make logs SERVICE=php
make logs SERVICE=nginx
make logs SERVICE=db
```

Follow WordPress/PHP application errors with:

```bash
make php.log
```

Both log-following commands run until interrupted with Ctrl+C; interrupting the
viewer does not stop the stack. Clear the PHP application logs explicitly with:

```bash
make php.log.clear
```

For unit, coverage, and isolated WordPress integration commands, see
[Testing](TESTING.md).
