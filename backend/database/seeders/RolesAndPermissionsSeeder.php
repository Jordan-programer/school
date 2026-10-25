<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Cadastra todos os papéis e permissões do contexto escolar angolano.
     */
    public function run(): void
    {
        // Limpar cache de permissões do Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Definição de permissões granulares
        $permissions = [
            // Alunos e Encarregados
            'students.view',
            'students.create',
            'students.edit',
            'students.delete',
            'students.export',

            // Matrículas e Confirmações
            'enrollments.create',
            'enrollments.confirm',
            'enrollments.batch_renew',
            'enrollments.cancel',

            // Chamada Diária e Assiduidade
            'attendance.view',
            'attendance.record',
            'attendance.justify',

            // Avaliações, Pautas e Conselho
            'grades.enter',
            'grades.view_own_classes',
            'grades.view_all_classes',
            'grades.close_council',
            'grades.request_correction',
            'grades.approve_correction',
            'reports.mini_pautas',
            'reports.pautas',
            'reports.boletins',

            // Turmas, Horários e Estrutura
            'academic_structure.manage',
            'academic_structure.view',
            'timetables.manage',
            'timetables.view',

            // Finanças e Propinas
            'finances.view',
            'finances.manage',
            'finances.issue_receipt',

            // Gestão de Utilizadores, Auditoria e Sistema
            'users.view',
            'users.manage',
            'audit.view',
            'provincial_stats.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 2. Criação dos Papéis e Atribuição de Permissões
        $roles = [
            RoleEnum::DIRECTOR->value => $permissions, // Diretor tem todas as permissões

            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value => [
                'students.view', 'students.export',
                'enrollments.confirm', 'enrollments.batch_renew',
                'attendance.view', 'attendance.justify',
                'grades.enter', 'grades.view_all_classes', 'grades.close_council',
                'grades.request_correction', 'grades.approve_correction',
                'reports.mini_pautas', 'reports.pautas', 'reports.boletins',
                'academic_structure.manage', 'academic_structure.view',
                'timetables.manage', 'timetables.view',
                'users.view', 'audit.view',
            ],

            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value => [
                'students.view', 'students.create', 'students.edit',
                'enrollments.create', 'enrollments.confirm', 'enrollments.batch_renew',
                'finances.view', 'finances.manage', 'finances.issue_receipt',
                'academic_structure.view', 'users.view', 'audit.view',
            ],

            RoleEnum::SECRETARY->value => [
                'students.view', 'students.create', 'students.edit', 'students.export',
                'enrollments.create', 'enrollments.confirm', 'enrollments.batch_renew',
                'attendance.view', 'attendance.justify',
                'reports.mini_pautas', 'reports.pautas', 'reports.boletins',
                'academic_structure.view', 'timetables.view',
                'finances.view', 'finances.issue_receipt',
            ],

            RoleEnum::TEACHER->value => [
                'students.view',
                'attendance.view', 'attendance.record',
                'grades.enter', 'grades.view_own_classes', 'grades.request_correction',
                'reports.mini_pautas',
                'timetables.view',
            ],

            RoleEnum::CLASS_COUNCIL_LEADER->value => [
                'students.view',
                'attendance.view', 'attendance.record', 'attendance.justify',
                'grades.enter', 'grades.view_own_classes', 'grades.view_all_classes',
                'grades.request_correction',
                'reports.mini_pautas', 'reports.pautas', 'reports.boletins',
                'timetables.view',
            ],

            RoleEnum::COORDINATOR->value => [
                'students.view',
                'attendance.view',
                'grades.view_all_classes',
                'reports.mini_pautas', 'reports.pautas',
                'academic_structure.view', 'timetables.view',
            ],

            RoleEnum::TREASURER->value => [
                'students.view',
                'finances.view', 'finances.manage', 'finances.issue_receipt',
                'enrollments.confirm',
            ],

            RoleEnum::GUARDIAN->value => [
                'reports.boletins',
                'attendance.view',
                'finances.view',
                'timetables.view',
            ],

            RoleEnum::STUDENT->value => [
                'reports.boletins',
                'attendance.view',
                'timetables.view',
            ],

            RoleEnum::PROVINCIAL_INSPECTOR->value => [
                'students.view', 'students.export',
                'reports.pautas',
                'academic_structure.view',
                'provincial_stats.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }
    }
}
