<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_phase_deliverable_id', 'file_path', 'original_filename', 'mime_type', 'size'])]
class ProjectPhaseFile extends Model
{
    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(ProjectPhaseDeliverable::class, 'project_phase_deliverable_id');
    }
}
