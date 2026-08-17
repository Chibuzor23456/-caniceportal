<?php

namespace App\Models;

use App\Enums\SectionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'title', 'body', 'order'])]
class QuotationSection extends Model
{
    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
