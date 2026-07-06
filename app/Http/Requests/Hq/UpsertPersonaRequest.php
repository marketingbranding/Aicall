<?php

namespace App\Http\Requests\Hq;

use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->route('persona')) {
            return $this->user()->can('update', $this->route('persona'));
        }

        return $this->user()->can('create', Persona::class);
    }

    public function rules(): array
    {
        $personaId = $this->route('persona')?->id;

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('personas', 'code')->ignore($personaId)],
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
        ];
    }
}
