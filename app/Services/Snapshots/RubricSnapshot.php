<?php

namespace App\Services\Snapshots;

readonly class RubricSnapshot
{
    public function __construct(
        public array $items,
    ) {}

    public function toArray(): array
    {
        return [
            'items' => $this->items,
        ];
    }
}
