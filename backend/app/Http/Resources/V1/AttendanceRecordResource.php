<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'attendance_id'        => $this->attendance_id,
            'student_id'           => $this->student_id,
            'enrollment_id'        => $this->enrollment_id,
            'status'               => $this->status?->value ?? $this->status,
            'status_label'         => $this->status?->label() ?? $this->status,
            'abbreviation'         => $this->status?->abbreviation() ?? 'P',
            'justification_reason' => $this->justification_reason,
            'justified_at'         => $this->justified_at?->format('d/m/Y H:i'),
            'justified_by'         => $this->whenLoaded('justifiedBy', function () {
                return [
                    'id'   => $this->justifiedBy->id,
                    'name' => $this->justifiedBy->name,
                ];
            }),
            'student'              => $this->whenLoaded('student', function () {
                return [
                    'id'          => $this->student->id,
                    'student_uid' => $this->student->student_uid,
                    'full_name'   => $this->student->full_name,
                    'gender'      => $this->student->gender?->value ?? $this->student->gender,
                ];
            }),
        ];
    }
}
