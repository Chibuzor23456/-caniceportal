<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable(['name', 'created_by'])]
class QuotationTemplate extends Model
{
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(QuotationTemplateSection::class)->orderBy('order');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(QuotationTemplateLineItem::class)->orderBy('order');
    }

    public function paymentPhases(): HasMany
    {
        return $this->hasMany(QuotationTemplatePaymentPhase::class)->orderBy('order');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
