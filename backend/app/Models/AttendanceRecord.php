<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'attendance_records';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'attendance_id',
        'student_id',
        'enrollment_id',
        'status',
        'justification_reason',
        'justified_at',
        'justified_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status'       => AttendanceStatusEnum::class,
            'justified_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function justifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'justified_by_user_id');
    }
}
