# AGENTS.md — Session Memory for AI Coding Assistants

## Objective
- Phase 9: TranscriptAssembler validation, interrupted AI turn handling, transcript integrity status.

## Important Details
- `TranscriptAssembler` at `app/Services/Transcript/TranscriptAssembler.php`.
- Reads `RoleplayTranscriptTurn` records for a `RoleplaySession`, ordered by sequence ASC.
- Returns `TranscriptAssemblyResult` DTO with integrity, turns, issues, interruptedTurns.
- Validation rules:
  - Sequence ordering: detects gaps between expected contiguous sequences.
  - Duplicate sequences: detects same sequence appearing twice (safety net — DB unique key prevents this).
  - Missing/empty text: null or whitespace-only text flagged.
  - Speaker validity: must be `USER` or `AI`.
  - Partial vs final: any non-FINAL status tracked; AI PARTIAL turns flagged as interrupted.
- Integrity rules:
  - No turns → `FAILED`.
  - Any gap, partial, empty text, invalid speaker, invalid status → `PARTIAL`.
  - All FINAL, contiguous, valid data → `COMPLETE`.
- Interrupted AI turn detection: any AI turn with status `PARTIAL` is flagged in `interruptedTurns` array on the result.
- `TranscriptAssemblyResult` is a readonly DTO with `integrity`, `turns`, `issues`, `interruptedTurns`.
- `session.transcript_integrity` updated via `updateQuietly()` when changed.
- `RoleplaySession.transcriptTurns()` hasMany relationship added.
- No AI model called, no evaluation dispatch, no Director event classifier.

## Files Created/Modified
- `app/Services/Transcript/TranscriptAssembler.php` (NEW) — server-side assembler service
- `app/Services/Transcript/TranscriptAssemblyResult.php` (NEW) — readonly result DTO
- `app/Models/RoleplaySession.php` — added `transcriptTurns()` HasMany relationship, HasMany import
- `tests/Unit/Services/Transcript/TranscriptAssemblerTest.php` (NEW) — 12 tests / 54 assertions

## Work State
- All files created/modified as above.
- Tests: 705 passing (2178 assertions) — up from 693.
- Build: `npm run build` passes (62 modules).

## Next Incomplete Task (TASKLIST.md Phase 9)
- [ ] Implement idempotent transcript finalization
