<?php

namespace App\Services\Scenarios;

use App\Models\ScenarioVersion;
use Illuminate\Http\Request;

class ScenarioBuilderService
{
    public function buildTargetBehaviors(Request $request): array
    {
        return $this->parseCommaList($request->input('target_behaviors_text'));
    }

    public function buildDiscoveryPoints(Request $request): array
    {
        return $this->parseCommaList($request->input('discovery_points_text'));
    }

    public function buildMandatoryTopics(Request $request): array
    {
        return $this->parseCommaList($request->input('mandatory_topics_text'));
    }

    public function buildProhibitedClaims(Request $request): array
    {
        return $this->parseCommaList($request->input('prohibited_claims_text'));
    }

    public function buildSuccessConditions(Request $request): array
    {
        return $this->parseCommaList($request->input('success_conditions_text'));
    }

    public function buildFailureConditions(Request $request): array
    {
        return $this->parseCommaList($request->input('failure_conditions_text'));
    }

    public function buildDifficultyConfig(Request $request): array
    {
        $data = $request->input('difficulty_config', []);

        return array_filter([
            'trust_gain_multiplier' => $data['trust_gain_multiplier'] ?? null,
            'trust_loss_multiplier' => $data['trust_loss_multiplier'] ?? null,
            'disclosure_resistance' => $data['disclosure_resistance'] ?? null,
            'objection_persistence' => $data['objection_persistence'] ?? null,
            'irritation_sensitivity' => $data['irritation_sensitivity'] ?? null,
            'weak_explanation_tolerance' => $data['weak_explanation_tolerance'] ?? null,
            'closing_resistance' => $data['closing_resistance'] ?? null,
            'boundary_persistence' => $data['boundary_persistence'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function buildAllowedPersonaModes(Request $request): array
    {
        return $request->input('allowed_persona_modes', []);
    }

    public function syncAssignedPersonas(Request $request, ScenarioVersion $version): void
    {
        $version->assignedPersonas()->delete();

        $personaIds = $request->input('persona_ids', []);

        foreach ($personaIds as $personaId) {
            if (empty($personaId)) {
                continue;
            }

            $version->assignedPersonas()->create([
                'persona_id' => $personaId,
                'is_enabled' => true,
            ]);
        }
    }

    private function parseCommaList(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        return array_map('trim', explode(',', $text));
    }
}
