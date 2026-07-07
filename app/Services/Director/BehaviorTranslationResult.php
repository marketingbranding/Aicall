<?php

namespace App\Services\Director;

readonly class BehaviorTranslationResult
{
    /** @var array<string, StateBand> */
    public array $bands;

    public function __construct(
        array $bands,
        public string $qualitativeText,
        public string $directorNoteSuggestion,
    ) {
        $normalized = [];
        foreach ($bands as $key => $band) {
            $normalized[$key] = $band instanceof StateBand ? $band : StateBand::from($band);
        }
        $this->bands = $normalized;
    }

    public function toArray(): array
    {
        $bands = [];
        foreach ($this->bands as $key => $band) {
            $bands[$key] = $band->value;
        }
        return [
            'bands' => $bands,
            'qualitative_text' => $this->qualitativeText,
            'director_note_suggestion' => $this->directorNoteSuggestion,
        ];
    }
}
