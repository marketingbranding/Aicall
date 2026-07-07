<?php

namespace App\Services\Director;

readonly class DirectorSessionSummary
{
    /** @param DirectorSessionEvent[] $events */
    public function __construct(
        public array $events,
        public ?string $aiEndingReason = null,
        public ?string $aiEndingNote = null,
    ) {}

    public function toArray(): array
    {
        return [
            'events' => array_map(fn(DirectorSessionEvent $e) => $e->toArray(), $this->events),
            'ai_ending_reason' => $this->aiEndingReason,
            'ai_ending_note' => $this->aiEndingNote,
        ];
    }
}
