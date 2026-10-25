<?php

namespace App\Http\Requests\V1\Enrollments;

use Illuminate\Foundation\Http\FormRequest;

class AssignClassGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assignClass', $this->route('enrollment'));
    }

    public function rules(): array
    {
        return [
            'class_group_id' => ['nullable', 'uuid', 'exists:class_groups,id'],
            'force_capacity' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_group_id.exists' => 'A turma seleccionada não existe no sistema.',
        ];
    }
}
