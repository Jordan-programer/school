<?php

namespace App\Http\Requests\V1\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;

class JustifyAbsenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('justify', Attendance::class);
    }

    public function rules(): array
    {
        return [
            'justification_reason' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'justification_reason.required' => 'O motivo da justificação é obrigatório (ex: Atestado médico, luto, comparência judicial).',
        ];
    }
}
