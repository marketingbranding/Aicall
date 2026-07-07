<?php

namespace App\Services\Snapshots;

readonly class ScenarioSnapshot
{
    public function __construct(
        public string $scenarioKey,
        public string $name,
        public int $versionNumber,
        public string $description,
        public ?string $salesBriefing,
        public ?string $trainingObjective,
        public ?string $hiddenContext,
        public string $startingPhase,
        public string $firstSpeaker,
        public ?string $aiOpeningContext,
        public ?string $initialCustomerIntent,
        public array $targetBehaviors,
        public array $importantDiscoveryPoints,
        public array $mandatoryTopics,
        public array $prohibitedClaims,
        public array $successConditions,
        public array $failureConditions,
        public string $difficultyLevel,
        public ?array $difficultyConfig,
        public int $maxDurationSeconds,
        public bool $allowAiEndCall,
        public array $allowedPersonaModes,
    ) {}

    public function toArray(): array
    {
        return [
            'scenario_key' => $this->scenarioKey,
            'name' => $this->name,
            'version_number' => $this->versionNumber,
            'description' => $this->description,
            'sales_briefing' => $this->salesBriefing,
            'training_objective' => $this->trainingObjective,
            'hidden_context' => $this->hiddenContext,
            'starting_phase' => $this->startingPhase,
            'first_speaker' => $this->firstSpeaker,
            'ai_opening_context' => $this->aiOpeningContext,
            'initial_customer_intent' => $this->initialCustomerIntent,
            'target_behaviors' => $this->targetBehaviors,
            'important_discovery_points' => $this->importantDiscoveryPoints,
            'mandatory_topics' => $this->mandatoryTopics,
            'prohibited_claims' => $this->prohibitedClaims,
            'success_conditions' => $this->successConditions,
            'failure_conditions' => $this->failureConditions,
            'difficulty_level' => $this->difficultyLevel,
            'difficulty_config' => $this->difficultyConfig,
            'max_duration_seconds' => $this->maxDurationSeconds,
            'allow_ai_end_call' => $this->allowAiEndCall,
            'allowed_persona_modes' => $this->allowedPersonaModes,
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'scenario_key' => $this->scenarioKey,
            'name' => $this->name,
            'version_number' => $this->versionNumber,
            'description' => $this->description,
            'sales_briefing' => $this->salesBriefing,
            'training_objective' => $this->trainingObjective,
            'starting_phase' => $this->startingPhase,
            'first_speaker' => $this->firstSpeaker,
            'difficulty_level' => $this->difficultyLevel,
            'max_duration_seconds' => $this->maxDurationSeconds,
        ];
    }
}
