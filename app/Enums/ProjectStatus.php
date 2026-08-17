<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function pillColor(): string
    {
        return match ($this) {
            self::Active => 'blue',
            self::Paused => 'orange',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }
}
