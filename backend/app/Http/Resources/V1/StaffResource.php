<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_code' => $this->staff_code,
            'full_name' => $this->full_name,
            'id_document_type' => $this->id_document_type,
            'id_document_number' => $this->id_document_number,
            'position' => $this->position,
            'academic_degree' => $this->academic_degree,
            'specialty' => $this->specialty,
            'phone_primary' => $this->phone_primary,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'hire_date' => $this->hire_date?->format('d/m/Y'),
        ];
    }
}
