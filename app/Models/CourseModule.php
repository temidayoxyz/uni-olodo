<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_offering_id', 'title', 'summary', 'position', 'published_at'])]
class CourseModule extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(CourseContent::class)->orderBy('position');
    }

    public function publishedContents(): HasMany
    {
        return $this->contents()->whereNotNull('published_at');
    }
}
