# Testing

The repository has four complementary validation layers:

- unit tests use PHPUnit and Brain Monkey without booting WordPress;
- the local smoke command exercises the library against the persistent
  development WordPress database;
- integration profiles create isolated temporary WordPress installations for
  the supported minimum and current environments;
- the package-install smoke creates a Composer archive and installs it in a
  disposable consumer project without development dependencies.

Run all commands in this guide from the repository root. Complete the initial
[local-development setup](LOCAL-DEVELOPMENT.md) first. The Make targets install
Composer dependencies through the persistent PHP container before running the
requested test command.

## Requirements and side effects

The Make-based commands require Docker Compose v2 and a configured
`.env.localdev`. Unit and coverage commands use the persistent local PHP
container but do not use the WordPress database. The smoke command temporarily
writes one fixture post to the persistent local database and removes it after a
successful run.

Each integration profile uses a uniquely named Compose project with its own
MariaDB and WordPress named volumes. It publishes no host port and mounts the
repository read-only. It does not stop, reset, or share data with the persistent
local-development stack.

Network access is required when Composer packages, Docker images, or WordPress
Core are not already cached. The `latest` integration profile resolves and
downloads the current stable WordPress release on every clean run. Composer
security audit data in CI also comes from an external package registry.

## Unit tests

Run the unit suite through the local PHP container:

```bash
make test.unit
```

The shorter alias runs the same target:

```bash
make test
```

The underlying Composer command is `composer test:test-unit`, which selects the
`unit` suite in `phpunit.xml.dist`. Unit tests live in `tests/Unit/` and load
`tests/bootstrap.php`; they do not load WordPress Core.

## Lint and complete local check

Lint all PHP under `src/`, `tests/`, and `scripts/`:

```bash
make lint
```

Run the normal pre-commit quality check:

```bash
make check
```

`make check` installs the locked Composer dependencies if needed, validates
`composer.json` and `composer.lock` strictly, lints PHP, and runs the unit suite.
It does not run coverage, smoke, or WordPress integration profiles.

Install only the locked Composer dependencies with:

```bash
make composer.install
```

## Enforced source coverage

Generate the unit-test coverage report with:

```bash
make coverage
```

This runs the same mandatory unit suite with a supported coverage driver and
writes:

```text
coverage/clover.xml
coverage/summary.json
```

The report covers PHP files in `src/` only. It is unit coverage: execution by
the smoke and integration layers is not merged into the Clover result. The
`coverage/` directory is ignored by Git.

The coverage launcher uses, in order, an already loaded Xdebug extension, an
already loaded PCOV extension, or a bundled but normally disabled `xdebug.so`.
It enables coverage only for this command. If none is available, it exits with
a message asking for Xdebug or PCOV. Normal unit tests do not enable Xdebug.

After PHPUnit writes Clover, `scripts/check-coverage.php` reads the aggregate
project metrics and requires both:

- 100% executable source lines (`coveredstatements == statements` in Clover);
- 100% source methods (`coveredmethods == methods` in Clover).

Missing, malformed, internally inconsistent, or zero-total metrics fail the
gate. A coverage reduction prints the covered and total counts, percentages,
and numbers of uncovered lines and methods. `coverage/summary.json` records the
same threshold result for automation.

This gate does not measure or make any claim about branch coverage or mutation
testing. Behavior assertions and both real-WordPress profiles remain mandatory
independent gates even while line and method coverage are 100%.

When PHP and Composer are installed directly on the host and a coverage driver
is enabled, the equivalent CI-facing command is:

```bash
composer test:coverage
```

The checker can also validate an existing report directly:

```bash
php scripts/check-coverage.php coverage/clover.xml coverage/summary.json
```

## Production package-install smoke

Verify that the generated local package installs and autoloads without
development dependencies:

```bash
make test.package-install
```

The CI-facing equivalent is:

```bash
composer test:package-install
```

The command creates a ZIP with `composer archive`, extracts it to a temporary
directory, and exposes that extracted artifact to a clean Composer project as a
mirrored path repository. The test repository supplies a synthetic `0.0.0`
version externally, in the same role that VCS/Packagist metadata supplies a real
release version; it does not add a `version` field to the package manifest.
Packagist is disabled for this isolated install.

Composer first resolves the isolated lock in a disposable resolver workspace,
copies only `composer.json` and `composer.lock` into a separate empty consumer,
and then runs a clean, locked, classmap-authoritative
`composer install --no-dev`. The smoke fails unless the
package is copied under `vendor/` rather than symlinked, the package has no
nested `vendor/` or environment files, PHPUnit and Brain Monkey are absent, the
public interface, trait, and exception classes autoload from the installed copy,
and the runtime constraints and PSR-4 mapping match the package manifest. The
archive root is restricted to runtime source plus `composer.json`, the license,
changelog, and public documentation; local WordPress, CI, IDE, test, coverage,
and developer-tooling paths are excluded.

Temporary archive and consumer directories are always removed. A successful
run writes machine-readable evidence to:

```text
coverage/package-install.json
```

The evidence includes the archive SHA-256 and size, PHP and Composer versions,
install mode, locked package count, and the public symbols that were loaded.

## Persistent-site smoke test

Exercise the installed fixture against the persistent local WordPress site:

```bash
make smoke
```

The command verifies create/save/reload, title and status, structured metadata,
JSON-backed parameters, and publish behavior. It deletes its fixture post on a
successful run. Use the isolated profiles below for repeatable WordPress matrix
testing.

## WordPress integration profiles

Run the minimum supported profile, WordPress 6.0 on PHP 7.4:

```bash
make test.integration.minimum
```

Run the current profile, latest stable WordPress on PHP 8.4:

```bash
make test.integration.latest
```

Run both profiles sequentially, minimum first:

```bash
make test.integration
```

The Make targets ensure Composer dependencies exist, then invoke these direct
runner equivalents:

```bash
./scripts/test-integration.sh minimum
./scripts/test-integration.sh latest
./scripts/test-integration.sh all
```

Do not use the direct runner on a clean checkout until `vendor/` exists; the
WordPress fixture loads `/workspace/vendor/autoload.php`. Prefer the Make
targets after `make setup` for the complete contributor workflow.

Each profile downloads a clean English (`en_US`) WordPress installation,
installs it against a temporary MariaDB 10.11 database, links the local fixture
as an mu-plugin, verifies the actual WordPress and PHP versions, and runs
`tests/integration/wp-lifecycle.php` through WP-CLI. The lifecycle covers
create/save/reload, status transitions, metadata, parameter serialization, and
deletion behavior in real WordPress.

The following environment variables can override integration inputs for a
targeted investigation:

| Variable | Effect |
| --- | --- |
| `WPPA_IT_MINIMUM_CORE_VERSION` | Replaces the default minimum WordPress `6.0` request. |
| `WPPA_IT_MINIMUM_CLI_IMAGE` | Replaces `wordpress:cli-php7.4`. |
| `WPPA_IT_LATEST_CORE_VERSION` | Replaces the default `latest` WordPress request. |
| `WPPA_IT_LATEST_CLI_IMAGE` | Replaces `wordpress:cli-php8.4`. |
| `WPPA_IT_DB_IMAGE` | Replaces the default `mariadb:10.11` database image. |
| `WPPA_IT_ARTIFACTS` | Changes the host directory receiving diagnostics. |
| `KEEP_INTEGRATION_ENV=1` | Keeps the unique test Compose project and volumes for manual investigation. |

Overrides can invalidate the profile's version assertions; use them only when
the requested image and Core versions still match the profile contract.

### Diagnostics and cleanup

By default, every integration run writes an ignored evidence directory under:

```text
tests/integration/artifacts/<profile>-<UTC timestamp>-<process id>-<random>/
```

As the run progresses, it records the resolved environment, command log,
lifecycle JSON and stderr, Compose logs, and the WordPress debug log when
present. An early failure can therefore leave only the files collected up to
that point. On failure, the runner prints the exact evidence directory.

By default the runner collects diagnostics and then removes its unique
containers, network, and both named volumes on success or failure. Artifact
files remain on the host for review. Setting `KEEP_INTEGRATION_ENV=1` disables
that automatic Docker cleanup and intentionally leaves temporary resources;
do not set it during routine test runs.

## CI parity

GitHub Actions runs on pushes and pull requests targeting `master`, and through
manual workflow dispatch. Jobs are skipped while a pull request is a draft.
The workflow has three job groups and produces exactly five required check runs:

1. **PHP quality** on PHP 7.4 and 8.4: strict Composer validation, locked
   dependency installation, `composer audit`, PHP lint, and unit tests.
2. **Coverage and package gates** on PHP 8.4 with Xdebug: enforced 100% source
   line/method coverage followed by the production package-install smoke. The
   Clover report, coverage summary, and package-install evidence are uploaded
   as seven-day artifacts.
3. **WordPress integration** with `minimum` and `latest` matrix entries: Composer
   installation followed by the matching integration runner profile. Failed
   jobs upload `tests/integration/artifacts/` for seven days when diagnostics
   are present.

For the closest local pre-push reproduction, run:

```bash
make check
make coverage
make test.package-install
make test.integration
```

The local PHP container currently represents the PHP 8.4 side of the quality
matrix. The minimum WordPress integration profile supplies the repository's
real-WordPress PHP 7.4 compatibility check; the CI quality job additionally
runs the unit suite and lint directly on PHP 7.4.
