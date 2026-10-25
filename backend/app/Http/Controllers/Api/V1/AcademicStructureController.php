<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Shift;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicStructureController extends Controller
{
    /**
     * Retorna a matriz estrutural completa (escola, anos, classes, turnos, salas e disciplinas).
     */
    public function index(Request $request): JsonResponse
    {
        $school = School::where('is_active', true)->first();

        $academicYears = AcademicYear::with(['terms' => fn($q) => $q->orderBy('term_number')])
            ->latest('start_date')
            ->get();

        $levels = Level::with(['cycles.grades' => fn($q) => $q->orderBy('order_index')])
            ->orderBy('order_index')
            ->get();

        $grades = Grade::with('cycle.level')
            ->orderBy('order_index')
            ->get();

        $subjects = Subject::orderBy('name')->get();

        $shifts = $school
            ? Shift::where('school_id', $school->id)->orderBy('start_time')->get()
            : Shift::orderBy('start_time')->get();

        $classrooms = $school
            ? Classroom::where('school_id', $school->id)->orderBy('name')->get()
            : Classroom::orderBy('name')->get();

        return response()->json([
            'school' => $school,
            'academic_years' => $academicYears,
            'levels' => $levels,
            'grades' => $grades,
            'subjects' => $subjects,
            'shifts' => $shifts,
            'classrooms' => $classrooms,
        ]);
    }
}
