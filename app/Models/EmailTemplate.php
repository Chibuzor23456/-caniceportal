<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'subject', 'body', 'variables'])]
class EmailTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }
}
