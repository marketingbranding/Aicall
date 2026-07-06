# Architecture

## Architectural Style

Use a Laravel modular monolith with explicit domain boundaries.

The application is one deployable Laravel codebase for the initial Hostinger Business release.

Avoid microservices.

The key design requirement is domain separation, not network separation.

## High-Level System

```text
Sales Browser
    |
    | HTTPS: auth, setup, transcript/events, state sync, results
    v
Laravel Application
    |- Authentication & Authorization
    |- Persona / Scenario Administration
    |- Persona Salience Compiler
    |- Roleplay Instruction Compiler
    |- Roleplay Director Engine
    |- Session Snapshot Service
    |- Transcript Persistence
    |- Evaluation Orchestrator
    |- HQ Analytics
    |
    | short-lived credential provisioning
    v
Gemini Live API

Sales Browser <==== direct Live WebSocket/audio ====> Gemini Live API

Laravel Queue / Cron
    |
    v
Evaluation Providers
    |- OpenRouter
    |- Groq
    |- Gemini
```

## Why Direct Browser-to-Gemini Live

Real-time microphone audio should not be proxied through Hostinger PHP request handling.

Laravel provisions authorized short-lived Live credentials. The browser directly streams audio to Gemini Live.

This reduces latency and avoids requiring a custom permanently running WebSocket relay on Hostinger Business.

The application backend still owns roleplay identity, snapshots, canonical Director State, transcript persistence, event validation, and evaluation.

## Module Boundaries

Suggested application domains:

### Identity

- users
- branches
- account status
- authorization

### Persona

- persona identity
- knowledge/beliefs
- personality profile
- human behavior traits
- communication style
- objections
- hidden information
- versioning

### Scenario

- scenario configuration
- available personas
- persona modes
- difficulty
- AI-ending configuration
- scenario rubric

### Roleplay

- session lifecycle
- snapshots
- roleplay setup
- session ending

### Roleplay Director

- dynamic state
- normalized events
- state transitions
- objection state machine
- disclosure state machine
- boundary state machine
- conversation phase
- AI-ending eligibility
- Director Notes

### Live Voice

- Gemini capability registry
- ephemeral token provisioning
- Live configuration generation
- browser Live transport
- audio capture/playback
- interruption
- transcription event parsing
- session resumption

### Transcript

- partial accumulation protocol
- normalized canonical turns
- duplicate detection
- ordering
- integrity status

### Evaluation

- rubric merge
- evaluation request
- provider adapters
- structured-output validation
- retry/fallback
- evaluation findings

### Analytics

- Sales history/trends
- HQ training intelligence

## Server-Side Roleplay Creation Transaction

Creating a roleplay session is security-sensitive.

Recommended transaction:

1. Authenticate user.
2. Confirm `ACTIVE` status.
3. Authorize selected scenario.
4. Validate selected persona mode against scenario.
5. Resolve persona server-side.
6. Load current persona version.
7. Create immutable Persona Snapshot.
8. Create immutable Scenario Snapshot.
9. Create Difficulty Snapshot.
10. Create Rubric Snapshot.
11. Run Persona Salience Compiler.
12. Compile Actor Instructions.
13. Initialize Director State.
14. Persist Roleplay Session and snapshots.
15. Commit.
16. Provision ephemeral Live token bound to the intended Live model/configuration where supported.
17. Return only authorized session bootstrap data.

Do not send hidden persona configuration to the Sales browser.

## Client Roleplay Runtime

Use a dedicated browser runtime object/service rather than a Blade/Livewire component holding all low-level state.

Conceptual browser components:

- `RoleplayRuntime`
- `GeminiLiveClient`
- `MicrophoneCapture`
- `PcmAudioEncoder`
- `AudioPlaybackQueue`
- `TranscriptEventBuffer`
- `LiveSessionResumptionStore`
- `RoleplayEventBridge`

The Livewire page may render the roleplay shell and expose bootstrap endpoints, but the real-time loop is a browser module.

## Director Event Flow

```text
Conversation / Runtime Signal
        |
        v
Normalized Roleplay Event
        |
        v
Laravel validates event
        |
        v
Deduplication / cooldown
        |
        v
RoleplayDirectorEngine
        |
        +--> State transition
        +--> Objection transition
        +--> Disclosure transition
        +--> Boundary transition
        +--> Phase transition
        +--> AI ending eligibility
        |
        v
DirectorNotePlanner
        |
        +--> no note
        or
        +--> concise qualitative Director Note
                 |
                 v
         Browser sends internal realtime text update to Gemini Live
```

## Sparse Semantic Events

The first version should use:

1. deterministic runtime events, and
2. sparse Gemini Live semantic function calls.

Do not add a separate AI classifier after every sentence.

Deterministic events include:

- session start
- user interrupted model
- repeated interruption
- time-limit threshold
- manual end
- session lifecycle event

Gemini may occasionally call `report_roleplay_event` for semantic events such as:

- relevant follow-up
- concern dismissal
- unsupported claim
- clear professional redirection
- objection discovery

The tool must use strict enums.

## Synchronous Tool Constraint

Provider capability must be checked.

When Live function calling is synchronous, local Director tool processing must be fast.

Do not perform evaluator calls or slow third-party requests inside the live tool response path.

Director tool handler flow:

1. validate schema
2. authorize roleplay session
3. deduplicate
4. apply local domain rules
5. persist meaningful transition/event
6. optionally produce concise guidance
7. immediately return tool response

## Evaluation Asynchronous Flow

```text
Roleplay End
   |
   v
Finalize Transcript
   |
   v
Build Director Session Summary
   |
   v
Create evaluation PENDING record
   |
   v
Dispatch idempotent evaluation job
   |
   v
Cron launches bounded queue worker
   |
   v
Evaluation Provider Manager
   |
   +--> Primary Provider
   |       failure
   +--> Secondary Provider
   |       failure
   +--> Tertiary Provider
   |
   v
Validate Structured Result
   |
   +--> COMPLETED
   or
   +--> FAILED
```

## Historical Integrity

Sessions must use immutable snapshots.

Editing Persona Rina today cannot change yesterday's roleplay context.

Store the exact effective persona/scenario/difficulty/rubric configuration used by a session.

The evaluator uses session snapshots, not current admin configuration.

## Authorization Architecture

Use Policies/Gates or permission services.

Do not scatter role-name comparisons.

Examples:

- `BranchPolicy`
- `PersonaPolicy`
- `ScenarioPolicy`
- `RoleplaySessionPolicy`
- `AiProviderConfigPolicy`

Future Branch Supervisor support should become new policy rules, not a rewrite of every controller/component.

## Idempotency

Critical operations must be idempotent:

- finalize transcript
- end roleplay
- dispatch evaluation
- execute evaluation
- store provider result
- retry failed evaluation

A page refresh must never create a duplicate evaluation.

## Logging

Use structured logs with session correlation IDs.

Important events:

- user registered/approved
- session created
- persona resolved
- salience compiled
- Actor Instructions compiled
- Director initialized
- Live connection lifecycle
- resumption attempt
- interruption
- roleplay event accepted/rejected
- state threshold crossed
- Director Note sent
- objection/disclosure/boundary transition
- AI ending eligibility
- roleplay end
- transcript finalized
- evaluation lifecycle

Never log API keys, ephemeral credentials, authorization headers, or raw audio packets.
