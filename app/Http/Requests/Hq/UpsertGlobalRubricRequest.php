<?php

namespace App\Http\Requests\Hq;

use App\Models\EvaluationRubric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertGlobalRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->route('rubric')) {
            return $this->user()->can('update', $this->route('rubric'));
        }

        return $this->user()->can('create', EvaluationRubric::class);
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
        ];
    }
}
