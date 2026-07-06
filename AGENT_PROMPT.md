# Prompt to Paste into the AI Coding Agent

Copy the prompt below as one message while the coding agent is opened at the project repository root.

---

You are working on an AI voice roleplay training application.

Before writing or changing application code, read these files completely in order:

1. `00_START_HERE.md`
2. every Markdown file inside `docs/` in numeric filename order
3. `TASKLIST.md`

Treat these files as the project's authoritative source of truth.

Then inspect:

- the full repository structure
- `composer.json`
- `package.json`
- current migrations
- current routes
- current domain/service classes
- current tests
- `git status`
- `git diff`

Do not assume the repository is empty.
Do not recreate completed modules.
Do not replace an existing working architecture without identifying a direct specification conflict.

Before implementation, verify current official Gemini Live API documentation for:

- available Live models
- ephemeral token authentication
- audio format
- input transcription
- output transcription
- realtime text input
- function calling behavior
- session lifetime
- connection lifetime / GoAway behavior
- session resumption
- model capability limitations

Do not use an outdated model ID or unsupported feature merely because a specification describes the intended capability.
Model IDs must remain configuration.

After reading and inspecting, respond with:

1. architecture summary,
2. current repository state,
3. completed implementation phases,
4. partially completed work,
5. current technical risks,
6. the first incomplete task in `TASKLIST.md` that should be implemented next.

If a critical decision is truly required from me because it changes security, data integrity, or the core product architecture, ask concise specific questions.

Do not ask trivial implementation questions that can be resolved using the specifications and good engineering practices.

Then continue implementation from the first valid incomplete task.

Work incrementally.

For each task:

1. read the relevant specification file,
2. inspect existing related code,
3. implement only the required scope,
4. add or update relevant tests,
5. run focused tests,
6. inspect the result and `git diff`,
7. fix failures,
8. mark the task complete only after verification.

Do not start secondary features early.

The first priority is the complete vertical roleplay flow.

Preserve the core architecture:

GEMINI LIVE IS THE ACTOR.
THE ROLEPLAY DIRECTOR ENGINE IS THE DIRECTOR.
THE LARAVEL APPLICATION OWNS CANONICAL ROLEPLAY STATE.

Do not collapse the Director Engine into one giant system prompt.
Do not make Gemini assign canonical numeric trust/emotion state.
Do not run an expensive AI classifier after every sentence.
Use deterministic Director rules plus sparse semantic Live tool events.

The initial production target is Hostinger Business Web Hosting.
Do not introduce a mandatory Redis server, Supervisor daemon, Docker runtime, Kubernetes cluster, or custom permanent WebSocket server for v1.

Keep the codebase reasonably migratable to a VPS later.

If documentation must change because verified provider behavior or implementation reality conflicts with it, update the relevant specification Markdown first and clearly document why.

Begin now.

---
