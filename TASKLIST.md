# Implementation Task List

## Task Rules

- Work top-to-bottom unless a documented dependency requires adjustment.
- Do not mark `[x]` until implementation and verification are complete.
- Add a short verification note under completed phase headings when useful.
- Update specification documents if architecture changes.
- Inspect `git diff` before moving to a new phase.

---

## Phase 0 — Repository and Architecture Baseline

- [x] Read all specification Markdown files.
- [x] Inspect existing repository and current stack.
- [x] Verify current official Gemini Live documentation and selected model capabilities.
- [x] Verify Hostinger target PHP/CLI environment assumptions.
- [x] Create initial Laravel 13 project if repository is empty.
- [x] Configure MySQL development environment.
- [ ] Configure Blade, Livewire 4, Alpine.js, Tailwind CSS, and Vite.
- [x] Establish domain/module folder conventions.
- [x] Create `.env.example` with required non-secret keys.
- [x] Create Gemini Live Model Capability Registry abstraction.
- [x] Document selected current Gemini Live model ID in environment configuration, not domain code.
- [x] Run baseline tests.

### Phase 0 Verification

- [x] Application boots locally.
- [x] Test suite runs.
- [x] Vite production build works.
- [x] No API secret is committed.

Verification note 2026-07-06: Laravel 13.18.1 scaffold boots with PHP 8.3.31. Local MariaDB 10.4.32 database `aicall` migrated successfully through XAMPP. `php artisan test` passed 4 tests / 12 assertions. `npm run build` passed. No real API keys were added; `.env` remains ignored.

Livewire 4 note 2026-07-06: `livewire/livewire v4.0.0` currently requires Illuminate `^10|^11|^12`, so it cannot be installed with Laravel 13.18.1. This is documented in `docs/02_TECH_STACK.md`; the task remains open until Livewire supports Laravel 13 or the backend version requirement changes explicitly.

Hostinger note 2026-07-06: Current Hostinger PHP documentation shows PHP 8.3 as the default for new websites and PHP 8.2 through 8.5 available in hPanel. Cron documentation confirms custom cron commands and UTC scheduling. Account-specific website PHP and SSH/CLI PHP binaries remain deployment-phase checks.

---

## Phase 1 — Authentication, Accounts, and Authorization

- [x] Implement official Laravel authentication foundation.
- [x] Customize registration with full name, email, password, confirmation.
- [x] Default new sales account to `PENDING_APPROVAL`.
- [x] Implement waiting-for-approval screen.
- [x] Implement suspended-account screen.
- [x] Implement forgot-password flow.
- [x] Implement password-reset flow.
- [x] Create branches table/model.
- [x] Add user branch relationship.
- [x] Add initial roles `SUPER_ADMIN` and `SALES`.
- [x] Add account statuses.
- [x] Implement Policies/Gates/authorization services.
- [x] Seed/create first Super Admin using secure documented procedure.
- [ ] Build HQ pending-user list.
- [ ] Implement assign branch + approve account.
- [ ] Implement suspend/reactivate account.

### Phase 1 Tests

Verification note 2026-07-06: Installed Laravel Breeze 2.4 Blade authentication scaffold on Laravel 13. Auth routes, controllers, Blade views, password reset flow, profile routes, and generated feature tests are present. `php artisan test` passed 27 tests / 71 assertions, and `npm run build` passed.

Verification note 2026-07-06: Registration now uses Nama Lengkap, Email, Password, and Konfirmasi Password; stores full name in `users.name`; creates registered users as `PENDING_APPROVAL`; and redirects pending/suspended accounts away from the dashboard to clear status pages. Local migration applied successfully. `php artisan test` passed 33 tests / 86 assertions, and `npm run build` passed.

Verification note 2026-07-06: Existing Breeze forgot-password/password-reset flow was audited against product requirements. It uses email-based reset links, Laravel's `password_reset_tokens` broker with 60-minute expiry, and hashed password updates. User-facing reset views and password broker status messages were localized to simple Bahasa Indonesia. `php artisan test` passed 33 tests / 90 assertions, and `npm run build` passed.

Verification note 2026-07-06: Added `branches` table/model with unique code, name, active flag, timestamps, factory support, nullable `users.branch_id`, and User/Branch relationships. Local migration applied successfully. `php artisan test` passed 37 tests / 97 assertions, and `npm run build` passed.

Verification note 2026-07-06: Added centralized `UserRole` enum for `SUPER_ADMIN` and `SALES`, `users.role` with default `SALES`, User role helpers, HQ access helper, registration default role, and Super Admin factory/dev creation path. Local migration applied successfully. `php artisan test` passed 41 tests / 109 assertions, and `npm run build` passed.

Verification note 2026-07-06: Implemented centralized authorization structure. Added `UserRole` enum methods for all business abilities (manage branches/users/personas/scenarios, approve users, configure AI providers, view all training sessions). Created `AuthServiceProvider` with gates for all abilities, `BranchPolicy`, and `UserPolicy`. Created `EnsureUserCanAccessHq` middleware registered as `hq` alias. All 36 authorization tests pass. `php artisan test` passed 77 tests / 162 assertions, and `npm run build` passed.

Verification note 2026-07-06: Created `app:create-super-admin` Artisan command with required `--name`, `--email`, `--password` options, input validation, duplicate email rejection, single-Super-Admin guard (with `--force` override), and auto-set `email_verified_at`. Documented in deployment docs. `php artisan test` passed 88 tests / 189 assertions, and `npm run build` passed.

- [x] pending user cannot train
- [x] suspended user cannot train
- [x] Sales cannot access HQ admin routes
- [x] password reset works in test environment
- [ ] approval requires authorized HQ user

---

## Phase 2 — Persona Domain and Versioning

- [ ] Create `personas` and immutable `persona_versions` model strategy.
- [ ] Implement Persona status and archival.
- [ ] Implement Persona Builder sections.
- [ ] Implement Static Persona configuration.
- [ ] Implement Housing Context.
- [ ] Implement Knowledge and Beliefs.
- [ ] Implement Personality Profile.
- [ ] Implement Communication Style.
- [ ] Implement Human Behavior Traits.
- [ ] Implement Initial Dynamic State and sensitivity advanced configuration.
- [ ] Implement Persona Objections.
- [ ] Implement Hidden Information.
- [ ] Implement Persona duplication.
- [ ] Editing published/used persona creates new version.
- [ ] Prevent Sales access to Persona admin endpoints.

### Phase 2 Tests

- [ ] persona version immutability
- [ ] new version created on edit
- [ ] Sales cannot modify persona
- [ ] hidden persona configuration is not exposed through Sales APIs

---

## Phase 3 — Scenario and Rubric Domain

- [ ] Create scenario/versioning models.
- [ ] Implement Scenario Builder.
- [ ] Implement first-speaker configuration.
- [ ] Implement allowed Persona Selection Modes.
- [ ] Implement scenario-persona assignments.
- [ ] Implement difficulty levels and Custom modifiers.
- [ ] Enforce max roleplay duration <= 900 seconds.
- [ ] Implement `allow_ai_end_call`.
- [ ] Implement Global Rubric management.
- [ ] Implement Scenario Rubric management.
- [ ] Implement Scenario global-rubric weight overrides.
- [ ] Create `RubricMerger`.

### Phase 3 Tests

- [ ] mode restrictions
- [ ] persona assignment restrictions
- [ ] max duration validation
- [ ] rubric merge
- [ ] rubric weight override

---

## Phase 4 — Persona Salience Compiler

- [ ] Create salience value objects/DTOs.
- [ ] Implement explicit Primary/Secondary/Background override support.
- [ ] Implement automatic salience selection.
- [ ] Consider trait intensity.
- [ ] Consider scenario relevance.
- [ ] Consider difficulty.
- [ ] Implement behavior compatibility/conflict handling.
- [ ] Limit recommended Primary traits to 2–3.
- [ ] Limit recommended Secondary traits to 2–3.
- [ ] Build Admin salience preview.

### Phase 4 Tests

- [ ] many high sliders do not all become Primary
- [ ] scenario relevance changes salience
- [ ] explicit override works
- [ ] incompatible dominant traits are handled predictably

---

## Phase 5 — Roleplay Instruction Compiler

- [ ] Create immutable Persona/Scenario/Difficulty snapshot DTOs.
- [ ] Implement Session Snapshot service.
- [ ] Implement structured Actor Instruction sections.
- [ ] Compile Primary Behavior.
- [ ] Compile Secondary Behavior.
- [ ] Compile Background tendencies selectively.
- [ ] Compile Knowledge and Misconceptions.
- [ ] Compile Scenario context.
- [ ] Compile Director Note rules.
- [ ] Compile Actor guardrails.
- [ ] Hash/store instruction snapshot privately.
- [ ] Prevent Sales endpoints from exposing compiled instructions.

### Phase 5 Tests

- [ ] instructions are structured
- [ ] instructions do not dump all sliders
- [ ] misconceptions are preserved
- [ ] hidden information rules exist
- [ ] Director Notes are explicitly internal

---

## Phase 6 — Roleplay Director Engine v1

- [ ] Create `RoleplayDirectorEngine` domain module.
- [ ] Implement bounded Dynamic State.
- [ ] Implement State-to-Behavior Translator.
- [ ] Implement normalized Event taxonomy.
- [ ] Implement Event validation.
- [ ] Implement Event fingerprint/deduplication.
- [ ] Implement recent-event memory.
- [ ] Implement diminishing returns.
- [ ] Implement transition-rule architecture.
- [ ] Implement core communication transition rules.
- [ ] Implement Objection State Machine.
- [ ] Implement Hidden Information State Machine.
- [ ] Implement Boundary State Machine.
- [ ] Implement Conversation Phase Manager.
- [ ] Implement Difficulty modifiers.
- [ ] Implement Director Note Planner.
- [ ] Implement Director Note cooldown and duplicate suppression.
- [ ] Implement AI-ending eligibility.
- [ ] Implement Director Session Summary Builder.

### Phase 6 Tests

- [ ] state clamp
- [ ] persona multiplier effects
- [ ] diminishing returns
- [ ] duplicate event suppression
- [ ] note cooldown
- [ ] objection transitions
- [ ] disclosure transitions
- [ ] boundary transitions
- [ ] AI-ending eligibility

---

## Phase 7 — Roleplay Session Setup and Sales Flow

- [ ] Build Training Dashboard.
- [ ] Build Scenario selection.
- [ ] Display difficulty.
- [ ] Implement CHOOSE_PERSONA flow.
- [ ] Implement RANDOM_PERSONA server-side resolution.
- [ ] Implement HIDDEN_PERSONA server-side resolution.
- [ ] Build briefing screen.
- [ ] Build pre-call screen.
- [ ] Implement microphone-permission UI.
- [ ] Implement secure roleplay-session creation transaction.
- [ ] Create Persona Snapshot.
- [ ] Create Scenario Snapshot.
- [ ] Create Difficulty Snapshot.
- [ ] Create Rubric Snapshot.
- [ ] Save Salience Snapshot.
- [ ] Initialize Director State.

### Phase 7 Tests

- [ ] hidden data absent from browser response
- [ ] unauthorized persona cannot be selected
- [ ] same setup creates one application session
- [ ] snapshots are immutable

---

## Phase 8 — Gemini Live Vertical Voice Integration

- [ ] Re-verify current official Gemini Live API and model capabilities.
- [ ] Implement `GeminiLiveRoleplayProvider` server-side provisioning service.
- [ ] Implement ephemeral-token endpoint.
- [ ] Verify short-lived token/config restrictions.
- [ ] Implement browser `RoleplayRuntime`.
- [ ] Implement `GeminiLiveClient`.
- [ ] Implement microphone capture.
- [ ] Implement PCM conversion/resampling according to current API requirements.
- [ ] Implement streaming microphone audio.
- [ ] Implement AI PCM playback queue.
- [ ] Implement speaking/listening state events.
- [ ] Implement barge-in/interruption handling.
- [ ] Clear stale playback on model interruption.
- [ ] Implement input transcription parsing.
- [ ] Implement output transcription parsing.
- [ ] Process all Live event content parts correctly.
- [ ] Implement AI-first scenario opening.
- [ ] Implement 14-minute warning.
- [ ] Implement 15-minute maximum ending flow.
- [ ] Implement Live lifecycle/GoAway handling.
- [ ] Implement official session resumption.
- [ ] Preserve same Laravel roleplay session through reconnect.

### Phase 8 Manual Verification

- [ ] real Indonesian voice conversation works
- [ ] user can interrupt AI
- [ ] stale AI audio stops
- [ ] AI-first scenario works
- [ ] reconnect/session resumption tested
- [ ] 15-minute roleplay tested
- [ ] permanent Gemini key is not exposed

---

## Phase 9 — Transcript Normalization

- [ ] Implement browser `TranscriptEventBuffer`.
- [ ] Implement server transcript final-turn endpoint/protocol.
- [ ] Implement `TranscriptAssembler` validation.
- [ ] Implement sequence ordering.
- [ ] Implement duplicate prevention.
- [ ] Implement interrupted AI turn handling.
- [ ] Implement transcript integrity status.
- [ ] Implement idempotent transcript finalization.

### Phase 9 Tests

- [ ] partial accumulation
- [ ] duplicate final prevention
- [ ] ordering
- [ ] interruption handling
- [ ] finalization idempotency

---

## Phase 10 — Sparse Director Semantic Tool Integration

- [ ] Define strict `report_roleplay_event` Live tool schema.
- [ ] Add semantic-tool rules to Actor Instructions.
- [ ] Implement browser tool-call bridge.
- [ ] Implement authenticated Director event endpoint.
- [ ] Validate event enums/schema.
- [ ] Deduplicate event.
- [ ] Apply Director transition.
- [ ] Persist meaningful event/state transition.
- [ ] Return fast tool response.
- [ ] Return optional Director guidance.
- [ ] Send approved Director Notes through realtime text input.
- [ ] Implement `request_roleplay_end` tool.
- [ ] Validate scenario + Director ending eligibility.

### Phase 10 Manual Verification

- [ ] Actor does not call semantic tool every turn
- [ ] Director Note frequency remains sparse
- [ ] objection reveal behavior follows Director state
- [ ] boundary retest behavior respects cooldown
- [ ] AI end is blocked when disabled
- [ ] AI end works naturally when eligible/enabled

---

## Phase 11 — Evaluation Engine

- [ ] Create `EvaluationProvider` interface.
- [ ] Create Evaluation Provider Manager.
- [ ] Implement provider configuration admin UI.
- [ ] Implement environment secret references.
- [ ] Implement OpenRouter provider adapter.
- [ ] Implement Groq provider adapter.
- [ ] Implement Gemini evaluation provider adapter.
- [ ] Implement Evaluation Request Builder.
- [ ] Implement effective rubric merge snapshot usage.
- [ ] Implement Director Session Summary input.
- [ ] Define Evaluation JSON Schema v1.
- [ ] Implement JSON parsing.
- [ ] Implement schema validation.
- [ ] Implement semantic validation.
- [ ] Implement provider timeout/error normalization.
- [ ] Implement retries.
- [ ] Implement fallback.
- [ ] Implement idempotent evaluation job.
- [ ] Configure database queue.
- [ ] Configure bounded cron queue execution strategy.
- [ ] Implement failed-evaluation manual retry for authorized user.

### Phase 11 Tests

- [ ] primary success
- [ ] primary failure/secondary success
- [ ] malformed JSON handling
- [ ] semantic validation
- [ ] all-provider failure
- [ ] duplicate evaluation prevention
- [ ] immutable snapshot usage on retry

---

## Phase 12 — Coaching Result and Training History

- [ ] Build evaluation-processing state.
- [ ] Build evaluation-failed state.
- [ ] Build Overall Performance section.
- [ ] Build Session Summary.
- [ ] Build Score Breakdown.
- [ ] Build strengths.
- [ ] Build improvement areas.
- [ ] Build critical mistakes.
- [ ] Build missed opportunities.
- [ ] Build Customer Emotional Journey.
- [ ] Build better response examples.
- [ ] Build Next Training Focus.
- [ ] Build transcript viewer.
- [ ] Link findings to transcript sequences.
- [ ] Build Sales Training History.
- [ ] Build personal improvement trends.
- [ ] Protect Hidden Persona private configuration.

---

## Phase 13 — HQ Training Dashboard

- [ ] Total sessions metric.
- [ ] Active sales users metric.
- [ ] Sessions by branch.
- [ ] Average performance.
- [ ] Performance by branch.
- [ ] Weakest competencies.
- [ ] Common sales mistakes.
- [ ] Common missed opportunities.
- [ ] Most-used scenarios.
- [ ] Evaluator failure rate.
- [ ] AI-ended session rate.
- [ ] Filters: date, branch, salesperson, scenario, persona, mode, difficulty.

Do not add CRM metrics.

---

## Phase 14 — Persona Lab

- [ ] Super Admin-only Persona Lab route/policy.
- [ ] Select persona/scenario/difficulty.
- [ ] Start short simulation.
- [ ] Show normalized Director Events.
- [ ] Show current Dynamic State.
- [ ] Show objection states.
- [ ] Show disclosure states.
- [ ] Show boundary state.
- [ ] Show Director Notes.
- [ ] Ensure simulation is not official Sales training history.
- [ ] Provide link back to edit persona.

---

## Phase 15 — Hostinger Production Deployment

- [ ] Verify Hostinger PHP web version.
- [ ] Verify SSH/CLI PHP version.
- [ ] Create production MySQL database.
- [ ] Configure domain/subdomain document root/layout.
- [ ] Configure GitHub deployment workflow.
- [ ] Configure production `.env`.
- [ ] Configure SMTP/password reset.
- [ ] Configure production assets.
- [ ] Configure Laravel scheduler cron.
- [ ] Configure bounded database queue worker cron strategy.
- [ ] Deploy migrations.
- [ ] Run Laravel optimize command.
- [ ] Verify storage permissions.
- [ ] Verify HTTPS.
- [ ] Verify `APP_DEBUG=false`.
- [ ] Test registration/approval.
- [ ] Test password reset.
- [ ] Test Gemini ephemeral credentials.
- [ ] Test real mobile roleplay.
- [ ] Test session resumption.
- [ ] Test 15-minute session.
- [ ] Test evaluation provider fallback.
- [ ] Inspect logs for secret leakage.
- [ ] Document rollback and backup procedure.

---

# Core Release Gate

Do not call v1 production-ready until the following vertical flow works:

- [ ] registration
- [ ] HQ approval and branch assignment
- [ ] persona creation/version
- [ ] scenario creation/version
- [ ] persona mode resolution
- [ ] snapshots
- [ ] Persona Salience Compiler
- [ ] Actor Instruction Compiler
- [ ] Director initialization
- [ ] secure Gemini Live bootstrap
- [ ] live Indonesian voice roleplay
- [ ] interruption
- [ ] session resumption
- [ ] normalized transcript
- [ ] sparse Director events
- [ ] deterministic Director transitions
- [ ] selective Director Notes
- [ ] user/AI/time-limit end
- [ ] evaluation queue
- [ ] provider fallback
- [ ] validated structured coaching
- [ ] transcript evidence UI
- [ ] HQ session review
