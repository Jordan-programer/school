<?php

namespace App\Http\Requests\V1\Enrollments;

use App\Enums\EnrollmentTypeEnum;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Enrollment::class);
    }

    public function rules(): array
    {
        return [
            'student_id'       => ['required', 'uuid', 'exists:students,id'],
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'grade_id'         => ['required', 'uuid', 'exists:grades,id'],
            'class_group_id'   => ['nullable', 'uuid', 'exists:class_groups,id'],
            'school_id'        => ['nullable', 'uuid', 'exists:schools,id'],
            'enrollment_type'  => ['nullable', Rule::enum(EnrollmentTypeEnum::class)],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required'       => 'O aluno é obrigatório para a matrícula.',
            'student_id.exists'         => 'O aluno indicado não existe.',
            'academic_year_id.required' => 'O ano lectivo é obrigatório.',
            'grade_id.required'         => 'A classe de ingresso é obrigatória.',
            'class_group_id.exists'     => 'A turma seleccionada não existe.',
        ];
    }
}
