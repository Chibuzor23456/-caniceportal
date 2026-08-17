<?php

namespace App\Models;

use App\Enums\PhaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'name', 'order', 'status', 'approved_at'])]
class ProjectPhase extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PhaseStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ProjectPhaseDeliverable::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProjectPhaseComment::class)->orderBy('created_at');
    }

    /**
     * Reachable once every earlier phase (by order) is approved.
     */
    public function isUnlocked(): bool
    {
        return ! $this->project->phases
            ->where('order', '<', $this->order)
            ->contains(fn (self $phase) => $phase->status !== PhaseStatus::Approved);
    }
}
