# Entity-Relationship Diagram

```mermaid
erDiagram
    branches {
        bigint id PK
        varchar code UK
        varchar name
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    users {
        bigint id PK
        bigint branch_id FK nullable
        varchar name
        varchar email UK
        timestamp email_verified_at nullable
        varchar password
        enum role "SUPER_ADMIN | SALES"
        enum status "PENDING_APPROVAL | ACTIVE | SUSPENDED"
        timestamp approved_at nullable
        bigint approved_by FK nullable
        varchar remember_token nullable
        timestamp created_at
        timestamp updated_at
    }

    personas {
        bigint id PK
        varchar code UK
        varchar name
        enum status "ACTIVE | ARCHIVED"
        bigint current_version_id FK nullable
        bigint created_by FK nullable
        timestamp created_at
        timestamp updated_at
    }

    persona_versions {
        bigint id PK
        bigint persona_id FK
        int version_number
        text public_profile_text nullable
        json identity_json
        json housing_context_json
        json knowledge_beliefs_json
        json personality_profile_json
        json human_behavior_traits_json
        json communication_style_json
        json initial_dynamic_state_json
        json state_sensitivity_json
        json salience_overrides_json nullable
        bigint created_by FK nullable
        timestamp created_at
        UK (persona_id, version_number)
    }

    persona_objections {
        bigint id PK
        bigint persona_version_id FK
        varchar key
        varchar title
        text context nullable
        enum visibility "VISIBLE | HIDDEN"
        int severity nullable
        int emotional_importance nullable
        json trigger_conditions_json nullable
        json disclosure_conditions_json nullable
        json resolution_conditions_json nullable
        int persistence nullable
        boolean is_resolvable
        boolean is_active
        timestamp created_at
        timestamp updated_at
        UK (persona_version_id, key)
    }

    persona_hidden_information {
        bigint id PK
        bigint persona_version_id FK
        varchar key
        varchar title
        text information nullable
        int sensitivity
        int disclosure_difficulty
        json relevant_topics_json nullable
        int direct_question_effectiveness
        int trust_requirement
        json disclosure_conditions_json nullable
        boolean is_active
        timestamp created_at
        timestamp updated_at
        UK (persona_version_id, key)
    }

    scenarios {
        bigint id PK
        varchar code UK
        varchar name
        enum status "ACTIVE | ARCHIVED"
        bigint current_version_id FK nullable
        bigint created_by FK nullable
        timestamp created_at
        timestamp updated_at
    }

    scenario_versions {
        bigint id PK
        bigint scenario_id FK
        int version_number
        text description nullable
        text sales_briefing nullable
        text hidden_context nullable
        text training_objective nullable
        varchar starting_phase nullable
        varchar first_speaker
        text ai_opening_context nullable
        text initial_customer_intent nullable
        json target_behaviors_json nullable
        json important_discovery_points_json nullable
        json mandatory_topics_json nullable
        json prohibited_claims_json nullable
        json success_conditions_json nullable
        json failure_conditions_json nullable
        varchar difficulty_level
        json difficulty_config_json nullable
        int max_duration_seconds nullable
        boolean allow_ai_end_call
        json allowed_persona_modes_json nullable
        bigint created_by FK nullable
        timestamp created_at
        UK (scenario_id, version_number)
    }

    scenario_personas {
        bigint id PK
        bigint scenario_version_id FK
        bigint persona_id FK
        boolean is_enabled
        int weight nullable
        timestamp created_at
        timestamp updated_at
        UK (scenario_version_id, persona_id)
    }

    evaluation_rubrics {
        bigint id PK
        varchar name
        varchar type "GLOBAL | SCENARIO"
        bigint scenario_version_id FK nullable
        int version_number
        boolean is_active
        bigint created_by FK nullable
        timestamp created_at
        timestamp updated_at
    }

    evaluation_rubric_items {
        bigint id PK
        bigint evaluation_rubric_id FK
        varchar key
        varchar title
        text description nullable
        int weight
        boolean is_enabled
        text evaluation_guidance nullable
        timestamp created_at
        timestamp updated_at
        UK (evaluation_rubric_id, key)
    }

    scenario_rubric_overrides {
        bigint id PK
        bigint scenario_version_id FK
        varchar global_rubric_item_key
        int weight_override nullable
        boolean is_enabled_override nullable
        timestamp created_at
        timestamp updated_at
        UK (scenario_version_id, global_rubric_item_key)
    }

    roleplay_sessions {
        bigint id PK
        uuid public_id UK
        bigint user_id FK
        bigint branch_id FK
        bigint scenario_id FK
        bigint persona_id FK
        enum persona_mode "CHOOSE_PERSONA | RANDOM_PERSONA | HIDDEN_PERSONA"
        varchar difficulty_level
        enum status
        timestamp started_at nullable
        timestamp ended_at nullable
        int duration_seconds nullable
        enum ending_type nullable
        text ending_reason nullable
        enum transcript_integrity
        enum evaluation_status
        varchar director_version
        uuid correlation_id UK
        timestamp created_at
        timestamp updated_at
    }

    roleplay_session_snapshots {
        bigint id PK
        bigint roleplay_session_id FK UK
        bigint persona_version_id FK
        bigint scenario_version_id FK
        json persona_snapshot_json
        json scenario_snapshot_json
        json difficulty_snapshot_json
        json salience_snapshot_json
        json rubric_snapshot_json
        json director_config_snapshot_json
        varchar actor_instruction_hash
        text actor_instructions nullable
        timestamp created_at
    }

    roleplay_dynamic_states {
        bigint id PK
        bigint roleplay_session_id FK
        int sequence
        bigint trigger_event_id nullable
        json state_json
        timestamp created_at
    }

    roleplay_events {
        bigint id PK
        bigint roleplay_session_id FK
        int sequence
        enum source "APPLICATION | GEMINI_TOOL"
        enum event_type
        enum severity nullable
        varchar topic nullable
        varchar related_objection_key nullable
        varchar short_internal_reason nullable
        varchar fingerprint nullable
        int transcript_sequence_start nullable
        int transcript_sequence_end nullable
        json payload_json nullable
        boolean accepted
        varchar rejection_reason nullable
        timestamp created_at
        INDEX (roleplay_session_id, sequence)
        INDEX (roleplay_session_id, event_type)
    }

    roleplay_objection_states {
        bigint id PK
        bigint roleplay_session_id FK
        varchar objection_key
        enum state
        bigint last_transition_event_id nullable
        timestamp updated_at
        UK (roleplay_session_id, objection_key)
    }

    roleplay_disclosure_states {
        bigint id PK
        bigint roleplay_session_id FK
        varchar hidden_information_key
        enum state
        bigint last_transition_event_id nullable
        timestamp updated_at
    }

    roleplay_boundary_states {
        bigint id PK
        bigint roleplay_session_id FK
        enum state
        bigint last_transition_event_id nullable
        timestamp updated_at
    }

    director_notes {
        bigint id PK
        bigint roleplay_session_id FK
        enum priority
        enum note_type
        text content
        bigint trigger_event_id nullable
        timestamp sent_at nullable
        timestamp suppressed_at nullable
        varchar suppression_reason nullable
        timestamp created_at
    }

    transcript_turns {
        bigint id PK
        bigint roleplay_session_id FK
        int sequence
        enum speaker "USER | AI"
        text text
        int start_offset_ms nullable
        int end_offset_ms nullable
        boolean was_interrupted
        bigint source_event_id nullable
        timestamp created_at
        timestamp updated_at
        UK (roleplay_session_id, sequence)
    }

    session_evaluations {
        bigint id PK
        bigint roleplay_session_id FK UK
        enum status
        varchar provider_type nullable
        varchar model_id nullable
        int overall_score nullable
        text summary nullable
        int processing_duration_ms nullable
        int retry_count
        varchar error_category nullable
        text error_message_safe nullable
        varchar schema_version
        text raw_response_private nullable
        timestamp completed_at nullable
        timestamp created_at
        timestamp updated_at
    }

    evaluation_score_items {
        bigint id PK
        bigint session_evaluation_id FK
        varchar rubric_key
        varchar title
        int score
        int weight
        text reason nullable
        timestamp created_at
        timestamp updated_at
    }

    evaluation_findings {
        bigint id PK
        bigint session_evaluation_id FK
        enum type
        varchar title
        text explanation
        text better_approach nullable
        json transcript_sequences_json
        int sort_order
        timestamp created_at
        timestamp updated_at
    }

    ai_provider_configs {
        bigint id PK
        varchar name
        enum provider_type "OPENROUTER | GROQ | GEMINI"
        varchar model_id
        boolean is_enabled
        int priority
        int timeout_seconds
        int maximum_retries
        varchar secret_reference nullable
        json options_json nullable
        timestamp created_at
        timestamp updated_at
    }

    application_settings {
        bigint id PK
        varchar key UK
        json value_json
        timestamp created_at
        timestamp updated_at
    }

    password_reset_tokens {
        varchar email PK
        varchar token
        timestamp created_at nullable
    }

    personal_access_tokens {
        bigint id PK
        varchar tokenable_type
        bigint tokenable_id
        varchar name
        varchar token UK
        text abilities nullable
        timestamp last_used_at nullable
        timestamp expires_at nullable
        timestamp created_at
        timestamp updated_at
        INDEX (tokenable_type, tokenable_id)
    }

    failed_jobs {
        bigint id PK
        varchar uuid UK
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at
    }

    job_batches {
        varchar id PK
        varchar name
        int total_jobs
        int pending_jobs
        int failed_jobs
        longtext failed_job_ids
        longtext options nullable
        int cancelled_at nullable
        int created_at
        int finished_at nullable
    }

    jobs {
        bigint id PK
        varchar queue
        longtext payload
        int attempts
        int reserved_at nullable
        int available_at
        int created_at
        INDEX (queue, reserved_at)
    }

    cache {
        varchar key PK
        mediumtext value
        int expiration
    }

    cache_locks {
        varchar key PK
        varchar owner
        int expiration
    }

    sessions {
        varchar id PK
        bigint user_id nullable
        varchar ip_address nullable
        text user_agent nullable
        longtext payload
        int last_activity
        INDEX (user_id)
        INDEX (last_activity)
    }

    notifications {
        char id PK
        varchar type
        morphs notifiable
        text data
        timestamp read_at nullable
        timestamp created_at
        timestamp updated_at
    }

    %% Relationships
    users ||--o{ branches : "belongs to"
    users ||--o{ users : "approved by"
    users ||--o{ personas : "created by"
    users ||--o{ persona_versions : "created by"
    users ||--o{ scenarios : "created by"
    users ||--o{ scenario_versions : "created by"
    users ||--o{ evaluation_rubrics : "created by"

    personas ||--o| personas : "current version"
    personas ||--o{ persona_versions : "has versions"
    persona_versions ||--o{ persona_objections : "has objections"
    persona_versions ||--o{ persona_hidden_information : "has hidden info"

    scenarios ||--o| scenarios : "current version"
    scenarios ||--o{ scenario_versions : "has versions"
    scenario_versions ||--o{ scenario_personas : "assigned personas"
    scenario_versions ||--o{ scenario_rubric_overrides : "rubric overrides"
    scenario_personas ||--o{ personas : "references"

    evaluation_rubrics ||--o{ evaluation_rubric_items : "has items"
    evaluation_rubrics ||--o| scenario_versions : "scenario version (nullable)"

    scenario_rubric_overrides ||--o| scenario_versions : "references"
    scenario_rubric_overrides ||--o{ evaluation_rubric_items : "references by key"

    roleplay_sessions ||--o{ users : "user"
    roleplay_sessions ||--o{ branches : "branch"
    roleplay_sessions ||--o{ scenarios : "scenario"
    roleplay_sessions ||--o{ personas : "persona"
    roleplay_sessions ||--o| roleplay_session_snapshots : "has one snapshot"
    roleplay_sessions ||--o{ roleplay_dynamic_states : "dynamic states"
    roleplay_sessions ||--o{ roleplay_events : "events"
    roleplay_sessions ||--o{ roleplay_objection_states : "objection states"
    roleplay_sessions ||--o{ roleplay_disclosure_states : "disclosure states"
    roleplay_sessions ||--o{ roleplay_boundary_states : "boundary states"
    roleplay_sessions ||--o{ director_notes : "director notes"
    roleplay_sessions ||--o{ transcript_turns : "transcript turns"
    roleplay_sessions ||--o| session_evaluations : "evaluation"

    roleplay_session_snapshots ||--o{ persona_versions : "persona version"
    roleplay_session_snapshots ||--o{ scenario_versions : "scenario version"

    session_evaluations ||--o{ evaluation_score_items : "score items"
    session_evaluations ||--o{ evaluation_findings : "findings"
```
