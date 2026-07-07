<?php

namespace App\Services\Rubrics;

use App\Models\EvaluationRubric;
use App\Models\ScenarioVersion;
use Illuminate\Http\Request;

class RubricBuilderService
{
    public function syncItems(Request $request, EvaluationRubric $rubric): void
    {
        $rubric->items()->delete();

        $items = $request->input('items', []);

        foreach ($items as $data) {
            if (empty($data['key']) || empty($data['title'])) {
                continue;
            }

            $rubric->items()->create([
                'key' => $data['key'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'weight' => (int) ($data['weight'] ?? 100),
                'is_enabled' => !isset($data['is_disabled']),
                'evaluation_guidance' => $data['evaluation_guidance'] ?? null,
            ]);
        }
    }

    public function syncOverrides(Request $request, ScenarioVersion $version): void
    {
        $version->rubricOverrides()->delete();

        $overrides = $request->input('overrides', []);

        foreach ($overrides as $data) {
            if (empty($data['global_rubric_item_key'])) {
                continue;
            }

            $version->rubricOverrides()->create([
                'global_rubric_item_key' => $data['global_rubric_item_key'],
                'weight_override' => $data['weight_override'] ?? null,
                'is_enabled_override' => isset($data['is_enabled_override']) ? (bool) $data['is_enabled_override'] : null,
            ]);
        }
    }
}
