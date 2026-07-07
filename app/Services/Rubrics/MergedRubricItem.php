<?php

namespace App\Services\Rubrics;

readonly class MergedRubricItem
{
    public function __construct(
        public string $key,
        public string $title,
        public ?string $description,
        public int $weight,
        public bool $isEnabled,
        public ?string $evaluationGuidance,
        public string $source,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'weight' => $this->weight,
            'is_enabled' => $this->isEnabled,
            'evaluation_guidance' => $this->evaluationGuidance,
            'source' => $this->source,
        ];
    }
}
