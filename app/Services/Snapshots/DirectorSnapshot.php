<?php

namespace App\Services\Snapshots;

readonly class DirectorSnapshot
{
    public function __construct(
        public array $initialState,
        public array $difficultyValues,
        public array $objectionConfig,
        public array $hiddenInfoConfig,
        public array $boundaryConfig,
        public string $initialPhase,
    ) {}

    public function toArray(): array
    {
        return [
            'initial_state' => $this->initialState,
            'difficulty_values' => $this->difficultyValues,
            'objection_config' => $this->objectionConfig,
            'hidden_info_config' => $this->hiddenInfoConfig,
            'boundary_config' => $this->boundaryConfig,
            'initial_phase' => $this->initialPhase,
        ];
    }
}
