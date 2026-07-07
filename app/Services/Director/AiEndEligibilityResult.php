<?php

namespace App\Services\Director;

readonly class AiEndEligibilityResult
{
    public function __construct(
        public bool $eligible,
        public string $reasonCode,
        public ?string $directorNote = null,
    ) {}

    public function toArray(): array
    {
        return [
            'eligible' => $this->eligible,
            'reason_code' => $this->reasonCode,
            'director_note' => $this->directorNote,
        ];
    }
}
