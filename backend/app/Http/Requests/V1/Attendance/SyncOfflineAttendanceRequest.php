<?php

namespace App\Http\Requests\V1\Attendance;

use App\Enums\AttendanceStatusEnum;
use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncOfflineAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('record', Attendance::class);
    }

    public function rules(): array
    {
        return [
            'mutations'                      => ['required', 'array', 'min:1'],
            'mutations.*.client_mutation_id' => ['required', 'uuid'],
            'mutations.*.class_group_id'     => ['required', 'uuid', 'exists:class_groups,id'],
            'mutations.*.subject_id'         => ['nullable', 'uuid', 'exists:subjects,id'],
            'mutations.*.academic_year_id'   => ['nullable', 'uuid', 'exists:academic_years,id'],
            'mutations.*.date'               => ['required', 'date'],
            'mutations.*.lesson_number'      => ['required', 'integer', 'between:1,10'],
            'mutations.*.topic'              => ['nullable', 'string', 'max:255'],
            'mutations.*.notes'              => ['nullable', 'string', 'max:1000'],

            'mutations.*.records'            => ['required', 'array', 'min:1'],
            'mutations.*.records.*.student_id'    => ['required', 'uuid', 'exists:students,id'],
            'mutations.*.records.*.enrollment_id' => ['nullable', 'uuid', 'exists:enrollments,id'],
            'mutations.*.records.*.status'        => ['required', Rule::enum(AttendanceStatusEnum::class)],
            'mutations.*.records.*.justification_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
