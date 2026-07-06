# AI Sales Roleplay Training — Start Here

## Purpose

This repository defines and implements an internal AI voice roleplay training application for an Indonesian subsidized-housing developer.

The human user is a branch salesperson. The AI acts as a simulated prospective homebuyer. HQ manages personas, scenarios, training configuration, and evaluation through a Super Admin account.

This is not a CRM, generic chatbot, or customer support bot.

The product goal is to create believable voice conversations with difficult customers while keeping the training environment calm and psychologically safe.

## Core Architecture Principle

> Gemini Live is the actor. The Roleplay Director Engine is the director.

Gemini Live is responsible for natural listening, speaking, interruption, improvisation, and remaining in character.

The Laravel application owns canonical roleplay state such as trust, interest, confusion, anxiety, irritation, pressure perception, engagement, objection state, hidden-information disclosure state, boundary state, conversation phase, and AI-ending eligibility.

Never reduce the product to one giant persona prompt.

## Source of Truth

Read the documentation in this order:

1. `docs/01_PRODUCT_SPEC.md`
2. `docs/02_TECH_STACK.md`
3. `docs/03_ARCHITECTURE.md`
4. `docs/04_DATABASE_SCHEMA.md`
5. `docs/05_ROLEPLAY_ENGINE.md`
6. `docs/06_ROLEPLAY_DIRECTOR.md`
7. `docs/07_GEMINI_LIVE.md`
8. `docs/08_EVALUATION_ENGINE.md`
9. `docs/09_UI_UX_DESIGN.md`
10. `docs/10_DEPLOYMENT_HOSTINGER.md`
11. `docs/11_TESTING_SPEC.md`
12. `TASKLIST.md`

`TASKLIST.md` is the implementation execution order.

Authentication requirements currently live across `docs/01_PRODUCT_SPEC.md`, `docs/02_TECH_STACK.md`, `docs/04_DATABASE_SCHEMA.md`, `docs/09_UI_UX_DESIGN.md`, `docs/11_TESTING_SPEC.md`, and `TASKLIST.md`. Do not create a separate `AUTH_SPEC.md` unless authentication rules grow enough to justify a dedicated spec.

## Rules for Coding Agents

- Read all specification files before changing architecture.
- Inspect the current repository and `git diff` before implementation.
- Do not rebuild completed modules without a documented reason.
- Do not mark tasks complete until implementation and verification both exist.
- Keep documentation synchronized when architecture or business rules change.
- Prioritize the complete vertical roleplay flow before Persona Lab or advanced analytics.
- Verify current official Gemini Live API documentation before implementing provider-specific methods or model IDs.
- Model IDs and provider capabilities are configuration, not scattered constants.
- Hostinger Business Web Hosting is the initial deployment target.
- The application must remain reasonably migratable to a VPS later.

## Initial Roles

- `SUPER_ADMIN`
- `SALES`

Future architecture must be able to add `BRANCH_SUPERVISOR`, but its UI is out of initial scope.

## First Definition of Done

The first complete product flow is:

1. Salesperson registers with full name, email, and password.
2. Account waits for HQ approval.
3. Super Admin creates a branch, assigns the user, and approves the account.
4. Super Admin creates a persona with knowledge, behavior, objections, and hidden information.
5. Super Admin creates a scenario with difficulty, persona modes, AI-ending rules, and scenario rubric.
6. Salesperson logs in and selects a scenario.
7. Persona is resolved securely.
8. Persona and scenario snapshots are created.
9. Persona Salience Compiler creates behavioral hierarchy.
10. Roleplay Instruction Compiler creates concise actor instructions.
11. Roleplay Director initializes dynamic state.
12. Browser receives short-lived Gemini Live credentials from Laravel.
13. Live voice roleplay runs for up to 15 minutes.
14. Transcript turns and meaningful roleplay events are collected.
15. Director applies deterministic state transitions and sparse guidance.
16. User, AI, or time limit ends the call.
17. Transcript and Director Session Summary are finalized.
18. Evaluation provider analyzes the session using Global + Scenario Rubrics.
19. Structured evaluation is validated and stored.
20. Salesperson sees evidence-based coaching and transcript references.
21. HQ can review the session.

Do not claim the core application is complete before this flow works end-to-end.
