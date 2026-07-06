<?php

namespace App\Services\Personas;

use App\Models\PersonaVersion;
use Illuminate\Http\Request;

class PersonaBuilderService
{
    public function buildIdentity(Request $request): array
    {
        return array_filter([
            'age' => $request->input('identity.age'),
            'gender' => $request->input('identity.gender'),
            'marital_status' => $request->input('identity.marital_status'),
            'children' => $request->input('identity.children'),
            'occupation' => $request->input('identity.occupation'),
            'employment_type' => $request->input('identity.employment_type'),
            'income_range' => $request->input('identity.income_range'),
            'spouse_occupation' => $request->input('identity.spouse_occupation'),
            'spouse_income' => $request->input('identity.spouse_income'),
            'current_residence' => $request->input('identity.current_residence'),
            'education_background' => $request->input('identity.education_background'),
            'notes' => $request->input('identity.notes'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function buildHousingContext(Request $request): array
    {
        $data = $request->input('housing_context', []);

        return array_filter([
            'current_housing_situation' => $data['current_housing_situation'] ?? null,
            'target_location' => $data['target_location'] ?? null,
            'reason_for_moving' => $data['reason_for_moving'] ?? null,
            'budget_range' => $data['budget_range'] ?? null,
            'needs' => $data['needs'] ?? null,
            'timeline' => $data['timeline'] ?? null,
            'family_context' => $data['family_context'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function buildKnowledgeBeliefs(Request $request): array
    {
        $data = $request->input('knowledge_beliefs', []);

        $misconceptions = $this->parseCommaList($data['misconceptions_text'] ?? null);
        $rumors = $this->parseCommaList($data['rumors_text'] ?? null);
        $sources = $this->parseCommaList($data['information_sources_text'] ?? null);

        return array_filter([
            'kpr_knowledge' => $data['kpr_knowledge'] ?? null,
            'subsidy_knowledge' => $data['subsidy_knowledge'] ?? null,
            'slik_knowledge' => $data['slik_knowledge'] ?? null,
            'developer_knowledge' => $data['developer_knowledge'] ?? null,
            'buying_experience' => $data['buying_experience'] ?? null,
            'misconceptions' => $misconceptions,
            'rumors' => $rumors,
            'information_sources' => $sources,
            'notes' => $data['notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== '' && !(is_array($v) && empty($v)));
    }

    public function buildStateSensitivity(Request $request): array
    {
        $data = $request->input('state_sensitivity', []);

        $anxietyTopics = $this->parseCommaList($data['anxiety_sensitivity_topics_text'] ?? null);

        return array_filter([
            'trust_gain_rate' => $data['trust_gain_rate'] ?? null,
            'trust_loss_rate' => $data['trust_loss_rate'] ?? null,
            'irritation_gain_rate' => $data['irritation_gain_rate'] ?? null,
            'irritation_recovery_rate' => $data['irritation_recovery_rate'] ?? null,
            'skepticism_multiplier' => $data['skepticism_multiplier'] ?? null,
            'engagement_decay_rate' => $data['engagement_decay_rate'] ?? null,
            'anxiety_sensitivity_topics' => $anxietyTopics,
            'notes' => $data['notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== '' && !(is_array($v) && empty($v)));
    }

    public function buildSalienceOverrides(Request $request): array
    {
        $data = $request->input('salience_overrides', []);

        $primary = $this->parseCommaList($data['primary_traits_text'] ?? null);
        $secondary = $this->parseCommaList($data['secondary_traits_text'] ?? null);
        $background = $this->parseCommaList($data['background_traits_text'] ?? null);

        return array_filter([
            'primary_traits' => $primary,
            'secondary_traits' => $secondary,
            'background_traits' => $background,
            'notes' => $data['notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== '' && !(is_array($v) && empty($v)));
    }

    public function syncObjections(Request $request, PersonaVersion $version): void
    {
        $version->objections()->delete();

        $objections = $request->input('objections', []);

        foreach ($objections as $data) {
            if (empty($data['key']) || empty($data['title'])) {
                continue;
            }

            $version->objections()->create([
                'key' => $data['key'],
                'title' => $data['title'],
                'context' => $data['context'] ?? null,
                'visibility' => $data['visibility'] ?? 'VISIBLE',
                'severity' => (int) ($data['severity'] ?? 50),
                'emotional_importance' => (int) ($data['emotional_importance'] ?? 50),
                'trigger_conditions_json' => $this->parseCommaList($data['trigger_conditions_text'] ?? null),
                'disclosure_conditions_json' => $this->parseCommaList($data['disclosure_conditions_text'] ?? null),
                'resolution_conditions_json' => $this->parseCommaList($data['resolution_conditions_text'] ?? null),
                'persistence' => (int) ($data['persistence'] ?? 50),
                'is_resolvable' => isset($data['is_resolvable']),
                'is_active' => !isset($data['is_archived']),
            ]);
        }
    }

    public function syncHiddenInformation(Request $request, PersonaVersion $version): void
    {
        $version->hiddenInformation()->delete();

        $infoItems = $request->input('hidden_information', []);

        foreach ($infoItems as $data) {
            if (empty($data['key']) || empty($data['title'])) {
                continue;
            }

            $version->hiddenInformation()->create([
                'key' => $data['key'],
                'title' => $data['title'],
                'information' => $data['information'] ?? null,
                'sensitivity' => (int) ($data['sensitivity'] ?? 50),
                'disclosure_difficulty' => (int) ($data['disclosure_difficulty'] ?? 50),
                'relevant_topics_json' => $this->parseCommaList($data['relevant_topics_text'] ?? null),
                'direct_question_effectiveness' => (int) ($data['direct_question_effectiveness'] ?? 50),
                'trust_requirement' => (int) ($data['trust_requirement'] ?? 50),
                'disclosure_conditions_json' => $this->parseCommaList($data['disclosure_conditions_text'] ?? null),
                'is_active' => !isset($data['is_archived']),
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
