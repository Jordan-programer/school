<?php

namespace App\Models;

use App\Enums\EnrollmentStatusEnum;
use App\Enums\EnrollmentTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Enrollment extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'enrollments';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'grade_id',
        'class_group_id',
        'school_id',
        'enrollment_code',
        'enrollment_type',
        'status',
        'order_number',
        'age_at_enrollment',
        'has_age_distortion',
        'distortion_details',
        'notes',
        'enrolled_at',
        'enrolled_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_type'    => EnrollmentTypeEnum::class,
            'status'             => EnrollmentStatusEnum::class,
            'order_number'       => 'integer',
            'age_at_enrollment'  => 'integer',
            'has_age_distortion' => 'boolean',
            'enrolled_at'        => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'student_id',
                'academic_year_id',
                'grade_id',
                'class_group_id',
                'enrollment_code',
                'enrollment_type',
                'status',
                'order_number',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ─── Relações Eloquent ───────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by_user_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(StudentTransfer::class);
    }

    // ─── Regras de Negócio e Auxiliares ─────────────────────────────────────

    /**
     * Gera o código oficial de matrícula ou confirmação (ex: MAT-2026-0001, CONF-2026-0001).
     */
    public static function generateEnrollmentCode(EnrollmentTypeEnum|string $type, int $schoolYear): string
    {
        $prefix = match ($type instanceof EnrollmentTypeEnum ? $type->value : $type) {
            EnrollmentTypeEnum::CONFIRMACAO->value => 'CONF',
            EnrollmentTypeEnum::TRANSFERENCIA->value => 'TRF',
            default => 'MAT',
        };

        $count = self::whereYear('enrolled_at', $schoolYear)
            ->where('enrollment_code', 'like', "{$prefix}-{$schoolYear}-%")
            ->count();

        do {
            $count++;
            $code = sprintf('%s-%d-%04d', $prefix, $schoolYear, $count);
        } while (self::where('enrollment_code', $code)->exists());

        return $code;
    }

    /**
     * Avalia se existe distorção idade-classe com base na idade regular oficial em Angola.
     * Idades de referência:
     * - Iniciação: 5 anos
     * - 1ª à 6ª classe (Primário): 6 a 11 anos (limite regular: 12 anos no fim do primário)
     * - 7ª à 9ª classe (1º Ciclo): 12 a 14 anos (limite regular: 17 anos no fim do 1º ciclo)
     */
    public static function checkAgeDistortion(Student $student, Grade $grade, int $schoolYear): array
    {
        // Idade calculada a 31 de Dezembro do ano lectivo
        $refDate = Carbon::createFromDate($schoolYear, 12, 31);
        $age = $student->birth_date ? (int) $student->birth_date->diffInYears($refDate) : 0;

        $expectedAges = [
            'INIC' => 5,
            '1'    => 6,
            '2'    => 7,
            '3'    => 8,
            '4'    => 9,
            '5'    => 10,
            '6'    => 11,
            '7'    => 12,
            '8'    => 13,
            '9'    => 14,
        ];

        // Tentar obter a idade esperada pelo código ou pelo order_index
        $gradeKey = preg_replace('/[^0-9]/', '', $grade->code);
        if (empty($gradeKey) && str_contains(strtoupper($grade->code), 'INIC')) {
            $gradeKey = 'INIC';
        }

        $expectedAge = $expectedAges[$gradeKey] ?? ($grade->order_index > 0 ? 5 + $grade->order_index : 6);

        // Considera-se distorção quando a idade for 2 ou mais anos acima da idade regular esperada
        $hasDistortion = ($age >= $expectedAge + 2);
        $details = null;

        if ($hasDistortion) {
            $details = sprintf(
                'Distorção idade-classe detectada: O aluno tem %d anos para a %s (idade esperada regular: %d anos).',
                $age,
                $grade->name,
                $expectedAge
            );
        }

        return [
            'age'            => $age,
            'expected_age'   => $expectedAge,
            'has_distortion' => $hasDistortion,
            'details'        => $details,
        ];
    }

    /**
     * Atribui o próximo número de ordem disponível na turma.
     */
    public function assignNextOrderNumber(): int
    {
        if (!$this->class_group_id) {
            return 1;
        }

        $query = self::where('class_group_id', $this->class_group_id);
        if (!empty($this->id)) {
            $query->where('id', '!=', $this->id);
        }
        $maxOrder = $query->max('order_number') ?? 0;

        $newOrder = $maxOrder + 1;
        $this->order_number = $newOrder;

        return $newOrder;
    }
}
