<?php

namespace App\Models;

use App\Enums\FileCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'uploaded_by', 'category', 'file_path', 'original_filename', 'mime_type', 'size', 'description'])]
class ClientFile extends Model
{
    protected function casts(): array
    {
        return [
            'category' => FileCategory::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
