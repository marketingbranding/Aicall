# Product Specification

## Product Name

Working title: **Sales Roleplay Training**.

The final product name may change. Do not bake the working title deeply into domain logic.

## Product Context

The application is an internal training platform for salespeople at branches of an Indonesian subsidized-housing developer.

Salespeople need realistic practice handling prospective homebuyers. Real customers may be skeptical, confused, financially anxious, misinformed, impatient, dominant, passive-aggressive, personally intrusive, flirtatious, or professionally inappropriate.

The application simulates these customers using live voice AI.

The AI customer must behave as a human consumer, not as a sales coach or AI assistant.

## Product Principle

The AI customer creates tension.

The product creates psychological safety.

A salesperson may think:

> "Konsumennya susah."

The interface should quietly communicate:

> "Tidak apa-apa. Di sini tempat latihan."

## User Roles

### SUPER_ADMIN

Represents HQ.

Capabilities:

- HQ dashboard
- branch CRUD
- user review and approval
- assign sales users to branches
- suspend/reactivate users
- persona CRUD, duplication, archival, and versioning
- scenario CRUD
- assign personas to scenarios
- configure persona-selection modes
- configure difficulty
- configure whether the AI may end a call
- Global Rubric management
- Scenario Rubric management
- view all roleplay sessions
- view transcripts and evaluations
- view training analytics
- configure evaluator providers
- application settings
- Persona Lab after the core vertical flow is complete

### SALES

Represents a branch salesperson.

Capabilities:

- login
- access Training Dashboard when active
- view available scenarios
- see scenario difficulty
- use allowed persona-selection modes
- start live voice roleplay
- end roleplay
- view own results
- view own transcript
- view own history
- view own improvement trends

Restrictions:

- cannot create or edit personas
- cannot inspect hidden persona data
- cannot inspect Actor Instructions
- cannot inspect Director Notes or canonical Director State
- cannot configure AI providers
- cannot modify rubrics
- cannot view private sessions owned by another salesperson unless a future permission grants this

### Future BRANCH_SUPERVISOR

Not implemented initially.

Authorization architecture must be able to later support branch-scoped session review and analytics without rewriting all role checks.

## Authentication and Registration

Sales users authenticate using email and password.

Registration fields:

- `Nama Lengkap`
- `Email`
- `Password`
- `Konfirmasi Password`

Display helper copy:

> Gunakan nama lengkap Anda. Nama ini akan digunakan pada riwayat dan laporan training.

Account statuses:

- `PENDING_APPROVAL`
- `ACTIVE`
- `SUSPENDED`

Registration flow:

1. Salesperson registers.
2. User becomes `PENDING_APPROVAL`.
3. User sees a waiting-for-approval screen.
4. HQ sees the pending user.
5. HQ assigns a branch.
6. HQ approves the account.
7. Status becomes `ACTIVE`.
8. User can train.

A suspended user must receive an explicit explanation and cannot start a roleplay session.

Implement secure forgot-password and password-reset flows with expiring reset tokens.

## Persona Selection Modes

### CHOOSE_PERSONA

Salesperson chooses from personas allowed by the scenario.

May display limited public customer information:

- simulated name
- age or age range
- public short profile

Never expose hidden objections, hidden financial information, Director State, behavior sliders, or system instructions.

### RANDOM_PERSONA

Application selects one eligible persona server-side.

The public identity may be shown after selection.

The user cannot arbitrarily replace it without restarting the setup flow.

### HIDDEN_PERSONA

Application selects the persona privately.

Salesperson receives only realistic lead information, for example:

> Lead dari TikTok. Menanyakan cicilan rumah subsidi.

Hidden persona data must not be serialized to unauthorized frontend code.

## Scenario System

Persona means: **who the customer is**.

Scenario means: **what is happening**.

Example scenarios:

- first incoming call
- TikTok lead follow-up
- asking about KPR subsidi
- comparing developers
- worried about SLIK OJK
- installment feels expensive
- spouse has not agreed
- previously ghosted sales
- angry customer
- incorrect KPR information
- almost ready to book
- cold lead
- follow-up after site visit

Scenario configuration should include:

- name
- code
- description
- sales briefing
- hidden scenario context
- training objective
- starting conversation phase
- who speaks first
- AI opening context
- initial customer intent
- target salesperson behaviors
- important discovery points
- mandatory topics
- prohibited claims
- success conditions
- failure conditions
- difficulty
- product maximum duration, capped at 15 minutes
- `allow_ai_end_call`
- allowed persona-selection modes
- available personas
- Scenario Rubric

## Difficulty

Difficulty is visible to Sales users.

Levels:

- `BEGINNER`
- `NORMAL`
- `DIFFICULT`
- `EXPERT`
- `CUSTOM`

Difficulty affects actual Director rules and thresholds. It is not only a badge.

General intent:

### BEGINNER

- clearer concerns
- easier disclosure
- faster trust growth
- lower objection persistence

### NORMAL

- realistic resistance
- some discovery required
- generic answers are less effective

### DIFFICULT

- slower trust growth
- stronger objection persistence
- more hidden concerns
- weak explanations challenged

### EXPERT

- indirect concerns
- incomplete answers
- multiple tensions
- contradiction sensitivity
- low tolerance for scripts
- stronger closing resistance
- possible disengagement or AI-ended call when enabled

### CUSTOM

HQ explicitly configures Director modifiers.

## Maximum Session Duration

Product maximum: **15 minutes**.

At approximately 14 minutes, display a subtle time-limit indication.

At the maximum, gracefully finalize the current short conversational turn where technically practical, end the roleplay, finalize the transcript, and start evaluation.

`TIME_LIMIT` is a normal ending type.

Ending types:

- `USER_ENDED`
- `AI_ENDED`
- `TIME_LIMIT`
- `TECHNICAL_FAILURE`

## AI-Ended Calls

Scenario setting: `allow_ai_end_call`.

When disabled, the AI may express frustration or disengagement but cannot trigger the application ending flow.

When enabled, the Director may make the AI eligible to end a call for reasons such as:

- trust collapse
- very low engagement
- repeated concern dismissal
- repeated unsupported claims
- excessively aggressive closing
- repeated interruption
- significant boundary violation
- natural scenario-ending condition

The Director owns ending eligibility.

The Actor closes naturally and uses an internal end-request mechanism. The application validates Director eligibility before ending the call.

## Persona Lab

Secondary feature, Super Admin only.

Purpose: test whether a persona behaves as intended without creating an official Sales training record.

Persona Lab may expose:

- Director Events
- Dynamic State
- Director Notes
- objection transitions
- disclosure transitions
- boundary transitions

Do not delay the first complete vertical flow for Persona Lab.

## Out of Initial Scope

- CRM lead management
- sales transaction management
- property inventory
- training assignments from HQ
- fantasy gamification
- coins, loot boxes, character levels, XP systems
- Branch Supervisor UI
- explicit/erotic roleplay
- simulated physical assault scenarios
