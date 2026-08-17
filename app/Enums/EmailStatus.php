<?php

namespace App\Enums;

enum EmailStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
    case Bounced = 'bounced';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Bounced => 'Bounced',
        };
    }

    public function pillColor(): string
    {
        return match ($this) {
            self::Sent => 'green',
            self::Failed => 'red',
            self::Bounced => 'orange',
        };
    }
}
