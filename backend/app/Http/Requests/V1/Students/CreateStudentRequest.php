<?php

namespace App\Http\Requests\V1\Students;

use App\Enums\DocumentTypeEnum;
use App\Enums\GenderEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Student::class);
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
            // Dados pessoais
            'first_name'              => ['required', 'string', 'min:2', 'max:100'],
            'last_name'               => ['required', 'string', 'min:2', 'max:150'],
            'birth_date'              => ['required', 'date', 'before:today', 'after:' . now()->subYears(25)->toDateString()],
            'gender'                  => ['required', Rule::enum(GenderEnum::class)],

            // Documento de identificação
            'id_document_type'        => ['required', Rule::enum(DocumentTypeEnum::class)],
            'id_document_number'      => ['nullable', 'string', 'max:50'],

            // Necessidades especiais
            'has_special_needs'       => ['boolean'],
            'special_needs_description' => ['nullable', 'required_if:has_special_needs,true', 'string', 'max:1000'],

            // Morada e localização
            'province'                => ['required', Rule::in($provinces)],
            'municipality'            => ['required', 'string', 'max:100'],
            'neighborhood'            => ['nullable', 'string', 'max:100'],
            'address'                 => ['nullable', 'string', 'max:500'],
            'origin_school'           => ['nullable', 'string', 'max:200'],

            // Encarregado de educação (pelo menos um, obrigatório)
            'guardians'                     => ['required', 'array', 'min:1'],
            'guardians.*.full_name'          => ['required', 'string', 'max:200'],
            'guardians.*.id_document_type'   => ['required', Rule::enum(DocumentTypeEnum::class)],
            'guardians.*.id_document_number' => ['nullable', 'string', 'max:50'],
            'guardians.*.phone_primary'      => ['required', 'string', 'regex:/^\+?[0-9\s\-]{9,15}$/'],
            'guardians.*.phone_secondary'    => ['nullable', 'string', 'regex:/^\+?[0-9\s\-]{9,15}$/'],
            'guardians.*.email'              => ['nullable', 'email'],
            'guardians.*.profession'         => ['nullable', 'string', 'max:100'],
            'guardians.*.workplace'          => ['nullable', 'string', 'max:200'],
            'guardians.*.address'            => ['nullable', 'string', 'max:500'],
            'guardians.*.relationship_type'  => ['required', 'string', Rule::in([
                'Pai', 'Mãe', 'Avô', 'Avó', 'Tio', 'Tia', 'Tutor Legal', 'Outro',
            ])],
            'guardians.*.is_primary_contact'          => ['boolean'],
            'guardians.*.is_financially_responsible'  => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'             => 'O primeiro nome do aluno é obrigatório.',
            'last_name.required'              => 'O apelido do aluno é obrigatório.',
            'birth_date.required'             => 'A data de nascimento é obrigatória.',
            'birth_date.before'               => 'A data de nascimento deve ser anterior a hoje.',
            'birth_date.after'                => 'A idade do aluno não pode ultrapassar 25 anos.',
            'gender.required'                 => 'O género do aluno é obrigatório.',
            'id_document_type.required'       => 'O tipo de documento de identificação é obrigatório.',
            'province.required'               => 'A província é obrigatória.',
            'municipality.required'           => 'O município é obrigatório.',
            'guardians.required'              => 'É obrigatório registar pelo menos um encarregado de educação.',
            'guardians.min'                   => 'É obrigatório registar pelo menos um encarregado de educação.',
            'guardians.*.full_name.required'  => 'O nome completo do encarregado é obrigatório.',
            'guardians.*.phone_primary.required' => 'O telefone principal do encarregado é obrigatório.',
            'guardians.*.phone_primary.regex' => 'Introduza um número de telefone válido (ex: +244923456789).',
            'guardians.*.relationship_type.required' => 'O tipo de parentesco é obrigatório.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Garantir que pelo menos um encarregado é o contacto principal
        if ($this->has('guardians') && is_array($this->guardians) && count($this->guardians) > 0) {
            $hasPrimary = collect($this->guardians)->some(fn($g) => !empty($g['is_primary_contact']));
            if (!$hasPrimary) {
                $guardians = $this->guardians;
                $guardians[0]['is_primary_contact'] = true;
                $this->merge(['guardians' => $guardians]);
            }
        }
    }
}
