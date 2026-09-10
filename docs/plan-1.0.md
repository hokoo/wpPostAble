# wpPostAble 1.0 delivery plan

- Status: Approved; T7a completed; 0.7.0 publication gate closed
- Approved: 2026-09-11
- Target milestone: [1.0.0](https://github.com/hokoo/wpPostAble/milestone/1)
- Decision record: [ADR 0001](decisions/0001-versioning-and-release-strategy.md)
- Planning PR: [#25](https://github.com/hokoo/wpPostAble/pull/25)
- Baseline: `master` at `b1e998dac9a1d16dd969f2b2e2e2744be0a9ecd0`

## Objective

Publish a minimal, tested, documented, and Composer-installable wpPostAble `1.0.0` with a precise SemVer-protected public API and a reproducible release process.

## Success criteria

- The public API, exceptions, hooks, and persistence contracts are documented and match the implementation.
- Issues #2, #3, and #4 are implemented with unit and real-WordPress coverage.
- Source coverage remains 100% for lines and methods, with behavioral tests treated as a separate gate.
- PHP 7.4/8.4 and WordPress 6.0/latest CI profiles pass on release commits.
- `0.7.0`, `1.0.0-rc.1`, and `1.0.0` are published through the controlled workflow and resolve to verified Packagist refs.
- Representative downstream consumers pass RC compatibility checks.
- `master` is protected by the agreed CI and pull-request rules.
- Every task and PR records documentation, changelog, test, and verification impact.

## Constraints and out of scope

- Preserve PHP 7.4 and WordPress 6.0 minimum support for 1.0.
- Do not rename existing namespaces or classes.
- Do not rewrite historical Git tags.
- Do not add features beyond the agreed minimal API.
- Do not push changes to or release downstream consumer repositories without separate authorization.
- Do not perform PHP 8-only modernization as part of 1.0.

## Status model

- `needs_design`: accepted but missing a technical or contract decision.
- `waiting_dependency`: clear task waiting on a listed dependency.
- `todo`: ready to pull with DoR satisfied.
- `in_progress`: actively being implemented.
- `blocked`: cannot progress without owner input or external change.
- `review`: implemented and awaiting verification or owner review.
- `completed`: committed or merged and verified for the task scope.
- `deferred`: intentionally removed from the current milestone.

GitHub issues hold the complete Goal, Scope, Out of Scope, DoR, DoD, AC, Dependencies, Artifacts, Verification, and Notes/Risks for each execution task. This plan is the delivery index and transition log.

## Delivery order

`E1 → E2 → E3 → E4 → E5`

Release tasks #20, #22, and #24 each require explicit human publication approval. Epic-level QA is performed independently after all implementation tasks in that epic are complete.

## E1. Versioning and documentation system

Outcome: Contributors and consumers have one explicit versioning, compatibility, testing, and contribution system.

Scope:

- SemVer and public-contract policy.
- Historical and unreleased changelog.
- Local development and test documentation.
- Contribution and PR evidence requirements.

Out of Scope:

- Runtime behavior changes.
- Publishing a version.

Success Criteria:

- Documentation is internally consistent, linked, and verified against real commands.
- Every future execution PR has an explicit documentation and changelog path.

Dependencies:

- Approved ADR 0001.

Risks/Open Questions:

- Historical release descriptions may contain gaps and must not be invented.

Tasking Guidance:

- Re-run `$decompose-work` if a task reveals new contract or documentation scope.
- Preserve all required task attributes and assign status from actual readiness.

Tasks:

| ID | GitHub issue | Status | Dependencies |
|---|---|---|---|
| T1 | [#13 Document SemVer and the 1.0 public contract](https://github.com/hokoo/wpPostAble/issues/13) | `completed` | None |
| T2 | [#14 Establish the historical changelog and release-note policy](https://github.com/hokoo/wpPostAble/issues/14) | `completed` | T1 |
| T3 | [#15 Document local development and every test layer](https://github.com/hokoo/wpPostAble/issues/15) | `completed` | None |
| T4 | [#16 Add contribution and pull-request documentation gates](https://github.com/hokoo/wpPostAble/issues/16) | `completed` | T1, T2, T3 |

## E2. Release governance and 0.7.0

Outcome: The current tested baseline is published as `0.7.0` through a protected and reproducible release process.

Scope:

- Enforceable coverage and package-install gates.
- Controlled manual release workflow and runbook.
- Branch protection and merged-branch cleanup.
- Stable/latest `0.7.0` release and Packagist verification.

Out of Scope:

- The new 1.0 API features.
- Rewriting old tags.

Success Criteria:

- A release cannot mutate GitHub state unless every release gate passes.
- `0.7.0` installs from Packagist at the exact approved commit.
- Repository protections match ADR 0001 and are read back after configuration.

Dependencies:

- E1 documentation relevant to each release control.

Risks/Open Questions:

- Incorrect required-check names could block merges.
- Packagist indexing may be asynchronous.

Tasking Guidance:

- Re-run `$decompose-work` for any security or packaging finding.
- Require independent security review for the release workflow and independent epic QA.

Tasks:

| ID | GitHub issue | Status | Dependencies |
|---|---|---|---|
| T5 | [#17 Enforce coverage and package-install release gates](https://github.com/hokoo/wpPostAble/issues/17) | `completed` | T3 |
| T6 | [#18 Add a controlled manual release workflow](https://github.com/hokoo/wpPostAble/issues/18) | `completed` | T1, T2, T5 |
| T7 | [#19 Protect master and automate merged-branch cleanup](https://github.com/hokoo/wpPostAble/issues/19) | `completed` | T5 |
| T7a | [#28 Enable and verify immutable GitHub releases](https://github.com/hokoo/wpPostAble/issues/28) | `completed` | T6, owner approval |
| T8 | [#20 Publish the tested baseline as 0.7.0](https://github.com/hokoo/wpPostAble/issues/20) | `waiting_dependency` | T1–T7, T7a, publication approval |

## E3. Minimal 1.0 public API

Outcome: The minimal consumer-facing contract is complete, consistent, documented, and frozen.

Scope:

- Initialization from `WP_Post`.
- Slug and menu-order accessors.
- Interface/trait alignment, including parameter accessors.
- Complete API and migration documentation.

Out of Scope:

- Additional feature requests.
- Namespace/class renaming.
- Unsupported language modernization.

Success Criteria:

- Each public behavior is represented consistently in code, tests, and docs.
- All three original feature issues are closed by verified implementation PRs.
- No unresolved contract ambiguity remains before RC.

Dependencies:

- Verified `0.7.0` release.

Risks/Open Questions:

- Manual implementations of the interface may require migration when methods are added.

Tasking Guidance:

- Re-run `$decompose-work` on any compatibility finding instead of expanding an active feature silently.
- Run independent epic-level contract QA after T12.

Tasks:

| ID | GitHub issue | Status | Dependencies |
|---|---|---|---|
| T9 | [#2 Allow initialization from a WP_Post object](https://github.com/hokoo/wpPostAble/issues/2) | `waiting_dependency` | T8 |
| T10 | [#3 Add slug accessors](https://github.com/hokoo/wpPostAble/issues/3) | `waiting_dependency` | T8 |
| T11 | [#4 Add menu-order accessors](https://github.com/hokoo/wpPostAble/issues/4) | `waiting_dependency` | T8 |
| T12 | [#21 Freeze and document the complete 1.0 public API](https://github.com/hokoo/wpPostAble/issues/21) | `waiting_dependency` | T1–T4, T9–T11 |

## E4. Release candidate and consumer validation

Outcome: `1.0.0-rc.1` is validated through Packagist and representative real consumers.

Scope:

- RC publication.
- Public consumer checks in `cf7-telegram`, `cf7-vk`, and `neuralseo`.
- At least one authorized private `^0.6.2` consumer check.
- Evidence report and focused follow-up tasks for defects.

Out of Scope:

- Pushing or releasing downstream projects.
- Accepting unresolved release-blocking findings.

Success Criteria:

- Every selected consumer has reproducible pass/fail evidence against the exact RC.
- All release blockers are resolved and reverified before E5.

Dependencies:

- Frozen E3 public contract.

Risks/Open Questions:

- Some consumers follow `dev-master`; tests must force the RC.
- Private consumer environments may require additional access.

Tasking Guidance:

- Use `$decompose-work` to create focused tasks for each release-blocking RC defect.
- Do not treat a compatibility failure as accepted risk without a human gate.

Tasks:

| ID | GitHub issue | Status | Dependencies |
|---|---|---|---|
| T13 | [#22 Publish 1.0.0-rc.1 for compatibility validation](https://github.com/hokoo/wpPostAble/issues/22) | `waiting_dependency` | T12, publication approval |
| T14 | [#23 Validate 1.0.0-rc.1 in downstream consumers](https://github.com/hokoo/wpPostAble/issues/23) | `waiting_dependency` | T13 |

## E5. Stable 1.0.0 release

Outcome: Consumers can install a stable, documented, SemVer-protected `1.0.0` from Packagist.

Scope:

- Final release QA and release-note freeze.
- Git tag, stable/latest GitHub Release, and Packagist verification.
- Stable Composer install smoke check.
- Milestone closure and post-release evidence.

Out of Scope:

- New features after RC freeze.
- Downstream updates/releases.
- 2.0 or PHP 8-only work.

Success Criteria:

- `composer require hokoo/wppostable:^1.0` installs the approved ref.
- GitHub, Packagist, code, documentation, and changelog agree on `1.0.0`.
- Every milestone task and release-blocking follow-up is complete.

Dependencies:

- E4 consumer validation and explicit publication approval.

Risks/Open Questions:

- `1.0.0` makes the documented surface a long-term compatibility commitment.

Tasking Guidance:

- Re-run `$decompose-work` for any final QA defect.
- Do not publish while a release-blocking task remains open.

Tasks:

| ID | GitHub issue | Status | Dependencies |
|---|---|---|---|
| T15 | [#24 Publish stable wpPostAble 1.0.0](https://github.com/hokoo/wpPostAble/issues/24) | `waiting_dependency` | T14, all RC blockers, publication approval |

## Planned execution batches

1. Batch 1 (`completed`): T1 and T3 in parallel — version/public-contract policy plus localdev/testing documentation.
2. Batch 2 (`completed`): T2 and T5 — historical changelog plus enforceable release gates.
3. Batch 3 (`completed`): T4, T6, and T7 — contribution process, controlled release workflow, and repository governance; T7a follows the owner immutability decision.
4. Gate: close T7a, repeat independent E2 security QA, then request explicit approval to publish `0.7.0` through T8.
5. Batch 4: T9, T10, and T11 in parallel after `0.7.0`.
6. Batch 5: T12 contract audit/freeze and independent E3 QA.
7. Gate: explicit approval to publish T13 `1.0.0-rc.1`.
8. Batch 6: T14 downstream validation; create and complete focused defect tasks if needed.
9. Gate: independent E4/E5 release QA and explicit approval to publish T15 `1.0.0`.

Batch 1 was approved for execution on 2026-09-11.

## Documentation rule for every task

Each implementation task must, in the same PR:

1. update its status in this plan and its GitHub issue;
2. update `CHANGELOG.md` unless the contribution policy explicitly permits no changelog;
3. update affected user, API, testing, localdev, or release documentation;
4. record exact verification commands and immutable CI/commit links;
5. receive independent epic QA before the epic closes.

## Evidence register

| Date | Task/Epic | Evidence | Result |
|---|---|---|---|
| 2026-09-11 | Planning | ADR 0001 approved; milestone and execution issues created | Pass |
| 2026-09-11 | Planning artifact | Commit `183f428`; [PR #25](https://github.com/hokoo/wpPostAble/pull/25); [CI run 34524922288](https://github.com/hokoo/wpPostAble/actions/runs/34524922288) | Pass: 5/5 jobs |
| 2026-09-11 | Planning merge | Merge commit `f38b461`; [post-merge CI run 34526304611](https://github.com/hokoo/wpPostAble/actions/runs/34526304611) | Pass: 5/5 jobs |
| 2026-09-11 | T1 | Commit `d86c438`; source/API/tag audit; `composer validate --strict`; `git diff --check` | Pass |
| 2026-09-11 | T3 | Commit `d90cd49`; `make check`; `make smoke`; `make coverage`; minimum/latest integration artifacts `minimum-20260910T203202Z-36997-22347` and `latest-20260910T203243Z-38215-22367` | Pass |
| 2026-09-11 | Batch 1 integration | Commit `86f1fda`; documentation links and PHP 7.4-compatible README example | Pass |
| 2026-09-11 | Batch 1 PR | [PR #26](https://github.com/hokoo/wpPostAble/pull/26); [CI run 34527618018](https://github.com/hokoo/wpPostAble/actions/runs/34527618018) | Pass: 5/5 jobs |
| 2026-09-11 | Batch 1 merge | Merge commit `e865dbc`; [post-merge CI run 34527776340](https://github.com/hokoo/wpPostAble/actions/runs/34527776340) | Pass: 5/5 jobs |
| 2026-09-11 | T2 | Commit `58fe3da`; 12 local/remote tags and 10 GitHub Release records reconciled; all ref, release, and comparison links verified | Pass |
| 2026-09-11 | T5 | Commit `e974684`; `make check`; `make coverage`; line-only and method-only negative Clover checks; `make test.package-install`; Composer 1/PHP 7.4 compatibility run; `actionlint` | Pass |
| 2026-09-11 | T5 WordPress matrix | Artifacts `minimum-20260910T205636Z-53336-22973` and `latest-20260910T205716Z-54494-12965` | Pass: WP 6.0/PHP 7.4.33 and WP 7.1/PHP 8.4.25; zero fixtures |
| 2026-09-11 | Batch 2 PR | [PR #27](https://github.com/hokoo/wpPostAble/pull/27); [CI run 34530586203](https://github.com/hokoo/wpPostAble/actions/runs/34530586203) | Pass: 5/5 jobs |
| 2026-09-11 | Batch 2 merge | Merge commit `26426f4`; [post-merge CI run 34530717523](https://github.com/hokoo/wpPostAble/actions/runs/34530717523) | Pass: 5/5 jobs |
| 2026-09-11 | T7 | GitHub protection/repository API read-back; old branch head `4390e21`; `git merge-base --is-ancestor`; unique commit count `0` | Pass |
| 2026-09-11 | T4 | Commit `ca15664`; internal-link and Make-target audit; template structure; `make help`; `git diff --check` | Pass |
| 2026-09-11 | T6 | Commit `c8f59c1`; actionlint 1.7.12/ShellCheck 0.11.0; permission/YAML invariants; SemVer/changelog negative cases; exact-version `0.6.2` Composer install | Pass; publication intentionally not run |
| 2026-09-11 | E1 independent QA | SemVer/history, localdev/testing, contribution/template, links/commands, archive boundary, and documentation-language audit | Pass; no blockers |
| 2026-09-11 | E2 independent security QA | Live release-immutability API and release-workflow TOCTOU review; [#28](https://github.com/hokoo/wpPostAble/issues/28) | Fail for publication: release immutability disabled; previous tag not revalidated in final job |
| 2026-09-11 | T6 security remediation | Commit `7c051a2`; final live remote-tag ancestry proof, immutable-release pre/post gates, exact Packagist source reference | Pass: independent re-review closed the workflow blocker |
| 2026-09-11 | Batch 3 initial CI | [Run 34534475421](https://github.com/hokoo/wpPostAble/actions/runs/34534475421) | Expected gate failure: new `CONTRIBUTING.md` was not yet in the package allowlist |
| 2026-09-11 | Batch 3 package remediation | Commit `b6dee5e`; `make test.package-install`; [PR CI run 34534670305](https://github.com/hokoo/wpPostAble/actions/runs/34534670305) | Pass: reviewed public-documentation allowlist and 5/5 jobs |
| 2026-09-11 | Batch 3 final PR | [PR #29](https://github.com/hokoo/wpPostAble/pull/29); [CI run 34534813793](https://github.com/hokoo/wpPostAble/actions/runs/34534813793) | Pass: protected merge, 5/5 jobs |
| 2026-09-11 | Batch 3 merge | Merge commit `6b2b023`; [post-merge CI run 34535010279](https://github.com/hokoo/wpPostAble/actions/runs/34535010279) | Pass: 5/5 jobs |

## Transition log

### 2026-09-11 — Planning baseline

- All five decision gates were approved by the repository owner.
- ADR 0001 records the versioning, API, governance, documentation, release, and downstream-validation decisions.
- GitHub milestone `1.0.0` was created.
- Existing issues #2, #3, and #4 were normalized with complete execution contracts.
- Twelve additional execution issues were created.
- Planning artifacts were committed on `codex/1.0-release-plan` and opened as PR #25.
- PR #25 passed PHP 7.4/8.4 quality, coverage, and WordPress minimum/latest checks.
- Readiness sweep: T1 and T3 are `todo`; every other task is `waiting_dependency` with an explicit upstream dependency.
- Next decision: approve or redirect execution Batch 1 (T1 and T3).

### 2026-09-11 — Batch 1 started

- The repository owner approved the recommended Batch 1.
- Planning PR #25 was merged as `f38b461` and post-merge CI passed 5/5 jobs.
- T1/#13 and T3/#15 moved from `todo` to `in_progress`.
- Worker ownership is isolated: T1 owns `VERSIONING.md`; T3 owns the local-development and testing guides. README, plan status, and evidence integration remain with the delivery owner.

### 2026-09-11 — Batch 1 implementation complete

- T1 produced the SemVer policy and a source-grounded inventory of the current public compatibility surface in `d86c438`.
- T3 produced local-development and testing guides in `d90cd49`; unit, smoke, coverage, and both isolated WordPress profiles passed.
- README integration was committed as `86f1fda`, including correction of the PHP 7.4 constructor example from `int|null` to `?int`.
- `make setup` could not pass its host-mapping pre-step in the headless agent shell because interactive `sudo` is required. The guide now states this explicitly; the non-destructive setup script then reconciled the existing site successfully and preserved content.
- T1/#13 and T3/#15 moved to `review`, pending branch CI and merge.

### 2026-09-11 — Batch 1 completed; Batch 2 started

- PR #26 passed all five required jobs and was merged as `e865dbc`; the post-merge run also passed all five jobs.
- T1/#13 and T3/#15 are closed and `completed`.
- Readiness sweep: T1 completion unblocked T2/#14; T3 completion unblocked T5/#17. Both tasks satisfy their DoR and moved from `waiting_dependency` to `in_progress`.
- Batch 2 ownership is isolated: T2 owns the historical changelog; T5 owns coverage enforcement, package-install smoke, CI, and affected testing documentation. Plan, cross-document integration, and evidence remain with the delivery owner.

### 2026-09-11 — T2 implementation complete

- `CHANGELOG.md` reconstructs all releases from `0.1` through `0.6.2`, records the post-`0.6.2` upgrade impact planned for `0.7.0`, and defines the future Unreleased-entry policy.
- Verification reconciled all 12 local tags with their remote SHAs and all 10 GitHub Release records. `0.2.1` and `0.3` were confirmed as tag-only; the historical prerelease/latest mismatch is recorded as a dated snapshot.
- T2/#14 moved from `in_progress` to `review`, pending Batch 2 CI and merge.

### 2026-09-11 — T5 implementation complete

- Clover aggregate metrics now enforce 100% executable source lines and methods and emit a machine-readable summary; independent line-only and method-only reductions both failed with actionable diagnostics.
- A clean Composer archive/install gate now resolves the package in an isolated workspace, performs a locked classmap-authoritative `--no-dev` install, loads every public symbol from the installed copy, and emits artifact evidence.
- During implementation, the initial Composer archive was found to include workspace-only files. The temporary archive was deleted without opening `.env`, and the final package allowlist now rejects environment files, dependencies, local WordPress state, tests, scripts, and developer tooling.
- The final package gate passed on PHP 8.4/Composer 2 and PHP 7.4/Composer 1.10.27. Both WordPress integration profiles passed and removed their temporary resources.
- Root verification repeated quality, coverage, package-install, evidence, cleanup, and both negative threshold scenarios. T5/#17 moved from `in_progress` to `review`, pending Batch 2 CI and merge.

### 2026-09-11 — Batch 2 completed; Batch 3 started

- PR #27 passed all five stable check runs and was merged as `26426f4`; the post-merge run passed the same five checks, including the enforced coverage and package-install steps.
- T2/#14 and T5/#17 are closed and `completed`.
- Readiness sweep: merged T1–T3 and T5 satisfy every dependency and DoR for T4/#16, T6/#18, and T7/#19. All three moved from `waiting_dependency` to `in_progress`.
- Batch 3 isolates contribution docs, release-workflow implementation, and repository-settings mutations. The workflow receives an independent security review, and repository protection is accepted only after API read-back.

### 2026-09-11 — T7 repository governance applied

- Before mutation, `master` had no branch protection and `delete_branch_on_merge` was false. The exact five successful GitHub Actions check names and their GitHub Actions App ID were read from merge commit `26426f4`.
- `master` now requires pull requests and all five checks in strict/up-to-date mode, with zero required approvals for the single-maintainer flow. The rule applies to administrators; force pushes and branch deletion are disabled.
- Automatic deletion of merged branches is enabled and was confirmed by repository API read-back.
- The remote branch `codex/test-foundation-localdev-ci` was deleted only after PR #12, its exact head `4390e21`, ancestry from `master`, and zero unique commits were verified. Its history remains reachable from `master`.
- Protection, repository settings, and branch absence were all read back successfully. T7/#19 moved from `in_progress` to `review`, pending recorded-evidence merge and independent E2 QA.

### 2026-09-11 — T4 implementation complete

- `CONTRIBUTING.md` defines the lightweight single-maintainer branch/commit flow, SemVer assessment, compatibility/security review, test evidence, documentation impact, and narrow no-changelog cases.
- The pull-request template requires linked intent, before/after behavior, one SemVer choice, compatibility risks, all verification rows, documentation/changelog decisions, and final secret/artifact checks.
- README and the Unreleased changelog now expose the contribution workflow. Every internal link and referenced Make target was verified against the repository.
- T4/#16 moved from `in_progress` to `review`, pending Batch 3 CI, merge, and independent E1 documentation QA.

### 2026-09-11 — T6 implementation complete; independent QA started

- The manual release workflow accepts an explicit full SemVer, full `master` SHA, UTC date, and `publish` boolean. Dry runs execute all five release gates without a write-capable job.
- The final publication job alone receives `contents: write`, requires both the original and rerun actor to be the repository owner, revalidates the target, notes hash, changelog links, and tag/Release absence, then performs one `gh release create` operation.
- The runbook documents preparation, non-mutating validation, the owner publication gate, exact-ref GitHub/Packagist checks, and immutable stop/recovery boundaries. Its Composer verification sequence was executed against `0.6.2`.
- T6/#18 moved from `in_progress` to `review`. Independent E1 documentation QA and E2 workflow/security/governance QA are running before Batch 3 can merge.

### 2026-09-11 — Independent QA and immutable-release decision gate

- Independent E1 QA passed with no blockers. One wording recommendation about dependency setup was applied; README API mismatches remain intentionally assigned to T12 before RC.
- Independent E2 QA confirmed the workflow permission/input/gate model and all live `master` protections, but failed publication readiness on two focused findings.
- The workflow's final job did not repeat remote previous-tag ancestry validation; remediation is in progress within T6.
- GitHub's live repository API reports release immutability `enabled: false`. GitHub documents that enablement applies only to future releases, so it must be enabled before `0.7.0` if the approved immutable-release contract is to be technically enforced.
- Focused task T7a/#28 was created as `needs_design`. No release was dispatched and no release/tag setting was changed pending the repository owner's explicit decision.
- T6/#18 returned from `review` to `in_progress` for the workflow/runbook remediation that does not require the live-setting decision.

### 2026-09-11 — T6 security remediation verified

- Immediately before its sole mutation, the final job now re-reads the exact comparison link, validates and fetches the current remote previous tag into an isolated ref, resolves its commit, and proves ancestry to the approved target.
- The runbook adds the owner-side Administration-read immutability preflight without adding a workflow administration secret. Post-publication verification requires `isImmutable: true`, and Packagist verification requires the installed source reference to equal the approved SHA.
- Independent security re-review passed the code, permissions, quoting, ordering, future-only immutability semantics, and exact-SHA checks. T6/#18 returned to `review` for Batch 3 CI and merge.
- The sole residual publication blocker is T7a: the live GitHub release-immutability setting remains disabled pending explicit owner approval.

### 2026-09-11 — Batch 3 package gate finding resolved

- The first Batch 3 CI run correctly rejected the Composer archive because the newly added root-level `CONTRIBUTING.md` was not in the exact production allowlist.
- `CONTRIBUTING.md` is intentionally public package documentation and is linked from the packaged README, so commit `b6dee5e` added it to the allowlist instead of shipping a broken README link or weakening the boundary.
- The local package-install smoke passed and the synchronized PR run passed all five required checks. The failed run remains linked as evidence that the release gate detects unreviewed archive-boundary changes.

### 2026-09-11 — Batch 3 completed at the security gate

- Final PR #29 CI passed all five required checks on `83b4214`; protected `master` reported the pull request mergeable only after those checks passed.
- PR #29 was merged as `6b2b023`, closing T4/#16, T6/#18, and T7/#19. The post-merge run passed all five checks, including coverage/package gates and both WordPress profiles.
- T4, T6, and T7 are `completed`; their intermediate review labels were removed from the closed issues.
- T7a/#28 remains `needs_design`. No `0.7.0` preparation or publication work starts until the repository owner decides whether to enable release immutability.

### 2026-09-11 — T7a owner decision approved

- The repository owner explicitly approved enabling GitHub Release immutability for this repository.
- T7a/#28 moved from `needs_design` to `in_progress`. The setting will be accepted only after live API read-back and independent security verification.
- This decision does not authorize preparation or publication of `0.7.0`; the publication gate remains separate.

### 2026-09-11 — T7a immutable-release control completed

- The versioned GitHub API enablement request succeeded, and an immediate independent read-back returned `enabled: true`. `enforced_by_owner: false` records that no higher-level owner policy imposes the setting; repository-level enablement is active.
- Independent read-only security QA passed with no blockers. It reconfirmed strict `master` protection, the exact five required GitHub Actions checks, admin enforcement, and disabled force-push/deletion.
- The verification found exactly the 12 historical tags from `0.1` through `0.6.2` and 10 historical Releases, with no `0.7.0` tag or Release, no Release workflow runs, and no evidence that T7a mutated an existing release object.
- The release workflow and runbook remain unchanged from protected `master` and satisfy T7a's preflight, post-publication `isImmutable`, remote previous-tag ancestry, future-only boundary, and exact Packagist SHA requirements.
- Because administrators can still disable a repository-level setting, the live owner preflight remains mandatory for every publication. T7a is `completed`; T8 remains `waiting_dependency` on the separate explicit approval to prepare and publish `0.7.0`.
