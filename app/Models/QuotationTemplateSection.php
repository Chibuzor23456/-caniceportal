<?php

namespace App\Models;

use App\Enums\SectionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'title', 'body', 'order'])]
class QuotationTemplateSection extends Model
{
    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QuotationTemplate::class, 'quotation_template_id');
    }
}
