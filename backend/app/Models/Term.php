<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Term extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'terms';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'academic_year_id',
        'term_number',
        'name',
        'start_date',
        'end_date',
        'grades_deadline',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'term_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'grades_deadline' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Verifica se o conselho de notas deste trimestre está bloqueado para edição normal.
     */
    public function isGradesDeadlinePassed(): bool
    {
        if ($this->is_closed) {
            return true;
        }

        if (!$this->grades_deadline) {
            return false;
        }

        return now()->startOfDay()->gt($this->grades_deadline->endOfDay());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'grades_deadline', 'is_closed'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Trimestre {$this->name} foi {$eventName}");
    }
}
