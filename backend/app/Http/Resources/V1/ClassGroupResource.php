<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'max_students' => $this->max_students,
            'is_active' => $this->is_active,
            'academic_year' => [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
                'is_active' => $this->academicYear->is_active,
            ],
            'grade' => [
                'id' => $this->grade->id,
                'code' => $this->grade->code,
                'name' => $this->grade->name,
                'is_cycle_ending' => $this->grade->is_cycle_ending,
                'cycle' => [
                    'id' => $this->grade->cycle->id,
                    'name' => $this->grade->cycle->name,
                ],
            ],
            'shift' => [
                'id' => $this->shift->id,
                'name' => $this->shift->name,
                'start_time' => substr($this->shift->start_time, 0, 5),
                'end_time' => substr($this->shift->end_time, 0, 5),
            ],
            'classroom' => $this->classroom ? [
                'id' => $this->classroom->id,
                'name' => $this->classroom->name,
                'capacity' => $this->classroom->capacity,
                'location_block' => $this->classroom->location_block,
            ] : null,
            'teaching_assignments' => TeachingAssignmentResource::collection(
                $this->whenLoaded('teachingAssignments')
            ),
        ];
    }
}
