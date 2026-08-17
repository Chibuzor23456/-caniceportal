<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * Thin wrapper around bacon/bacon-qr-code (already a dependency via Fortify's
 * 2FA setup). Rasterizes to a PNG via GD rather than the library's SVG
 * backend: DomPDF's inline SVG support is unreliable for the dense, deeply
 * nested paths a real QR code produces (renders blank), while a plain <img>
 * with a base64 PNG works everywhere DomPDF is used, both in the PDF and in
 * regular Blade views.
 */
class QrCode
{
    public static function pngDataUri(string $data, int $size = 240): string
    {
        $qrCode = Encoder::encode($data, ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $moduleCount = $matrix->getWidth();

        $quietZoneModules = 4;
        $totalModules = $moduleCount + ($quietZoneModules * 2);
        $scale = max(1, intdiv($size, $totalModules));
        $imageSize = $totalModules * $scale;

        $image = imagecreatetruecolor($imageSize, $imageSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $left = ($x + $quietZoneModules) * $scale;
                    $top = ($y + $quietZoneModules) * $scale;
                    imagefilledrectangle($image, $left, $top, $left + $scale - 1, $top + $scale - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}
