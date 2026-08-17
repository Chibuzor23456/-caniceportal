<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Pdf
{
    /**
     * DomPDF renders from a string of HTML with no browser-style network
     * fetching, and R2 (S3) disks don't support local path() resolution, so
     * images stored there are embedded as base64 data URIs instead.
     */
    public static function embedImage(?string $r2Path): ?string
    {
        if (! $r2Path) {
            return null;
        }

        try {
            if (! Storage::disk('r2')->exists($r2Path)) {
                return null;
            }

            $contents = Storage::disk('r2')->get($r2Path);
            $mime = Storage::disk('r2')->mimeType($r2Path) ?? 'image/png';

            return "data:{$mime};base64,".base64_encode($contents);
        } catch (\Throwable) {
            return null;
        }
    }
}
