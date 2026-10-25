<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Versão 1 (/api/v1)
|--------------------------------------------------------------------------
*/

// Rotas de Autenticação Pública
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
});

// Rotas Protegidas por Autenticação Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Sessão do Utilizador
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    // Módulo 1: Gestão de Utilizadores e Contas
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::apiResource('users', UserController::class);

    // Módulo 2: Estrutura Escolar e Turmas
    Route::get('academic-structure', [\App\Http\Controllers\Api\V1\AcademicStructureController::class, 'index'])->name('academic-structure.index');
    Route::post('class-groups/{class_group}/assign-teacher', [\App\Http\Controllers\Api\V1\ClassGroupController::class, 'assignTeacher'])->name('class-groups.assign-teacher');
    Route::apiResource('class-groups', \App\Http\Controllers\Api\V1\ClassGroupController::class);

    // Módulo 3: Alunos e Encarregados de Educação
    Route::patch('students/{student}/toggle-status', [StudentController::class, 'toggleStatus'])->name('students.toggle-status');
    Route::post('students/{student}/guardians', [StudentController::class, 'addGuardian'])->name('students.guardians.add');
    Route::delete('students/{student}/guardians/{guardian}', [StudentController::class, 'removeGuardian'])->name('students.guardians.remove');
    Route::apiResource('students', StudentController::class);

    // Módulo 4: Matrículas e Confirmações
    Route::get('enrollments/stats', [\App\Http\Controllers\Api\V1\EnrollmentController::class, 'stats'])->name('enrollments.stats');
    Route::post('enrollments/confirm', [\App\Http\Controllers\Api\V1\EnrollmentController::class, 'confirm'])->name('enrollments.confirm');
    Route::patch('enrollments/{enrollment}/assign-class', [\App\Http\Controllers\Api\V1\EnrollmentController::class, 'assignClass'])->name('enrollments.assign-class');
    Route::patch('enrollments/{enrollment}/cancel', [\App\Http\Controllers\Api\V1\EnrollmentController::class, 'cancel'])->name('enrollments.cancel');
    Route::post('enrollments/{enrollment}/transfer', [\App\Http\Controllers\Api\V1\EnrollmentController::class, 'transfer'])->name('enrollments.transfer');
    Route::apiResource('enrollments', \App\Http\Controllers\Api\V1\EnrollmentController::class)->only(['index', 'store', 'show']);

    // Módulo 5: Chamada Diária e Assiduidade Offline
    Route::get('attendances/sheet', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'sheet'])->name('attendances.sheet');
    Route::post('attendances/sync-offline', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'syncOffline'])->name('attendances.sync-offline');
    Route::patch('attendance-records/{record}/justify', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'justify'])->name('attendance-records.justify');
    Route::get('attendances/student-summary/{student}', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'studentSummary'])->name('attendances.student-summary');
    Route::apiResource('attendances', \App\Http\Controllers\Api\V1\AttendanceController::class)->only(['index', 'store', 'show']);
});

