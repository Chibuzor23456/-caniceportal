<?php

namespace App\Models;

use App\Enums\SignatureType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['signer_name', 'signature_type', 'signature_image_path', 'signed_at', 'ip_address', 'user_agent', 'device_summary'])]
class ContractSignature extends Model
{
    protected function casts(): array
    {
        return [
            'signature_type' => SignatureType::class,
            'signed_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
