<?php

namespace App\Models;

use App\Enums\DocumentTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use HasUuids, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'full_name',
        'id_document_type',
        'id_document_number',
        'phone_primary',
        'phone_secondary',
        'email',
        'profession',
        'workplace',
        'address',
    ];

    protected $casts = [
        'id_document_type' => DocumentTypeEnum::class,
    ];

    /**
     * Conta de utilizador associada (opcional).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alunos sob a responsabilidade deste encarregado.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_guardian')
            ->withPivot(['relationship_type', 'is_primary_contact', 'is_financially_responsible'])
            ->withTimestamps();
    }
}
