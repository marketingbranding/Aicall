<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\PersonaHiddenInformation;
use App\Models\PersonaObjection;
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

            'identity' => ['nullable', 'array'],
            'identity.age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'identity.gender' => ['nullable', 'string', 'max:50'],
            'identity.marital_status' => ['nullable', 'string', 'max:50'],
            'identity.children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'identity.occupation' => ['nullable', 'string', 'max:255'],
            'identity.employment_type' => ['nullable', 'string', 'max:50'],
            'identity.income_range' => ['nullable', 'string', 'max:50'],
            'identity.spouse_occupation' => ['nullable', 'string', 'max:255'],
            'identity.spouse_income' => ['nullable', 'string', 'max:50'],
            'identity.current_residence' => ['nullable', 'string', 'max:255'],
            'identity.education_background' => ['nullable', 'string', 'max:255'],
            'identity.notes' => ['nullable', 'string', 'max:65535'],

            'housing_context' => ['nullable', 'array'],
            'knowledge_beliefs' => ['nullable', 'array'],

            'personality' => ['nullable', 'array'],
            'personality.*' => ['nullable', 'integer', 'in:0,25,50,75,100'],

            'human_behavior_traits' => ['nullable', 'array'],
            'human_behavior_traits.*' => ['nullable', 'integer', 'in:0,25,50,75,100'],

            'communication_style' => ['nullable', 'array'],

            'initial_state' => ['nullable', 'array'],
            'initial_state.*' => ['nullable', 'integer', 'min:0', 'max:100'],

            'state_sensitivity' => ['nullable', 'array'],

            'salience_overrides' => ['nullable', 'array'],

            'objections' => ['nullable', 'array', 'max:10'],
            'objections.*.key' => ['nullable', 'string', 'max:255'],
            'objections.*.title' => ['nullable', 'string', 'max:255'],
            'objections.*.context' => ['nullable', 'string', 'max:65535'],
            'objections.*.visibility' => ['nullable', 'string', 'in:VISIBLE,HIDDEN'],
            'objections.*.severity' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'objections.*.emotional_importance' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'objections.*.persistence' => ['nullable', 'integer', 'in:0,25,50,75,100'],

            'hidden_information' => ['nullable', 'array', 'max:10'],
            'hidden_information.*.key' => ['nullable', 'string', 'max:255'],
            'hidden_information.*.title' => ['nullable', 'string', 'max:255'],
            'hidden_information.*.information' => ['nullable', 'string', 'max:65535'],
            'hidden_information.*.sensitivity' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'hidden_information.*.disclosure_difficulty' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'hidden_information.*.direct_question_effectiveness' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'hidden_information.*.trust_requirement' => ['nullable', 'integer', 'in:0,25,50,75,100'],
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
            'identity_json' => $this->buildIdentity($request),
            'housing_context_json' => $this->buildHousingContext($request),
            'knowledge_beliefs_json' => $this->buildKnowledgeBeliefs($request),
            'personality_profile_json' => $request->input('personality', []),
            'human_behavior_traits_json' => $request->input('human_behavior_traits', []),
            'communication_style_json' => $request->input('communication_style', []),
            'initial_dynamic_state_json' => $request->input('initial_state', []),
            'state_sensitivity_json' => $this->buildStateSensitivity($request),
            'salience_overrides_json' => $this->buildSalienceOverrides($request),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->syncObjections($request, $version);
        $this->syncHiddenInformation($request, $version);

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

    public function update(Request $request, Persona $persona): RedirectResponse
    {
        $this->authorize('update', $persona);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:personas,code,' . $persona->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],

            'identity' => ['nullable', 'array'],
            'identity.age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'identity.gender' => ['nullable', 'string', 'max:50'],
            'identity.marital_status' => ['nullable', 'string', 'max:50'],
            'identity.children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'identity.occupation' => ['nullable', 'string', 'max:255'],
            'identity.employment_type' => ['nullable', 'string', 'max:50'],
            'identity.income_range' => ['nullable', 'string', 'max:50'],
            'identity.spouse_occupation' => ['nullable', 'string', 'max:255'],
            'identity.spouse_income' => ['nullable', 'string', 'max:50'],
            'identity.current_residence' => ['nullable', 'string', 'max:255'],
            'identity.education_background' => ['nullable', 'string', 'max:255'],
            'identity.notes' => ['nullable', 'string', 'max:65535'],

            'housing_context' => ['nullable', 'array'],
            'knowledge_beliefs' => ['nullable', 'array'],

            'personality' => ['nullable', 'array'],
            'personality.*' => ['nullable', 'integer', 'in:0,25,50,75,100'],

            'human_behavior_traits' => ['nullable', 'array'],
            'human_behavior_traits.*' => ['nullable', 'integer', 'in:0,25,50,75,100'],

            'communication_style' => ['nullable', 'array'],

            'initial_state' => ['nullable', 'array'],
            'initial_state.*' => ['nullable', 'integer', 'min:0', 'max:100'],

            'state_sensitivity' => ['nullable', 'array'],

            'salience_overrides' => ['nullable', 'array'],

            'objections' => ['nullable', 'array', 'max:10'],
            'objections.*.key' => ['nullable', 'string', 'max:255'],
            'objections.*.title' => ['nullable', 'string', 'max:255'],
            'objections.*.context' => ['nullable', 'string', 'max:65535'],
            'objections.*.visibility' => ['nullable', 'string', 'in:VISIBLE,HIDDEN'],
            'objections.*.severity' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'objections.*.emotional_importance' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'objections.*.persistence' => ['nullable', 'integer', 'in:0,25,50,75,100'],

            'hidden_information' => ['nullable', 'array', 'max:10'],
            'hidden_information.*.key' => ['nullable', 'string', 'max:255'],
            'hidden_information.*.title' => ['nullable', 'string', 'max:255'],
            'hidden_information.*.information' => ['nullable', 'string', 'max:65535'],
            'hidden_information.*.sensitivity' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'hidden_information.*.disclosure_difficulty' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'hidden_information.*.direct_question_effectiveness' => ['nullable', 'integer', 'in:0,25,50,75,100'],
            'hidden_information.*.trust_requirement' => ['nullable', 'integer', 'in:0,25,50,75,100'],
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
            'identity_json' => $this->buildIdentity($request),
            'housing_context_json' => $this->buildHousingContext($request),
            'knowledge_beliefs_json' => $this->buildKnowledgeBeliefs($request),
            'personality_profile_json' => $request->input('personality', []),
            'human_behavior_traits_json' => $request->input('human_behavior_traits', []),
            'communication_style_json' => $request->input('communication_style', []),
            'initial_dynamic_state_json' => $request->input('initial_state', []),
            'state_sensitivity_json' => $this->buildStateSensitivity($request),
            'salience_overrides_json' => $this->buildSalienceOverrides($request),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->syncObjections($request, $version);
        $this->syncHiddenInformation($request, $version);

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

    private function buildIdentity(Request $request): array
    {
        $identity = $request->input('identity', []);
        unset($identity['age'], $identity['gender'], $identity['marital_status'],
            $identity['children'], $identity['occupation'], $identity['employment_type'],
            $identity['income_range'], $identity['spouse_occupation'], $identity['spouse_income'],
            $identity['current_residence'], $identity['education_background'], $identity['notes']);

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

    private function buildHousingContext(Request $request): array
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

    private function buildKnowledgeBeliefs(Request $request): array
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

    private function buildStateSensitivity(Request $request): array
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

    private function buildSalienceOverrides(Request $request): array
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

    private function syncObjections(Request $request, PersonaVersion $version): void
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

    private function syncHiddenInformation(Request $request, PersonaVersion $version): void
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
