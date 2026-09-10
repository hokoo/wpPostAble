# Changelog

This file records user-visible changes to wpPostAble. Historical entries were
reconstructed from the tagged source diffs and GitHub Release records rather
than copied from commit subjects. They intentionally describe only changes
that can be verified from those sources.

Dates use the GitHub publication date when a Release exists and the tagged
commit date otherwise. Git tags are the canonical version refs; see
[VERSIONING.md](VERSIONING.md) for the current versioning and compatibility
policy.

## [Unreleased] — planned for 0.7.0

### Upgrade notes

- PHP 7.4 is now the declared minimum. Projects still running an older PHP
  version must upgrade PHP before requiring 0.7.0.
- `deletePost()` now reports a WordPress deletion failure with
  `wppaDeletePostException` and keeps the model usable for retry. Callers that
  handle failed deletions should catch this exception.
- `getParam()` and `setParam()` now reject malformed or unsupported JSON with
  `wppaParamException`; failed writes leave the stored field unchanged. Callers
  that may encounter legacy malformed data should handle this exception.

### Added

- Added `wppaDeletePostException`, preserving the original post, model, and a
  machine-readable `WP_Error` when Core refuses a deletion.
- Added `wppaParamException` with read/write operation, failure-reason, parameter
  name, and JSON error-code context.
- Added a persistent Docker-based local WordPress environment and a WP-CLI smoke
  command for exercising the package against a real database.
- Added PHPUnit/Brain Monkey unit tests, isolated WordPress 6.0/PHP 7.4 and
  latest WordPress/PHP 8.4 integration profiles, Clover unit coverage, and
  least-privilege GitHub Actions CI.
- Added enforceable 100% source line and method coverage gates plus a
  production Composer artifact-install smoke check, with machine-readable
  evidence for both.
- Added formal versioning, compatibility, local-development, testing, and
  contribution/PR evidence policies for the path to 1.0.

### Changed

- Declared PHP `>=7.4` in Composer while retaining the JSON extension
  requirement.
- `savePost()` now sends only metadata keys explicitly changed through
  `setMetaField()`. Untouched single-value, multi-value, and serialized metadata
  are no longer rewritten during unrelated saves.
- JSON parameter reads and writes now have explicit root-shape and encoding
  validation, preserve failed input atomically, and support empty, Unicode,
  nested, and numeric-key values consistently.
- Restricted Composer release archives to runtime source, package metadata,
  licensing, changelog, and public documentation; environment files, local
  WordPress state, dependencies, tests, and developer tooling are excluded.

### Fixed

- Fixed `deletePost()` invalidating its post before the after-delete hooks and
  dereferencing the invalidated value. The post is now invalidated only after a
  successful Core deletion, and hooks retain the original post context.
- Fixed PHP 8.4 implicit-nullable deprecations in exception constructors without
  changing the PHP 7.4 runtime baseline.

## Historical release metadata

The early project did not use one consistent release convention:

- `0.1`, `0.2`, `0.3`, `0.4`, `0.5`, and `0.6` are historical two-component
  tags. They remain valid immutable refs but do not follow the complete
  three-component SemVer form required for future releases.
- Tags `0.2.1` and `0.3` have no corresponding GitHub Release record; their
  entries below are reconstructed from their tag diffs.
- GitHub marks Releases `0.4` through `0.6.2` as prereleases even though the tag
  names contain no prerelease suffix. Earlier Releases `0.1`, `0.2`, and
  `0.2.2` are not marked as prereleases.
- Consequently, as of 2026-09-11 GitHub's “latest release” endpoint resolves to
  `0.2.2`, not the chronologically newest tag `0.6.2`. This is historical
  GitHub metadata, not a statement about package support or version ordering.

Historical GitHub metadata is left unchanged. Future prereleases use a SemVer
suffix such as `1.0.0-rc.1`; the Git tag, not a GitHub checkbox, determines
Composer ordering.

## [0.6.2] - 2024-09-12

[GitHub Release: “Meta serializing bug fix”](https://github.com/hokoo/wpPostAble/releases/tag/0.6.2)

### Fixed

- Deserialized each metadata value after bulk loading it from WordPress, so
  serialized structured values are exposed in their original PHP form. Despite
  the historical Release title, the tagged source change specifically adds
  deserialization on read.

## [0.6.1] - 2024-09-10

[GitHub Release: “Parameters' using bug fix”](https://github.com/hokoo/wpPostAble/releases/tag/0.6.1)

### Fixed

- Allowed `setParam()` to initialize an empty parameter field instead of trying
  to write into a null decoded value.

### Documentation

- Corrected the example model constructor to define and pass its post-type
  constant and to accept an optional post ID.

## [0.6] - 2024-09-06

[GitHub Release: “v0.6”](https://github.com/hokoo/wpPostAble/releases/tag/0.6)

### Changed

- Made `getParam()` and `setParam()` public so consumers can use the JSON-backed
  parameter API directly.
- Expanded the usage example with the trait and exception imports required by a
  concrete model.

## [0.5] - 2023-02-04

[GitHub Release: “Meta Fields got fixed”](https://github.com/hokoo/wpPostAble/releases/tag/0.5)

### Fixed

- Normalized bulk-loaded post metadata to the first value for each key instead
  of exposing WordPress's nested raw arrays.
- Passed a single WordPress error-message string to create/save exceptions
  instead of an array of messages.

### Documentation

- Expanded examples for loading existing posts and using parameter fields.

## [0.4.2] - 2021-10-25

[GitHub Release: “Minor fixes”](https://github.com/hokoo/wpPostAble/releases/tag/0.4.2)

### Fixed

- Corrected `setParam()` to assign a property on the object returned by
  `json_decode()` instead of using incompatible array access.

## [0.4.1] - 2021-10-25

[GitHub Release: “Minor fixes”](https://github.com/hokoo/wpPostAble/releases/tag/0.4.1)

### Fixed

- Corrected `getParam()` to read a property from the object returned by
  `json_decode()` instead of using incompatible array access.

## [0.4] - 2021-10-24

[GitHub Release: “Params introduced”](https://github.com/hokoo/wpPostAble/releases/tag/0.4)

### Added

- Added protected `getParam()` and `setParam()` helpers that store a JSON
  parameter map in `WP_Post::post_content_filtered`.
- Declared the JSON PHP extension as a Composer runtime requirement.

### Documentation

- Corrected the interface namespace in the usage example.

## [0.3] - 2021-08-21

_Tag only; no GitHub Release record exists._

### Added

- Added shared global and model-class-specific hook dispatch helpers, plus new
  filters for new-post defaults and optional metadata loading.
- Loaded post metadata when an existing model is initialized, with a filter for
  disabling that load.

### Changed

- Made new-post status, title, and content filterable and read status from the
  associated `WP_Post` rather than separate trait state.
- Routed the existing post-type, load, and delete lifecycle hooks through the
  shared dispatch helpers.

## [0.2.2] - 2021-08-21

[GitHub Release: “Composer Autoload”](https://github.com/hokoo/wpPostAble/releases/tag/0.2.2)

### Changed

- Updated the Composer PSR-4 source path from `src/` to `src`.

## [0.2.1] - 2021-08-21

_Tag only; no GitHub Release record exists._

### Fixed

- Corrected the Composer package name to `hokoo/wppostable` and declared its
  package type as `library`.

## [0.2] - 2021-08-21

[GitHub Release: “Composer added”](https://github.com/hokoo/wpPostAble/releases/tag/0.2)

### Added

- Added Composer package metadata and PSR-4 autoloading for the
  `iTRON\wpPostAble\` namespace.

### Changed

- Moved the interface, trait, and exception classes into the autoloaded `src/`
  tree without changing their contents.

## [0.1] - 2021-08-21

[GitHub Release: “First release”](https://github.com/hokoo/wpPostAble/releases/tag/0.1)

### Added

- Added the initial `wpPostAble` interface and `wpPostAbleTrait` for associating
  a model with a WordPress `WP_Post`, including creation and loading.
- Added title, status, metadata, save, publish, draft, and delete operations.
- Added creation, loading, and saving exception types plus initial lifecycle
  hooks and usage documentation.

## Changelog policy

Every pull request with a user-visible behavior, compatibility, dependency,
security, or supported-workflow change must add a concise entry under
`Unreleased` in the appropriate `Added`, `Changed`, `Deprecated`, `Removed`,
`Fixed`, or `Security` subsection. Describe the effect on users, not the commit
mechanics, and include migration guidance for incompatible or newly failing
behavior. Link the relevant issue or pull request when it adds useful context.

Purely internal changes may omit an entry, but the pull request should state
why no changelog entry is needed. Do not rewrite a published version except to
correct a factual error transparently.

At release time, rename the prepared `Unreleased` section to the exact version
and UTC release date, update its comparison link from `HEAD` to the immutable
tag, and create a new empty `Unreleased` section comparing that tag to `HEAD`.

[Unreleased]: https://github.com/hokoo/wpPostAble/compare/0.6.2...HEAD
[0.6.2]: https://github.com/hokoo/wpPostAble/compare/0.6.1...0.6.2
[0.6.1]: https://github.com/hokoo/wpPostAble/compare/0.6...0.6.1
[0.6]: https://github.com/hokoo/wpPostAble/compare/0.5...0.6
[0.5]: https://github.com/hokoo/wpPostAble/compare/0.4.2...0.5
[0.4.2]: https://github.com/hokoo/wpPostAble/compare/0.4.1...0.4.2
[0.4.1]: https://github.com/hokoo/wpPostAble/compare/0.4...0.4.1
[0.4]: https://github.com/hokoo/wpPostAble/compare/0.3...0.4
[0.3]: https://github.com/hokoo/wpPostAble/compare/0.2.2...0.3
[0.2.2]: https://github.com/hokoo/wpPostAble/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/hokoo/wpPostAble/compare/0.2...0.2.1
[0.2]: https://github.com/hokoo/wpPostAble/compare/0.1...0.2
[0.1]: https://github.com/hokoo/wpPostAble/commit/90396e2a7aa7e5c14700ab4b290b74df14f7d4ec
