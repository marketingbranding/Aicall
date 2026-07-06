<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hq\UpsertPersonaRequest;
use App\Models\Persona;
use App\Models\PersonaVersion;
use App\Services\Personas\PersonaBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonaController extends Controller
{
    public function __construct(
        private readonly PersonaBuilderService $builderService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Persona::class);

        $personas = Persona::with('currentVersion', 'createdBy')
            ->orderBy('name')
            ->get();

        return view('hq.personas.index', compact('personas'));
    }

    public function create(): View
    {
        $this->authorize('create', Persona::class);

        return view('hq.personas.create');
    }

    public function store(UpsertPersonaRequest $request): RedirectResponse
    {
        $persona = Persona::create([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'status' => Persona::STATUS_ACTIVE,
            'created_by' => $request->user()->id,
        ]);

        $version = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'public_profile_text' => $request->input('description'),
            'identity_json' => $this->builderService->buildIdentity($request),
            'housing_context_json' => $this->builderService->buildHousingContext($request),
            'knowledge_beliefs_json' => $this->builderService->buildKnowledgeBeliefs($request),
            'personality_profile_json' => $request->input('personality', []),
            'human_behavior_traits_json' => $request->input('human_behavior_traits', []),
            'communication_style_json' => $request->input('communication_style', []),
            'initial_dynamic_state_json' => $request->input('initial_state', []),
            'state_sensitivity_json' => $this->builderService->buildStateSensitivity($request),
            'salience_overrides_json' => $this->builderService->buildSalienceOverrides($request),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->builderService->syncObjections($request, $version);
        $this->builderService->syncHiddenInformation($request, $version);

        $persona->update(['current_version_id' => $version->id]);

        return redirect()->route('hq.personas.index')
            ->with('success', 'Persona berhasil dibuat.');
    }

    public function edit(Persona $persona): View
    {
        $this->authorize('update', $persona);

        $persona->load('currentVersion.objections', 'currentVersion.hiddenInformation');

        return view('hq.personas.edit', compact('persona'));
    }

    public function update(UpsertPersonaRequest $request, Persona $persona): RedirectResponse
    {
        $persona->update([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
        ]);

        $currentVersion = $persona->currentVersion;
        $nextVersion = $currentVersion ? $currentVersion->version_number + 1 : 1;

        $version = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => $nextVersion,
            'public_profile_text' => $request->input('description'),
            'identity_json' => $this->builderService->buildIdentity($request),
            'housing_context_json' => $this->builderService->buildHousingContext($request),
            'knowledge_beliefs_json' => $this->builderService->buildKnowledgeBeliefs($request),
            'personality_profile_json' => $request->input('personality', []),
            'human_behavior_traits_json' => $request->input('human_behavior_traits', []),
            'communication_style_json' => $request->input('communication_style', []),
            'initial_dynamic_state_json' => $request->input('initial_state', []),
            'state_sensitivity_json' => $this->builderService->buildStateSensitivity($request),
            'salience_overrides_json' => $this->builderService->buildSalienceOverrides($request),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->builderService->syncObjections($request, $version);
        $this->builderService->syncHiddenInformation($request, $version);

        $persona->update(['current_version_id' => $version->id]);

        return redirect()->route('hq.personas.index')
            ->with('success', 'Persona berhasil diperbarui.');
    }

    public function archive(Request $request, Persona $persona): RedirectResponse
    {
        $this->authorize('archive', $persona);

        $persona->archive();

        return redirect()->route('hq.personas.index')
            ->with('success', 'Persona berhasil diarsipkan.');
    }

    public function duplicate(Request $request, Persona $persona): RedirectResponse
    {
        $this->authorize('duplicate', $persona);

        $clone = $persona->duplicate($request->user());

        return redirect()->route('hq.personas.index')
            ->with('success', 'Persona berhasil digandakan sebagai ' . $clone->name . '.');
    }

}
