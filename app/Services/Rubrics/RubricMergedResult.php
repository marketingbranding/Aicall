<?php

namespace App\Services\Rubrics;

readonly class RubricMergedResult
{
    public function __construct(
        public array $items,
    ) {}

    public function toArray(): array
    {
        return array_map(fn(MergedRubricItem $item) => $item->toArray(), $this->items);
    }

    public function enabledItems(): array
    {
        return array_values(
            array_filter($this->items, fn(MergedRubricItem $item) => $item->isEnabled),
        );
    }
}
