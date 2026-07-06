<?php

namespace App\Domain\LiveVoice;

use InvalidArgumentException;

final class LiveModelCapabilityRegistry
{
    public function configured(): LiveModelCapabilities
    {
        return $this->forModel((string) config('gemini.live.model'));
    }

    public function forModel(string $modelId): LiveModelCapabilities
    {
        $models = config('gemini.live.models', []);

        if (! is_array($models) || ! isset($models[$modelId]) || ! is_array($models[$modelId])) {
            throw new InvalidArgumentException("Gemini Live model [{$modelId}] is not registered in the capability registry.");
        }

        return new LiveModelCapabilities($modelId, $models[$modelId]);
    }
}
