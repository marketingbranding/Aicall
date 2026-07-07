<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hq\UpsertGlobalRubricRequest;
use App\Models\EvaluationRubric;
use App\Services\Rubrics\RubricBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GlobalRubricController extends Controller
{
    public function __construct(
        private readonly RubricBuilderService $builderService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', EvaluationRubric::class);

        $rubrics = EvaluationRubric::with('items', 'createdBy')
            ->where('type', EvaluationRubric::TYPE_GLOBAL)
            ->orderBy('name')
            ->orderBy('version_number', 'desc')
            ->get()
            ->groupBy('name')
            ->map(fn ($group) => $group->first());

        return view('hq.global-rubrics.index', compact('rubrics'));
    }

    public function create(): View
    {
        $this->authorize('create', EvaluationRubric::class);

        return view('hq.global-rubrics.create');
    }

    public function store(UpsertGlobalRubricRequest $request): RedirectResponse
    {
        $rubric = EvaluationRubric::create([
            'name' => $request->input('name'),
            'type' => EvaluationRubric::TYPE_GLOBAL,
            'version_number' => 1,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        $this->builderService->syncItems($request, $rubric);

        return redirect()->route('hq.global-rubrics.index')
            ->with('success', 'Rubrik global berhasil dibuat.');
    }

    public function edit(EvaluationRubric $rubric): View
    {
        $this->authorize('update', $rubric);

        $rubric->load('items');

        return view('hq.global-rubrics.edit', compact('rubric'));
    }

    public function update(UpsertGlobalRubricRequest $request, EvaluationRubric $rubric): RedirectResponse
    {
        $rubric->update([
            'name' => $request->input('name'),
        ]);

        $this->builderService->syncItems($request, $rubric);

        return redirect()->route('hq.global-rubrics.index')
            ->with('success', 'Rubrik global berhasil diperbarui.');
    }

    public function archive(EvaluationRubric $rubric): RedirectResponse
    {
        $this->authorize('archive', $rubric);

        $rubric->update(['is_active' => false]);

        return redirect()->route('hq.global-rubrics.index')
            ->with('success', 'Rubrik global berhasil dinonaktifkan.');
    }
}
