<?php

namespace App\Services\Snapshots;

readonly class SalienceSnapshot
{
    public function __construct(
        public array $primary,
        public array $secondary,
        public array $background,
    ) {}

    public function toArray(): array
    {
        return [
            'primary' => $this->primary,
            'secondary' => $this->secondary,
            'background' => $this->background,
        ];
    }
}
