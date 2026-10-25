<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'grades';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'cycle_id',
        'code',
        'name',
        'order_index',
        'is_cycle_ending',
    ];

    protected function casts(): array
    {
        return [
            'is_cycle_ending' => 'boolean',
            'order_index' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function curriculumPlans(): HasMany
    {
        return $this->hasMany(CurriculumPlan::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
