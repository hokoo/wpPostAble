Follow the [contribution guide](https://github.com/hokoo/wpPostAble/blob/master/CONTRIBUTING.md)
and [versioning policy](https://github.com/hokoo/wpPostAble/blob/master/VERSIONING.md).

## Linked issue

<!-- Use "Closes #123" when merge should close the issue. For a small factual
documentation correction without an issue, explain why an issue is unnecessary. -->

Closes #

## Intent

<!-- What problem does this solve, why is this approach appropriate, and what is
intentionally out of scope? Keep this user- and maintainer-oriented. -->

### Behavior

<!-- Describe the observable behavior before and after this PR. For a change
with no observable behavior, say so explicitly. Include migration/recovery
steps when existing consumers or stored data need action. -->

- Before:
- After:
- Migration or recovery:
- Out of scope:

## SemVer impact

<!-- Select exactly one. Follow VERSIONING.md. Before 1.0, incompatible contract
changes are MINOR; starting with 1.0 they are MAJOR. The maintainer makes the
final release decision. -->

- [ ] `none` — no package release required by this change alone
- [ ] `PATCH` — backward-compatible bug or security fix
- [ ] `MINOR` — feature/deprecation, or a pre-1.0 compatibility change
- [ ] `MAJOR` — incompatible change starting with 1.0

Rationale:

## Compatibility and security

<!-- Select every affected surface, or select "None" after reviewing them all.
Add details below for each affected item. -->

- [ ] Public/protected API, signatures, visibility, return values, or chainability
- [ ] Exception types or machine-readable error context
- [ ] WordPress hook names, arguments, ordering, timing, or defaults
- [ ] Existing metadata or JSON persistence and migration
- [ ] Composer dependencies or production archive contents
- [ ] PHP/WordPress support bounds or current-version compatibility
- [ ] Security, permissions, external input, secrets, or diagnostic output
- [ ] None of these surfaces is affected

Risk and compatibility details:

## Verification evidence

<!-- Keep every row. Record pass/fail/not run/not applicable, exact versions or
metrics when relevant, and the reason for anything not run. See docs/TESTING.md. -->

| Check | Result | Evidence or reason |
| --- | --- | --- |
| `make check` |  |  |
| `make coverage` |  |  |
| `make test.package-install` |  |  |
| `make smoke` |  |  |
| `make test.integration.minimum` |  |  |
| `make test.integration.latest` |  |  |
| `git diff --check` |  |  |
| Other targeted check |  |  |

## Documentation

<!-- Select every file updated, or the final option and explain why. A prose-only
change can still alter public policy or a supported workflow. -->

- [ ] `README.md`
- [ ] `VERSIONING.md`
- [ ] `docs/LOCAL-DEVELOPMENT.md`
- [ ] `docs/TESTING.md`
- [ ] `CONTRIBUTING.md` or repository templates
- [ ] Public code/API documentation
- [ ] Other documentation named below
- [ ] No documentation change is required

Documentation rationale:

## Changelog

<!-- Select exactly one. CONTRIBUTING.md narrowly defines valid no-changelog
cases. "Documentation only" or "internal" is not sufficient by itself. -->

- [ ] Added a user-oriented entry under `Unreleased` in `CHANGELOG.md`
- [ ] No changelog entry is valid for this pull request

Changelog entry or concrete no-changelog rationale:

## Final review

- [ ] The branch is focused and excludes unrelated changes.
- [ ] Tests assert behavior and failure paths, not only coverage percentage.
- [ ] No credentials, private `.env` values, generated dependencies, local
      WordPress files, coverage reports, or integration artifacts are included.
- [ ] Published tags and release artifacts are unchanged.
- [ ] Remaining risks, follow-ups, and checks not run are stated above.
- [ ] Required CI checks are green, or the PR remains a draft pending them.

<!-- References:
https://github.com/hokoo/wpPostAble/blob/master/CONTRIBUTING.md
https://github.com/hokoo/wpPostAble/blob/master/VERSIONING.md
https://github.com/hokoo/wpPostAble/blob/master/CHANGELOG.md
https://github.com/hokoo/wpPostAble/blob/master/docs/TESTING.md
https://github.com/hokoo/wpPostAble/blob/master/docs/LOCAL-DEVELOPMENT.md
-->
