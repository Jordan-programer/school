<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumPlan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'curriculum_plans';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'academic_year_id',
        'grade_id',
        'subject_id',
        'weekly_hours',
        'is_core',
    ];

    protected function casts(): array
    {
        return [
            'weekly_hours' => 'integer',
            'is_core' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
