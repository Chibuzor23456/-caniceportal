<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'client_id',
    'project_id',
    'created_by',
    'reference',
    'slug',
    'title',
    'status',
    'body',
    'uploaded_file_path',
    'issue_date',
    'expiry_date',
    'rejection_reason',
])]
class Contract extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'secure_token_expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }

    public function signature(): HasOne
    {
        return $this->hasOne(ContractSignature::class)->latestOfMany();
    }

    public function isUploaded(): bool
    {
        return ! empty($this->uploaded_file_path);
    }
}
