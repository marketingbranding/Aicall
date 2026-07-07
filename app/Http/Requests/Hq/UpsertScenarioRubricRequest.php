<?php

namespace App\Http\Requests\Hq;

use App\Models\Scenario;
use Illuminate\Foundation\Http\FormRequest;

class UpsertScenarioRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageRubrics();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'items' => ['nullable', 'array', 'max:20'],
            'items.*.key' => ['nullable', 'string', 'max:255'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:65535'],
            'items.*.weight' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'items.*.evaluation_guidance' => ['nullable', 'string', 'max:65535'],
            'overrides' => ['nullable', 'array', 'max:20'],
            'overrides.*.global_rubric_item_key' => ['nullable', 'string', 'max:255'],
            'overrides.*.weight_override' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
