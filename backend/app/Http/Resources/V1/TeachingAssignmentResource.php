<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeachingAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => [
                'id' => $this->subject->id,
                'code' => $this->subject->code,
                'name' => $this->subject->name,
            ],
            'teacher' => [
                'id' => $this->staff->id,
                'staff_code' => $this->staff->staff_code,
                'full_name' => $this->staff->full_name,
                'specialty' => $this->staff->specialty,
            ],
        ];
    }
}
