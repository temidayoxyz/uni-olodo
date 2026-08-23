<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'phone', 'subject', 'message', 'status', 'handled_by', 'handled_at'])]
class ContactEnquiry extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }
}
