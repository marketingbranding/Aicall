# Database Schema Specification

## Principles

- MySQL/MariaDB compatible.
- Use UUID/ULID for public/session-facing identifiers where useful.
- Use numeric primary keys or ULIDs consistently; do not mix styles without reason.
- Use foreign keys and indexes.
- Use explicit columns for frequently queried business data.
- Use JSON for immutable snapshots and genuinely flexible provider/model payloads.
- Do not persist Director State after every audio packet.
- Preserve historical session integrity.

The exact migration names may differ. The logical relationships below are authoritative.

## branches

Suggested fields:

- `id`
- `code` unique
- `name`
- `is_active`
- timestamps

## users

Suggested fields:

- `id`
- `branch_id` nullable FK
- `name`
- `email` unique
- `email_verified_at` nullable
- `password`
- `role`
- `status`
- `approved_at` nullable
- `approved_by` nullable FK users
- `remember_token`
- timestamps

Role values initially:

- `SUPER_ADMIN`
- `SALES`

Account status:

- `PENDING_APPROVAL`
- `ACTIVE`
- `SUSPENDED`

Do not make `branch_id` mandatory for Super Admin.

## personas

Represents a logical persona identity.

Fields:

- `id`
- `code` unique
- `name`
- `status` (`ACTIVE`, `ARCHIVED`)
- `current_version_id` nullable
- `created_by`
- timestamps

## persona_versions

Immutable versioned configuration.

Fields:

- `id`
- `persona_id`
- `version_number`
- `public_profile_text` nullable
- `identity_json`
- `housing_context_json`
- `knowledge_beliefs_json`
- `personality_profile_json`
- `human_behavior_traits_json`
- `communication_style_json`
- `initial_dynamic_state_json`
- `state_sensitivity_json`
- `salience_overrides_json` nullable
- `created_by`
- `created_at`

Unique:

- `(persona_id, version_number)`

Version rows are immutable after publication/use. Editing a persona creates a new version.

## persona_objections

Version-bound objection definitions.

Fields:

- `id`
- `persona_version_id`
- `key`
- `title`
- `context`
- `visibility`
- `severity`
- `emotional_importance`
- `trigger_conditions_json`
- `disclosure_conditions_json`
- `resolution_conditions_json`
- `persistence`
- `is_resolvable`
- timestamps

Unique:

- `(persona_version_id, key)`

Visibility:

- `VISIBLE`
- `HIDDEN`

## persona_hidden_information

Fields:

- `id`
- `persona_version_id`
- `key`
- `information`
- `sensitivity`
- `disclosure_difficulty`
- `relevant_topics_json`
- `direct_question_effectiveness`
- `trust_requirement`
- `disclosure_conditions_json`
- timestamps

Unique:

- `(persona_version_id, key)`

## scenarios

Logical scenario identity.

Fields:

- `id`
- `code` unique
- `name`
- `status`
- `current_version_id` nullable
- `created_by`
- timestamps

## scenario_versions

Immutable scenario versions.

Fields:

- `id`
- `scenario_id`
- `version_number`
- `description`
- `sales_briefing`
- `hidden_context`
- `training_objective`
- `starting_phase`
- `first_speaker`
- `ai_opening_context` nullable
- `initial_customer_intent`
- `target_behaviors_json`
- `important_discovery_points_json`
- `mandatory_topics_json`
- `prohibited_claims_json`
- `success_conditions_json`
- `failure_conditions_json`
- `difficulty_level`
- `difficulty_config_json`
- `max_duration_seconds`
- `allow_ai_end_call`
- `allowed_persona_modes_json`
- `created_by`
- `created_at`

Constraints:

- `max_duration_seconds <= 900`

First speaker:

- `AI`
- `USER`

Persona modes:

- `CHOOSE_PERSONA`
- `RANDOM_PERSONA`
- `HIDDEN_PERSONA`

## scenario_personas

Fields:

- `id`
- `scenario_version_id`
- `persona_id`
- `is_enabled`
- optional `weight`
- timestamps

The session resolves the persona's current version when creating the session snapshot.

## evaluation_rubrics

Fields:

- `id`
- `name`
- `type` (`GLOBAL`, `SCENARIO`)
- `scenario_version_id` nullable
- `version_number`
- `is_active`
- `created_by`
- timestamps

## evaluation_rubric_items

Fields:

- `id`
- `evaluation_rubric_id`
- `key`
- `title`
- `description`
- `weight`
- `is_enabled`
- `evaluation_guidance` nullable
- timestamps

Unique:

- `(evaluation_rubric_id, key)`

## scenario_rubric_overrides

Optional explicit global-weight override per scenario rubric/version.

Fields:

- `id`
- `scenario_version_id`
- `global_rubric_item_key`
- `weight_override`
- `is_enabled_override` nullable

## roleplay_sessions

Fields:

- `id`
- `public_id` unique
- `user_id`
- `branch_id`
- `scenario_id`
- `persona_id`
- `persona_mode`
- `difficulty_level`
- `status`
- `started_at` nullable
- `ended_at` nullable
- `duration_seconds` nullable
- `ending_type` nullable
- `ending_reason` nullable
- `transcript_integrity`
- `evaluation_status`
- `director_version`
- `correlation_id` unique
- timestamps

Session states:

- `CREATED`
- `PREPARING`
- `REQUESTING_MICROPHONE`
- `CONNECTING`
- `READY`
- `ACTIVE`
- `RECONNECTING`
- `ENDING`
- `TRANSCRIPT_FINALIZING`
- `EVALUATING`
- `COMPLETED`
- `FAILED`

Transcript integrity:

- `COMPLETE`
- `PARTIAL`
- `FAILED`

Evaluation status:

- `PENDING`
- `PROCESSING`
- `COMPLETED`
- `FAILED`

## roleplay_session_snapshots

One effective immutable configuration snapshot per session.

Fields:

- `id`
- `roleplay_session_id` unique
- `persona_version_id`
- `scenario_version_id`
- `persona_snapshot_json`
- `scenario_snapshot_json`
- `difficulty_snapshot_json`
- `salience_snapshot_json`
- `rubric_snapshot_json`
- `director_config_snapshot_json`
- `actor_instruction_hash`
- optional encrypted/private `actor_instructions`
- created timestamp

Actor Instructions must never be exposed to Sales endpoints.

## roleplay_dynamic_states

Persist meaningful state snapshots, not every packet.

Fields:

- `id`
- `roleplay_session_id`
- `sequence`
- `trigger_event_id` nullable
- `state_json`
- `created_at`

State JSON initially contains bounded values for:

- trust
- interest
- confusion
- anxiety
- irritation
- pressure_perception
- engagement

Plus compact state-machine references if appropriate.

## roleplay_events

Fields:

- `id`
- `roleplay_session_id`
- `sequence`
- `source`
- `event_type`
- `severity`
- `topic` nullable
- `related_objection_key` nullable
- `short_internal_reason` nullable
- `fingerprint` nullable
- `transcript_sequence_start` nullable
- `transcript_sequence_end` nullable
- `payload_json` nullable
- `accepted`
- `rejection_reason` nullable
- `created_at`

Sources:

- `APPLICATION`
- `GEMINI_TOOL`
- future `CHECKPOINT_CLASSIFIER`

Index:

- `(roleplay_session_id, sequence)`
- `(roleplay_session_id, event_type)`
- fingerprint where useful

## roleplay_objection_states

Fields:

- `id`
- `roleplay_session_id`
- `objection_key`
- `state`
- `last_transition_event_id` nullable
- `updated_at`

States:

- `DORMANT`
- `ACTIVE_HIDDEN`
- `ACTIVE_VISIBLE`
- `ACKNOWLEDGED`
- `PARTIALLY_RESOLVED`
- `RESOLVED`
- `REACTIVATED`

Unique:

- `(roleplay_session_id, objection_key)`

## roleplay_disclosure_states

Fields:

- `id`
- `roleplay_session_id`
- `hidden_information_key`
- `state`
- `last_transition_event_id` nullable
- `updated_at`

States:

- `LOCKED`
- `ELIGIBLE`
- `DISCLOSED_PARTIAL`
- `DISCLOSED_FULL`

## roleplay_boundary_states

One current boundary state per session or a state table plus transition log.

Current states:

- `NOT_TESTED`
- `MILD_TEST_OCCURRED`
- `SALESPERSON_PARTICIPATED`
- `INDIRECTLY_REDIRECTED`
- `CLEAR_BOUNDARY_ESTABLISHED`
- `CUSTOMER_RESPECTED_BOUNDARY`
- `CUSTOMER_RETESTED_BOUNDARY`
- `SIGNIFICANT_VIOLATION`
- `PROFESSIONAL_TERMINATION_ELIGIBLE`

## director_notes

Fields:

- `id`
- `roleplay_session_id`
- `priority`
- `note_type`
- `content`
- `trigger_event_id` nullable
- `sent_at` nullable
- `suppressed_at` nullable
- `suppression_reason` nullable
- `created_at`

Sales endpoints must never return Director Note content.

## transcript_turns

Fields:

- `id`
- `roleplay_session_id`
- `sequence`
- `speaker`
- `text`
- `start_offset_ms` nullable
- `end_offset_ms` nullable
- `was_interrupted`
- `source_event_id` nullable
- timestamps

Speakers:

- `USER`
- `AI`

Unique:

- `(roleplay_session_id, sequence)`

Canonical turns should be final. Store partial transcription only in ephemeral/client buffer or a separate staging representation if server recovery requires it.

## session_evaluations

Fields:

- `id`
- `roleplay_session_id` unique
- `status`
- `provider_type` nullable
- `model_id` nullable
- `overall_score` nullable
- `summary` nullable
- `processing_duration_ms` nullable
- `retry_count`
- `error_category` nullable
- `error_message_safe` nullable
- `schema_version`
- `raw_response_private` nullable
- `completed_at` nullable
- timestamps

## evaluation_score_items

Fields:

- `id`
- `session_evaluation_id`
- `rubric_key`
- `title`
- `score`
- `weight`
- `reason`
- timestamps

## evaluation_findings

Fields:

- `id`
- `session_evaluation_id`
- `type`
- `title`
- `explanation`
- `better_approach` nullable
- `transcript_sequences_json`
- `sort_order`
- timestamps

Finding types include:

- `STRENGTH`
- `FACTUAL_MISINFORMATION`
- `SERIOUS_SALES_MISTAKE`
- `WEAK_TECHNIQUE`
- `MISSED_OPPORTUNITY`
- `STYLE_PREFERENCE`
- `NEXT_TRAINING_FOCUS`

## ai_provider_configs

Do not store plaintext production API secrets.

Fields:

- `id`
- `name`
- `provider_type`
- `model_id`
- `is_enabled`
- `priority`
- `timeout_seconds`
- `maximum_retries`
- `secret_reference` nullable
- `options_json` nullable
- timestamps

Provider types initially:

- `OPENROUTER`
- `GROQ`
- `GEMINI`

## application_settings

Store non-secret configurable application settings.

Fields:

- `id`
- `key` unique
- `value_json`
- timestamps

Examples:

- configured Gemini Live model ID
- default global rubric ID/version
- Director ruleset version

## Password Reset Tables

Use Laravel's official authentication/password-reset schema appropriate to the installed Laravel version.

## Data Retention

Initial rule:

- do not persist raw voice audio by default
- persist transcript and evaluation
- roleplay snapshot data is internal training data

Any future audio-recording feature requires an explicit privacy and retention specification.
