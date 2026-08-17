<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_name', 'service_category', 'quantity', 'unit_price', 'discount', 'tax', 'line_total', 'order'])]
class QuotationLineItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function computeLineTotal(): float
    {
        $subtotal = $this->quantity * $this->unit_price;

        return round($subtotal - $this->discount + $this->tax, 2);
    }
}
