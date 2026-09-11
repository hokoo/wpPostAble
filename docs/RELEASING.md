# Releasing wpPostAble

wpPostAble releases are published only by the manual `Release` GitHub Actions
workflow. The workflow tags one explicitly selected commit from `master`, uses
that tag as the canonical Composer version, and creates a GitHub Release whose
notes are the exact matching `CHANGELOG.md` section. It does not publish a
plugin ZIP or modify Packagist directly.

## Security and mutation boundary

The workflow is deliberately split into read-only validation and test jobs and
one final publication job:

- the workflow and all pre-publication jobs have `contents: read` permission;
- every checkout uses an immutable action revision and
  `persist-credentials: false`;
- the selected `target_sha` must be the complete SHA of the `master` commit from
  which the workflow was dispatched and must still be an ancestor of current
  `master` immediately before publication;
- the version, date, changelog section, comparison links, existing tags, and
  existing GitHub Releases are checked before tests and checked again where
  necessary immediately before mutation;
- two PHP quality runs, one coverage/package run, and two WordPress integration
  runs must all pass before the publication job can start;
- the coverage/package job downloads GitHub's source zipball through the
  authenticated API at the validated full `target_sha`, installs it without
  development dependencies, and rejects any extracted-content or API drift;
- only the final job receives `contents: write`, and only when `publish` was set
  to `true` by the repository owner; both the original actor and the actor who
  triggers a rerun must be the owner;
- a repository-wide release concurrency lock allows one release workflow at a
  time and never cancels an in-progress publication;
- the only release mutation is one `gh release create` call targeted at the
  validated SHA. GitHub creates the unprefixed tag as part of that operation.

The owner check is intentionally suitable for this repository's personal-owner
governance. If the repository is transferred to an organization, replace it
through a reviewed ADR and an equivalent protected-environment approval before
attempting another publication.

GitHub's release-immutability setting applies only to releases created after it
is enabled. It does not retroactively protect the historical releases or tags,
so the workflow fetches the live remote previous tag and proves its commit is an
ancestor of the selected target immediately before publication. For a newly
published immutable release, GitHub technically locks the associated tag and
release assets. GitHub still permits changes to the release title, notes,
prerelease flag, and latest flag; the project policy forbids all of those
post-publication changes even though that part of immutability is governance,
not a GitHub technical control.

## Prepare the release commit

1. Choose the next version according to [VERSIONING.md](../VERSIONING.md). Use a
   full, unprefixed SemVer such as `0.7.0`, `1.0.0-rc.1`, or `1.0.0`.
2. Choose the UTC publication date in `YYYY-MM-DD` form. The workflow requires
   that date to be the current UTC date when it starts.
3. On a pull request, replace the prepared Unreleased heading with exactly:

   ```text
   ## [VERSION] - YYYY-MM-DD
   ```

   Leave at least one non-empty `###` subsection in that version section. Its
   body, up to but excluding the next `##` heading, becomes the release notes.
4. Add a new Unreleased section for subsequent work and update the reference
   links at the bottom of `CHANGELOG.md` to exactly this shape:

   ```text
   [Unreleased]: https://github.com/hokoo/wpPostAble/compare/VERSION...HEAD
   [VERSION]: https://github.com/hokoo/wpPostAble/compare/PREVIOUS...VERSION
   ```

   `PREVIOUS` must be an existing tag that is an ancestor of the selected
   commit. There must be one version heading and one comparison link for the new
   version.
5. Merge the preparation pull request to `master` only after the five required
   CI checks are green and the branch is up to date.
6. Copy the full lowercase 40-character SHA of the resulting `master` commit.
   Do not use a short SHA, branch name, or tag as `target_sha`.

No `version` field is added to `composer.json`. Composer and Packagist derive
the package version from the VCS tag.

## Run a non-mutating validation

From the GitHub Actions page, select the `Release` workflow and choose **Run
workflow** with branch `master`. Enter:

- `version`: the exact prepared SemVer;
- `target_sha`: the full SHA copied from `master`;
- `release_date`: the prepared current UTC date;
- `publish`: `false`.

This is the review path. It validates the release intent, stores the extracted
release notes and gate evidence as workflow artifacts, and reruns the complete
release matrix. The publication job is skipped, so this run cannot create a tag
or GitHub Release.

Review the `Validated release intent` summary, the extracted notes artifact,
and all five gate results:

1. `Release PHP quality / PHP 7.4`
2. `Release PHP quality / PHP 8.4`
3. `Release coverage and package / PHP 8.4`
4. `Release WordPress integration / minimum`
5. `Release WordPress integration / latest`

The coverage/package job enforces 100% source lines and methods. It installs the
Composer archive, the local Git-hosted-equivalent archive, and an authenticated
GitHub zipball requested by the validated full commit SHA. All three must expose
the exact production allowlist, load the frozen API from the installed copy,
and omit development dependencies. The remote archive check uses extracted
contents and commit provenance rather than treating the generated ZIP byte hash
as stable. The two integration profiles exercise the supported WordPress
minimum and current latest release. These are separate gates; coverage is not a
substitute for the behavior or real-WordPress checks.

## Owner publication gate

After reviewing a successful non-mutating run, the repository owner must check
the live immutable-release setting immediately before the publication dispatch.
Use an owner-side `gh` session whose token has repository **Administration:
read** permission:

```bash
test "$(
  gh api \
    --method GET \
    -H 'Accept: application/vnd.github+json' \
    -H 'X-GitHub-Api-Version: 2026-03-10' \
    /repos/hokoo/wpPostAble/immutable-releases \
    --jq '.enabled'
)" = true
```

A `404`, authorization error, `false`, or empty result is a hard stop: do not
dispatch publication. This Administration check deliberately runs in the
owner's authenticated local session. The workflow's `github.token` has only the
documented Contents permission and must not be treated as able to call this
Administration endpoint. No dedicated Administration token is stored as a
workflow secret by design.

After that successful live GET, without changing the repository setting, the
owner runs the same workflow again from `master` with the identical version,
SHA, and UTC date, and sets `publish` to `true`. The workflow deliberately reruns
every gate instead of trusting artifacts from a previous run.

No mutation occurs unless all release jobs succeed. Immediately before
publication the final job confirms that:

- the actor is the repository owner;
- the checkout and dispatch SHA still equal `target_sha`;
- the target remains in current `master`;
- neither the exact local/remote tag nor a GitHub Release exists;
- the release notes still have the hash recorded by the first validation job;
- the exact current remote previous tag from the changelog resolves to a commit
  that is an ancestor of `target_sha`;
- the authenticated GitHub source archive for `target_sha` has already passed
  the production manifest, no-development-dependencies, autoload, signature,
  and installed-file provenance checks in the required coverage/package job.

For a version with a SemVer prerelease component, such as `1.0.0-rc.1`, the
workflow passes `--prerelease --latest=false`. For a stable version it passes
`--latest`. The published release is not a draft. After creation, the job reads
the GitHub Release back and fails unless its `isImmutable` field is `true`.

## Verify publication

Use the workflow summary first, then independently verify the tag, target,
release flags, and exact notes:

```bash
git ls-remote --tags origin refs/tags/VERSION
gh release view VERSION \
  --repo hokoo/wpPostAble \
  --json tagName,targetCommitish,isDraft,isPrerelease,isImmutable,url
gh release view VERSION --repo hokoo/wpPostAble
```

Fetch the tag and confirm that it resolves to the approved SHA:

```bash
git fetch --no-tags origin refs/tags/VERSION:refs/tags/VERSION
git rev-parse refs/tags/VERSION^{commit}
```

For a stable release, also confirm that GitHub's latest-release endpoint returns
the new version. For a release candidate, confirm that it does not replace the
latest stable release:

```bash
gh api repos/hokoo/wpPostAble/releases/latest --jq .tag_name
```

Packagist normally discovers the VCS tag through its configured GitHub hook or
periodic update. Wait for that synchronization, then test the exact version in a
disposable directory. Do not loosen the constraint to a range:

```bash
release_check_dir="$(mktemp -d)"
release_version=VERSION
approved_target_sha=APPROVED_TARGET_SHA
cd "${release_check_dir}"
composer init \
  --name wppostable/release-check \
  --require "hokoo/wppostable:${release_version}" \
  --no-interaction
composer install --no-dev --prefer-dist --no-interaction
php -r '
$lock = json_decode(file_get_contents("composer.lock"), true);
foreach ($lock["packages"] as $package) {
    if ("hokoo/wppostable" !== $package["name"]) continue;
    $expected = $argv[1];
    exit(
        $expected === ($package["source"]["reference"] ?? null)
        && $expected === ($package["dist"]["reference"] ?? null)
        ? 0 : 1
    );
}
exit(1);
' "${approved_target_sha}"
expected_entries="$(printf '%s\n' \
  CHANGELOG.md CONTRIBUTING.md LICENSE README.md VERSIONING.md \
  composer.json docs src | LC_ALL=C sort)"
installed_entries="$(
  find vendor/hokoo/wppostable -mindepth 1 -maxdepth 1 -printf '%f\n' |
    LC_ALL=C sort
)"
test "${installed_entries}" = "${expected_entries}"
test -z "$(find vendor/hokoo/wppostable \( -type l -o -name '.env*' \) -print -quit)"
php -r 'require "vendor/autoload.php"; exit(interface_exists("iTRON\\wpPostAble\\wpPostAble") ? 0 : 1);'
```

The workflow does not call Packagist and a successful GitHub publication does
not prove that Packagist has synchronized. The `source.reference` comparison is
mandatory: a package named with the requested version but resolving to any SHA
other than the approved `target_sha` fails verification. The dist reference and
eight-entry installed root are equally mandatory; this detects a Packagist
distribution that includes tracked development files even when the local
Composer archive is lean. Treat this exact-ref installation as a separate
post-publication verification.

Before stable `1.0.0`, verify the actual Packagist dist of a later prerelease
with this sequence. If no later prerelease is published, the repository owner
must explicitly accept the alternative safe route: the successful
prepublication authenticated GitHub zipball gate for the exact stable target
SHA, followed by the same Packagist checks immediately after publication. Never
modify or replace an immutable earlier release to correct archive contents.

## Failure and immutable recovery rules

Validation or gate failure is safe to rerun after correcting the release pull
request: no tag or release has been created. A dry run (`publish: false`) is
always non-mutating.

If the publication job fails, inspect both the remote tag and GitHub Release
before retrying. An API response may have been lost after GitHub accepted the
creation request. The workflow intentionally rejects any retry once either the
tag or Release exists; it never deletes, moves, recreates, or overwrites one.

- If neither tag nor Release exists, fix the non-mutating cause and repeat the
  owner publication run with the same approved inputs.
- If both exist at the approved SHA and the Release reports `isImmutable: true`,
  treat publication as complete and finish the independent verification.
- If the Release reports `isImmutable: false`, stop. Enabling the setting later
  cannot protect that existing release; enable it through a separate owner
  action and publish a new version instead of editing or recreating this one.
- If only a tag exists, or the tag/Release points somewhere unexpected, stop.
  Do not repair it by moving or deleting the tag. The repository owner must
  review the immutable state and either complete metadata against the already
  approved tag through a separately authorized recovery or publish a new
  version.
- If published notes, flags, or code need correction, prepare and release a new
  SemVer version. The tag and assets are technically locked; the project policy
  requires the otherwise-editable release metadata to remain unchanged too.

The workflow contains no rollback path by design. Any action that would delete
or mutate published release state is outside this runbook and requires a new,
explicit owner decision.
