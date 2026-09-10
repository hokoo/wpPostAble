# wpPostAble 1.0 delivery plan

- Status: Approved; Batch 2 in review
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
| T2 | [#14 Establish the historical changelog and release-note policy](https://github.com/hokoo/wpPostAble/issues/14) | `review` | T1 |
| T3 | [#15 Document local development and every test layer](https://github.com/hokoo/wpPostAble/issues/15) | `completed` | None |
| T4 | [#16 Add contribution and pull-request documentation gates](https://github.com/hokoo/wpPostAble/issues/16) | `waiting_dependency` | T1, T2, T3 |

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
| T5 | [#17 Enforce coverage and package-install release gates](https://github.com/hokoo/wpPostAble/issues/17) | `review` | T3 |
| T6 | [#18 Add a controlled manual release workflow](https://github.com/hokoo/wpPostAble/issues/18) | `waiting_dependency` | T1, T2, T5 |
| T7 | [#19 Protect master and automate merged-branch cleanup](https://github.com/hokoo/wpPostAble/issues/19) | `waiting_dependency` | T5 |
| T8 | [#20 Publish the tested baseline as 0.7.0](https://github.com/hokoo/wpPostAble/issues/20) | `waiting_dependency` | T1–T7, publication approval |

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
2. Batch 2 (`review`): T2 and T5 — historical changelog plus enforceable release gates.
3. Batch 3: T4, T6, and T7 — contribution process, controlled release workflow, and repository governance.
4. Gate: independent E1/E2 QA, then explicit approval to publish `0.7.0` through T8.
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
