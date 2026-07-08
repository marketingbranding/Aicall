<?php

namespace App\Services\Transcript;

use App\Enums\TranscriptIntegrity;
use App\Models\RoleplayTranscriptTurn;

readonly class TranscriptAssemblyResult
{
    /** @param RoleplayTranscriptTurn[] $turns */
    public function __construct(
        public TranscriptIntegrity $integrity,
        public array $turns,
        public array $issues,
        public array $interruptedTurns,
    ) {}

    public function isComplete(): bool
    {
        return $this->integrity === TranscriptIntegrity::COMPLETE;
    }

    public function toArray(): array
    {
        return [
            'integrity' => $this->integrity->value,
            'turn_count' => count($this->turns),
            'issues' => $this->issues,
            'interrupted_turns' => $this->interruptedTurns,
            'is_complete' => $this->isComplete(),
        ];
    }
}
