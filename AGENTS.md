# AGENTS.md — Session Memory for AI Coding Assistants

## Objective
- Phase 9: Browser TranscriptEventBuffer → server persistence.

## Important Details
- Buffer is `resources/js/transcript-event-buffer.js` — standalone ES class.
- Consumes normalized transcript events from `GeminiLiveClient` via `RoleplayRuntime.handleTranscriptEvent`.
- Assigns stable monotonic sequence numbers at submission time.
- Submits to `POST /training/sessions/{publicId}/transcript` only for final turns.
- Partial events buffered in memory: one pending partial per speaker (USER/AI).
- Deduplication: tracks `_submittedSequences` (Set) and `_submittedTexts` (Set) per seq:speaker:text.
- Server-side `updateOrCreate` on `(session_id, sequence)` provides idempotency.
- Buffer starts when audio streaming begins (ACTIVE session).
- Buffer flushes pending partials + stops on `stopSessionAudio`, `handleSessionTimeLimit`, or explicit flush.
- Buffer serializes submissions (enqueue if one in flight).
- Runs entirely in-browser; no token, API key, actor instructions, or Director state exposed.
- `data-transcript-url` attribute on the runtime DOM root.

## Files Created/Modified
- `resources/js/transcript-event-buffer.js` (NEW) — TranscriptEventBuffer class
- `resources/js/roleplay-runtime.js` — imported buffer, wired `initTranscriptBuffer()`, feed events, flush/stop lifecycle
- `resources/views/training/prepare.blade.php` — added `data-transcript-url`, updated description text
- `tests/Feature/TrainingBriefingTest.php` — added `test_prepare_page_includes_transcript_endpoint_hook`, updated changed text assertions

## Work State
- All files created/modified as above.
- Tests: 693 passing (2124 assertions) — up from 692.
- Build: `npm run build` passes (62 modules, up from 61).

## Next Incomplete Task (TASKLIST.md Phase 9)
- [ ] Implement `TranscriptAssembler` validation (server-side turn assembly)
- [ ] Implement interrupted AI turn handling
- [ ] Implement transcript integrity status
- [ ] Implement idempotent transcript finalization
