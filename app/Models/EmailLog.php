<?php

namespace App\Models;

use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['recipient', 'subject', 'message_id', 'status', 'error_message', 'bounced_at'])]
class EmailLog extends Model
{
    protected function casts(): array
    {
        return [
            'status' => EmailStatus::class,
            'bounced_at' => 'datetime',
        ];
    }
}
