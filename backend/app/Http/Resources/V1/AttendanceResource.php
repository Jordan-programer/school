<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'class_group_id'     => $this->class_group_id,
            'subject_id'         => $this->subject_id,
            'academic_year_id'   => $this->academic_year_id,
            'date'               => $this->date?->format('Y-m-d'),
            'date_formatted'     => $this->date?->format('d/m/Y'),
            'lesson_number'      => $this->lesson_number,
            'topic'              => $this->topic,
            'notes'              => $this->notes,
            'client_mutation_id' => $this->client_mutation_id,
            'synced_at'          => $this->synced_at?->format('d/m/Y H:i'),

            // Relações carregadas
            'class_group' => $this->whenLoaded('classGroup', function () {
                return [
                    'id'    => $this->classGroup->id,
                    'name'  => $this->classGroup->name,
                    'grade' => $this->classGroup->grade ? [
                        'id'   => $this->classGroup->grade->id,
                        'name' => $this->classGroup->grade->name,
                    ] : null,
                ];
            }),

            'subject' => $this->whenLoaded('subject', function () {
                return [
                    'id'   => $this->subject->id,
                    'code' => $this->subject->code,
                    'name' => $this->subject->name,
                ];
            }),

            'recorded_by' => $this->whenLoaded('recordedBy', function () {
                return [
                    'id'   => $this->recordedBy->id,
                    'name' => $this->recordedBy->name,
                ];
            }),

            'records' => AttendanceRecordResource::collection($this->whenLoaded('records')),

            // Contagens sumarizadas
            'stats' => [
                'total'       => $this->records->count(),
                'present'     => $this->present_count,
                'unjustified' => $this->unjustified_count,
                'justified'   => $this->justified_count,
                'late'        => $this->late_count,
            ],

            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
