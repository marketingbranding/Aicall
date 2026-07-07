<?php

namespace App\Services\Personas;

readonly class SalientTrait
{
    public function __construct(
        public string $key,
        public int $intensity,
        public string $source,
    ) {}
}
