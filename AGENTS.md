# AGENTS.md — Session Memory for AI Coding Assistants

## Objective
- Phase 9: Transcript Persistence — database schema and persistence endpoint for roleplay session transcript turns.

## Important Details
- Schema: `roleplay_transcript_turns` table with `(roleplay_session_id, sequence)` unique constraint for dedup/upsert.
- Model: `RoleplayTranscriptTurn` with fillable fields, `BelongsTo` session relation, JSON `source_metadata`.
- Controller: `RoleplayTranscriptController@store` — POST `/training/sessions/{publicId}/transcript` (route: `training.sessions.transcript.store`).
- Allowed session statuses for submission: `ACTIVE`, `ENDING`.
- Idempotency strategy: `updateOrCreate` on `(session_id, sequence)`; FINAL → any rejected (409); PARTIAL → FINAL or newer PARTIAL allowed.
- Only session owner can submit; pending/suspended users blocked (`auth, verified, account.active` middleware).
- Route added to `routes/web.php`, 11 tests in `tests/Feature/RoleplayTranscriptTest.php`.

## Work State
- Completed: Migration, Model, Factory, Controller, Route, Test (11 tests, all passing).
- Test Count: 692 passing (2121 assertions).
- Build: `npm run build` succeeds.
- Pending: (none — awaiting Phase 9 next steps)

## Files Created/Modified
- `database/migrations/2026_07_08_000001_create_roleplay_transcript_turns_table.php`
- `app/Models/RoleplayTranscriptTurn.php`
- `database/factories/RoleplayTranscriptTurnFactory.php`
- `app/Http/Controllers/RoleplayTranscriptController.php`
- `routes/web.php` (route added)
- `tests/Feature/RoleplayTranscriptTest.php`
