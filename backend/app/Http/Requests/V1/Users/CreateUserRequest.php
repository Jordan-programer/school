<?php

namespace App\Http\Requests\V1\Users;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(RoleEnum::values())],
            'is_active' => ['nullable', 'boolean'],

            // Dados opcionais de funcionário/professor (staff)
            'staff' => ['nullable', 'array'],
            'staff.staff_code' => ['required_with:staff', 'string', 'unique:staff,staff_code'],
            'staff.position' => ['required_with:staff', 'string', 'max:150'],
            'staff.id_document_type' => ['nullable', 'string', Rule::in(['BI', 'CEDULA', 'ASSENTO', 'PROVISORIO'])],
            'staff.id_document_number' => ['nullable', 'string', 'max:50'],
            'staff.academic_degree' => ['nullable', 'string', 'max:150'],
            'staff.specialty' => ['nullable', 'string', 'max:150'],
            'staff.hire_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome completo do utilizador é obrigatório.',
            'email.required' => 'O endereço de e-mail é obrigatório.',
            'email.unique' => 'Já existe uma conta associada a este endereço de e-mail.',
            'phone.unique' => 'Já existe uma conta associada a este número de telefone.',
            'role.required' => 'O perfil funcional (papel) é obrigatório.',
            'role.in' => 'O perfil seleccionado não é válido no contexto escolar angolano.',
            'password.min' => 'A palavra-passe deve possuir pelo menos 8 caracteres.',
            'staff.staff_code.unique' => 'Já existe um funcionário com este código institucional.',
        ];
    }
}
