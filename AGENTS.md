# AGENTS.md — Session Memory for AI Coding Assistants

## Objective
- Phase 9: Idempotent transcript finalization.

## Important Details
- `RoleplayTranscriptFinalizeController` at `app/Http/Controllers/RoleplayTranscriptFinalizeController.php`.
- Route: `POST /training/sessions/{publicId}/transcript/finalize` → `training.sessions.transcript.finalize`.
- Accepts sessions in ENDING, TRANSCRIPT_FINALIZING, EVALUATING, COMPLETED, FAILED status.
- First call from ENDING → transitions to TRANSCRIPT_FINALIZING; subsequent calls return same result (idempotent).
- Rejects ACTIVE and earlier statuses (409).
- After finalization, transcript store endpoint naturally rejects (session no longer ACTIVE/ENDING).
- Runs `TranscriptAssembler::assemble()` which validates turns, computes integrity, stores on session.
- No evaluation dispatched, no AI model called, no Director event classifier.

## Files Created/Modified
- `app/Http/Controllers/RoleplayTranscriptFinalizeController.php` (NEW) — finalization endpoint
- `routes/web.php` — added `/sessions/{publicId}/transcript/finalize` route
- `tests/Feature/RoleplayTranscriptFinalizeTest.php` (NEW) — 11 tests / 36 assertions

## Work State
- All Phase 9 tasks complete. TASKLIST Phase 9 fully checked.
- Tests: 716 passing (2214 assertions) — up from 705.
- Build: `npm run build` passes (62 modules).

## Next Incomplete Task (TASKLIST.md)
- Phase 10: Sparse Director Semantic Tool Integration
