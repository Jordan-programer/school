<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cycle extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cycles';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'level_id',
        'cycle_number',
        'name',
        'order_index',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class)->orderBy('order_index');
    }
}
