<?php

namespace App\Services\Personas;

readonly class SalienceResult
{
    public function __construct(
        public array $primary,
        public array $secondary,
        public array $background,
    ) {}
}
