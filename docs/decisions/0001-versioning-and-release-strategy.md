# ADR 0001: Versioning and release strategy

- Status: Accepted
- Date: 2026-09-11
- Decision owner: Repository owner
- Delivery milestone: [1.0.0](https://github.com/hokoo/wpPostAble/milestone/1)

## Context

wpPostAble has published Git tags from `0.1` through `0.6.2`, and Packagist currently resolves `0.6.2`. The repository did not define a version-selection policy, a complete public compatibility contract, a maintained changelog, or a reproducible release process.

The historical release metadata is inconsistent: several suffix-less `0.x` releases are marked as GitHub pre-releases, while GitHub still identifies `0.2.2` as latest. Composer treats suffix-less VCS tags as stable versions regardless of the GitHub pre-release flag.

The current `master` contains tested changes after `0.6.2`: a persistent local WordPress harness, unit and real-WordPress integration suites, CI and coverage reporting, PHP 8.4 compatibility work, and runtime bug fixes. The source currently has 100% line and method coverage, but coverage alone is not considered proof of the complete public contract.

Known downstream users include public and private projects. Some require `^0.6.x`; others follow `dev-master`. A stable 1.x contract therefore needs explicit downstream validation.

## Decision

### Version source and syntax

- Follow Semantic Versioning 2.0.0.
- Use complete, unprefixed VCS tags: `0.7.0`, `1.0.0-rc.1`, and `1.0.0`.
- Keep Git tags as the canonical Composer version source.
- Do not add a hardcoded `version` field to `composer.json`.
- Never move, recreate, or change the contents of a published tag.
- Preserve historical tag names unchanged and document their irregularities.

Before 1.0, compatible fixes increment PATCH and feature or contract changes increment MINOR. Starting with 1.0, compatible fixes increment PATCH, compatible public additions or deprecations increment MINOR, and incompatible public changes increment MAJOR.

### Public compatibility contract

The 1.x compatibility promise covers:

- the `wpPostAble` interface and consumer-facing methods supplied by `wpPostAbleTrait`;
- documented method signatures, return behavior, chainability, and exceptions;
- documented WordPress hook names and argument contracts;
- documented metadata and parameter-storage semantics.

The 1.0 scope includes initialization from `WP_Post`, symmetric slug and menu-order accessors, and alignment of existing public `getParam()`/`setParam()` behavior with the interface. Existing namespaces and class names will not be renamed.

### Support baseline

Version 1.0 supports PHP 7.4 or newer and WordPress 6.0 or newer. CI verifies PHP 7.4 and PHP 8.4 plus minimum and latest WordPress profiles.

### Release sequence

1. Publish the already-tested baseline as stable `0.7.0` after documentation and release controls are complete.
2. Implement and freeze the minimal 1.0 public API.
3. Publish `1.0.0-rc.1` as a GitHub/Composer pre-release.
4. Validate the RC in representative public consumers and at least one authorized private consumer.
5. Publish stable/latest `1.0.0` after all release-blocking findings are resolved.

Each publishing operation requires explicit human approval.

### Repository governance

- Changes to `master` must use pull requests.
- The five CI jobs are required and branches must be up to date before merge.
- Rules apply to administrators; force-push and deletion of `master` are disabled.
- No human approval count is required, preserving the single-maintainer workflow.
- Merged feature branches are deleted automatically after their commits are verified reachable from `master`.

### Documentation and traceability

- Public project documentation is written in English.
- `docs/plan-1.0.md` records task status, dependencies, evidence, decisions, and delivery transitions.
- GitHub milestone issues are the executable task records.
- Every behavior-changing PR updates tests, relevant documentation, and `CHANGELOG.md` in the same change.
- A manual, least-privilege GitHub Actions workflow performs releases from an explicitly approved green `master` commit.

### Downstream validation boundary

The 1.0 RC is tested in `cf7-telegram`, `cf7-vk`, `neuralseo`, and at least one authorized private consumer. Temporary local dependency changes are allowed for verification. Pushing changes or releasing downstream repositories requires separate authorization and is outside this plan.

## Consequences

- Publishing `1.0.0` turns the documented surface into a compatibility commitment.
- Interface alignment may require migration by consumers that implement the interface manually without the trait; this must be documented before RC.
- The release workflow will have narrowly scoped permission to create tags and GitHub Releases and therefore requires independent security review.
- A 100% line/method threshold remains useful for this small codebase, but behavioral and real-WordPress tests remain independent mandatory gates.
- Historical tags remain immutable even where their names or GitHub metadata do not match the new convention.

## Alternatives rejected

- Jump directly from `0.6.2` to `1.0.0`: rejected because it would not exercise the new release process before the compatibility commitment.
- Store a version constant or Composer `version` field in source: rejected because it duplicates the VCS tag and can drift.
- Release automatically on every merge or tag push: rejected because publishing must remain an explicit owner action.
- Require one approving review: rejected for now because it can deadlock a single-maintainer repository.
- Validate only the library repository: rejected because known real consumers use both stable constraints and `dev-master`.

## Change control

Changing the public contract, support baseline, tag convention, release sequence, publishing authority, or downstream scope requires a new ADR and explicit repository-owner approval.
