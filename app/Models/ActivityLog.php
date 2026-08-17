<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['causer_id', 'client_id', 'subject_type', 'subject_id', 'action', 'description', 'properties'])]
class ActivityLog extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * $client is explicit rather than derived from $subject (which would
     * need per-subject-type reverse-lookup logic) so the client-facing
     * activity feed stays a simple `where('client_id', ...)` query.
     */
    public static function record(string $action, string $description, mixed $subject = null, array $properties = [], ?Client $client = null): self
    {
        return static::create([
            'causer_id' => auth()->id(),
            'client_id' => $client?->id,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}
