<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['signature_image_path', 'signatory_name', 'signatory_position', 'default_currency', 'timezone', 'bank_details'])]
class CompanySetting extends Model
{
    public static function current(): self
    {
        // Explicit, not left to the DB column default: firstOrCreate()
        // doesn't reflect DB-level defaults back onto the in-memory model
        // it returns, so a caller reading the attribute in the same request
        // as its first-ever creation would see null instead of the real
        // value.
        return static::query()->firstOrCreate([], ['default_currency' => 'NGN', 'timezone' => 'Africa/Lagos']);
    }
}
