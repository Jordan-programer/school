<?php

namespace App\Http\Requests\V1\Students;

use App\Enums\DocumentTypeEnum;
use App\Enums\GenderEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student'));
    }

    public function rules(): array
    {
        $provinces = [
            'Bengo', 'Benguela', 'Bié', 'Cabinda', 'Cuando Cubango',
            'Cuanza Norte', 'Cuanza Sul', 'Cunene', 'Huambo', 'Huíla',
            'Luanda', 'Lunda Norte', 'Lunda Sul', 'Malanje', 'Moxico',
            'Namibe', 'Uíge', 'Zaire',
        ];

        return [
            'first_name'              => ['sometimes', 'string', 'min:2', 'max:100'],
            'last_name'               => ['sometimes', 'string', 'min:2', 'max:150'],
            'birth_date'              => ['sometimes', 'date', 'before:today'],
            'gender'                  => ['sometimes', Rule::enum(GenderEnum::class)],
            'id_document_type'        => ['sometimes', Rule::enum(DocumentTypeEnum::class)],
            'id_document_number'      => ['nullable', 'string', 'max:50'],
            'has_special_needs'       => ['boolean'],
            'special_needs_description' => ['nullable', 'string', 'max:1000'],
            'province'                => ['sometimes', Rule::in($provinces)],
            'municipality'            => ['sometimes', 'string', 'max:100'],
            'neighborhood'            => ['nullable', 'string', 'max:100'],
            'address'                 => ['nullable', 'string', 'max:500'],
            'origin_school'           => ['nullable', 'string', 'max:200'],
            'is_active'               => ['boolean'],
        ];
    }
}
