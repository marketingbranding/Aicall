<?php

namespace App\Services\Personas;

use App\Models\PersonaVersion;

class PersonaSalienceCompiler
{
    const int MAX_PRIMARY = 3;
    const int MAX_SECONDARY = 3;

    private const array CONFLICT_MAP = [
        'friendliness' => ['dismissiveness', 'passive_aggression', 'social_superiority'],
        'skepticism' => ['trust_tendency'],
        'impulsiveness' => ['patience'],
        'anxiety_tendency' => ['social_confidence'],
        'dismissiveness' => ['politeness'],
        'dominance' => ['politeness'],
    ];

    private const array TRAIT_LABELS = [
        'Keramahan' => 'friendliness',
        'Keterbukaan' => 'openness',
        'Skeptis' => 'skepticism',
        'Kepercayaan' => 'trust_tendency',
        'Kesabaran' => 'patience',
        'Impulsif' => 'impulsiveness',
        'Banyak Bicara' => 'talkativeness',
        'Ketegasan' => 'assertiveness',
        'Rasa Ingin Tahu' => 'curiosity',
        'Kecemasan' => 'anxiety_tendency',
        'Kesopanan' => 'politeness',
        'Percaya Diri Sosial' => 'social_confidence',
        'Sensitivitas Finansial' => 'financial_sensitivity',
        'Menghindari Risiko' => 'risk_aversion',
        'Kepercayaan dalam Memutuskan' => 'decision_confidence',
        'Kecenderungan Menyela' => 'interrupting_tendency',
        'Dominan' => 'dominance',
        'Meremehkan' => 'dismissiveness',
        'Agresif Pasif' => 'passive_aggression',
        'Rasa Superior' => 'social_superiority',
        'Tidak Percaya Sales' => 'salesperson_distrust',
        'Ramah Palsu' => 'false_friendliness',
        'Menghindari Komitmen' => 'commitment_avoidance',
        'Suka Membantah' => 'contradiction_tendency',
        'Meminta Janji' => 'promise_extraction',
        'Pamer Status' => 'status_display',
        'Merendahkan (Usia)' => 'age_based_condescension',
        'Merendahkan (Gender)' => 'gender_based_condescension',
        'Menguji Batas Personal' => 'personal_boundary_testing',
        'Genit / Menggoda' => 'flirtatiousness',
        'Humor Tidak Pantas' => 'inappropriate_humor',
        'Sugestif' => 'suggestiveness',
        'Mencari Kontak Personal' => 'personal_contact_seeking',
        'Mencari Isolasi' => 'isolation_seeking',
    ];

    public function compile(PersonaVersion $version, ?array $scenarioRelevance = null): SalienceResult
    {
        $traits = $this->collectTraits($version);
        $overrides = $version->salience_overrides_json ?? [];

        $hasExplicitOverrides = $this->hasExplicitOverrides($overrides);

        if ($hasExplicitOverrides) {
            return $this->applyOverrides($traits, $overrides);
        }

        return $this->autoSelect($traits, $scenarioRelevance);
    }

    private function collectTraits(PersonaVersion $version): array
    {
        $profile = $version->personality_profile_json ?? [];
        $behaviors = $version->human_behavior_traits_json ?? [];
        $traits = [];

        foreach ($profile as $key => $value) {
            $intensity = (int) $value;
            if ($intensity > 0) {
                $traits[$key] = new SalientTrait($key, $intensity, 'personality_profile');
            }
        }

        foreach ($behaviors as $key => $value) {
            $intensity = (int) $value;
            $existing = $traits[$key] ?? null;
            if ($existing) {
                $intensity = max($existing->intensity, $intensity);
            }
            if ($intensity > 0) {
                $traits[$key] = new SalientTrait($key, $intensity, $existing ? 'both' : 'human_behavior_traits');
            }
        }

        return $traits;
    }

    private function hasExplicitOverrides(array $overrides): bool
    {
        return !empty($overrides['primary_traits'])
            || !empty($overrides['secondary_traits'])
            || !empty($overrides['background_traits']);
    }

    private function applyOverrides(array $traits, array $overrides): SalienceResult
    {
        $primary = [];
        $secondary = [];
        $background = [];
        $handledKeys = [];

        foreach ($overrides['primary_traits'] ?? [] as $entry) {
            $key = $this->resolveTraitKey($entry, $traits);
            if ($key !== null && isset($traits[$key])) {
                $primary[$key] = $traits[$key];
                $handledKeys[$key] = true;
            }
        }

        foreach ($overrides['secondary_traits'] ?? [] as $entry) {
            $key = $this->resolveTraitKey($entry, $traits);
            if ($key !== null && isset($traits[$key])) {
                $secondary[$key] = $traits[$key];
                $handledKeys[$key] = true;
            }
        }

        foreach ($overrides['background_traits'] ?? [] as $entry) {
            $key = $this->resolveTraitKey($entry, $traits);
            if ($key !== null && isset($traits[$key])) {
                $background[$key] = $traits[$key];
                $handledKeys[$key] = true;
            }
        }

        foreach ($traits as $key => $trait) {
            if (!isset($handledKeys[$key])) {
                $background[$key] = $trait;
            }
        }

        $primary = array_slice(array_values($primary), 0, self::MAX_PRIMARY);
        $secondary = array_slice(array_values($secondary), 0, self::MAX_SECONDARY);
        $background = array_values($background);

        return new SalienceResult($primary, $secondary, $background);
    }

    private function resolveTraitKey(string $entry, array $traits): ?string
    {
        $normalized = trim($entry);
        if ($normalized === '') {
            return null;
        }

        if (isset($traits[$normalized])) {
            return $normalized;
        }

        $lower = mb_strtolower($normalized);
        foreach ($traits as $key => $trait) {
            if (mb_strtolower($key) === $lower) {
                return $key;
            }
        }

        $labelKey = self::TRAIT_LABELS[$normalized] ?? null;
        if ($labelKey !== null && isset($traits[$labelKey])) {
            return $labelKey;
        }

        foreach (self::TRAIT_LABELS as $label => $traitKey) {
            if (mb_strtolower($label) === $lower && isset($traits[$traitKey])) {
                return $traitKey;
            }
        }

        return null;
    }

    private function autoSelect(array $traits, ?array $scenarioRelevance = null): SalienceResult
    {
        if (empty($traits)) {
            return new SalienceResult([], [], []);
        }

        $scored = $traits;
        if ($scenarioRelevance !== null) {
            $scored = $this->applyRelevance($scored, $scenarioRelevance);
        }

        $sorted = $this->sortTraits($scored);

        $primary = [];
        $secondary = [];
        $background = [];

        $pool = $sorted;
        $usedKeys = [];

        foreach ($pool as $trait) {
            if (count($primary) >= self::MAX_PRIMARY) {
                break;
            }
            if ($this->conflictsWith($trait->key, $primary)) {
                continue;
            }
            $primary[] = $trait;
            $usedKeys[$trait->key] = true;
        }

        foreach ($pool as $trait) {
            if (isset($usedKeys[$trait->key])) {
                continue;
            }
            if (count($secondary) >= self::MAX_SECONDARY) {
                break;
            }
            $secondary[] = $trait;
            $usedKeys[$trait->key] = true;
        }

        foreach ($pool as $trait) {
            if (!isset($usedKeys[$trait->key])) {
                $background[] = $trait;
            }
        }

        return new SalienceResult($primary, $secondary, $background);
    }

    private function applyRelevance(array $traits, array $relevance): array
    {
        $result = [];
        foreach ($traits as $key => $trait) {
            $multiplier = $relevance[$key] ?? 1.0;
            $boostedIntensity = min(100, (int) round($trait->intensity * $multiplier));
            $result[$key] = new SalientTrait($key, $boostedIntensity, $trait->source);
        }
        return $result;
    }

    private function sortTraits(array $traits): array
    {
        $list = array_values($traits);
        usort($list, fn (SalientTrait $a, SalientTrait $b) => $b->intensity <=> $a->intensity
            ?: strcmp($a->key, $b->key));
        return $list;
    }

    private function conflictsWith(string $key, array $selected): bool
    {
        $conflicts = self::CONFLICT_MAP[$key] ?? [];
        foreach ($selected as $st) {
            if (in_array($st->key, $conflicts, true)) {
                return true;
            }
            $reverseConflicts = self::CONFLICT_MAP[$st->key] ?? [];
            if (in_array($key, $reverseConflicts, true)) {
                return true;
            }
        }
        return false;
    }
}
