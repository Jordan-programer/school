<?php

namespace App\Http\Requests\V1\Enrollments;

use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transfer', $this->route('enrollment'));
    }

    public function rules(): array
    {
        return [
            'destination_school'    => ['required', 'string', 'max:200'],
            'transfer_guide_number' => ['nullable', 'string', 'max:50'],
            'reason'                => ['nullable', 'string', 'max:255'],
            'transfer_date'         => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_school.required' => 'A escola de destino é obrigatória para a transferência.',
            'transfer_date.required'      => 'A data da transferência é obrigatória.',
        ];
    }
}
