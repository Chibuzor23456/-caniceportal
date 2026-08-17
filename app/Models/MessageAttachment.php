<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['message_id', 'file_path', 'original_filename', 'mime_type', 'size'])]
class MessageAttachment extends Model
{
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
