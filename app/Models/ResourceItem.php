<?php

namespace App\Models;

use App\Enums\ResourceVisibility;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'resource_category_id', 'title', 'slug', 'description', 'type',
    'file_path', 'external_url', 'mime_type', 'size_bytes', 'visibility',
    'uploaded_by', 'published_at', 'download_count',
])]
class ResourceItem extends Model
{
    use HasFactory;

    protected $table = 'resources';

    protected function casts(): array
    {
        return [
            'visibility' => ResourceVisibility::class,
            'type' => 'string',
            'size_bytes' => 'integer',
            'published_at' => 'datetime',
            'download_count' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'resource_category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** Visibility check against the authenticated user's role (server-side truth). */
    public function visibleTo(?User $user): bool
    {
        if ($this->visibility === ResourceVisibility::Public) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($this->visibility) {
            ResourceVisibility::Students => $user->role === UserRole::Student,
            ResourceVisibility::Staff => $user->isStaff(),
        };
    }
}
