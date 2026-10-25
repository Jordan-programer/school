<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'enrollment_code'    => $this->enrollment_code,
            'enrollment_type'    => $this->enrollment_type?->value ?? $this->enrollment_type,
            'enrollment_type_label' => $this->enrollment_type?->label() ?? $this->enrollment_type,
            'status'             => $this->status?->value ?? $this->status,
            'status_label'       => $this->status?->label() ?? $this->status,
            'order_number'       => $this->order_number,

            // Distorção Idade-Classe
            'age_at_enrollment'  => $this->age_at_enrollment,
            'has_age_distortion' => (bool) $this->has_age_distortion,
            'distortion_details' => $this->distortion_details,

            'enrolled_at'        => $this->enrolled_at?->format('d/m/Y H:i'),
            'enrolled_at_date'   => $this->enrolled_at?->format('d/m/Y'),
            'notes'              => $this->notes,

            // Relações carregadas
            'student' => $this->whenLoaded('student', function () {
                return new StudentResource($this->student);
            }),

            'academic_year' => $this->whenLoaded('academicYear', function () {
                return [
                    'id'        => $this->academicYear->id,
                    'name'      => $this->academicYear->name,
                    'is_active' => $this->academicYear->is_active,
                ];
            }),

            'grade' => $this->whenLoaded('grade', function () {
                return [
                    'id'          => $this->grade->id,
                    'code'        => $this->grade->code,
                    'name'        => $this->grade->name,
                    'order_index' => $this->grade->order_index,
                ];
            }),

            'class_group' => $this->whenLoaded('classGroup', function () {
                return [
                    'id'           => $this->classGroup->id,
                    'name'         => $this->classGroup->name,
                    'max_students' => $this->classGroup->max_students,
                    'shift'        => $this->classGroup->shift ? [
                        'id'   => $this->classGroup->shift->id,
                        'name' => $this->classGroup->shift->name,
                    ] : null,
                    'classroom'    => $this->classGroup->classroom ? [
                        'id'   => $this->classGroup->classroom->id,
                        'name' => $this->classGroup->classroom->name,
                    ] : null,
                ];
            }),

            'school' => $this->whenLoaded('school', function () {
                return [
                    'id'           => $this->school->id,
                    'code'         => $this->school->code,
                    'name'         => $this->school->name,
                    'province'     => $this->school->province,
                    'municipality' => $this->school->municipality,
                ];
            }),

            'enrolled_by' => $this->whenLoaded('enrolledBy', function () {
                return [
                    'id'   => $this->enrolledBy->id,
                    'name' => $this->enrolledBy->name,
                ];
            }),

            'transfers' => StudentTransferResource::collection($this->whenLoaded('transfers')),

            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
