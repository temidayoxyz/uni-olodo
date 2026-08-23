<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quiz_id', 'type', 'prompt', 'options', 'answers', 'points', 'position'])]
class QuizQuestion extends Model
{
    use HasFactory;

    public const TYPES = [
        'single_choice' => 'Multiple choice (single answer)',
        'multi_choice' => 'Multiple select',
        'true_false' => 'True / False',
        'short_answer' => 'Short answer',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'answers' => 'array',
            'points' => 'float',
            'position' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Grade a response. Auto-scores all objective types; short answers are
     * case-insensitive exact matches against the stored accepted phrases.
     */
    public function grade(mixed $response): array // [is_correct, awarded_points]
    {
        if ($response === null) {
            return [false, 0.0];
        }

        $normalized = is_array($response)
            ? array_values(array_map('strval', $response))
            : [(string) $response];

        sort($normalized);
        $expected = $this->answers ?? [];
        sort($expected);

        $correct = match ($this->type) {
            'short_answer' => collect($this->answers ?? [])
                ->contains(fn (string $accepted) => mb_strtolower(trim((string) $response)) === mb_strtolower(trim($accepted))),
            default => $normalized === $expected,
        };

        return [$correct, $correct ? (float) $this->points : 0.0];
    }
}
