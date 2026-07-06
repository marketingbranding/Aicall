<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\PersonaVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonaController extends Controller
{
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

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Persona::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:personas,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        $persona = Persona::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'status' => Persona::STATUS_ACTIVE,
            'created_by' => $request->user()->id,
        ]);

        $version = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'public_profile_text' => $validated['description'] ?? null,
            'identity_json' => [],
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $persona->update(['current_version_id' => $version->id]);

        return redirect()->route('hq.personas.index')
            ->with('success', 'Persona berhasil dibuat.');
    }

    public function edit(Persona $persona): View
    {
        $this->authorize('update', $persona);

        $persona->load('currentVersion');

        return view('hq.personas.edit', compact('persona'));
    }

    public function update(Request $request, Persona $persona): RedirectResponse
    {
        $this->authorize('update', $persona);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:personas,code,' . $persona->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        $persona->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
        ]);

        $currentVersion = $persona->currentVersion;
        $nextVersion = $currentVersion ? $currentVersion->version_number + 1 : 1;

        $version = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => $nextVersion,
            'public_profile_text' => $validated['description'] ?? null,
            'identity_json' => $currentVersion?->identity_json ?? [],
            'housing_context_json' => $currentVersion?->housing_context_json,
            'knowledge_beliefs_json' => $currentVersion?->knowledge_beliefs_json,
            'personality_profile_json' => $currentVersion?->personality_profile_json,
            'human_behavior_traits_json' => $currentVersion?->human_behavior_traits_json,
            'communication_style_json' => $currentVersion?->communication_style_json,
            'initial_dynamic_state_json' => $currentVersion?->initial_dynamic_state_json,
            'state_sensitivity_json' => $currentVersion?->state_sensitivity_json,
            'salience_overrides_json' => $currentVersion?->salience_overrides_json,
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

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
