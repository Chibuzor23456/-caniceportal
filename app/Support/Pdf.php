<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Pdf
{
    /**
     * DomPDF renders from a string of HTML with no browser-style network
     * fetching, so stored images are embedded as base64 data URIs instead of
     * being referenced by URL.
     */
    public static function embedImage(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            if (! Storage::disk('local')->exists($path)) {
                return null;
            }

            $contents = Storage::disk('local')->get($path);
            $mime = Storage::disk('local')->mimeType($path) ?? 'image/png';

            return "data:{$mime};base64,".base64_encode($contents);
        } catch (\Throwable) {
            return null;
        }
    }
}
