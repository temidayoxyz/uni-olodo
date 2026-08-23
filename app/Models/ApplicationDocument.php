<?php

namespace App\Models;

use App\Enums\DocumentVerification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'application_id', 'type', 'original_name', 'stored_path', 'mime_type', 'size_bytes',
    'verification', 'reviewer_note', 'reviewed_by', 'reviewed_at',
])]
class ApplicationDocument extends Model
{
    use HasFactory;

    public const TYPES = [
        'passport_photograph' => 'Passport photograph',
        'olevel_result' => 'O-level result (WAEC/NECO/NABTEB)',
        'entrance_exam_slip' => 'Entrance examination slip',
        'birth_certificate' => 'Birth certificate / declaration of age',
        'other' => 'Other supporting document',
    ];

    protected function casts(): array
    {
        return [
            'verification' => DocumentVerification::class,
            'size_bytes' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
}
