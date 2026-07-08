# AGENTS.md — Session Memory for AI Coding Assistants

## Objective
- Phase 10 (Backend): Director event ingestion endpoint — accept normalized RoleplayEvent payloads, validate through RoleplayEventType enum, process through RoleplayDirectorEngine, store event + resulting Director outputs in new `roleplay_events`, `director_notes`, `director_state_snapshots` tables.

## Important Details
- `RoleplayDirectorEventController` at `app/Http/Controllers/RoleplayDirectorEventController.php`.
- Route: `POST /training/sessions/{publicId}/director/events` → `training.sessions.director.events.store`.
- Accepts `event_type` (valid RoleplayEventType), optional `severity`, `topic`, `related_objection_key`, `hidden_information_key`, `short_internal_reason`, `source_turn_sequence`.
- Validates event_type against RoleplayEventType enum, severity against known values.
- DB-level dedup via unique index on `(roleplay_session_id, fingerprint)`; first occurrence processes, subsequent returns stored result (idempotent).
- Only ACTIVE/READY sessions accepted (409 for others).
- Owner-only access + auth/verified/active middleware.
- Runs RoleplayDirectorEngine::applyEvent() with rehydrated state from DirectorStateSnapshot.
- Stores engine result in `roleplay_events` table: accepted, state before/after, rejection reason.
- Generates Director Notes via DirectorNotePlanner, filtered through DirectorNoteCooldown.
- Persists notes in `director_notes` table.
- `DirectorEngineFactory` builds engine from persisted `director_state_snapshots` (state + machine states) or initializes from session snapshot.
- `DirectorStateSnapshot` stores 7 DirectorState vars + machine states (objections, hidden_info, boundary, phase) as JSON.
- Sub-machine `restoreState` methods added to ObjectionStateMachine, HiddenInfoStateMachine, BoundaryStateMachine, ConversationPhaseManager.
- No `request_roleplay_end` tool yet, no Director notes sent to Gemini, no frontend tool-call parsing.

## Files Created/Modified
- `app/Enums/RoleplayEventSeverity.php` (NEW)
- `app/Models/RoleplayEvent.php` (NEW)
- `app/Models/DirectorNote.php` (NEW)
- `app/Models/DirectorStateSnapshot.php` (NEW)
- `app/Http/Controllers/RoleplayDirectorEventController.php` (NEW)
- `app/Services/Director/DirectorEngineFactory.php` (NEW)
- `database/factories/RoleplayEventFactory.php` (NEW)
- `database/factories/DirectorNoteFactory.php` (NEW)
- `database/factories/DirectorStateSnapshotFactory.php` (NEW)
- `database/migrations/2026_07_08_000002_create_roleplay_events_table.php` (NEW)
- `database/migrations/2026_07_08_000003_create_director_notes_table.php` (NEW)
- `database/migrations/2026_07_08_000004_create_director_state_snapshots_table.php` (NEW)
- `tests/Feature/RoleplayDirectorEventTest.php` (NEW) — 12 tests / 44 assertions
- `app/Models/RoleplaySession.php` — added directorEvents, directorNotes, directorStateSnapshot relationships
- `app/Services/Director/ObjectionStateMachine.php` — added restoreState()
- `app/Services/Director/HiddenInfoStateMachine.php` — added restoreState()
- `app/Services/Director/BoundaryStateMachine.php` — added restoreState()
- `app/Services/Director/ConversationPhaseManager.php` — added restorePhase()
- `routes/web.php` — added `/sessions/{publicId}/director/events` route

## Work State
- Phase 10 backend complete: event ingestion, validation, dedup, engine processing, note generation, state persistence.
- Tests: 728 passing (2258 assertions) — up from 716.
- Build: `npm run build` passes (62 modules).
- Remaining Phase 10 frontend tasks: tool schema definition, Actor Instruction integration, browser tool-call bridge, Director notes through realtime text input, `request_roleplay_end` tool.

## Next Incomplete Task (TASKLIST.md)
- Phase 10 frontend tasks (tool schema, Actor Instructions, browser bridge, realtime text input)
- Phase 11: Evaluation Engine
