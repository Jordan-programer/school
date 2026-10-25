<?php

namespace App\Http\Requests\V1\Attendance;

use App\Enums\AttendanceStatusEnum;
use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('record', Attendance::class);
    }

    public function rules(): array
    {
        return [
            'class_group_id'     => ['required', 'uuid', 'exists:class_groups,id'],
            'subject_id'         => ['nullable', 'uuid', 'exists:subjects,id'],
            'academic_year_id'   => ['nullable', 'uuid', 'exists:academic_years,id'],
            'date'               => ['required', 'date'],
            'lesson_number'      => ['required', 'integer', 'between:1,10'],
            'topic'              => ['nullable', 'string', 'max:255'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'client_mutation_id' => ['nullable', 'uuid'],

            'records'            => ['required', 'array', 'min:1'],
            'records.*.student_id'           => ['required', 'uuid', 'exists:students,id'],
            'records.*.enrollment_id'        => ['nullable', 'uuid', 'exists:enrollments,id'],
            'records.*.status'               => ['required', Rule::enum(AttendanceStatusEnum::class)],
            'records.*.justification_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_group_id.required' => 'A turma é obrigatória para o registo da chamada.',
            'date.required'           => 'A data da aula é obrigatória.',
            'lesson_number.required'  => 'O tempo lectivo (ex: 1º tempo) é obrigatório.',
            'records.required'        => 'É necessário registar a lista de presenças dos alunos.',
        ];
    }
}
