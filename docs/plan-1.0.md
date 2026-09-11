# wpPostAble 1.0 delivery plan

- Status: Approved; E2, 0.7.0, and Batch 4 completed; T12 in review
- Approved: 2026-09-11
- Target milestone: [1.0.0](https://github.com/hokoo/wpPostAble/milestone/1)
- Decision record: [ADR 0001](decisions/0001-versioning-and-release-strategy.md)
- Accepted API freeze decision: [ADR 0002](decisions/0002-1.0-api-freeze.md)
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
| T8 | [#20 Publish the tested baseline as 0.7.0](https://github.com/hokoo/wpPostAble/issues/20) | `completed` | T1–T7, T7a, staged owner approvals |

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
| T9 | [#2 Allow initialization from a WP_Post object](https://github.com/hokoo/wpPostAble/issues/2) | `completed` | T8 |
| T10 | [#3 Add slug accessors](https://github.com/hokoo/wpPostAble/issues/3) | `completed` | T8 |
| T11 | [#4 Add menu-order accessors](https://github.com/hokoo/wpPostAble/issues/4) | `completed` | T8 |
| T12 | [#21 Freeze and document the complete 1.0 public API](https://github.com/hokoo/wpPostAble/issues/21) | `review` | T1–T4, T9–T11; accepted ADR 0002 |

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
3. Batch 3 (`completed`): T4, T6, and T7 — contribution process, controlled release workflow, and repository governance.
4. Batch 3a (`completed`): T7a — owner-approved immutable-release enablement and independent security verification.
5. Batch 3b (`completed`): T8 release preparation, non-mutating validation, owner-approved immutable publication, Packagist verification, and independent E2 QA.
6. Batch 4 (`completed`): T9, T10, and T11 after the verified `0.7.0` baseline.
7. Batch 5 (`review`): T12 contract freeze and independent E3 QA under [accepted ADR 0002](decisions/0002-1.0-api-freeze.md); protected PR verification and merge remain.
8. Gate: explicit approval to publish T13 `1.0.0-rc.1`.
9. Batch 6: T14 downstream validation; create and complete focused defect tasks if needed.
10. Gate: independent E4/E5 release QA and explicit approval to publish T15 `1.0.0`.

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
| 2026-09-11 | T7a immutable releases | [PR #30](https://github.com/hokoo/wpPostAble/pull/30); merge `cfcc26b`; [final PR CI run 34536313635](https://github.com/hokoo/wpPostAble/actions/runs/34536313635); [post-merge CI run 34536458166](https://github.com/hokoo/wpPostAble/actions/runs/34536458166) | Pass: live `enabled: true`, independent security QA, 5/5 jobs twice |
| 2026-09-11 | T8 release preparation | Commit `e526bed`; independent staged security review; [PR #31](https://github.com/hokoo/wpPostAble/pull/31); [first full PR CI run 34537495465](https://github.com/hokoo/wpPostAble/actions/runs/34537495465) | Pass for preparation/dry run: exact changelog/ref checks and 5/5 jobs; publication remains NO-GO |
| 2026-09-11 | T8 preparation merge | [Final PR CI run 34537667676](https://github.com/hokoo/wpPostAble/actions/runs/34537667676); merge `612fb575`; [post-merge CI run 34537798149](https://github.com/hokoo/wpPostAble/actions/runs/34537798149) | Pass: protected merge and 5/5 jobs twice |
| 2026-09-11 | T8 non-mutating validation | [Release run 34537950609](https://github.com/hokoo/wpPostAble/actions/runs/34537950609); target `612fb575`; notes SHA-256 `8090afc12f40fd0bf37bece550edb53f9e1f223ffafd22d3c30692eb97f5c497` | Pass: intent and five release gates succeeded; publication job skipped; tag/Release remained absent |
| 2026-09-11 | T8 publication | [Release run 34538775979](https://github.com/hokoo/wpPostAble/actions/runs/34538775979); [immutable Release 0.7.0](https://github.com/hokoo/wpPostAble/releases/tag/0.7.0); target `612fb575` | Pass: five gates preceded the sole mutation; stable/latest, not draft/prerelease, `isImmutable: true` |
| 2026-09-11 | T8 distribution verification | Packagist `0.7.0.0` source/dist refs; disposable exact-version `--no-dev` install; local fetched tag | Pass: all refs `612fb575`; zero dev packages; all public symbols autoloaded |
| 2026-09-11 | E2 independent release QA | GitHub tag/Release/API/events, workflow ordering/artifacts, Packagist/install, historical-object and branch-protection audit | Pass: no defects, blockers, missing AC, or accepted risk |
| 2026-09-11 | T8/E2 closure | [PR #32](https://github.com/hokoo/wpPostAble/pull/32); [PR CI run 34539696113](https://github.com/hokoo/wpPostAble/actions/runs/34539696113); merge `0b05e33`; [post-merge CI run 34539817971](https://github.com/hokoo/wpPostAble/actions/runs/34539817971) | Pass: 5/5 jobs twice; #20 closed; Release remained immutable at `612fb575` |
| 2026-09-11 | T9 implementation | `make check`; `make coverage`; `make test.package-install`; integration artifacts `minimum-20260910T230625Z-28416-22771` and `latest-20260910T230711Z-29747-24204` | Pass: 45 tests/270 assertions; 174/174 lines and 39/39 methods; package install and WP 6.0/PHP 7.4 plus WP 7.1/PHP 8.4 passed with zero remaining fixtures |
| 2026-09-11 | T10 implementation | `make check`; PHP 7.4 unit suite; `make coverage`; `make test.package-install`; integration artifacts `minimum-20260910T231637Z-35090-18841` and `latest-20260910T231756Z-36534-31869` | Pass: 51 tests/309 assertions on PHP 7.4/8.4; 177/177 lines and 41/41 methods; package install and both WordPress edges passed with zero remaining fixtures |
| 2026-09-11 | T11 implementation | `make check`; PHP 7.4 unit suite; `make coverage`; `make test.package-install`; `make smoke`; integration artifacts `minimum-20260910T232558Z-42780-14671` and `latest-20260910T232631Z-44008-10331` | Pass: 59 tests/354 assertions on PHP 7.4/8.4; 180/180 lines and 43/43 methods; localdev, package install, and both WordPress edges passed with zero remaining fixtures |
| 2026-09-11 | Batch 4 pre-PR QA | Root integration artifacts `minimum-20260910T233033Z-47568-5574` and `latest-20260910T233211Z-49057-3120`; independent E3 contract/regression review of `0b05e33..dde6dc6` | Pass: all T9–T11 AC/DoD, 59 tests/354 assertions, 100% line/method coverage, package boundary, PHP 7.4/8.4 and WordPress 6.0/7.1; no findings, missing evidence, leaks, or scope expansion |
| 2026-09-11 | Batch 4 initial PR validation | [PR #33](https://github.com/hokoo/wpPostAble/pull/33); [CI run 34543169846](https://github.com/hokoo/wpPostAble/actions/runs/34543169846) | Pass: 5/5 required jobs; protected PR remains open for final evidence synchronization |
| 2026-09-11 | Batch 4 merge | [Final PR CI run 34543321838](https://github.com/hokoo/wpPostAble/actions/runs/34543321838); merge `48b2923`; [post-merge CI run 34579837783](https://github.com/hokoo/wpPostAble/actions/runs/34579837783) | Pass: 5/5 jobs twice; #2, #3, and #4 closed as completed; merged branch deleted |
| 2026-09-11 | T12 design audit | Source/history/reflection audit; public consumers `cf7-telegram`, `cf7-vk`, `neuralseo`, `cf7-slack`, `ct-antiscam`, `ct-exchanges`, and `ct-treasures`; three independent contract reviews | Pass for decision readiness: four owner gates isolated in proposed ADR 0002; no runtime changes made |
| 2026-09-11 | T12 implementation verification | Commits `d5a1685` and `d743888`; `make check`; `make coverage`; `make test.package-install`; `make smoke`; direct PHP 7.4 unit and package-install runs; integration artifacts `minimum-20260911T091113Z-81343-18280` and `latest-20260911T091153Z-82644-10398` | Pass: 70 tests/970 assertions on PHP 7.4 and 8.4; 192/192 lines and 43/43 methods; installed 19-method API on both PHP edges; localdev and all 16 WordPress lifecycle groups passed with zero fixtures and empty debug/stderr logs |
| 2026-09-11 | T12 documentation and CI/security review | Independent documentation consistency audit; independent review of `48b2923..d743888`; Actionlint 1.7.12; Composer audit | Pass: source/API/migration each contain the same 19 methods, links and fences are valid, no stale contract wording or security/correctness findings, least-privilege CI preserved, no vulnerable Composer advisories |
| 2026-09-11 | Independent E3 contract QA | Review of `48b2923..d743888`; PHP 7.4/8.4 unit and installed-package checks; coverage/localdev; integration artifacts `minimum-20260911T092019Z-88289-31792` and `latest-20260911T092055Z-89341-13439`; AC/DoD trace | Pass: all eight T12 acceptance criteria and implementation/documentation/testing DoD items satisfied; no blocker/high/medium findings, no residual fixtures/resources, and no unapproved scope; only protected PR merge/post-merge CI remains |
| 2026-09-11 | T12 initial PR validation | [PR #34](https://github.com/hokoo/wpPostAble/pull/34); [CI run 34584543192](https://github.com/hokoo/wpPostAble/actions/runs/34584543192) on head `af72df6` | Pass: all 5 required jobs, including installed-package API checks in both PHP 7.4 and PHP 8.4 quality jobs; evidence synchronization is the only subsequent source change |

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
- PR #30's first full CI run `34536131811` passed all five required checks and reported the branch cleanly mergeable under the protected-branch policy. The final evidence-only synchronization must pass the same checks before merge.
- Because administrators can still disable a repository-level setting, the live owner preflight remains mandatory for every publication. T7a is `completed`; T8 remains `waiting_dependency` on the separate explicit approval to prepare and publish `0.7.0`.

### 2026-09-11 — T8 preparation and dry run approved

- The repository owner approved starting T8 release preparation and a non-mutating `publish: false` validation run. This approval does not authorize creating the `0.7.0` tag or GitHub Release.
- T8/#20 moved from `waiting_dependency` to `in_progress`; T1 through T7a are complete and protected `master` at `cfcc26b` has a successful five-job post-merge run.
- The prepared release date is `2026-09-10`, the current UTC date at task start. If UTC changes before validation, the workflow must fail closed and the date must be updated through another reviewed commit.
- Independent read-only review of preparation commit `e526bed` passed the changelog, ancestry, repository-state, task-contract, and mutation-boundary checks. Its staged verdict is PREPARATION/Dry-run PASS and PUBLISH NO-GO pending the final owner gate.
- PR #31's first full CI run `34537495465` passed all five protected checks. This evidence-only synchronization must pass the same matrix before the release-preparation merge.
- After the preparation PR merges, the exact resulting `master` SHA and successful dry-run evidence will be presented at the separate owner publication gate before any `publish: true` dispatch.

### 2026-09-11 — T8 and E2 completed; E3 ready

- PR #31 passed its final five protected checks, merged the prepared `0.7.0` changelog as `612fb57501486850289a04ec8d2f6041a1069a30`, and passed the five-job post-merge run.
- Non-mutating Release run `34537950609` validated that exact SHA/date/notes, passed both PHP jobs, coverage/package installation, and both WordPress profiles, and skipped its publication job. The owner reviewed this evidence and explicitly approved the separate `publish: true` operation.
- Immediate fail-closed preflight reconfirmed UTC date `2026-09-10`, exact remote `master`, absent tag/Release, successful dry run, and live immutable-release enablement. Publication run `34538775979` then reran every gate before its single release mutation succeeded.
- GitHub tag and immutable stable/latest Release `0.7.0` resolve directly to `612fb57501486850289a04ec8d2f6041a1069a30`; the Release is neither draft nor prerelease, and its notes exactly match the approved SHA-256 hash.
- Packagist exposes `0.7.0` with source and dist references at the same SHA. Disposable exact-version installation contained no development packages and autoloaded every public symbol.
- Mandatory independent T8/E2 QA passed with no defects, blockers, missing verification, or risk acceptance. Historical tags/Releases and protected `master` rules remained unchanged apart from the one authorized `0.7.0` addition.
- T8 and E2 are `completed`. Readiness sweep moves T9/#2, T10/#3, and T11/#4 to `todo`; T12/#21 remains `waiting_dependency` until those three API tasks complete.

### 2026-09-11 — Batch 4 started

- Evidence-only PR #32 passed all five protected checks, merged as `0b05e3344706ae24829a346a9c3c79a1746d735a`, closed T8/#20, and passed all five post-merge checks. The immutable `0.7.0` Release remained unchanged at its approved `612fb575` target.
- T9/#2, T10/#3, and T11/#4 moved from `todo` to `in_progress` on `codex/1.0-api-batch-4`; their approved ADR/task contracts satisfy DoR.
- T9 has an isolated implementation owner. T10 and T11 receive parallel read-only contract/test design reviews, then implementation proceeds sequentially because all three tasks share the interface, trait, unit/integration tests, README, versioning policy, and changelog.
- T12/#21 remains `waiting_dependency` until all three implementation tasks are delivered and verified. No RC preparation begins in Batch 4.

### 2026-09-11 — T9 implementation ready for review

- Initialization now accepts exactly `int|WP_Post|null` on the PHP 7.4-compatible untyped boundary. Unsupported weakly coercible values fail before WordPress calls or model mutation.
- A supplied `WP_Post` retains object identity, bypasses insertion and lookup, and shares the existing post-type, metadata, unserialization, and loading-hook path. A type mismatch preserves the supplied object and exposes its ID and model context through `wppaLoadPostException`.
- Unit tests cover object identity, hooks, metadata, mismatch atomicity, `null`/zero creation, and unsupported types. Both real-WordPress profiles cover the supplied-object success and mismatch paths.
- Root verification repeated `make check`, enforced 100% line/method coverage, package-install smoke, and diff validation. Independent minimum/latest integration runs passed with zero leaked fixtures.
- T9/#2 moved from `in_progress` to `review`; it remains open until the integrated Batch 4 PR passes protected CI and merges.

### 2026-09-11 — T10 implementation ready for review

- The interface and trait now expose typed, symmetric `getSlug(): string` and chainable `setSlug(string $slug): self` operations over `WP_Post::post_name`.
- The setter deliberately stores the raw value in memory and does not save. WordPress Core owns normalization, uniqueness, and persistence; the current object keeps its raw value after save, while a reload exposes the persisted Core value.
- Dedicated unit tests freeze the interface signatures, exact save payload, no-autosave behavior, and failed-save/retry state. Real-WordPress tests cover Core normalization, reload, and stability across later title, metadata, publish, and draft saves.
- README, versioning/migration policy, changelog, and testing documentation now describe the slug contract and the requirement for manual interface implementations.
- PHP 7.4 and 8.4 unit checks, enforced 100% line/method coverage, package installation, and both WordPress integration edges passed. T10/#3 moved to `review` pending Batch 4 protected CI and merge.

### 2026-09-11 — T11 implementation ready for review

- The interface and trait now expose typed, symmetric `getMenuOrder(): int` and chainable `setMenuOrder(int $menuOrder): self` operations over `WP_Post::menu_order`.
- The setter accepts any integer, changes only in-memory state, and does not reorder posts or save automatically. The existing save lifecycle persists the field without introducing a separate range or query policy.
- Dedicated unit tests freeze the interface signatures, zero/positive/negative values, exact save payload, no-autosave behavior, and failed-save/retry state.
- Both real-WordPress profiles proved positive, zero, and negative persistence. A forced Core `WP_Error` left the database at zero while retaining `-7` in memory; retry then persisted `-7` without changing title, status, content, parameters, metadata, or slug.
- README, versioning/migration policy, changelog, and testing documentation describe the contract and manual-interface migration. Root repeated unit, coverage, package-install, localdev smoke, and diff checks.
- T11/#4 moved to `review` pending integrated Batch 4 QA, protected CI, and merge.

### 2026-09-11 — Batch 4 pre-PR QA passed

- The delivery-owner integration run on the combined T9–T11 HEAD passed all 16 lifecycle groups on WordPress 6.0/PHP 7.4 and WordPress 7.1/PHP 8.4. Both runs removed every fixture and project-scoped Docker resource; diagnostic logs were empty.
- Independent E3 contract/regression QA reviewed `0b05e33..dde6dc6`, all three issue contracts, code, behavioral tests, documentation, package contents, and compatibility boundaries. It returned PASS with no findings, missing AC/DoD, human risk acceptance, secret/artifact leakage, unrelated changes, or T12 scope expansion.
- Batch 4 is ready for one protected pull request. T9–T11 remain in `review` and their issues remain open until required CI passes and the PR merges.

### 2026-09-11 — Batch 4 protected PR validation passed

- PR #33 links and will close #2, #3, and #4 only when merged. Its body records behavior, migration, SemVer, compatibility/security review, every required verification row, documentation/changelog impact, and independent QA.
- Initial run `34543169846` passed all five required jobs: PHP 7.4 quality, PHP 8.4 quality and coverage/package installation, and WordPress minimum/latest integration.
- This evidence synchronization is the only change after that run. The PR remains open until the new head passes the same strict checks.

### 2026-09-11 — Batch 4 completed; T12 moved to design

- The repository owner explicitly approved merging PR #33. REST merge was bound to the verified head `dca29408050482b9eca1add35a3218bd935d5426` and produced merge commit `48b292379e4b03c207a068cd000442e59374ac39`.
- Final PR run `34543321838` and post-merge run `34579837783` each passed all five required jobs. GitHub closed #2, #3, and #4; their bodies now say `completed`, review labels were removed, and the merged branch was deleted automatically.
- T9, T10, and T11 are `completed`. Their merged implementation satisfies the dependency side of T12's DoR.
- T12 changed from `waiting_dependency` to `needs_design`: its remaining blocker is an explicit owner decision on exact return-type hardening, trait state visibility, legacy exception surface, and synthetic create/save error codes.

### 2026-09-11 — T12 decision audit prepared

- Three independent read-only reviews covered the complete interface/trait surface, exception state and constructors, initializer/loader visibility, PHP 7.4 constraints, tests, history, and known public consumers. The delivery owner also checked GitHub code search and local consumer heads.
- All observed implementers use the trait and call private `wpPostAble()` from the adopting constructor; none calls or overrides `loadPost()`. Current consumers use `getPost()` instead of protected `$post`, though historical `cf7-telegram` code confirms direct property access existed and may remain in stale/private consumers.
- No observed consumer constructs library exceptions directly or reads their context properties directly. Existing consumers use getters and `getMessage()`, but absence in known public heads does not prove private consumers are unaffected.
- Proposed ADR 0002 records four explicit owner gates and recommended choices. Runtime implementation, T12 status `todo`, and RC preparation must wait until that ADR is accepted.

### 2026-09-11 — T12 contract approved and implementation started

- The repository owner approved all four recommendations in ADR 0002: the
  19-method PHP 7.4 contract, private initializer/loaders/post state, preserved
  legacy public exception context, and stable create/save fallback codes.
- ADR 0002 is `Accepted`, and T12 moved from `needs_design` to `in_progress`.
  Runtime, tests, and documentation are being implemented; T12 is not complete
  and no T12 verification evidence has been recorded yet.

### 2026-09-11 — T12 implementation verified before PR

- Commit `d5a1685` aligns the exact 19-method PHP 7.4 interface/trait contract,
  makes the accepted composition state private, adds stable defensive create and
  save errors, adds API and exception contract fixtures, and verifies signatures
  from the installed Composer archive. The PHP quality matrix now runs that
  archive check on PHP 7.4 and 8.4.
- Commit `d743888` publishes the accepted API reference and complete `0.7.x` to
  `1.0` migration guide, updates all public entry points, and records safe
  exception diagnostics and the accepted ADR.
- PHP 7.4 and 8.4 each passed 70 tests with 970 assertions. Coverage remains
  100% at 192/192 executable lines and 43/43 methods. Package installation and
  its 19-signature reflection contract passed on both PHP edges; Composer audit
  reported no advisories; the persistent local WordPress smoke passed.
- Root integration artifacts `minimum-20260911T091113Z-81343-18280` and
  `latest-20260911T091153Z-82644-10398` passed all 16 lifecycle groups against
  WordPress 6.0/PHP 7.4.33 and WordPress 7.1/PHP 8.4.25. Both retained zero
  fixtures, empty lifecycle stderr and WordPress debug logs, and no Docker test
  resources.
- Independent documentation review found exact agreement among source, API,
  migration, versioning, changelog, and testing guidance. Independent CI and
  security review found no correctness or security issue; permissions remain
  read-only, actions remain pinned, and package verification uses the mirrored
  isolated install rather than the source checkout.
- Independent E3 contract QA passed every acceptance criterion with no
  blocker/high/medium finding. Its repeat integration artifacts
  `minimum-20260911T092019Z-88289-31792` and
  `latest-20260911T092055Z-89341-13439` retained zero fixtures and empty logs.
- T12 moved to `review`; only protected PR checks, owner-approved merge, and
  post-merge CI remain. RC publication remains a separate owner gate.

### 2026-09-11 — T12 protected PR opened

- [PR #34](https://github.com/hokoo/wpPostAble/pull/34) links and will close #21
  only when merged. It records the accepted behavior, breaking migrations,
  SemVer classification, compatibility/security analysis, every required local
  verification row, and all three independent reviews.
- Initial GitHub Actions run `34584543192` passed all five required jobs on exact
  head `af72df6bc7c0e0848ee9deb7542b166a4d974399`. Both PHP 7.4 and PHP 8.4
  quality jobs now verify the mirrored installed-package API in addition to
  unit/lint/audit checks; coverage and both WordPress edge jobs also passed.
- This evidence synchronization is the only change after that run. The PR must
  pass the same required checks on the new head before an owner merge gate is
  presented.
