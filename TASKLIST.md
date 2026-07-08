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

- [x] Create `RoleplayDirectorEngine` domain module.
- [x] Implement bounded Dynamic State.
- [x] Implement State-to-Behavior Translator.
- [x] Implement normalized Event taxonomy.
- [x] Implement Event validation.
- [x] Implement Event fingerprint/deduplication.
- [x] Implement recent-event memory.
- [x] Implement diminishing returns.
- [x] Implement transition-rule architecture.
- [x] Implement core communication transition rules.
- [x] Implement Objection State Machine.
- [x] Implement Hidden Information State Machine.
- [x] Implement Boundary State Machine.
- [x] Implement Conversation Phase Manager.
- [x] Implement Difficulty modifiers.
- [x] Implement Director Note Planner.
- [x] Implement Director Note cooldown and duplicate suppression.
- [x] Implement AI-ending eligibility.
- [x] Implement Director Session Summary Builder.

### Phase 6 Tests

- [x] state clamp (DirectorState)
- [x] normalized event taxonomy (29 event types)
- [x] duplicate event suppression
- [x] state-to-behavior translation (13 tests)
- [ ] persona multiplier effects (deferred — requires full Phase 6 integration wiring with API runtime)
- [x] diminishing returns
- [x] note cooldown
- [x] objection transitions
- [x] disclosure transitions
- [x] boundary transitions
- [x] AI-ending eligibility

Verification note 2026-07-07: Created StateToBehaviorTranslator, StateBand enum, and BehaviorTranslationResult DTO. Translator maps 7 DirectorState vars to qualitative bands (VERY_LOW—VERY_HIGH) per spec thresholds (0-20, 21-40, 41-60, 61-80, 81-100). Generates deterministic Bahasa Indonesia qualitative text and Director Note suggestions. No AI dependency. All 311 tests pass (931 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented diminishing returns via DiminishingReturnCalculator. Tracks event type frequency in 20-entry ring buffer. Positive event deltas (trust/interest/engagement gains, confusion/anxiety/irritation/pressure reductions) are multiplied by 1.0 → 0.5 → 0.25 → 0.0 on repeat occurrences. Negative/harmful deltas are never softened. Integrated into RoleplayDirectorEngine alongside existing fingerprint dedup. All 327 tests pass (975 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented ObjectionStateMachine with ObjectionState enum (7 states: DORMANT, ACTIVE_HIDDEN, ACTIVE_VISIBLE, ACKNOWLEDGED, PARTIALLY_RESOLVED, RESOLVED, REACTIVATED) and ObjectionTransition DTO. Transition rules defined for OBJECTION_TRIGGERED, RELEVANT_FOLLOW_UP, CONCERN_DISCOVERED, OBJECTION_ACKNOWLEDGED, OBJECTION_PARTIALLY_RESOLVED, OBJECTION_RESOLVED_CANDIDATE, DISMISSED_CONCERN, UNSUPPORTED_CLAIM, CONTRADICTORY_STATEMENT. Integrated into RoleplayDirectorEngine via injectable `?ObjectionStateMachine`. DirectorEngineResult includes `objectionTransitions` array and `toArray()`. All 352 tests pass (1058 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented HiddenInfoStateMachine with HiddenInfoState enum (4 states: LOCKED, ELIGIBLE, DISCLOSED_PARTIAL, DISCLOSED_FULL) and HiddenInfoTransition DTO. State transitions consider trust_requirement, sensitivity, direct_question_effectiveness, relevant_topics, and event type. Trigger events: RELEVANT_FOLLOW_UP, EMPATHIC_RESPONSE, CLEAR_EXPLANATION, TRUST_SIGNAL, CONCERN_DISCOVERED, APPROPRIATE_NEXT_STEP. Integrated into RoleplayDirectorEngine via injectable `?HiddenInfoStateMachine`. DirectorEngineResult includes `hiddenInfoTransitions`. All 377 tests pass (1132 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented BoundaryStateMachine with BoundaryState enum (9 states: NOT_TESTED, MILD_TEST_OCCURRED, SALESPERSON_PARTICIPATED, INDIRECTLY_REDIRECTED, CLEAR_BOUNDARY_ESTABLISHED, CUSTOMER_RESPECTED_BOUNDARY, CUSTOMER_RETESTED_BOUNDARY, SIGNIFICANT_VIOLATION, PROFESSIONAL_TERMINATION_ELIGIBLE) and BoundaryTransition DTO. Transition rules for all 8 boundary event types (CUSTOMER_BOUNDARY_TEST, SALESPERSON_PARTICIPATED_PERSONALLY, INDIRECT_REDIRECTION, CLEAR_PROFESSIONAL_REDIRECTION, EXPLICIT_BOUNDARY_SET, CUSTOMER_RESPECTED_BOUNDARY, CUSTOMER_REPEATED_BOUNDARY_TEST, SIGNIFICANT_BOUNDARY_VIOLATION). Cooldown repeat protection prevents same boundary test type within 3 events. Persona parameters (respect_for_boundaries, persistence_after_redirection) influence Director Notes. Integrated into RoleplayDirectorEngine via injectable `?BoundaryStateMachine`. DirectorEngineResult includes `boundaryTransitions`. All 407 tests pass (1216 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented ConversationPhaseManager with ConversationPhase enum (9 phases: OPENING, RAPPORT, DISCOVERY, NEED_EXPLORATION, EXPLANATION, OBJECTION_HANDLING, COMMITMENT, CLOSING, ENDING) and ConversationPhaseTransition DTO. Non-linear phase transitions driven by RoleplayEventType. Forward progression: OPENING→RAPPORT/DISCOVERY, RAPPORT→DISCOVERY/EXPLANATION/OBJECTION_HANDLING, DISCOVERY→NEED_EXPLORATION, NEED_EXPLORATION→EXPLANATION, EXPLANATION→COMMITMENT/OBJECTION_HANDLING, OBJECTION_HANDLING→COMMITMENT, COMMITMENT→CLOSING, CLOSING→ENDING. Backward from EXPLANATION/OBJECTION_HANDLING/COMMITMENT/CLOSING to DISCOVERY when new concerns arise. Premature closing detection via `isPrematureClosingEvent()` for AGGRESSIVE_CLOSING in early phases (OPENING—NEED_EXPLORATION). Configurable initial phase. Integrated into RoleplayDirectorEngine. All 437 tests pass (1308 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented DirectorNotePlanner with DirectorNote DTO. Planner consumes DirectorState (prev+new), BehaviorTranslationResult, objection/hidden-info/boundary/phase transitions. Generates notes for: objection transitions with directorNote, hidden info transitions with directorNote, boundary transitions with directorNote, phase changes (with 14 pre-mapped phase pairs + fallback), premature closing, and state threshold crossings (trust ≤30/≥70, irritation ≥60, engagement ≤30/≥70, confusion ≥60, pressure ≥60, anxiety ≥70 — only when newly crossed). Notes are qualitative Bahasa Indonesia, never expose numeric state. Deterministic output. All 490 tests pass (1467 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented DirectorNoteCooldown with turn-based cooldown and duplicate suppression. Cooldown periods per category: objection/hidden_info/boundary=4, state_threshold/phase_change=5, premature_closing=8, unknown=5. Duplicate detection via 3-entry text ring buffer. Notes with priority ≥3 (critical) bypass all cooldown/duplicate checks. Turn counter advances via `nextTurn()`. `reset()` clears all state. Deterministic output. All 513 tests pass (1511 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented AiEndEligibility + AiEndEligibilityResult DTO. `evaluate()` accepts allowAiEndCall, DirectorState, boundaryStateValue, conversationPhaseValue, and event counts. Priority-ordered checks: not_enabled → boundary_termination → trust_collapse (≤20) → low_engagement (≤20) → high_pressure (≥80) → repeated_dismissal (≥2) → aggressive_closing_early (≥2 in pre-COMMITMENT phases) → repeated_unsupported_claim (≥3) → natural_ending (ENDING phase). Qualitative Bahasa Indonesia director note, no numeric state exposure. Deterministic output. All 536 tests pass (1553 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented DirectorSessionSummaryBuilder with DirectorSessionSummary and DirectorSessionEvent DTOs. Builder accumulates objection/hidden-info/boundary/phase transitions, state threshold notes, and AI-ending eligibility. Filters out rejected and self-transitions. Events sorted by turn in `build()`. Output is JSON-serializable via `toArray()`. Reset support for all accumulated state. All 556 tests pass (1609 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Wired DifficultyModifier's objectionPersistence, disclosureResistance, and boundaryPersistence into ObjectionStateMachine, HiddenInfoStateMachine, and BoundaryStateMachine. OSM: blocks ACKNOWLEDGED→PARTIALLY_RESOLVED at ≥85 persistence, blocks PARTIALLY_RESOLVED→RESOLVED at ≥65. HISM: scales trust requirement by `1 + ((disclosureResistance - 50) / 200)` factor. BSM: dynamic cooldown via `round(5 - boundaryPersistence * 0.04)` clamped 1–5. No modifier (null) uses default NORMAL (50) behavior. All 575 tests pass (1666 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Created Phase 7 data model and snapshot infrastructure. Added `roleplay_sessions` and `roleplay_session_snapshots` tables with migrations. 5 enums: RoleplaySessionStatus (12 states), EndingType, TranscriptIntegrity, EvaluationStatus, PersonaMode. 6 snapshot DTOs in `app/Services/Snapshots/`: PersonaSnapshot, ScenarioSnapshot, DifficultySnapshot, RubricSnapshot, SalienceSnapshot, DirectorSnapshot. `SessionSnapshotService` orchestrates snapshot creation from domain models. Actor instructions encrypted via Laravel's `'encrypted'` cast, SHA-256 hashed. `RoleplaySession` model with lifecycle checks and scopes. `RoleplaySessionSnapshot` model with `HasOne` relationship. Factories for both models. All 588 tests pass (1727 assertions), `npm run build` succeeds (56 modules).

---

## Phase 7 — Roleplay Session Setup and Sales Flow

- [x] Build Training Dashboard.
- [x] Build Scenario selection (briefing page with persona mode selection).
- [x] Display difficulty.
- [x] Implement CHOOSE_PERSONA server-side resolution + session creation.
- [x] Implement RANDOM_PERSONA server-side resolution + session creation.
- [x] Implement HIDDEN_PERSONA server-side resolution + session creation.
- [x] Build briefing screen.
- [x] Build pre-call screen (preparation placeholder after session creation).
- [x] Implement microphone-permission UI.
- [x] Implement secure roleplay-session creation transaction.
- [x] Create Persona Snapshot (PersonaSnapshot DTO + SessionSnapshotService).
- [x] Create Scenario Snapshot (ScenarioSnapshot DTO + SessionSnapshotService).
- [x] Create Difficulty Snapshot (DifficultySnapshot DTO).
- [x] Create Rubric Snapshot (RubricSnapshot DTO).
- [x] Save Salience Snapshot (SalienceSnapshot DTO).
- [x] Create DirectorSnapshot DTO.
- [x] Initialize Director State (via DirectorSnapshot).

### Phase 7 Tests

- [x] session can be created for active Sales user
- [x] session belongs to user
- [x] session has snapshot
- [x] actor instruction hash is stored
- [x] actor instructions are encrypted in database
- [x] actor instructions decrypt when accessed
- [x] snapshot JSON is stored
- [x] snapshot is one per session (UNIQUE constraint)
- [x] session lifecycle transitions
- [x] session toArray does not expose snapshot data
- [x] hidden data absent from browser response (verified — hidden_context, target_behaviors, etc. never loaded)
- [x] briefing does not expose hidden scenario fields (tested)
- [x] briefing does not expose hidden persona data (tested)
- [x] disabled personas not shown in CHOOSE_PERSONA list (tested)
- [x] unauthorized persona cannot be selected (server-side validation + session creation)
- [x] same setup creates one application session (idempotency / duplicate-submit prevention)
- [x] snapshots are immutable (application-level contract, tested via factory)

Verification note 2026-07-07: Built Training Dashboard with card layout listing active scenarios (name, difficulty, description, max duration, allowed persona modes). Used existing `account.active` middleware + `view-own-training` gate. Added `/training` route (training.dashboard) and "Latihan" nav link. Hidden fields (hidden_context, target_behaviors, sales_briefing, training_objective, success/failure conditions) are never loaded in controller query. All 597 tests pass (1753 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Built scenario briefing page (GET /training/scenarios/{scenario}). Shows Sales-safe data: name, difficulty, description, sales briefing, max duration, allowed persona modes. Persona mode selection with radio buttons for each allowed mode. CHOOSE_PERSONA lists enabled active personas with public_profile_text and identity tags. Hidden scenario fields (hidden_context, target_behaviors, conditions, prohibited claims) and hidden persona data (human_behavior_traits, objections, hidden information) are never loaded or rendered. All 608 tests pass (1787 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented Phase 7.3 server-side persona resolution and RoleplaySession creation. Added POST /training/scenarios/{scenario}/sessions and GET /training/sessions/{publicId}/prepare. CHOOSE_PERSONA requires an assigned active persona; RANDOM_PERSONA and HIDDEN_PERSONA resolve an assigned active persona server-side. Creation runs in one DB transaction: validate mode, resolve persona, compile salience, merge rubric, compile actor instructions, create RoleplaySession, create immutable snapshot via SessionSnapshotService, hash and encrypt actor instructions. Preparation response exposes only safe session/scenario metadata and no persona hidden data, Director internals, or actor instructions. Fixed a flaky HQ nav assertion that matched random CSRF token text. All 618 tests pass (1840 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented duplicate-submit idempotency for roleplay session creation. Briefing forms now include a generated `idempotency_key`. RoleplaySession stores `idempotency_key` and `idempotency_fingerprint`; database uniqueness on `(user_id, idempotency_key)` prevents double-click duplicate sessions. Repeat valid submits with the same user/key/fingerprint redirect to the same preparation page. Reusing a key with a different request is rejected. Covered CHOOSE_PERSONA, RANDOM_PERSONA, HIDDEN_PERSONA, same-session redirect, different-key new session, and pending/suspended blocking. All 624 tests pass (1863 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Implemented microphone-permission UI on the preparation page. The page now shows browser-only microphone states: preparing, checking microphone, microphone allowed, permission denied, and unavailable/error. JavaScript uses `navigator.mediaDevices.getUserMedia({ audio: true })` only, stops granted tracks immediately, and does not request Gemini credentials or start Live API. Added retry flow and calm Bahasa Indonesia permission guidance. Preparation page remains owner-only and hides actor instructions, Director state, hidden persona data, and selected hidden persona details. All 630 tests pass (1886 assertions), `npm run build` succeeds (56 modules).

Verification note 2026-07-07: Enforced RoleplaySessionSnapshot immutability at the model level. Snapshots can still be created normally by factories and session creation, but any Eloquent update after creation now throws a LogicException. Added tests proving actor_instructions, actor_instruction_hash, and all JSON snapshot fields cannot be changed after creation while factory creation remains valid. All 635 tests pass (1899 assertions), `npm run build` succeeds (56 modules).

---

## Phase 8 — Gemini Live Vertical Voice Integration

- [x] Re-verify current official Gemini Live API and model capabilities.
- [x] Implement `GeminiLiveRoleplayProvider` server-side provisioning service.
- [x] Implement ephemeral-token endpoint.
- [x] Verify short-lived token/config restrictions.
- [x] Implement browser `RoleplayRuntime`.
- [x] Implement `GeminiLiveClient`.
- [x] Implement microphone capture.
- [x] Implement PCM conversion/resampling according to current API requirements.
- [x] Implement streaming microphone audio.
- [x] Implement AI PCM playback queue.
- [x] Implement speaking/listening state events.
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

Verification note 2026-07-08: Re-verified official Google Gemini Live docs and model pages. `gemini-3.1-flash-live-preview` remains current for Live roleplay. Updated `config/gemini.php` to disable unsupported Gemini 3.1 affective dialogue and proactive audio while preserving native audio, transcription, realtime text input, synchronous function calling, session resumption, and context compression. Documented ephemeral token defaults/limits, 10-minute connection resets, GoAway/session resumption behavior, 15-minute audio session limit, function-calling limitations, and direct browser-to-Gemini requirements in `docs/07_GEMINI_LIVE.md`.

Verification note 2026-07-08: Implemented backend-only Gemini Live ephemeral token provisioning. Added `POST /training/sessions/{publicId}/live-credentials`, owner/status/snapshot checks, active-account enforcement, configured model usage, server-side permanent API key handling, controlled missing-config/provider errors, and a provider request that binds the short-lived token to the Live model/config with encrypted Actor Instructions sent only server-to-Google. Browser response returns only sanitized bootstrap data and the ephemeral token. Added feature coverage for owner access, non-owner denial, pending/suspended denial, invalid status rejection, secret/instruction non-exposure, and missing API key handling. `php artisan test --filter=RoleplayLiveCredentialsTest` passed 6 tests / 26 assertions.

Verification note 2026-07-08: Implemented prepare-page browser `RoleplayRuntime` foundation only. Added a Vite-loaded runtime module with states `idle`, `requesting_credentials`, `credentials_ready`, and `credentials_failed`; `Mulai Sesi` appears after microphone permission succeeds and requests credentials from `POST /training/sessions/{publicId}/live-credentials`. Ephemeral token is held only in memory and is not logged or written to the DOM/storage. No Gemini Live WebSocket connection, microphone audio streaming, or audio playback was implemented. Added server-rendered tests for runtime DOM hooks, credentials endpoint URL, and no permanent API key/private data in prepare HTML. `php artisan test --filter=TrainingBriefingTest` passed 35 tests / 141 assertions.

Verification note 2026-07-08: Implemented browser `GeminiLiveClient` handshake foundation only. The runtime now uses the in-memory ephemeral token to open the official constrained Gemini Live WebSocket endpoint with `access_token`, sends a setup-only message for the configured model/audio modality, and handles `connecting_live`, `live_connected`, `live_connection_failed`, and `live_closed` states with calm Bahasa Indonesia messages. Debug message-shape logging is disabled by default and never logs credentials. No microphone audio streaming, audio playback, transcription handling, tool handling, or Director events were implemented. Added server-rendered tests for Live client hooks and that no token/API key/private data is present in prepare HTML. `php artisan test --filter=TrainingBriefingTest` passed 36 tests / 147 assertions; `npm run build` passed.

Verification note 2026-07-08: Implemented browser microphone capture foundation. Added a `MicrophoneCapture` module using `navigator.mediaDevices.getUserMedia` and Web Audio API, starts capture only after Live setup completes, prepares in-browser PCM16 chunks at 16 kHz through a no-op callback boundary, and does not stream audio to Gemini yet. Runtime now handles `microphone_capturing`, `microphone_capture_failed`, and `microphone_stopped`; microphone tracks/audio nodes are stopped on Live close/failure and page unload/pagehide. Added prepare-page render coverage for microphone capture hooks while preserving hidden persona, actor instruction, token, and API-key non-exposure checks. `php artisan test` passed 645 tests / 1948 assertions; `npm run build` passed.

Verification note 2026-07-08: Implemented isolated browser PCM utilities for Gemini Live input requirements. Added `audio-pcm-utils.js` with `resampleTo16k`, `float32ToPcm16`, `prepareGeminiLivePcm16`, and `encodeBase64`; PCM16 output is written explicitly little-endian via `DataView` and returned as an `ArrayBuffer` with metadata `audio/pcm;rate=16000`, `LINEAR16`, and `littleEndian: true`. `MicrophoneCapture` now delegates conversion/resampling to these utilities and still does not stream audio to Gemini. No JS test runner is configured, so verification is build/manual-boundary based plus existing PHP render/security tests. `php artisan test` and `npm run build` passed.

Verification note 2026-07-08: Implemented browser microphone audio streaming to Gemini Live after setup completion. `GeminiLiveClient.sendAudioChunk()` now safely sends base64-encoded PCM16 16 kHz chunks as `realtimeInput.mediaChunks[]` with `mimeType: audio/pcm;rate=16000`, only when the WebSocket is open and Live setup has completed; not-ready/send failures return false without logging raw audio or token. `RoleplayRuntime` wires `MicrophoneCapture` chunks to the Live client and handles `audio_streaming`, `audio_stream_failed`, and `audio_stream_stopped`; streaming stops on Live close/failure, user `Hentikan Audio`, `pagehide`, and `beforeunload`. No Gemini output playback, transcription persistence, or Director Notes were implemented. Added prepare-page render coverage for streaming hooks while existing secret/private-data exposure tests remain in place. `php artisan test` passed 646 tests / 1953 assertions; `npm run build` passed.

Verification note 2026-07-08: Implemented browser AI PCM playback queue. Added `AiPcmPlaybackQueue` to decode Gemini output `audio/pcm` chunks as PCM16 little-endian at 24 kHz, enqueue chunks in order, schedule playback through Web Audio API, and clear current/queued playback on interruption events. `GeminiLiveClient` now extracts audio parts from Live server content and surfaces interruption signals without logging raw audio. Runtime handles `ai_speaking`, `playback_error`, and `playback_idle`, primes playback from the user start gesture, and cleans playback on Live close/failure, user stop, `pagehide`, and `beforeunload`. No transcript persistence, Director Notes, or evaluation were implemented. Added prepare-page render coverage for playback hooks and 24 kHz output format. `php artisan test` passed 647 tests / 1957 assertions; `npm run build` passed.

Verification note 2026-07-08: Implemented browser-only speaking/listening state events. Runtime now tracks `idle`, `listening`, `user_speaking`, `waiting_for_ai`, `thinking`, `ai_speaking`, and `interrupted` through microphone RMS activity, outbound audio chunk activity, Gemini audio chunk arrival, AI playback queue callbacks, and Live interruption signals. Added calm Bahasa Indonesia conversation-state copy and accessible visual chips on the prepare/live runtime panel. No transcript persistence, Director Notes, or evaluation were implemented. Added prepare-page render coverage for speaking/listening hooks while existing private-data exposure tests remain in place. `php artisan test` passed 648 tests / 1967 assertions; `npm run build` passed.

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
