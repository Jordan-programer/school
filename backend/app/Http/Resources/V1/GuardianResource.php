<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'full_name'            => $this->full_name,
            'id_document_type'     => $this->id_document_type?->value,
            'id_document_type_label' => $this->id_document_type?->label(),
            'id_document_number'   => $this->id_document_number,
            'phone_primary'        => $this->phone_primary,
            'phone_secondary'      => $this->phone_secondary,
            'email'                => $this->email,
            'profession'           => $this->profession,
            'workplace'            => $this->workplace,
            'address'              => $this->address,
            // Dados do pivot (quando carregado via Student->guardians)
            'relationship_type'          => $this->whenPivotLoaded('student_guardian', fn() => $this->pivot->relationship_type),
            'is_primary_contact'         => $this->whenPivotLoaded('student_guardian', fn() => (bool) $this->pivot->is_primary_contact),
            'is_financially_responsible' => $this->whenPivotLoaded('student_guardian', fn() => (bool) $this->pivot->is_financially_responsible),
            'created_at' => $this->created_at?->format('d/m/Y'),
        ];
    }
}
