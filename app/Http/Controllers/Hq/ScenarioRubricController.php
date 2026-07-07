<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hq\UpsertScenarioRubricRequest;
use App\Models\EvaluationRubric;
use App\Models\Scenario;
use App\Services\Rubrics\RubricBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScenarioRubricController extends Controller
{
    public function __construct(
        private readonly RubricBuilderService $builderService,
    ) {}

    public function edit(Scenario $scenario): View
    {
        $this->authorize('update', $scenario);

        $scenario->load('currentVersion.assignedPersonas', 'currentVersion.rubricOverrides');

        $rubric = EvaluationRubric::where('type', EvaluationRubric::TYPE_SCENARIO)
            ->where('scenario_version_id', $scenario->currentVersion?->id)
            ->with('items')
            ->first();

        $globalRubrics = EvaluationRubric::where('type', EvaluationRubric::TYPE_GLOBAL)
            ->where('is_active', true)
            ->with('items')
            ->orderBy('name')
            ->get();

        return view('hq.scenario-rubrics.edit', compact('scenario', 'rubric', 'globalRubrics'));
    }

    public function update(UpsertScenarioRubricRequest $request, Scenario $scenario): RedirectResponse
    {
        $version = $scenario->currentVersion;

        if (!$version) {
            return redirect()->route('hq.scenarios.edit', $scenario)
                ->with('error', 'Skenario belum memiliki versi. Simpan skenario terlebih dahulu.');
        }

        $rubric = EvaluationRubric::firstOrNew([
            'type' => EvaluationRubric::TYPE_SCENARIO,
            'scenario_version_id' => $version->id,
        ]);

        $rubric->fill([
            'name' => $request->input('name'),
            'created_by' => $request->user()->id,
        ]);

        if (!$rubric->exists) {
            $rubric->version_number = 1;
        }

        $rubric->save();

        $this->builderService->syncItems($request, $rubric);
        $this->builderService->syncOverrides($request, $version);

        return redirect()->route('hq.scenario-rubrics.edit', $scenario)
            ->with('success', 'Rubrik skenario berhasil diperbarui.');
    }
}
