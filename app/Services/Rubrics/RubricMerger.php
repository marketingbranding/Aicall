<?php

namespace App\Services\Rubrics;

use App\Models\EvaluationRubric;
use App\Models\EvaluationRubricItem;
use App\Models\ScenarioRubricOverride;
use Illuminate\Support\Collection;

class RubricMerger
{
    private const string SOURCE_GLOBAL = 'global';
    private const string SOURCE_SCENARIO = 'scenario';

    public function merge(
        Collection $activeGlobalRubrics,
        ?EvaluationRubric $scenarioRubric,
        Collection $overrides,
    ): RubricMergedResult {
        $overridesByKey = $overrides->keyBy('global_rubric_item_key');

        $items = [];

        foreach ($activeGlobalRubrics->where('is_active', true) as $rubric) {
            foreach ($rubric->items as $item) {
                $items[] = $this->buildGlobalItem($item, $overridesByKey);
            }
        }

        if ($scenarioRubric !== null) {
            foreach ($scenarioRubric->items as $item) {
                $items[] = $this->buildScenarioItem($item);
            }
        }

        usort($items, fn(MergedRubricItem $a, MergedRubricItem $b) => $a->key <=> $b->key);

        return new RubricMergedResult($items);
    }

    private function buildGlobalItem(
        EvaluationRubricItem $item,
        Collection $overridesByKey,
    ): MergedRubricItem {
        $override = $overridesByKey->get($item->key);

        $weight = $item->weight;
        $isEnabled = $item->is_enabled;

        if ($override instanceof ScenarioRubricOverride) {
            if ($override->weight_override !== null) {
                $weight = $override->weight_override;
            }
            if ($override->is_enabled_override !== null) {
                $isEnabled = $override->is_enabled_override;
            }
        }

        return new MergedRubricItem(
            key: $item->key,
            title: $item->title,
            description: $item->description,
            weight: $weight,
            isEnabled: $isEnabled,
            evaluationGuidance: $item->evaluation_guidance,
            source: self::SOURCE_GLOBAL,
        );
    }

    private function buildScenarioItem(EvaluationRubricItem $item): MergedRubricItem
    {
        return new MergedRubricItem(
            key: $item->key,
            title: $item->title,
            description: $item->description,
            weight: $item->weight,
            isEnabled: $item->is_enabled,
            evaluationGuidance: $item->evaluation_guidance,
            source: self::SOURCE_SCENARIO,
        );
    }
}
