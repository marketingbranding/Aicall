# AI Evaluation Engine Specification

## Purpose

After a roleplay ends, a separate AI Evaluation Engine analyzes the salesperson's performance.

Gemini Live Actor is not the coach.

The evaluator must produce evidence-based professional feedback using the scenario objective, persona context, difficulty, transcript, rubrics, and concise Director Session Summary.

## Provider Abstraction

Create `EvaluationProvider` interface.

Adapters initially planned:

- `OpenRouterEvaluationProvider`
- `GroqEvaluationProvider`
- `GeminiEvaluationProvider`

Provider availability and model pricing/quotas can change.

Do not hardcode the belief that a specific model remains free.

Provider configuration:

- name
- provider type
- model ID
- enabled
- priority
- timeout
- maximum retries
- non-secret options
- secret reference/environment mapping

## Evaluation Provider Manager

Flow:

1. load enabled providers by priority
2. try primary provider
3. normalize timeout/API/provider failure
4. retry only according to configured policy
5. fall back to next provider
6. record provider/model actually used
7. fail permanently only after eligible providers fail

Evaluation statuses:

- `PENDING`
- `PROCESSING`
- `COMPLETED`
- `FAILED`

## Shared-Hosting Queue Behavior

Evaluation is asynchronous.

The end-call request must not wait for a potentially slow evaluator response.

Flow:

1. finalize transcript
2. create one evaluation record if absent
3. dispatch idempotent evaluation job
4. cron-triggered bounded database queue worker processes the job
5. UI shows `Menganalisis percakapan`
6. result becomes available when completed

A page refresh must never create another evaluation.

Use unique session evaluation relationship and idempotency checks.

## Rubrics

### Global Rubric

Possible competencies:

- Opening
- Rapport
- Communication
- Discovery
- Active Listening
- Follow-up Questions
- Customer Need Understanding
- Empathy
- Financial Sensitivity
- Explanation Clarity
- Objection Handling
- Handling Misinformation
- Conversational Control
- Closing
- Next-Step Commitment
- Avoiding Unsupported Claims
- Professional Boundary Management

Global item:

- key
- title
- description
- weight
- enabled
- evaluation guidance

### Scenario Rubric

Scenario-specific criteria.

Example SLIK scenario:

- identify actual credit concern
- do not guarantee bank approval
- explain process clearly
- ask sensitive credit-history questions appropriately

Example post-site-visit follow-up:

- recall customer interest
- identify remaining objection
- establish a reasonable next-step commitment

## Rubric Merge

Create `RubricMerger`.

Effective evaluation rubric:

`Global Rubric Snapshot + Scenario Rubric Snapshot + Scenario Overrides`

A scenario may change global competency weight.

Example early-discovery scenario:

- Discovery: high weight
- Closing: low weight

Do not penalize failure to book when booking is not the scenario objective.

## Evaluation Input

Create `EvaluationRequestBuilder`.

Input should include only required context:

- Scenario Snapshot
- training objective
- difficulty
- relevant persona coaching context
- effective rubric
- canonical transcript turns
- ending type/reason
- Director Session Summary
- prohibited claims configured by scenario

Do not send every Director numeric snapshot or raw technical log.

Do not send raw audio by default.

## Director Signals Are Supporting Evidence

The evaluator may use normalized Director events and major state-transition summaries.

It must still inspect transcript evidence.

Director state is not unquestionable truth.

The evaluator should not invent conversation facts merely because an event exists.

## Evaluation Philosophy

Evaluate actual conversation behavior.

Bad feedback:

> Improve your communication.

Good feedback:

> When the customer said the monthly installment felt heavy, you immediately explained house specifications. The financial concern was not explored before you changed topics.

Distinguish:

- `FACTUAL_MISINFORMATION`
- `SERIOUS_SALES_MISTAKE`
- `WEAK_TECHNIQUE`
- `MISSED_OPPORTUNITY`
- `STYLE_PREFERENCE`

Do not make every imperfection critical.

Consider:

- scenario objective
- persona
- difficulty
- conversation phase
- active concerns
- ending context
- transcript evidence

## Professional Boundary Evaluation

The evaluator must not blame the salesperson for simulated customer misconduct.

Bad:

> You caused the customer to become more flirtatious.

Better:

> After the customer asked about your relationship status, you answered the personal question in detail. This prolonged the non-professional direction of the conversation. A brief boundary statement followed by a return to the housing topic would provide stronger conversational control.

Evaluate, when relevant:

- Professional Boundary Management
- Conversation Redirection
- Composure Under Inappropriate Behavior
- Escalation Recognition
- Safe Conversation Termination

Distinguish:

- harmless friendliness
- mild flirting
- boundary testing
- repeated inappropriate behavior
- significant professional boundary violation

Context and repetition matter.

## Structured Evaluation Schema

Create a versioned strict schema.

Conceptual output:

```json
{
  "schema_version": "1.0",
  "overall_score": 78,
  "summary": "...",
  "scores": [
    {
      "rubric_key": "discovery",
      "score": 72,
      "reason": "..."
    }
  ],
  "strengths": [],
  "improvement_areas": [],
  "critical_mistakes": [],
  "missed_opportunities": [],
  "customer_emotional_journey": [],
  "recommended_better_responses": [],
  "next_training_focus": []
}
```

Finding concept:

```json
{
  "type": "MISSED_OPPORTUNITY",
  "title": "Concern cicilan tidak digali",
  "transcript_sequences": [12, 13],
  "explanation": "...",
  "better_approach": "..."
}
```

The implementation must define exact JSON Schema or equivalent validation rules.

## Validation Pipeline

Never trust raw model JSON.

Pipeline:

1. provider response received
2. extract intended structured payload
3. JSON parse
4. schema validation
5. semantic validation
6. limited repair only where safe and explicitly implemented
7. retry/fallback according to policy
8. persist normalized result

Semantic validation examples:

- overall score between 0 and 100
- rubric keys exist in effective rubric
- transcript sequence references exist
- no duplicate score item per rubric key
- required enabled rubric items are represented

## Customer Emotional Journey UI Data

Do not expose Director numeric state by default.

Preferred evaluator output:

- Awal Percakapan: Waspada
- Setelah Pembahasan Cicilan: Semakin Cemas
- Setelah Kebutuhan Digali: Lebih Terbuka
- Menjelang Akhir: Masih Tertarik, tetapi Belum Sepenuhnya Percaya

Director transitions may support this summary.

## Evaluation Result Storage

Store normalized records for:

- overall score
- summary
- score items
- findings
- transcript references
- provider/model metadata
- duration
- retry count
- status/failure category

Raw provider output, if retained for debugging, is private and must not be exposed to Sales users.

## Manual Retry

Authorized users may retry failed evaluations.

Retry must reuse the immutable session snapshots and canonical transcript.

Do not evaluate against the latest edited persona/scenario.
