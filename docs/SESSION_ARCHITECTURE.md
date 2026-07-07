# Session Architecture

## Purpose

Define the data model, ownership, lifecycle, and security boundaries for roleplay sessions and their immutable snapshots.

This document guides Phase 7 implementation without prescribing database column order, migration file names, or factory defaults.

---

## 1. Core Concepts

### Session vs Snapshot

| Concept | Role |
|---------|------|
| `RoleplaySession` | Mutable Eloquent model tracking session lifecycle, status, ownership, and timing. |
| `RoleplaySessionSnapshot` | Immutable record of all configuration at session creation time. One-per-session. |
| `PersonaSnapshot` | Value object capturing the exact persona version data used by this session. |
| `ScenarioSnapshot` | Value object capturing the exact scenario version data used by this session. |
| `RubricSnapshot` | Value object capturing the merged rubric result at session creation. |
| `DifficultySnapshot` | Value object capturing the effective difficulty modifier values. |
| `DirectorSnapshot` | Value object capturing initial DirectorState and applied difficulty settings. |

### Immutability Rule

Once a session is created, none of its snapshots may change. Editing a persona or scenario in the admin panel must never affect in-progress or completed sessions.

---

## 2. Ownership & Authorization

| Entity | Owner | HQ Access | Sales Access | AI Access |
|--------|-------|-----------|--------------|-----------|
| `RoleplaySession` | `user_id` (Sales) | Full | Own only | None |
| `PersonaSnapshot` | Session | Full | Limited public fields | Full |
| `ScenarioSnapshot` | Session | Full | Limited public fields | Full |
| `RubricSnapshot` | Session | Full | Result only (post-evaluation) | Full |
| `DifficultySnapshot` | Session | Full | Level label only | Full |
| `DirectorSnapshot` | Session | Full | None | Full |
| `RoleplayInstruction` (text) | Session | None | None | Full (via Gemini Live) |

### Authorization Rules

- Sales can view only their own sessions (`user_id`).
- HQ (SUPER_ADMIN) can view all sessions.
- Actor Instructions are stored encrypted and are **never** returned to any browser endpoint — they are sent only to Gemini Live.
- Hidden persona configuration (objections, hidden information, salience overrides) must not appear in Sales browser responses.
- `DirectorSnapshot` contains internal state machine configuration and difficulty modifiers. Exposing them would leak scoring mechanics — Sales must not see them.

---

## 3. Session Lifecycle

### States

```
CREATED → PREPARING → REQUESTING_MICROPHONE → CONNECTING → READY → ACTIVE
                                                                       │
                                                                       ├──→ RECONNECTING → ACTIVE
                                                                       │
                                                                       ├──→ ENDING → TRANSCRIPT_FINALIZING → EVALUATING → COMPLETED
                                                                       │
                                                                       └──→ FAILED (at any point)
```

| State | Meaning |
|-------|---------|
| `CREATED` | Session record inserted, snapshots taken, transaction committed |
| `PREPARING` | Server compiling instructions, provisioning token |
| `REQUESTING_MICROPHONE` | Browser waiting for user mic permission |
| `CONNECTING` | Browser establishing Gemini Live WebSocket |
| `READY` | Connection established, first turn pending |
| `ACTIVE` | Conversation in progress |
| `RECONNECTING` | Connection lost, resumption in progress |
| `ENDING` | End requested, final turns completing |
| `TRANSCRIPT_FINALIZING` | Transcript being assembled and validated |
| `EVALUATING` | Evaluation job dispatched, awaiting result |
| `COMPLETED` | All processing done |
| `FAILED` | Irrecoverable error |

### State Transitions

- Sales user action: `CREATED` → `PREPARING` (page load triggers preparation).
- Browser runtime events drive `REQUESTING_MICROPHONE` → `CONNECTING` → `READY` → `ACTIVE`.
- Browser detects disconnect: `ACTIVE` → `RECONNECTING` → `ACTIVE`.
- End trigger (user/AI/time/failure): any active state → `ENDING`.
- `ENDING` → `TRANSCRIPT_FINALIZING` → `EVALUATING` → `COMPLETED` is server-driven.
- Any state may transition to `FAILED` on unrecoverable error.

---

## 4. Snapshot Architecture

### 4.1 PersonaSnapshot

**Purpose:** Freeze the persona version's full configuration at session creation.

**Source:** `PersonaVersion` + `PersonaObjection` + `PersonaHiddenInformation` loaded at session creation time.

**Contents:**

```php
readonly class PersonaSnapshot
{
    public string $personaKey;
    public string $name;
    public int $versionNumber;

    // Layer 1 — Static Persona (public)
    public array $identity;
    public array $housingContext;

    // Layer 2 — Knowledge & Beliefs (public)
    public array $knowledgeBeliefs;
    public array $misconceptions;         // filtered from knowledge_beliefs

    // Layer 3 — Behavior Profile (public)
    public array $personalityProfile;

    // Layer 4 — Human Behavior Traits (HQ-only, not sent to Sales browser)
    public array $humanBehaviorTraits;

    // Communication Style (public)
    public array $communicationStyle;

    // Initial Dynamic State (Director-only, not sent to any browser)
    public array $initialDynamicState;
    public array $stateSensitivity;

    // Objections (HQ-only — includes hidden objections)
    public array $objections;            // PersonaObjection data

    // Hidden Information (HQ-only)
    public array $hiddenInformation;     // PersonaHiddenInformation data

    // Salience Overrides (Director-only)
    public array $salienceOverrides;
}
```

**Security classification by field:**

| Category | Browser (Sales) | Browser (HQ) | AI |
|----------|----------------|--------------|-----|
| `personaKey`, `name`, `versionNumber` | Visible | Visible | Visible |
| `identity`, `housingContext` | Visible | Visible | Visible |
| `knowledgeBeliefs` | Visible | Visible | Visible |
| `personalityProfile` | Visible | Visible | Visible |
| `communicationStyle` | Visible | Visible | Visible |
| `humanBehaviorTraits` | Hidden | Visible | Visible |
| `objections` | Hidden | Visible | Visible |
| `hiddenInformation` | Hidden | Visible | Visible |
| `initialDynamicState`, `stateSensitivity`, `salienceOverrides` | Hidden | Hidden | Visible |

### 4.2 ScenarioSnapshot

**Purpose:** Freeze the scenario version's configuration at session creation.

**Source:** `ScenarioVersion` + related records.

**Contents:**

```php
readonly class ScenarioSnapshot
{
    public string $scenarioKey;
    public string $name;
    public int $versionNumber;

    // Public briefing
    public string $description;
    public ?string $salesBriefing;
    public ?string $trainingObjective;

    // Actor-facing
    public ?string $hiddenContext;         // HQ-only
    public string $startingPhase;
    public string $firstSpeaker;
    public ?string $aiOpeningContext;
    public ?string $initialCustomerIntent;

    // Targets & conditions (HQ-only — eval reference)
    public array $targetBehaviors;
    public array $importantDiscoveryPoints;
    public array $mandatoryTopics;
    public array $prohibitedClaims;
    public array $successConditions;
    public array $failureConditions;

    // Difficulty
    public string $difficultyLevel;
    public ?array $difficultyConfig;       // custom modifier values

    // Constraints
    public int $maxDurationSeconds;
    public bool $allowAiEndCall;

    // Persona modes
    public array $allowedPersonaModes;
}
```

**Security:**

| Field | Browser (Sales) | Browser (HQ) | AI |
|-------|----------------|--------------|-----|
| `scenarioKey`, `name`, `versionNumber` | Visible | Visible | Visible |
| `description`, `salesBriefing`, `trainingObjective` | Visible | Visible | Visible |
| `startingPhase`, `firstSpeaker` | Visible | Visible | Visible |
| `hiddenContext` | Hidden | Visible | Visible |
| `targetBehaviors`, `importantDiscoveryPoints`, etc. | Hidden | Visible | Visible |
| `difficultyLevel` | Visible | Visible | Visible |
| `difficultyConfig` | Hidden | Visible | Visible |
| `maxDurationSeconds`, `allowAiEndCall` | Inferred from UX | Visible | Visible |

### 4.3 DifficultySnapshot

**Purpose:** Capture the effective difficulty modifier values used by the Director for this session.

**Source:** `DifficultyModifier` (either from `DifficultyModifier::forLevel()` or `fromCustomConfig()`).

**Contents:**

```php
readonly class DifficultySnapshot
{
    public string $level;                         // BEGINNER / NORMAL / DIFFICULT / EXPERT
    public bool $isCustom;
    public float $trustGainMultiplier;
    public float $trustLossMultiplier;
    public int $disclosureResistance;
    public int $objectionPersistence;
    public int $irritationSensitivity;
    public int $weakExplanationTolerance;
    public int $closingResistance;
    public int $boundaryPersistence;
}
```

**Security:** Stored but never returned to any browser. Only the level label (`BEGINNER` / etc.) is shown to Sales.

### 4.4 RubricSnapshot

**Purpose:** Capture the effective evaluation rubric at session creation. The evaluator reads this, not the current admin rubric configuration.

**Source:** `RubricMergedResult` (output of `RubricMerger::merge()`).

**Contents:**

```php
readonly class RubricSnapshot
{
    public array $items;   // MergedRubricItem[] — key, title, description, weight, isEnabled, evaluationGuidance, source
}
```

**Security:** Returned in full only to HQ post-evaluation. Sales sees only final scores derived from this rubric, never the raw items or weights.

### 4.5 DirectorSnapshot

**Purpose:** Capture the initial Director configuration needed for reproducible session evaluation.

**Source:** `DirectorState::default()` + `DifficultyModifier` values + applied state machine configuration.

**Contents:**

```php
readonly class DirectorSnapshot
{
    // Initial state (before any events)
    public array $initialState;           // trust=50, interest=50, etc.

    // Difficulty modifiers that affect transitions
    public array $difficultyValues;       // flattened modifier values

    // Objection state machine initial state (for reproducibility)
    public array $objectionConfig;

    // Hidden info state machine initial state
    public array $hiddenInfoConfig;

    // Boundary state machine initial state
    public array $boundaryConfig;

    // Phase manager initial phase
    public string $initialPhase;
}
```

**Security:** Never returned to any browser. Internal reference only.

### 4.6 SalienceSnapshot

**Purpose:** Capture the salience compiler output used in Actor Instructions.

**Source:** `SalienceResult` (output of `PersonaSalienceCompiler`).

**Contents:**

```php
readonly class SalienceSnapshot
{
    public array $primary;      // SalientTrait[]
    public array $secondary;    // SalientTrait[]
    public array $background;   // SalientTrait[]
}
```

**Security:** HQ-only. Salience is an internal detail not needed by Sales.

---

## 5. Snapshot Hashing

### Instruction Hash

The compiled Actor Instructions text is hashed using SHA-256:

```
actor_instruction_hash = hash('sha256', $roleplayInstruction->toText())
```

The hash is stored in `roleplay_session_snapshots.actor_instruction_hash`.

**Purpose:**
- Detect tampering or drift between the stored instructions and what was sent to Gemini.
- Provide a stable reference for debugging without exposing the full instruction text in logs.

### Integrity Check

If the compiled instructions need to be verified later (debugging), recompile from snapshots and compare the hash. A mismatch indicates the compilation inputs changed (which should not happen given immutable snapshots).

---

## 6. Data Visibility Matrix

### Browser Data Shape (Sales)

```json
{
    "session": {
        "public_id": "abc123",
        "status": "ACTIVE",
        "started_at": "...",
        "duration_seconds": null,
        "ending_type": null,
        "ending_reason": null
    },
    "persona": {
        "name": "Rina",
        "version": 3,
        "identity": { ... },
        "housing_context": { ... },
        "knowledge_beliefs": { ... },
        "personality_profile": { ... },
        "communication_style": { ... }
    },
    "scenario": {
        "name": "KPR First Home",
        "version": 2,
        "description": "...",
        "sales_briefing": "...",
        "training_objective": "...",
        "first_speaker": "AI",
        "difficulty_level": "NORMAL",
        "max_duration_seconds": 900
    }
}
```

### Browser Data Shape (HQ review)

All Sales data, plus:

```json
{
    "snapshots": {
        "persona": { /* full PersonaSnapshot incl. hidden fields */ },
        "scenario": { /* full ScenarioSnapshot incl. hidden fields */ },
        "rubric": { /* RubricSnapshot items */ },
        "salience": { /* SalienceSnapshot */ }
    },
    "evaluation": { /* evaluation results */ }
}
```

### Never Returned to Any Browser

- `roleplay_session_snapshots.actor_instructions` (encrypted column)
- `DirectorSnapshot` data
- `DifficultySnapshot` modifier values (only the level label is public)
- API keys, ephemeral tokens, or credentials of any kind

---

## 7. Session Creation Transaction

The session creation is a single atomic database transaction:

```
1. BEGIN TRANSACTION
2.   Authorize user (ACTIVE status, scenario access)
3.   Validate persona mode against scenario
4.   Resolve persona (RANDOM / HIDDEN → concrete persona)
5.   Load PersonaVersion (current_version_id)
6.   Load ScenarioVersion (current_version_id)
7.   Create PersonaSnapshot from PersonaVersion + related records
8.   Create ScenarioSnapshot from ScenarioVersion
9.   Create DifficultySnapshot from DifficultyModifier
10.  Run SalienceCompiler → create SalienceSnapshot
11.  Run RubricMerger → create RubricSnapshot
12.  Initialize DirectorState::default()
13.  Create DirectorSnapshot
14.  Compile Actor Instructions (RoleplayInstructionCompiler)
15.  Hash instructions (SHA-256)
16.  Encrypt instruction text
17.  INSERT roleplay_sessions
18.  INSERT roleplay_session_snapshots (with all snapshot JSON + encrypted instructions + hash)
19. COMMIT
20. Provision ephemeral Gemini Live token
21. Return bootstrap data (Sales-visible fields only)
```

### Rollback Rules

- Steps 1–19 are atomic. If any step fails (e.g., persona not found, compilation error), the entire transaction rolls back.
- Steps 20–21 are outside the transaction. If token provisioning fails, the session exists in `CREATED` state and the browser can retry preparation.
- The session's `correlation_id` (UUID) is generated before the transaction and used across all related logging.

---

## 8. Storage Strategy

### Database Schema

`roleplay_sessions` table (mutable):

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT PK | |
| `public_id` | VARCHAR(12) UNIQUE | Short URL-safe ID shown to users |
| `correlation_id` | UUID UNIQUE | Logging correlation, generated pre-transaction |
| `user_id` | FK → users | Session owner |
| `branch_id` | FK → branches | Denormalized for HQ reporting |
| `status` | VARCHAR(30) | Session state machine value |
| `persona_mode` | VARCHAR(20) | CHOOSE_PERSONA / RANDOM_PERSONA / HIDDEN_PERSONA |
| `difficulty_level` | VARCHAR(20) | Denormalized for quick display |
| `started_at` | DATETIME NULL | When first turn began |
| `ended_at` | DATETIME NULL | When session terminal state reached |
| `duration_seconds` | INT NULL | Computed on end |
| `ending_type` | VARCHAR(20) NULL | USER_END / AI_END / TIME_LIMIT / FAILURE |
| `ending_reason` | TEXT NULL | Human-readable reason |
| `transcript_integrity` | VARCHAR(20) | COMPLETE / PARTIAL / FAILED |
| `evaluation_status` | VARCHAR(20) | PENDING / PROCESSING / COMPLETED / FAILED |
| `director_version` | INT | Schema version for Director output |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

`roleplay_session_snapshots` table (immutable after creation):

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT PK | |
| `roleplay_session_id` | FK UNIQUE | One snapshot per session |
| `persona_version_id` | FK → persona_versions | Reference for audit |
| `scenario_version_id` | FK → scenario_versions | Reference for audit |
| `persona_snapshot_json` | JSON | Full PersonaSnapshot |
| `scenario_snapshot_json` | JSON | Full ScenarioSnapshot |
| `difficulty_snapshot_json` | JSON | DifficultySnapshot |
| `salience_snapshot_json` | JSON | SalienceSnapshot |
| `rubric_snapshot_json` | JSON | RubricSnapshot |
| `director_snapshot_json` | JSON | DirectorSnapshot |
| `actor_instruction_hash` | VARCHAR(64) | SHA-256 hex digest |
| `actor_instructions` | TEXT | Encrypted at application level |
| `created_at` | DATETIME | |

### Encryption

Actor Instructions are encrypted using Laravel's built-in encryption (`Crypt::encryptString()`) before storage. Decryption happens only when provisioning the Gemini Live session — never in response to a browser request.

### Indexes

- `roleplay_sessions.user_id` — Sales views own sessions
- `roleplay_sessions.branch_id` — HQ branch filters
- `roleplay_sessions.status` — Active session queries
- `roleplay_sessions.created_at` — Recent session queries
- `roleplay_session_snapshots.roleplay_session_id` — Lookup

---

## 9. Snapshot DTO Locations

All snapshot value objects live under `app/Services/Snapshots/`:

```
app/Services/Snapshots/
├── PersonaSnapshot.php          (readonly class)
├── ScenarioSnapshot.php         (readonly class)
├── DifficultySnapshot.php       (readonly class)
├── RubricSnapshot.php           (readonly class)
├── SalienceSnapshot.php         (readonly class)
├── DirectorSnapshot.php         (readonly class)
└── SessionSnapshotService.php   (service that creates all snapshots)
```

`SessionSnapshotService` orchestrates the snapshot creation within the session creation transaction. It accepts the loaded `PersonaVersion`, `ScenarioVersion`, `DifficultyModifier`, `SalienceResult`, `RubricMergedResult`, and `DirectorState`, and returns a `RoleplaySessionSnapshot` model instance (unsaved).

---

## 10. Session Model Responsibilities

```php
class RoleplaySession extends Model
{
    // Relationships
    public function user(): BelongsTo;
    public function branch(): BelongsTo;
    public function snapshot(): HasOne;           // RoleplaySessionSnapshot

    // Scopes
    public function scopeForUser(Builder $q, User $user): void;
    public function scopeActive(Builder $q): void;
    public function scopeByBranch(Builder $q, int $branchId): void;

    // State checks
    public function isActive(): bool;
    public function isEnding(): bool;
    public function canEnd(): bool;               // Not already in terminal state
    public function canReceiveEvents(): bool;     // ACTIVE or READY

    // Lifecycle
    public static function generatePublicId(): string;  // 12-char URL-safe
    public function markEnded(string $endingType, ?string $reason): void;
}
```

---

## 11. Evaluation Data Flow

```
RoleplaySession (COMPLETED)
    │
    ├── snapshot → PersonaSnapshot (for evaluator context)
    ├── snapshot → ScenarioSnapshot (for evaluator context)
    ├── snapshot → RubricSnapshot (the rubric the evaluator uses)
    ├── snapshot → DifficultySnapshot (context)
    ├── snapshot → DirectorSnapshot (state machine config reference)
    │
    ├── directorSessionSummary → DirectorSessionSummary (built during roleplay)
    │
    └── evaluation → EvaluationResult (linked to session)
```

The evaluator receives the `RubricSnapshot` (not the current `EvaluationRubric` records), ensuring rubric changes after session creation do not retroactively affect completed evaluations.

---

## 12. Key Rules Summary

1. **Snapshots are immutable.** Once `roleplay_session_snapshots` is inserted, no UPDATE.
2. **Actor Instructions are encrypted.** Decrypted only for Gemini Live provisioning.
3. **Sales never sees hidden fields.** The `PersonaSnapshot` returned to Sales omits `humanBehaviorTraits`, `objections`, `hiddenInformation`, `stateSensitivity`, and `salienceOverrides`.
4. **Sales never sees Director internals.** `DirectorSnapshot` and `DifficultySnapshot` full data never leave the server.
5. **Sales never sees raw rubric.** Only final evaluation scores derived from the rubric.
6. **One snapshot per session.** The `roleplay_session_id` column is UNIQUE.
7. **Session creation is transactional.** Steps 1–19 in Section 7 are atomic.
8. **Session `public_id` is URL-safe.** 12 characters, generated from random bytes (no sequential IDs exposed).
9. **evaluation_status** starts as `null` (no evaluation needed yet) and transitions to `PENDING` when the evaluation is dispatched.
10. **`correlation_id`** is a UUID generated before the transaction, used in all log lines for this session.
