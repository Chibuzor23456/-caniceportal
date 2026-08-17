<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'client_id',
    'template_id',
    'created_by',
    'reference',
    'slug',
    'status',
    'version',
    'currency',
    'watermark_text',
    'rejection_reason',
    'issue_date',
    'expiry_date',
])]
class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'secure_token_expires_at' => 'datetime',
            'reminder_3d_sent_at' => 'datetime',
            'reminder_1d_sent_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QuotationTemplate::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(QuotationSection::class)->orderBy('order');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(QuotationLineItem::class)->orderBy('order');
    }

    public function paymentPhases(): HasMany
    {
        return $this->hasMany(QuotationPaymentPhase::class)->orderBy('order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(QuotationVersion::class)->orderByDesc('version_number');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(QuotationSignature::class);
    }

    public function signature(): HasOne
    {
        return $this->hasOne(QuotationSignature::class)->latestOfMany();
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function grandTotal(): float
    {
        return (float) $this->lineItems->sum('line_total');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && Carbon::now()->startOfDay()->gt($this->expiry_date);
    }
}
