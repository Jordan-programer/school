<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeEnum;
use App\Enums\GenderEnum;
use App\Models\Guardian;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentsAndGuardiansSeeder extends Seeder
{
    public function run(): void
    {
        $studentsData = [
            [
                'first_name' => 'Kelson',
                'last_name' => 'Capitão Manuel',
                'birth_date' => '2016-04-12',
                'gender' => GenderEnum::MASCULINO,
                'id_document_type' => DocumentTypeEnum::BI,
                'id_document_number' => '007812934LA045',
                'province' => 'Luanda',
                'municipality' => 'Luanda',
                'neighborhood' => 'Maianga',
                'address' => 'Rua Amílcar Cabral, nº 44',
                'has_special_needs' => false,
                'is_active' => true,
                'guardians' => [
                    [
                        'full_name' => 'António Manuel Capitão',
                        'id_document_type' => DocumentTypeEnum::BI,
                        'id_document_number' => '002918274LA038',
                        'phone_primary' => '+244923102030',
                        'phone_secondary' => '+244912102030',
                        'email' => 'antonio.capitao@empresa.ao',
                        'profession' => 'Engenheiro Informático',
                        'workplace' => 'Sonangol EP',
                        'relationship_type' => 'Pai',
                        'is_primary_contact' => true,
                        'is_financially_responsible' => true,
                    ],
                    [
                        'full_name' => 'Esperança da Costa Capitão',
                        'id_document_type' => DocumentTypeEnum::BI,
                        'id_document_number' => '003819203LA039',
                        'phone_primary' => '+244924203040',
                        'profession' => 'Contabilista Certificada',
                        'workplace' => 'Banco BAI',
                        'relationship_type' => 'Mãe',
                        'is_primary_contact' => false,
                        'is_financially_responsible' => false,
                    ],
                ],
            ],
            [
                'first_name' => 'Yara',
                'last_name' => 'dos Santos Caxito',
                'birth_date' => '2014-08-23',
                'gender' => GenderEnum::FEMININO,
                'id_document_type' => DocumentTypeEnum::BI,
                'id_document_number' => '006912384LA044',
                'province' => 'Luanda',
                'municipality' => 'Cazenga',
                'neighborhood' => 'Hoji-ya-Henda',
                'address' => 'Avenida Deolinda Rodrigues, Casa 15',
                'has_special_needs' => false,
                'is_active' => true,
                'guardians' => [
                    [
                        'full_name' => 'Teresa Domingos Caxito',
                        'id_document_type' => DocumentTypeEnum::BI,
                        'id_document_number' => '004728192LA040',
                        'phone_primary' => '+244926554433',
                        'email' => 'teresa.caxito@educacao.gov.ao',
                        'profession' => 'Professora do Ensino Primário',
                        'workplace' => 'Complexo Escolar nº 1045',
                        'relationship_type' => 'Mãe',
                        'is_primary_contact' => true,
                        'is_financially_responsible' => true,
                    ],
                ],
            ],
            [
                'first_name' => 'Matias',
                'last_name' => 'Bernardo Luvualu',
                'birth_date' => '2012-02-17',
                'gender' => GenderEnum::MASCULINO,
                'id_document_type' => DocumentTypeEnum::BI,
                'id_document_number' => '005481920LA043',
                'province' => 'Luanda',
                'municipality' => 'Belas',
                'neighborhood' => 'Kilamba',
                'address' => 'Quarteirão B, Edifício B12, Apt 301',
                'has_special_needs' => true,
                'special_needs_description' => 'Apoio pedagógico na sala de aula devido a baixa acuidade visual',
                'is_active' => true,
                'guardians' => [
                    [
                        'full_name' => 'Paulo Bernardo Luvualu',
                        'id_document_type' => DocumentTypeEnum::BI,
                        'id_document_number' => '001928374LA035',
                        'phone_primary' => '+244933998877',
                        'email' => 'paulo.luvualu@juridico.ao',
                        'profession' => 'Advogado',
                        'workplace' => 'Ordem dos Advogados de Angola',
                        'relationship_type' => 'Pai',
                        'is_primary_contact' => true,
                        'is_financially_responsible' => true,
                    ],
                ],
            ],
            [
                'first_name' => 'Ndalu',
                'last_name' => 'Simão Quaresma',
                'birth_date' => '2019-11-05',
                'gender' => GenderEnum::MASCULINO,
                'id_document_type' => DocumentTypeEnum::CEDULA,
                'id_document_number' => 'CED-2019-8812',
                'province' => 'Luanda',
                'municipality' => 'Viana',
                'neighborhood' => 'Zango 3',
                'address' => 'Rua 14, Casa nº 88',
                'has_special_needs' => false,
                'is_active' => true,
                'guardians' => [
                    [
                        'full_name' => 'Albertina Nzuzi Quaresma',
                        'id_document_type' => DocumentTypeEnum::BI,
                        'id_document_number' => '005612349LA042',
                        'phone_primary' => '+244917445566',
                        'profession' => 'Enfermeira Geral',
                        'workplace' => 'Hospital Geral de Luanda',
                        'relationship_type' => 'Mãe',
                        'is_primary_contact' => true,
                        'is_financially_responsible' => true,
                    ],
                ],
            ],
            [
                'first_name' => 'Lukénia',
                'last_name' => 'Baptista Chissola',
                'birth_date' => '2011-06-30',
                'gender' => GenderEnum::FEMININO,
                'id_document_type' => DocumentTypeEnum::BI,
                'id_document_number' => '004192834LA042',
                'province' => 'Benguela',
                'municipality' => 'Benguela',
                'neighborhood' => 'Praia Morena',
                'address' => 'Rua das Acácias Rubras, nº 12',
                'has_special_needs' => false,
                'is_active' => true,
                'guardians' => [
                    [
                        'full_name' => 'Gaspar Baptista Chissola',
                        'id_document_type' => DocumentTypeEnum::BI,
                        'id_document_number' => '001827364BE034',
                        'phone_primary' => '+244944112233',
                        'email' => 'gaspar.chissola@porto.ao',
                        'profession' => 'Oficial Portuário',
                        'workplace' => 'Porto do Lobito',
                        'relationship_type' => 'Pai',
                        'is_primary_contact' => true,
                        'is_financially_responsible' => true,
                    ],
                ],
            ],
            [
                'first_name' => 'Afonso',
                'last_name' => 'Kitumba Zau',
                'birth_date' => '2015-09-14',
                'gender' => GenderEnum::MASCULINO,
                'id_document_type' => DocumentTypeEnum::PROVISORIO,
                'id_document_number' => null,
                'province' => 'Luanda',
                'municipality' => 'Talatona',
                'neighborhood' => 'Camama',
                'address' => 'Condomínio Rosa Linda, Moradia 4',
                'has_special_needs' => false,
                'is_active' => false, // Matrícula suspensa para teste de filtros
                'guardians' => [
                    [
                        'full_name' => 'Fátima Zau Kitumba',
                        'id_document_type' => DocumentTypeEnum::BI,
                        'id_document_number' => '003829104LA039',
                        'phone_primary' => '+244922334455',
                        'profession' => 'Gestora Comercial',
                        'workplace' => 'Unitel SA',
                        'relationship_type' => 'Tia',
                        'is_primary_contact' => true,
                        'is_financially_responsible' => true,
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($studentsData) {
            $year = now()->year;

            foreach ($studentsData as $idx => $data) {
                $uid = sprintf('PROC-%d-%04d', $year, $idx + 1);

                $student = Student::firstOrCreate(
                    ['student_uid' => $uid],
                    [
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'birth_date' => Carbon::parse($data['birth_date']),
                        'gender' => $data['gender'],
                        'id_document_type' => $data['id_document_type'],
                        'id_document_number' => $data['id_document_number'],
                        'province' => $data['province'],
                        'municipality' => $data['municipality'],
                        'neighborhood' => $data['neighborhood'],
                        'address' => $data['address'],
                        'has_special_needs' => $data['has_special_needs'],
                        'special_needs_description' => $data['special_needs_description'] ?? null,
                        'is_active' => $data['is_active'],
                    ]
                );

                foreach ($data['guardians'] as $gData) {
                    $guardian = Guardian::firstOrCreate(
                        ['phone_primary' => $gData['phone_primary']],
                        [
                            'full_name' => $gData['full_name'],
                            'id_document_type' => $gData['id_document_type'],
                            'id_document_number' => $gData['id_document_number'] ?? null,
                            'phone_secondary' => $gData['phone_secondary'] ?? null,
                            'email' => $gData['email'] ?? null,
                            'profession' => $gData['profession'] ?? null,
                            'workplace' => $gData['workplace'] ?? null,
                        ]
                    );

                    if (!$student->guardians()->where('guardian_id', $guardian->id)->exists()) {
                        $student->guardians()->attach($guardian->id, [
                            'relationship_type' => $gData['relationship_type'],
                            'is_primary_contact' => $gData['is_primary_contact'],
                            'is_financially_responsible' => $gData['is_financially_responsible'],
                        ]);
                    }
                }
            }
        });
    }
}
