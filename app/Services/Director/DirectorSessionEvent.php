<?php

namespace App\Services\Director;

readonly class DirectorSessionEvent
{
    public function __construct(
        public string $category,
        public string $description,
        public ?int $turn = null,
    ) {}

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'description' => $this->description,
            'turn' => $this->turn,
        ];
    }
}
