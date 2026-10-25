<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'student_uid'  => $this->student_uid,
            'first_name'   => $this->first_name,
            'last_name'    => $this->last_name,
            'full_name'    => $this->full_name,
            'birth_date'   => $this->birth_date?->format('d/m/Y'),
            'birth_date_raw' => $this->birth_date?->format('Y-m-d'),
            'age'          => $this->age,
            'gender'       => $this->gender?->value,
            'gender_label' => $this->gender?->label(),

            // Documento de identificação
            'id_document_type'       => $this->id_document_type?->value,
            'id_document_type_label' => $this->id_document_type?->label(),
            'id_document_number'     => $this->id_document_number,

            // Necessidades especiais
            'has_special_needs'         => $this->has_special_needs,
            'special_needs_description' => $this->special_needs_description,

            // Localização
            'province'      => $this->province,
            'municipality'  => $this->municipality,
            'neighborhood'  => $this->neighborhood,
            'address'       => $this->address,
            'origin_school' => $this->origin_school,

            // Foto
            'photo_path'    => $this->when($this->photo_path, fn() => asset("storage/{$this->photo_path}")),
            'photo_url'     => $this->when($this->photo_path, fn() => asset("storage/{$this->photo_path}")),

            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->format('d/m/Y'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),

            // Relações (carregadas quando solicitadas)
            'guardians' => GuardianResource::collection($this->whenLoaded('guardians')),
        ];
    }
}
