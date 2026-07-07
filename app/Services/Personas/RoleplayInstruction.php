<?php

namespace App\Services\Personas;

readonly class RoleplayInstruction
{
    public function __construct(
        public string $actorPersona,
        public string $conversationalRole,
        public string $primaryBehavior,
        public string $secondaryBehavior,
        public string $backgroundBehavior,
        public string $customerContext,
        public string $knowledgeAndMisconceptions,
        public string $currentScenario,
        public string $conversationalRules,
        public string $directorRules,
        public string $guardrails,
    ) {}

    public function toText(): string
    {
        return implode("\n\n", array_filter([
            "=== AKTOR PERSONA ===\n" . $this->actorPersona,
            "=== PERAN DALAM PERCAKAPAN ===\n" . $this->conversationalRole,
            "=== PERILAKU UTAMA ===\n" . $this->primaryBehavior,
            "=== PERILAKU SEKUNDER ===\n" . $this->secondaryBehavior,
            $this->backgroundBehavior ? "=== PERILAKU LATAR BELAKANG ===\n" . $this->backgroundBehavior : null,
            "=== KONTEKS KONSUMEN ===\n" . $this->customerContext,
            "=== PENGETAHUAN DAN KEYAKINAN ===\n" . $this->knowledgeAndMisconceptions,
            "=== SKENARIO SAAT INI ===\n" . $this->currentScenario,
            "=== ATURAN PERCAKAPAN ===\n" . $this->conversationalRules,
            "=== ATURAN DIRECTOR NOTES ===\n" . $this->directorRules,
            "=== PENGAMAN ===\n" . $this->guardrails,
        ]));
    }

    public function hasSection(string $sectionHeader): bool
    {
        return str_contains($this->toText(), "=== $sectionHeader ===");
    }
}
