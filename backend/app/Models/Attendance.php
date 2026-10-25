<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Attendance extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'attendances';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'class_group_id',
        'subject_id',
        'academic_year_id',
        'date',
        'lesson_number',
        'topic',
        'notes',
        'recorded_by_user_id',
        'client_mutation_id',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'lesson_number' => 'integer',
            'synced_at'     => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'class_group_id',
                'subject_id',
                'date',
                'lesson_number',
                'topic',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ─── Relações Eloquent ───────────────────────────────────────────────────

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    // ─── Métricas Rápidas ────────────────────────────────────────────────────

    public function getPresentCountAttribute(): int
    {
        return $this->records->where('status', AttendanceStatusEnum::PRESENTE)->count();
    }

    public function getUnjustifiedCountAttribute(): int
    {
        return $this->records->where('status', AttendanceStatusEnum::FALTA_INJUSTIFICADA)->count();
    }

    public function getJustifiedCountAttribute(): int
    {
        return $this->records->where('status', AttendanceStatusEnum::FALTA_JUSTIFICADA)->count();
    }

    public function getLateCountAttribute(): int
    {
        return $this->records->where('status', AttendanceStatusEnum::ATRASO)->count();
    }
}
