# Testing Specification

## Testing Principle

A visually complete UI is not a complete roleplay application.

Critical business logic must be testable without a real Gemini call.

Gemini Live audio requires separate integration/manual testing.

## Test Layers

### Unit Tests

Focus:

- Persona Salience Compiler
- Roleplay Instruction Compiler structure
- Director state transitions
- state clamping
- state sensitivity multipliers
- diminishing returns
- event deduplication
- Director Note cooldown
- objection state machine
- hidden disclosure state machine
- boundary state machine
- conversation phase rules
- AI-ending eligibility
- State-to-Behavior Translator
- Rubric Merger
- evaluation semantic validation

### Feature / HTTP Tests

Focus:

- registration
- approval workflow
- suspended-account restrictions
- authorization policies
- persona/scenario CRUD permissions
- hidden persona response serialization
- session creation transaction
- snapshot integrity
- roleplay event endpoint validation
- session ownership
- idempotent ending
- transcript finalization
- evaluation dispatch idempotency
- evaluation retry authorization

### Integration Tests with Fakes

Create provider fakes for:

- Gemini ephemeral token provisioning
- semantic Live tool event bridge
- evaluation providers

Evaluation Provider Manager tests:

- primary succeeds
- primary timeout then secondary succeeds
- malformed JSON then configured retry/fallback
- all providers fail
- completed evaluation is not rerun

### Manual Gemini Live Tests

Real microphone/audio behavior must be tested in supported browsers/devices.

## Mandatory Authorization Tests

- pending user cannot start training
- suspended user cannot start training
- Sales cannot create Persona
- Sales cannot edit Persona
- Sales cannot access evaluator provider config
- Sales cannot access another user's private session
- Sales cannot receive Director Notes through normal APIs
- Sales cannot receive hidden persona config
- branch restrictions work
- future permission architecture is not based solely on scattered role comparisons

## Mandatory Persona Tests

- high-intensity traits do not all become Primary
- admin salience override works
- scenario relevance influences automatic salience
- incompatible dominant traits are handled predictably
- Actor Instructions use structured sections
- Actor Instructions do not dump all sliders
- configured misconception remains in compiled instructions

## Mandatory Director Tests

- state values clamp 0–100
- Persona multipliers affect transitions
- repeated empathy has diminishing returns
- repeated interruption threshold produces semantic/deterministic event
- duplicate event fingerprint does not apply transition twice
- non-critical Director Notes obey cooldown
- critical note bypass works
- State-to-Behavior Translator produces qualitative output

## Mandatory Objection Tests

- hidden objection remains hidden initially
- valid discovery can make objection visible
- irrelevant explanation does not resolve objection
- persistent objection is not resolved by one weak explanation
- required event combination can partially resolve
- configured resolved objection remains resolved
- reactivation rule works when configured

## Mandatory Hidden Information Tests

- locked information remains locked
- trust requirement works
- relevant topic requirement works
- blunt question behavior can remain locked when configured
- partial disclosure works
- full disclosure requires configured conditions

## Mandatory Boundary Tests

- mild test transitions state
- personal participation is tracked
- clear professional redirection establishes boundary
- high boundary respect can stop behavior
- low respect/high persistence can allow later retest
- same personal question cannot repeat immediately
- significant violation can reach professional termination eligibility
- evaluator framing does not blame salesperson for customer misconduct

## Mandatory Live Runtime Tests

- microphone denied UI
- microphone permission retry
- Live connection bootstrap
- short-lived credential flow
- input audio streaming
- AI audio playback
- user interruption clears stale AI playback
- input transcription captured
- output transcription captured
- multiple content parts/events handled
- invalid Director tool event does not crash call
- GoAway/lifecycle event transitions to reconnect state
- session resumption keeps same Laravel session
- reconnect does not duplicate transcript turns/events
- 14-minute warning
- 15-minute product limit
- AI ending disabled blocks AI end
- AI ending enabled still requires Director eligibility

## Mandatory Transcript Tests

- partial accumulation
- final event replacement
- duplicate final prevention
- sequence ordering
- user/AI speaker normalization
- interrupted AI turn handling
- transcript integrity `COMPLETE`
- transcript integrity `PARTIAL`
- finalization is idempotent

## Mandatory Evaluation Tests

- Global + Scenario Rubric merge
- scenario weight override
- disabled rubric item behavior
- score 0–100 validation
- unknown rubric key rejected
- nonexistent transcript sequence reference rejected or normalized according to explicit policy
- provider timeout fallback
- malformed structured output fallback
- all-provider failure creates `FAILED`
- successful retry uses immutable snapshots
- page refresh does not create duplicate evaluation

## Mandatory History Tests

- editing persona creates a new version
- historical session retains old persona snapshot
- editing scenario does not change old session snapshot
- rubric update does not change historical effective rubric
- Hidden Persona history does not expose private persona configuration

## Manual Device Matrix

At minimum test:

### Desktop

- Chrome on Windows
- Edge on Windows

### Android

- Chrome

### iOS / iPadOS where relevant to organization devices

- Safari

Test:

- microphone permission
- Bluetooth headset if commonly used
- speaker playback
- interruption
- screen rotation
- dynamic viewport
- accidental back navigation
- app backgrounding/foregrounding
- network switch Wi-Fi/mobile data where practical

## Persona Acceptance Testing

Use Persona Lab after core flow.

For each representative persona, run multiple sessions and ask:

- are Primary Traits clearly observable?
- do Secondary Traits appear contextually rather than immediately?
- does one trait dominate the full 15 minutes?
- are objections too easy to resolve?
- does hidden information leak?
- does boundary behavior escalate too quickly?
- does Actor repeat phrases/questions?
- does Director send too many notes?
- does the persona remain believable after reconnection?

Do not tune a persona based on one simulation only.

## Release Gate

Do not release production v1 until:

- first complete vertical flow passes
- authorization tests pass
- Director core unit tests pass
- transcript tests pass
- evaluation fallback tests pass
- production password reset works
- Gemini Live works on target mobile device
- a 15-minute session with connection/session-resumption behavior is manually verified
- no permanent API key is present in frontend source/network bootstrap response
