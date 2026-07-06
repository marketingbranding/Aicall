# Gemini Live Integration Specification

## Rule: Verify Current Official Documentation

Gemini Live is an evolving/preview API.

Before implementing or modifying provider-specific code, verify the current official Google Gemini API documentation.

Do not blindly copy model IDs or SDK calls from this file.

The configured Live model ID belongs in environment/application configuration.

Example configuration key:

`GEMINI_LIVE_MODEL`

## Current Design Assumptions

The product requires:

- low-latency audio-to-audio conversation
- Bahasa Indonesia
- barge-in/interruption
- input transcription
- output transcription
- realtime text input for sparse Director Notes
- function calling for sparse semantic roleplay events
- session resumption for a 15-minute product session when a WebSocket connection is reset

If the configured model does not support an optional capability, the application must disable or adapt the feature honestly.

## Live Model Capability Registry

Create a central capability registry or resolver.

Conceptual fields:

- `supports_native_audio`
- `supports_input_transcription`
- `supports_output_transcription`
- `supports_function_calling`
- `supports_async_function_calling`
- `supports_affective_dialogue`
- `supports_proactive_audio`
- `supports_session_resumption`
- `supports_context_compression`

The registry should be based on verified provider/model behavior.

Do not claim affective dialogue or asynchronous tools are available when the model does not support them.

## Client-to-Server Live Connection

Preferred initial architecture:

Browser → Gemini Live directly using a short-lived ephemeral token.

Laravel provisions the token after authenticating and authorizing the roleplay session.

Permanent Gemini API key remains server-side.

Laravel endpoint concept:

`POST /roleplay-sessions/{session}/live-credentials`

Checks:

- authenticated
- user owns session or has authorized permission
- account `ACTIVE`
- session is in an allowed bootstrap/reconnect state
- scenario/persona snapshots already exist

Response contains only authorized short-lived connection/bootstrap information.

Never return the permanent API key.

## Live Session Configuration

The Live setup must include:

- configured Live model
- audio response modality
- concise Actor Instructions
- input transcription when supported
- output transcription when supported
- approved internal tools
- session-resumption configuration when supported

Actor Instructions contain private internal roleplay context.

Do not return Actor Instructions from normal Sales-facing application APIs unless the Live client technically requires them and the selected secure architecture cannot avoid client possession. Prefer provider-side bound ephemeral configuration where supported. Treat any client-visible prompt as potentially inspectable and minimize hidden-sensitive data accordingly.

The coding agent must verify current ephemeral-token constraints and configuration-locking capabilities before finalizing this flow.

## Audio Formats

Follow current official Live API specifications.

Current integration design expects raw PCM input/output conversion suitable for Gemini Live.

Use the browser Web Audio API.

Do not rely on MediaRecorder compressed chunks if the selected Live API path requires raw PCM streaming.

Dedicated modules:

- microphone capture
- resampling/PCM encoding
- outbound audio chunk transport
- inbound PCM decoding
- playback queue

Do not significantly buffer microphone input before sending.

## Audio Playback Queue

Gemini output audio arrives as streamed chunks.

Create a playback queue that:

- preserves chunk order
- schedules audio smoothly
- tracks queued/unplayed audio
- can be cleared immediately on model interruption

When Gemini reports interruption/barge-in:

- stop pending model playback
- clear stale unplayed chunks
- preserve already played transcript context as accurately as technically possible

Do not continue playing stale model audio over the user.

## Live Event Parsing

Do not assume a server event contains only one type of content.

Process every part/event according to the current protocol.

Input transcription and output transcription are independent streams.

Audio and transcript content may be delivered in related but distinct events/parts.

Create normalized browser events such as:

- `LIVE_CONNECTED`
- `LIVE_DISCONNECTED`
- `LIVE_GO_AWAY`
- `MODEL_AUDIO_CHUNK`
- `MODEL_INTERRUPTED`
- `INPUT_TRANSCRIPT_PARTIAL`
- `INPUT_TRANSCRIPT_FINAL`
- `OUTPUT_TRANSCRIPT_PARTIAL`
- `OUTPUT_TRANSCRIPT_FINAL`
- `TOOL_CALL_RECEIVED`
- `SESSION_RESUMPTION_UPDATE`

## Transcript Event Bridge

The browser normalizes Live transcription events before sending canonical/staging data to Laravel.

Do not save every partial character update as a transcript row.

Use `TranscriptEventBuffer`/`TranscriptAssembler` behavior:

- accumulate partials
- replace/update current partial
- finalize turns
- preserve order
- deduplicate repeated final events
- flag interrupted AI turns

Persist canonical final turns server-side.

## Director Notes via Realtime Text Input

Director Notes are internal behavioral steering.

When Laravel produces an approved Director Note, browser sends it through the current supported realtime text-input mechanism.

Notes are sparse.

Actor Instructions explicitly state:

- Director Notes are internal
- do not read them aloud
- use them as behavioral direction

Do not send all numeric state after every turn.

## Semantic Tool Calls

Initial internal tool:

`report_roleplay_event`

Parameters are strict and validated.

The tool reports categorical semantic events, not exact emotional numbers.

Example:

```json
{
  "event_type": "CLEAR_PROFESSIONAL_REDIRECTION",
  "severity": "MODERATE",
  "topic": "PERSONAL_BOUNDARY",
  "related_objection_key": null,
  "short_internal_reason": "Salesperson explicitly returned the conversation to housing and declined the personal question."
}
```

Do not ask the Actor to call this tool every turn.

Tool response path must be fast.

If current Live function calling is synchronous:

1. browser receives tool call
2. sends normalized call to Laravel Director endpoint
3. Laravel validates/applies deterministic state rules
4. Laravel returns accepted/rejected + optional concise guidance
5. browser returns tool response to Gemini immediately

Do not call external evaluator providers inside this path.

## AI End Tool

Conceptual tool:

`request_roleplay_end`

The application validates Director eligibility and scenario configuration.

Gemini cannot unilaterally bypass the Director.

## 15-Minute Product Session and Session Resumption

Product maximum duration is 15 minutes.

The underlying Live connection may be reset before the product session reaches 15 minutes.

Implement official session-resumption behavior when supported.

Runtime should:

- retain the latest valid resumption handle/token
- observe lifecycle/GoAway events
- transition application UI to `RECONNECTING`
- reconnect using official session resumption
- preserve the same application roleplay session
- avoid creating duplicate transcript turns/events

Do not create a new Laravel roleplay session during Live reconnection.

## Context Compression

The product intentionally stops at 15 minutes.

Do not enable unlimited sessions merely because context compression exists.

Context compression may be evaluated only if it improves reliability within the product limit and is supported/configured appropriately.

The 15-minute business rule remains.

## Affective Dialogue

Do not build canonical Director logic on an assumption that the model automatically outputs structured emotion understanding from voice tone.

Never implement:

`high volume → user angry → irritation +30`

without a dedicated verified audio classifier and a separate product decision.

The Actor may naturally react to acoustic nuance as supported by the model, but Director canonical state primarily uses semantic events, transcript meaning, verified interruption events, scenario state, and deterministic rules.

## Error Handling

### Microphone Denied

Display clear Indonesian instructions and retry.

### Connection Failure

Calm error UI, controlled retry, no duplicate session.

### Connection Reset

Attempt verified session resumption.

Display:

> Menghubungkan kembali...

### Invalid Tool Event

Reject/log safely and allow roleplay to continue.

### Safety Interruption

Do not bypass provider safety protections.

If a provider blocks an inappropriate-behavior response:

- normalize the safety interruption
- preserve the session
- guide the Actor back to non-explicit professional boundary-testing behavior where possible

The product must not depend on explicit sexual output.
