# Technology Stack

## Deployment Constraint

Initial production target: **Hostinger Business Web Hosting**.

The repository is hosted on GitHub and deployed through SSH `git pull` or Hostinger hPanel Git deployment.

The architecture must not require Docker, Kubernetes, Redis, Supervisor, or a permanently running custom WebSocket daemon for the first production release.

The system should remain migratable to a VPS when scale or background-processing requirements justify it.

## Backend

- Laravel 13
- PHP 8.3+
- MySQL / MariaDB using Laravel's MySQL driver
- Eloquent ORM
- Laravel Policies / Gates for authorization
- Laravel Fortify-backed authentication flow or an official Laravel starter-kit authentication foundation adapted to the custom Zen UI
- Database queue driver initially
- Database session driver
- File cache initially unless a verified Hostinger cache service is selected later
- Laravel Scheduler through hPanel cron

Do not use Laravel Octane, Horizon, or Redis as an initial production requirement.

Reason:

- Laravel 13 requires PHP 8.3.
- Hostinger currently supports modern PHP versions including 8.3 and later.
- Hostinger shared/business hosting is cron-oriented for scheduled/background tasks and does not provide the same process-control freedom as a VPS.

## Frontend

- Blade
- Livewire 4 where server-driven interactivity is useful, once its package supports Laravel 13
- Alpine.js for small local interactions
- Tailwind CSS
- Vite
- Vanilla TypeScript or JavaScript modules for Gemini Live audio transport and Web Audio API logic

Important separation:

Livewire must not own the low-level real-time audio streaming loop.

Gemini Live audio transport belongs in dedicated browser JavaScript/TypeScript modules because microphone capture, WebSocket state, playback queues, interruption, and audio conversion are client-side real-time concerns.

Use Livewire for:

- admin CRUD
- filters
- forms
- dashboards
- training setup
- result views

Implementation note verified on 2026-07-06:

- `livewire/livewire v4.0.0` currently requires Illuminate `^10.0|^11.0|^12.0` and cannot be installed with Laravel `13.18.1`.
- Do not downgrade Laravel below the specified Laravel 13 baseline solely to install Livewire.
- Use Blade and Alpine.js for the initial scaffold, then add Livewire 4 when the package supports Laravel 13 or the project explicitly changes the backend version requirement.

Use browser modules for:

- microphone capture
- PCM conversion
- Gemini Live WebSocket client
- audio playback queue
- Live event parsing
- input/output transcription event handling
- session-resumption token handling

## Authentication

Use Laravel's official authentication primitives.

Required features:

- registration
- login
- logout
- password reset request
- password reset

Customize registration to store:

- full name
- email
- password
- `PENDING_APPROVAL` status

Do not use usernames.

## Realtime Voice

Provider: Gemini Live API.

Initial architecture:

1. Browser authenticates to Laravel normally.
2. Laravel validates the user and roleplay setup.
3. Laravel resolves persona server-side and creates immutable session snapshots.
4. Laravel compiles Actor Instructions and initializes Director State.
5. Laravel provisions a short-lived Gemini ephemeral token.
6. Browser connects directly to Gemini Live using the short-lived token.
7. Browser streams microphone audio directly to Gemini.
8. Browser receives Gemini audio and Live events directly.
9. Browser sends normalized transcript events, internal semantic tool events, lifecycle events, and resumption metadata to authenticated Laravel endpoints.
10. Laravel validates and persists canonical application state.

Permanent Gemini credentials remain server-side.

## Roleplay Director

The Director Engine is Laravel domain logic.

Suggested namespace:

`App\Domain\RoleplayDirector`

Suggested components:

- `RoleplayDirectorEngine`
- `DirectorState`
- `RoleplayEvent`
- `RoleplayEventType`
- `StateTransitionRule`
- `StateTransitionRegistry`
- `StateToBehaviorTranslator`
- `DirectorNotePlanner`
- `DirectorNoteCooldown`
- `ObjectionStateMachine`
- `DisclosureStateMachine`
- `BoundaryStateMachine`
- `ConversationPhaseManager`
- `AiEndingEligibilityService`

Keep state transition logic testable without Gemini or HTTP.

## Persona Compilation

Suggested components:

- `PersonaSalienceCompiler`
- `RoleplayInstructionCompiler`
- immutable value objects or DTOs for persona/scenario/session snapshots

The compiler output is concise Actor Instructions, not a dump of every database field.

## Evaluation

Create a provider abstraction.

Suggested components:

- `EvaluationProvider` interface
- `EvaluationProviderManager`
- `OpenRouterEvaluationProvider`
- `GroqEvaluationProvider`
- `GeminiEvaluationProvider`
- `EvaluationRequestBuilder`
- `EvaluationSchemaValidator`
- `RubricMerger`
- `DirectorSessionSummaryBuilder`

Provider model IDs remain configuration.

Do not hardcode the assumption that DeepSeek, an OpenRouter model, or a Groq model will remain free.

Laravel 13 includes a first-party AI SDK, but do not force Gemini Live WebSocket behavior into a text-generation abstraction that does not fit Live API requirements. The evaluation layer may evaluate Laravel AI SDK usage if the current provider support and structured-output requirements fit the application, otherwise use focused provider adapters.

## Queue Strategy on Hostinger Business

Use `database` queue initially.

Do not assume a permanent `queue:work` daemon.

Design evaluation jobs to be idempotent and safely processable by short-lived workers launched through cron.

Recommended shared-hosting approach:

- enqueue evaluation job
- cron periodically runs a bounded queue command such as `php artisan queue:work database --stop-when-empty --tries=... --timeout=...`
- job is idempotent
- completed evaluation is never recreated on page refresh
- UI polls evaluation status or uses normal Livewire polling

Exact cron cadence must be tested against Hostinger limits and the acceptable coaching delay.

Future VPS migration may use Redis + Supervisor/Horizon without changing domain contracts.

## Mail

Use Laravel Mail with SMTP configuration.

Required initially for password reset.

Configuration remains environment-driven.

## Storage

Use Laravel Storage.

Initial disk: local/private application storage where appropriate.

Do not persist raw microphone audio by default.

Store normalized transcripts and session metadata.

Audio recording retention requires an explicit future privacy/product decision.

## Testing

- Pest or PHPUnit; choose one and use it consistently
- Laravel HTTP/Feature tests
- Unit tests for Director domain rules
- Browser/manual integration test checklist for Gemini Live audio

## Production Build

Vite assets must be built before or during deployment using a verified mechanism.

Preferred approaches:

1. GitHub Actions builds production assets and deployable artifacts, or
2. local/CI build commits or packages `public/build`, or
3. Hostinger Node build only after confirming the deployment path supports the selected workflow.

Do not assume Node is required as a permanent production runtime.
