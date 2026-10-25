<?php

namespace App\Http\Requests\V1\Academics;

use App\Models\ClassGroup;
use Illuminate\Foundation\Http\FormRequest;

class CreateClassGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ClassGroup::class);
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'grade_id' => ['required', 'uuid', 'exists:grades,id'],
            'shift_id' => ['required', 'uuid', 'exists:shifts,id'],
            'classroom_id' => ['nullable', 'uuid', 'exists:classrooms,id'],
            'name' => ['required', 'string', 'max:50'],
            'max_students' => ['required', 'integer', 'min:10', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'O ano lectivo é obrigatório.',
            'grade_id.required' => 'A classe escolar é obrigatória.',
            'shift_id.required' => 'O turno escolar é obrigatório.',
            'name.required' => 'O nome da turma (ex: 7ª A) é obrigatório.',
            'max_students.max' => 'A capacidade máxima de alunos não pode ultrapassar 60 no subsistema geral.',
        ];
    }
}
