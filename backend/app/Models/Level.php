<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'levels';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'order_index',
    ];

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class)->orderBy('order_index');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
