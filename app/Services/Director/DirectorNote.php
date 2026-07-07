<?php

namespace App\Services\Director;

readonly class DirectorNote
{
    public function __construct(
        public string $text,
        public string $category,
        public int $priority = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'category' => $this->category,
            'priority' => $this->priority,
        ];
    }
}
