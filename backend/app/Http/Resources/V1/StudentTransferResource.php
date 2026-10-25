<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'transfer_type'         => $this->transfer_type,
            'transfer_guide_number' => $this->transfer_guide_number,
            'destination_school'    => $this->destination_school,
            'origin_school'         => $this->origin_school,
            'reason'                => $this->reason,
            'transfer_date'         => $this->transfer_date?->format('d/m/Y'),
            'registered_by'         => $this->whenLoaded('registeredBy', function () {
                return [
                    'id'   => $this->registeredBy->id,
                    'name' => $this->registeredBy->name,
                ];
            }),
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
