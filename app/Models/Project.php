<?php

namespace App\Models;

use App\Enums\PhaseStatus;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_id', 'quotation_id', 'title', 'description', 'notes', 'status', 'expected_delivery_date', 'completion_date'])]
class Project extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'expected_delivery_date' => 'date',
            'completion_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('order');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Never stored (Section 13: "never manually entered by the admin").
     */
    public function progressPercentage(): int
    {
        $total = $this->phases->count();

        if ($total === 0) {
            return 0;
        }

        $approved = $this->phases->where('status', PhaseStatus::Approved)->count();

        return (int) round(($approved / $total) * 100);
    }

    public function currentPhase(): ?ProjectPhase
    {
        return $this->phases->first(fn (ProjectPhase $phase) => $phase->status !== PhaseStatus::Approved);
    }
}
