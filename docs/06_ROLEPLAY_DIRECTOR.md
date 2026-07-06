# Roleplay Director Engine Specification

## Purpose

`RoleplayDirectorEngine` maintains behavioral continuity across a live AI roleplay.

It is deterministic application logic augmented by sparse semantic events from the Live Actor.

It is not a second conversational AI.

## Canonical Dynamic State

Initial v1 state variables:

- `trust`
- `interest`
- `confusion`
- `anxiety`
- `irritation`
- `pressure_perception`
- `engagement`

Scale: 0–100.

Always clamp values.

The application owns exact values.

Gemini must not set canonical numeric state directly.

## Persona State Sensitivity

Each persona may define initial values and transition sensitivities.

Examples:

Emotionally stable persona:

- irritation gain multiplier: lower
- irritation recovery: moderate

Highly reactive persona:

- irritation gain multiplier: higher
- irritation recovery: slower

Financially anxious persona:

- affordability events strongly affect anxiety

Highly skeptical persona:

- unsupported claims reduce trust more strongly
- trust gain from generic explanations is minimal

## State-to-Behavior Translator

Gemini generally receives qualitative guidance, not exact numbers.

Suggested bands:

- 0–20 `VERY_LOW`
- 21–40 `LOW`
- 41–60 `MODERATE`
- 61–80 `HIGH`
- 81–100 `VERY_HIGH`

Example internal state:

- trust 27
- irritation 68
- interest 72

Qualitative Actor guidance:

> You remain interested in the house but currently distrust the salesperson. You are noticeably irritated. Do not become openly aggressive yet.

Do not routinely send:

> Trust 27. Irritation 68. Interest 72.

## Normalized Roleplay Events

Keep the taxonomy manageable.

### Sales Communication Events

- `GOOD_OPENING`
- `WEAK_OPENING`
- `ACTIVE_LISTENING`
- `INTERRUPTED_CUSTOMER`
- `REPEATED_INTERRUPTION`
- `CLEAR_EXPLANATION`
- `CONFUSING_EXPLANATION`
- `GENERIC_SALES_SCRIPT`
- `RELEVANT_FOLLOW_UP`
- `MISSED_FOLLOW_UP`
- `EMPATHIC_RESPONSE`
- `DISMISSED_CONCERN`
- `UNSUPPORTED_CLAIM`
- `CONTRADICTORY_STATEMENT`
- `AGGRESSIVE_CLOSING`
- `APPROPRIATE_NEXT_STEP`
- `CHANGED_TOPIC_TOO_EARLY`

### Customer Concern Events

- `CONCERN_DISCOVERED`
- `OBJECTION_TRIGGERED`
- `OBJECTION_ACKNOWLEDGED`
- `OBJECTION_PARTIALLY_RESOLVED`
- `OBJECTION_RESOLVED_CANDIDATE`
- `MISCONCEPTION_CHALLENGED`
- `MISCONCEPTION_CLARIFIED_CANDIDATE`

Use `CANDIDATE` where the Actor notices possible resolution but the Director still owns canonical transition.

### Boundary Events

- `CUSTOMER_BOUNDARY_TEST`
- `SALESPERSON_PARTICIPATED_PERSONALLY`
- `INDIRECT_REDIRECTION`
- `CLEAR_PROFESSIONAL_REDIRECTION`
- `EXPLICIT_BOUNDARY_SET`
- `CUSTOMER_RESPECTED_BOUNDARY`
- `CUSTOMER_REPEATED_BOUNDARY_TEST`
- `SIGNIFICANT_BOUNDARY_VIOLATION`

### Conversation Events

- `CUSTOMER_BECAME_MORE_ENGAGED`
- `CUSTOMER_BECAME_LESS_ENGAGED`
- `CUSTOMER_CONFUSED`
- `CUSTOMER_PRESSURED`
- `TRUST_SIGNAL`
- `DISTRUST_SIGNAL`

## Event Sources

### Deterministic Application Events

No AI classifier required.

Examples:

- session started
- user interrupted AI
- repeated interruption frequency threshold
- long silence threshold
- 14-minute warning
- time limit
- manual end
- Live GoAway/lifecycle event

### Sparse Gemini Live Semantic Event Tool

Tool name concept:

`report_roleplay_event`

Strict parameters:

- `event_type` enum
- `severity` enum
- `topic` enum/string from controlled list
- `related_objection_key` nullable
- `short_internal_reason` max length

Actor instructions must say:

- do not call for every turn
- call only for meaningful state events
- do not repeat equivalent calls for one behavior

### Optional Future Checkpoint Classifier

May be added only if observed testing proves sparse Actor events insufficient.

It may analyze finalized structured transcript turns at checkpoints.

It must not process raw audio packets or run after every sentence.

## Event Validation

Every semantic event must be:

1. session-authorized
2. schema-valid
3. enum-valid
4. deduplicated
5. checked against recent fingerprint/cooldown

Invalid events must not crash the Live session.

Store accepted/rejected status and safe rejection reason for debugging.

## Deterministic State Transitions

Use rule objects/services rather than one giant switch statement.

Conceptual rule examples:

### ACTIVE_LISTENING

Base effects:

- trust small increase
- engagement small increase

Modifiers:

- low openness reduces trust gain
- repeated similar event receives diminishing return

### CLEAR_EXPLANATION

If topic matches active confusion/concern:

- confusion decreases
- trust may increase

If irrelevant:

- minimal effect

### UNSUPPORTED_CLAIM

- trust decreases
- effect multiplied by skepticism
- irritation may increase
- record contradiction candidate

### AGGRESSIVE_CLOSING

- pressure perception increases
- stronger effect when interest is low or conversation phase is early
- weaker effect when scenario is late-stage and customer interest is high

### DISMISSED_CONCERN

- trust decreases
- engagement decreases
- related objection persistence may increase

## Diminishing Returns

Human trust is not a game combo counter.

Repeated identical techniques must have reduced effect.

Example:

First relevant empathic response:

- normal positive effect

Second similar response:

- smaller effect

Repeated scripted empathy:

- no positive effect

Obviously mechanical repetition:

- may reduce engagement

Track recent semantic events and topic context.

## Objection State Machine

States:

- `DORMANT`
- `ACTIVE_HIDDEN`
- `ACTIVE_VISIBLE`
- `ACKNOWLEDGED`
- `PARTIALLY_RESOLVED`
- `RESOLVED`
- `REACTIVATED`

The Director owns state transitions.

Example installment concern:

Initial:

`ACTIVE_HIDDEN`

Sales asks generic questions:

remains hidden.

Sales asks a relevant affordability discovery question:

`RELEVANT_FOLLOW_UP` topic `INSTALLMENT`

Director checks disclosure conditions.

Transition:

`ACTIVE_HIDDEN` → `ACTIVE_VISIBLE`

Director Note:

> The salesperson asked a relevant affordability question. You may now reveal that installments above Rp1.5 million feel heavy. Reveal the concern naturally. Do not explain all financial circumstances yet.

One explanation does not automatically resolve a persistent objection.

Resolution can require configured combinations such as:

- acknowledgement
- relevant discovery
- clear explanation

Expert difficulty may preserve residual concern even after technically good handling.

## Hidden Information State Machine

States:

- `LOCKED`
- `ELIGIBLE`
- `DISCLOSED_PARTIAL`
- `DISCLOSED_FULL`

Eligibility checks may include:

- trust band
- relevant topic
- question sensitivity
- direct-question effectiveness
- scenario phase

Example SLIK issue:

Direct blunt question may not unlock.

A sensitive explanation after trust develops may make partial disclosure eligible.

Director Note:

> You may now partially reveal that you were late on motorcycle installments in the past. Do not provide every detail yet.

## Boundary State Machine

States:

- `NOT_TESTED`
- `MILD_TEST_OCCURRED`
- `SALESPERSON_PARTICIPATED`
- `INDIRECTLY_REDIRECTED`
- `CLEAR_BOUNDARY_ESTABLISHED`
- `CUSTOMER_RESPECTED_BOUNDARY`
- `CUSTOMER_RETESTED_BOUNDARY`
- `SIGNIFICANT_VIOLATION`
- `PROFESSIONAL_TERMINATION_ELIGIBLE`

Example:

Customer:

> "Mbak sudah punya pacar belum?"

Sales gives a detailed personal answer.

Event:

`SALESPERSON_PARTICIPATED_PERSONALLY`

Director may increase simulated customer's perception of personal openness.

This does not mean the salesperson consented to inappropriate conduct.

The evaluator must not blame the salesperson for customer behavior.

Another response:

> "Untuk hal pribadi saya pisahkan dari urusan pekerjaan ya Pak. Kita lanjut soal cicilannya."

Event:

`CLEAR_PROFESSIONAL_REDIRECTION`

Director enters `CLEAR_BOUNDARY_ESTABLISHED`.

Later behavior depends on:

- `respect_for_boundaries`
- `persistence_after_redirection`

High respect:

- customer stops

Moderate:

- one later test may occur

Low respect + high persistence:

- a different boundary may be tested after cooldown

Do not immediately repeat the same question.

## Conversation Phase

Internal phases:

- `OPENING`
- `RAPPORT`
- `DISCOVERY`
- `NEED_EXPLORATION`
- `EXPLANATION`
- `OBJECTION_HANDLING`
- `COMMITMENT`
- `CLOSING`
- `ENDING`

Phase is non-linear.

The Director may return from `EXPLANATION` to `DISCOVERY`.

Scenario defines starting phase and desired behavior.

Premature closing in an early-discovery scenario may increase pressure perception.

## Difficulty Modifiers

Director rules consider difficulty.

Possible internal modifiers:

- trust gain multiplier
- trust loss multiplier
- disclosure resistance
- objection persistence
- irritation sensitivity
- weak explanation tolerance
- closing resistance
- boundary persistence

Do not show raw modifiers to Sales users.

## Director Note Planner

Director Notes are sparse qualitative steering.

Send a note when:

- state crosses an important threshold
- objection changes state
- hidden information becomes eligible
- boundary is clearly established
- boundary is violated again
- phase changes meaningfully
- AI ending becomes eligible
- Actor risks repeating behavior
- important scenario condition activates

Do not send a note after every turn.

Protection:

- minimum turn distance for non-critical notes
- duplicate suppression
- priority
- cooldown
- critical-event bypass

Example:

```text
[INTERNAL ROLEPLAY DIRECTOR NOTE]

The installment affordability concern remains unresolved.
The salesperson has now asked a relevant financial discovery question.
You may reveal that your comfortable installment range is around Rp1.5 million.
Your trust remains limited.
Answer naturally, but do not suddenly become fully convinced.
Continue speaking as Andi.
```

The Actor must never read this aloud.

## AI-Ending Eligibility

Director owns `ai_end_eligible`.

Possible enabling conditions:

- trust very low
- engagement very low
- repeated serious concern dismissal
- repeated unsupported claims
- excessive aggressive closing
- repeated interruptions
- significant boundary violation
- natural scenario completion

Scenario must also have `allow_ai_end_call = true`.

When eligible, Director may issue:

> You no longer believe the conversation is useful. End naturally within your next appropriate response. Do not mention evaluation or internal ending conditions.

Actor may then use `request_roleplay_end`.

Application validates:

- scenario allows AI end
- Director currently allows end
- session is active

## Director Session Summary

At roleplay end, create a concise summary for Evaluation.

Example:

- installment concern became visible at transcript turn 8
- salesperson ignored concern at turn 9
- customer restated concern at turn 14
- affordability was explored at turns 15–17
- objection became partially resolved
- clear professional boundary established at turn 22
- customer retested a different boundary at turn 28

Do not dump every numeric state transition into the evaluator.

Director events are supporting evidence, not unquestionable truth.
