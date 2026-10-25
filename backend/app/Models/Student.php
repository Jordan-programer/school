<?php

namespace App\Models;

use App\Enums\DocumentTypeEnum;
use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasUuids, HasFactory, SoftDeletes;

    protected $fillable = [
        'student_uid',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'id_document_type',
        'id_document_number',
        'id_document_file_path',
        'has_special_needs',
        'special_needs_description',
        'origin_school',
        'province',
        'municipality',
        'neighborhood',
        'address',
        'photo_path',
        'is_active',
    ];

    protected $casts = [
        'birth_date'       => 'date',
        'gender'           => GenderEnum::class,
        'id_document_type' => DocumentTypeEnum::class,
        'has_special_needs' => 'boolean',
        'is_active'        => 'boolean',
    ];

    /**
     * Nome completo do aluno.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Calcular idade do aluno.
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date->age;
    }

    /**
     * Encarregados de educação do aluno.
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardian')
            ->withPivot(['relationship_type', 'is_primary_contact', 'is_financially_responsible'])
            ->withTimestamps();
    }

    /**
     * Encarregado principal de contacto.
     */
    public function primaryGuardian()
    {
        return $this->guardians()->wherePivot('is_primary_contact', true)->first();
    }

    /**
     * Matrículas e confirmações históricas do aluno.
     */
    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Transferências escolares do aluno.
     */
    public function transfers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentTransfer::class);
    }

    /**
     * Registos de assiduidade e chamadas do aluno.
     */
    public function attendanceRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Gerar código único do aluno (ex: 2026-0001).
     */
    public static function generateStudentUid(int $schoolYear): string
    {
        $lastStudent = static::withTrashed()
            ->where('student_uid', 'like', "{$schoolYear}-%")
            ->orderByDesc('student_uid')
            ->first();

        if ($lastStudent) {
            $lastNum = (int) substr($lastStudent->student_uid, -4);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return sprintf('%d-%04d', $schoolYear, $nextNum);
    }
}
