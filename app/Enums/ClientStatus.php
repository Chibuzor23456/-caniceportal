<?php

namespace App\Enums;

enum ClientStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Past = 'past';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Past => 'Past',
        };
    }

    public function pillColor(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'orange',
            self::Past => 'gray',
        };
    }
}
