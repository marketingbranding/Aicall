<?php

namespace App\Services\Snapshots;

readonly class PersonaSnapshot
{
    public function __construct(
        public string $personaKey,
        public string $name,
        public int $versionNumber,
        public array $identity,
        public array $housingContext,
        public array $knowledgeBeliefs,
        public array $misconceptions,
        public array $personalityProfile,
        public array $humanBehaviorTraits,
        public array $communicationStyle,
        public array $initialDynamicState,
        public array $stateSensitivity,
        public array $objections,
        public array $hiddenInformation,
        public array $salienceOverrides,
    ) {}

    public function toArray(): array
    {
        return [
            'persona_key' => $this->personaKey,
            'name' => $this->name,
            'version_number' => $this->versionNumber,
            'identity' => $this->identity,
            'housing_context' => $this->housingContext,
            'knowledge_beliefs' => $this->knowledgeBeliefs,
            'misconceptions' => $this->misconceptions,
            'personality_profile' => $this->personalityProfile,
            'human_behavior_traits' => $this->humanBehaviorTraits,
            'communication_style' => $this->communicationStyle,
            'initial_dynamic_state' => $this->initialDynamicState,
            'state_sensitivity' => $this->stateSensitivity,
            'objections' => $this->objections,
            'hidden_information' => $this->hiddenInformation,
            'salience_overrides' => $this->salienceOverrides,
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'persona_key' => $this->personaKey,
            'name' => $this->name,
            'version_number' => $this->versionNumber,
            'identity' => $this->identity,
            'housing_context' => $this->housingContext,
            'knowledge_beliefs' => $this->knowledgeBeliefs,
            'misconceptions' => $this->misconceptions,
            'personality_profile' => $this->personalityProfile,
            'communication_style' => $this->communicationStyle,
        ];
    }
}
