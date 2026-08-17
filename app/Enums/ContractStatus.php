<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }

    public function pillColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent, self::Viewed => 'blue',
            self::Accepted => 'green',
            self::Rejected, self::Expired => 'red',
        };
    }

    public function acceptsSignature(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Viewed], true);
    }
}
