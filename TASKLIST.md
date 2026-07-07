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
- [x] Build HQ pending-user list.
- [x] Implement assign branch + approve account.
- [x] Implement suspend/reactivate account.

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
- [x] approval requires authorized HQ user

---

## Phase 2 — Persona Domain and Versioning

- [x] Create `personas` and immutable `persona_versions` model strategy.
- [x] Implement Persona status and archival.
- [x] Implement Persona Builder sections.
- [x] Implement Static Persona configuration.
- [x] Implement Housing Context.
- [x] Implement Knowledge and Beliefs.
- [x] Implement Personality Profile.
- [x] Implement Communication Style.
- [x] Implement Human Behavior Traits.
- [x] Implement Initial Dynamic State and sensitivity advanced configuration.
- [x] Implement Persona Objections.
- [x] Implement Hidden Information.
- [x] Implement Persona duplication.
- [x] Editing published/used persona creates new version.
- [x] Prevent Sales access to Persona admin endpoints.

### Phase 2 Tests

- [x] persona version immutability
- [x] new version created on edit
- [x] Sales cannot modify persona
- [x] hidden persona configuration is not exposed through Sales APIs

Verification note 2026-07-06: Created `personas` and `persona_versions` tables with full schema readiness for all future sections. Built basic Persona CRUD (list, create, edit, archive, duplicate) with versioning on edit and immutable version history. Editing published persona creates new version. Sales access blocked via `PersonaPolicy` + `hq` middleware. All 150 tests pass / 364 assertions, and `npm run build` passes.

Verification note 2026-07-06: Extended Persona Builder with 8 structured sections (Identitas, Kondisi & Kebutuhan Rumah, Pengetahuan & Keyakinan, Kepribadian, Human Behavior Traits, Cara Berkomunikasi, Initial State & Sensitivity, Salience Overrides) stored in corresponding `persona_versions` JSON columns. Added validation for all sections. Archived personas cannot be edited (update/archive denied). Version immutability verified: old versions remain unchanged after edits. All 162 tests pass / 417 assertions, and `npm run build` passes.

Verification note 2026-07-06: Implemented Persona Objections as a dedicated `persona_objections` table linked to `persona_versions`. Added Section 7 (Keberatan) to the builder form with 4 slots supporting key, title, context, VISIBLE/HIDDEN visibility, severity, emotional_importance, persistence, is_resolvable, is_archived, and comma-separated trigger/disclosure/resolution conditions. Objections are version-bound and immutable through the versioning system. Persona duplication replicates objections. All 174 tests pass / 455 assertions, and `npm run build` passes.

Verification note 2026-07-06: Implemented Persona Hidden Information as a dedicated `persona_hidden_information` table linked to `persona_versions`. Added Section 8 (Informasi Tersembunyi) to the builder form with 4 slots supporting key, title, information/context, sensitivity, disclosure_difficulty, direct_question_effectiveness, trust_requirement, relevant_topics, disclosure_conditions, and arsipkan. Renumbered Section 8 → 9 (Initial State & Sensitivitas) and Section 9 → 10 (Salience Overrides). Architecture kept ready for Director disclosure states (LOCKED, ELIGIBLE, DISCLOSED_PARTIAL, DISCLOSED_FULL). Hidden information is version-bound and immutable through the versioning system. Persona duplication replicates hidden information. All 186 tests pass / 502 assertions, and `npm run build` passes.

---

## Phase 3 — Scenario and Rubric Domain

- [x] Create scenario/versioning models.
- [x] Implement Scenario Builder.
- [x] Implement first-speaker configuration.
- [x] Implement allowed Persona Selection Modes.
- [x] Implement scenario-persona assignments.
- [x] Implement difficulty levels and Custom modifiers.
- [x] Enforce max roleplay duration <= 900 seconds.
- [x] Implement `allow_ai_end_call`.
- [x] Implement Global Rubric management.
- [x] Implement Scenario Rubric management.
- [x] Implement Scenario global-rubric weight overrides.
- [x] Create `RubricMerger`.

### Phase 3 Tests

- [x] mode restrictions
- [x] persona assignment restrictions
- [x] max duration validation
- [x] global rubric CRUD (16 tests)
- [x] scenario rubric CRUD with overrides (10 tests)
- [x] rubric merge (11 tests)

Verification note 2026-07-06: Created `scenarios`, `scenario_versions`, and `scenario_personas` tables. Implemented full Scenario aggregate with immutable ScenarioVersion versioning. Added Scenario Builder UI with 8 sections (Deskripsi & Briefing, Konteks Tersembunyi, Konfigurasi Percakapan, Target & Kondisi, Kondisi Sukses & Gagal, Tingkat Kesulitan dengan CUSTOM modifiers, Mode Pemilihan Persona, Persona yang Tersedia). Enforced max_duration_seconds <= 900 via FormRequest validation. Added `allow_ai_end_call` checkbox. All 209 tests pass / 575 assertions, and `npm run build` passes. Stopping before Rubric implementation as instructed.

---

## Phase 4 — Persona Salience Compiler

- [x] Create salience value objects/DTOs (`SalientTrait`, `SalienceResult`).
- [x] Implement explicit Primary/Secondary/Background override support.
- [x] Implement automatic salience selection.
- [x] Consider trait intensity.
- [x] Consider scenario relevance.
- [ ] Consider difficulty (not yet wired to compiler; deferred to Phase 6 integration).
- [x] Implement behavior compatibility/conflict handling (`CONFLICT_MAP`).
- [x] Limit recommended Primary traits to 3 (`MAX_PRIMARY`).
- [x] Limit recommended Secondary traits to 3 (`MAX_SECONDARY`).
- [ ] Build Admin salience preview (optional UI, deferred).

### Phase 4 Tests

- [x] many high sliders do not all become Primary
- [x] scenario relevance changes salience
- [x] explicit override works
- [x] incompatible dominant traits are handled predictably

---

## Phase 5 — Roleplay Instruction Compiler

- [x] Create RoleplayInstruction DTO with 11 typed sections + `toText()` assembler.
- [x] Implement RoleplayInstructionCompiler as pure domain service.
- [x] Compile Primary Behavior with qualitative trait descriptions (sangat/cenderung/sedikit).
- [x] Compile Secondary Behavior.
- [x] Compile Background tendencies as concise single-sentence list.
- [x] Compile Knowledge and Misconceptions.
- [x] Compile Scenario context.
- [x] Compile Director Note rules (internal-only, never read aloud).
- [x] Compile Actor guardrails (no AI reveal, no coaching, boundary safety, hidden info rules).
- [ ] Create immutable Persona/Scenario/Difficulty snapshot DTOs (Phase 7).
- [ ] Implement Session Snapshot service (Phase 7).
- [ ] Hash/store instruction snapshot privately (Phase 7).
- [ ] Prevent Sales endpoints from exposing compiled instructions (Phase 7).

### Phase 5 Tests

- [x] instructions include persona identity and scenario context
- [x] instructions do not expose internal numeric state
- [x] misconceptions are preserved
- [x] hidden information rules and guardrails exist
- [x] objection behavior rules exist
- [x] boundary behavior safety rules exist
- [x] first speaker/opening instruction included
- [x] Director Notes are explicitly internal
- [x] deterministic output (same input → same output)

Verification note 2026-07-07: Created `RoleplayInstruction` (DTO with 11 typed sections + `toText()`) and `RoleplayInstructionCompiler` (pure domain service). Compiler takes `PersonaVersion`, `SalienceResult`, and `ScenarioVersion`, produces structured Actor Instructions with 11 sections in Bahasa Indonesia. 38 trait descriptions with intensity-based qualitative wording (sangat/cenderung/sedikit). All 267 tests pass (726 assertions), `npm run build` succeeds (56 modules).

---

## Phase 6 — Roleplay Director Engine v1

- [ ] Create `RoleplayDirectorEngine` domain module.
- [ ] Implement bounded Dynamic State.
- [x] Implement State-to-Behavior Translator.
- [ ] Implement normalized Event taxonomy.
- [ ] Implement Event validation.
- [ ] Implement Event fingerprint/deduplication.
- [ ] Implement recent-event memory.
- [x] Implement diminishing returns.
- [ ] Implement transition-rule architecture.
- [ ] Implement core communication transition rules.
- [x] Implement Objection State Machine.
- [ ] Implement Hidden Information State Machine.
- [ ] Implement Boundary State Machine.
- [ ] Implement Conversation Phase Manager.
- [ ] Implement Difficulty modifiers.
- [ ] Implement Director Note Planner.
- [ ] Implement Director Note cooldown and duplicate suppression.
- [ ] Implement AI-ending eligibility.
- [ ] Implement Director Session Summary Builder.

### Phase 6 Tests

- [x] state clamp (DirectorState)
- [x] normalized event taxonomy (29 event types)
- [x] duplicate event suppression
- [x] state-to-behavior translation (13 tests)
- [ ] persona multiplier effects
- [x] diminishing returns
- [ ] note cooldown
- [x] objection transitions
- [ ] disclosure transitions
- [ ] boundary transitions
- [ ] AI-ending eligibility

Verification note 2026-07-07: Created StateToBehaviorTranslator, StateBand enum, and BehaviorTranslationResult DTO. Translator maps 7 DirectorState vars to qualitative bands (VERY_LOW—VERY_HIGH) per spec thresholds (0-20, 21-40, 41-60, 61-80, 81-100). Generates deterministic Bahasa Indonesia qualitative text and Director Note suggestions. No AI dependency. All 311 tests pass (931 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented diminishing returns via DiminishingReturnCalculator. Tracks event type frequency in 20-entry ring buffer. Positive event deltas (trust/interest/engagement gains, confusion/anxiety/irritation/pressure reductions) are multiplied by 1.0 → 0.5 → 0.25 → 0.0 on repeat occurrences. Negative/harmful deltas are never softened. Integrated into RoleplayDirectorEngine alongside existing fingerprint dedup. All 327 tests pass (975 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented ObjectionStateMachine with ObjectionState enum (7 states: DORMANT, ACTIVE_HIDDEN, ACTIVE_VISIBLE, ACKNOWLEDGED, PARTIALLY_RESOLVED, RESOLVED, REACTIVATED) and ObjectionTransition DTO. Transition rules defined for OBJECTION_TRIGGERED, RELEVANT_FOLLOW_UP, CONCERN_DISCOVERED, OBJECTION_ACKNOWLEDGED, OBJECTION_PARTIALLY_RESOLVED, OBJECTION_RESOLVED_CANDIDATE, DISMISSED_CONCERN, UNSUPPORTED_CLAIM, CONTRADICTORY_STATEMENT. Integrated into RoleplayDirectorEngine via injectable `?ObjectionStateMachine`. DirectorEngineResult includes `objectionTransitions` array and `toArray()`. All 352 tests pass (1058 assertions), `npm run build` succeeds (56 modules).

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
