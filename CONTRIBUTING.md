# Contributing to wpPostAble

Thank you for improving wpPostAble. Contributions should be small enough to
review confidently and explicit about their effect on consumers, WordPress
data, and the release contract.

The project currently uses a lightweight single-maintainer flow: a focused
branch, a pull request, passing automated checks, and a maintainer decision. A
second human reviewer is not required, but every pull request must contain
enough intent and verification evidence for an informed self-review and merge.

## Before starting

Read the sources of truth relevant to the change:

- [VERSIONING.md](VERSIONING.md) defines SemVer, supported platforms, and the
  compatibility surface.
- [CHANGELOG.md](CHANGELOG.md) records user-visible changes and defines the
  release-note policy.
- [docs/TESTING.md](docs/TESTING.md) describes every local and CI validation
  layer.
- [docs/LOCAL-DEVELOPMENT.md](docs/LOCAL-DEVELOPMENT.md) describes the
  persistent local WordPress environment.

Link an existing issue in the pull request when one tracks the work. Open an
issue first for public API, hook, persistence, supported-platform, security, or
release-process decisions that have not already been approved. A small factual
documentation correction may explain its context directly in the pull request.

## Branches and commits

Create a focused branch from the current `master`. A short descriptive name
such as `fix/delete-failure` or `docs/parameter-contract` is recommended; no
specific prefix is enforced.

Keep commits reviewable and cohesive:

- use an imperative summary that describes the outcome;
- keep unrelated refactors and formatting out of the change;
- add tests with the behavior they protect;
- do not commit `.env` files, credentials, `vendor/`, `local-dev/`, coverage
  output, or integration diagnostic artifacts;
- do not move or replace published tags or artifacts.

Before opening the pull request, review the complete branch diff as a maintainer
would, including generated-file and secret checks.

## Describe intent and behavior

Use the pull-request template. State the problem, the chosen behavior, and what
is intentionally out of scope. For behavior changes, describe both the previous
and new observable result. Include migration or recovery instructions whenever
existing consumer code or stored data may require action.

Do not label a compatibility change as an internal refactor. The protected
surface includes public and protected methods, signatures, chainability,
exceptions, WordPress hooks, metadata and JSON persistence, Composer/runtime
requirements, and supported PHP and WordPress versions.

## Assess SemVer impact

Every pull request proposes one of `none`, `PATCH`, `MINOR`, or `MAJOR` and
explains why. The maintainer makes the final release decision, considering the
highest-impact change included in the release.

Before `1.0.0`:

- `PATCH` is a backward-compatible bug or security fix to the current `0.x`
  contract.
- `MINOR` is a feature, deprecation, or any compatibility-contract change,
  including an incompatible pre-1.0 change.

Starting with `1.0.0`:

- `PATCH` is a backward-compatible bug or security fix.
- `MINOR` is a backward-compatible public addition or deprecation.
- `MAJOR` is an incompatible change or removal.

Use `none` only when the pull request does not require a package release by
itself, such as a test-only refactor or a factual documentation correction with
no contract or workflow change. Documentation and tests do not lower the version
required by another change in the same release. Consult
[VERSIONING.md](VERSIONING.md) for the complete rules and examples.

## Add tests and record evidence

Choose tests according to risk, then record every command and its exact result
in the pull request. If a listed check is not run or does not apply, say so and
explain why; do not silently delete the row from the template.

For runtime source, dependency, persistence, hook, exception, or packaging
changes, the normal local gate is:

```bash
make check
make coverage
make test.package-install
make test.integration
```

These commands validate lint and unit behavior, enforced 100% source line and
method coverage, installation of the production Composer archive without
development dependencies, and both real-WordPress profiles. Coverage percentage
does not replace meaningful assertions or integration behavior.

Run the persistent-site smoke when the change affects the local fixture or a
normal end-to-end model lifecycle:

```bash
make smoke
```

During focused development, the individual integration targets are:

```bash
make test.integration.minimum
make test.integration.latest
```

Run `git diff --check` for every change. Documentation-only work should also
verify headings, code fences, internal links, and every command or claim it
adds. The full runtime matrix is not automatically required for a prose-only
correction, but the pull request must identify any checks not run and why they
cannot affect the result.

CI produces five checks: PHP quality on PHP 7.4 and 8.4, enforced coverage and
package gates on PHP 8.4, and WordPress integration for `minimum` and `latest`.
A pull request is not ready to merge while a required check is failing or
unexplained.

## Update documentation

Documentation changes follow the behavior in the same pull request:

- update `README.md` when the package overview or primary usage example changes;
- update `VERSIONING.md` when the compatibility contract, supported platform,
  or release rule changes;
- update `docs/LOCAL-DEVELOPMENT.md` when the persistent harness or its commands
  change;
- update `docs/TESTING.md` when a test layer, gate, artifact, prerequisite, or
  command changes;
- update code-level documentation when a public signature, exception, hook, or
  persistence rule changes.

A documentation-only pull request selects SemVer impact `none` only when it
corrects wording without changing promised behavior, support, or a contributor
workflow. New or changed public policy and supported workflows are documented
changes even when no runtime source changes.

## Update the changelog

Add a concise bullet under `Unreleased` in [CHANGELOG.md](CHANGELOG.md) for any
user-visible behavior, compatibility, dependency, security, packaging, or
supported-workflow change. Use the appropriate `Added`, `Changed`, `Deprecated`,
`Removed`, `Fixed`, or `Security` subsection. Describe user impact rather than
commit mechanics, and add migration guidance for incompatible or newly failing
behavior.

A no-changelog declaration is valid only when the entire pull request is one of
the following and changes no observable contract or supported workflow:

- an internal refactor with identical behavior and dependencies;
- a test-only refactor that does not add, remove, or change an enforced gate;
- a spelling, formatting, or factual documentation correction that does not
  change instructions, policy, compatibility, or support;
- planning or administrative metadata that is not shipped to consumers and
  does not change the release process.

The pull request must select the no-changelog option and give a concrete reason.
“Not needed”, “internal”, or “documentation only” without that explanation is
not sufficient.

## Compatibility and security review

Call out any effect on:

- implementers of the interface or trait;
- method signatures, visibility, return values, chainability, and exceptions;
- WordPress hook names, order, timing, defaults, and arguments;
- existing metadata or JSON stored in WordPress;
- Composer dependencies and production archive contents;
- the PHP 7.4 / WordPress 6.0 lower bounds and current-version profiles;
- secrets, permissions, external input, logs, and generated artifacts.

Never include credentials or private `.env` values in source, fixtures, logs,
screenshots, test evidence, or pull-request text. If a security concern cannot
be discussed safely in public, do not publish exploit details in an issue or
pull request; contact the maintainer privately before disclosure.

## Pull-request readiness

A pull request is ready when it is focused, the template is complete, the
SemVer and changelog decisions agree with the documented policies, relevant
documentation is updated, verification evidence is recorded, required CI is
green, and remaining risks are explicit. Merging a pull request does not by
itself authorize tagging or publishing a release.
