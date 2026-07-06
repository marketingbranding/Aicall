<?php

namespace App\Http\Requests\Hq;

use App\Models\Scenario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->route('scenario')) {
            return $this->user()->can('update', $this->route('scenario'));
        }

        return $this->user()->can('create', Scenario::class);
    }

    public function rules(): array
    {
        $scenarioId = $this->route('scenario')?->id;

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('scenarios', 'code')->ignore($scenarioId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'sales_briefing' => ['nullable', 'string', 'max:65535'],
            'hidden_context' => ['nullable', 'string', 'max:65535'],
            'training_objective' => ['nullable', 'string', 'max:65535'],
            'starting_phase' => ['nullable', 'string', 'max:255'],
            'first_speaker' => ['nullable', 'string', 'in:AI,USER'],
            'ai_opening_context' => ['nullable', 'string', 'max:65535'],
            'initial_customer_intent' => ['nullable', 'string', 'max:65535'],

            'difficulty_level' => ['nullable', 'string', 'in:BEGINNER,NORMAL,DIFFICULT,EXPERT,CUSTOM'],
            'difficulty_config' => ['nullable', 'array'],
            'max_duration_seconds' => ['nullable', 'integer', 'min:60', 'max:900'],
            'allow_ai_end_call' => ['nullable', 'boolean'],

            'allowed_persona_modes' => ['nullable', 'array'],
            'allowed_persona_modes.*' => ['string', 'in:CHOOSE_PERSONA,RANDOM_PERSONA,HIDDEN_PERSONA'],

            'persona_ids' => ['nullable', 'array'],
            'persona_ids.*' => ['integer', 'exists:personas,id'],
        ];
    }
}
