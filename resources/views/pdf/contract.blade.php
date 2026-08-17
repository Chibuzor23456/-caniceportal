<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 90px 50px 70px 50px;
        }

        body { font-family: 'DejaVu Sans', sans-serif; color: #0a0f1c; font-size: 11px; line-height: 1.5; }

        .header { width: 100%; margin-bottom: 24px; }
        .header td { vertical-align: top; }
        .logo { height: 32px; }
        .doc-title { font-size: 20px; font-weight: bold; color: #0a0f1c; text-align: right; }
        .meta { text-align: right; color: #6b7280; font-size: 10px; }

        .client-box { background: #f3f4f6; padding: 12px 16px; margin-bottom: 20px; }
        .client-box .label { color: #6b7280; font-size: 9px; text-transform: uppercase; }

        h2.section-title { font-size: 13px; color: #0a0f1c; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin: 20px 0 8px 0; }

        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 50%; vertical-align: top; padding-right: 20px; }
        .signatures img.sig { height: 40px; }
        .signatures .name { font-weight: bold; margin-top: 4px; }
        .signatures .sig-meta { text-align: left; color: #6b7280; font-size: 9px; }

        .footer-note { font-size: 9px; color: #9ca3af; margin-top: 30px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 50%;">
                <img class="logo" src="{{ public_path('images/brand/logo-full.png') }}" alt="Canice Technologies">
            </td>
            <td style="width: 50%;">
                <div class="doc-title">CONTRACT</div>
                <div class="meta">
                    {{ $contract->reference }}<br>
                    Issued {{ $contract->issue_date?->format('M j, Y') }}
                    @if ($contract->expiry_date)
                        <br>Valid until {{ $contract->expiry_date->format('M j, Y') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="client-box">
        <div class="label">Prepared For</div>
        <strong>{{ $contract->client->company_name }}</strong><br>
        {{ $contract->client->contact_person }}<br>
        {{ $contract->client->email }}
    </div>

    <h2 class="section-title">{{ $contract->title }}</h2>
    <div>{!! $contract->body !!}</div>

    @if ($contract->signature)
        <table class="signatures">
            <tr>
                <td>
                    <div class="label">Canice Technologies</div>
                    @if ($company->signature_image_path)
                        <img class="sig" src="{{ \App\Support\Pdf::embedImage($company->signature_image_path) }}">
                    @endif
                    <div class="name">{{ $company->signatory_name }}</div>
                    <div class="sig-meta">{{ $company->signatory_position }}</div>
                </td>
                <td>
                    <div class="label">{{ $contract->client->company_name }}</div>
                    @if ($contract->signature->signature_type === \App\Enums\SignatureType::Drawn && $contract->signature->signature_image_path)
                        <img class="sig" src="{{ \App\Support\Pdf::embedImage($contract->signature->signature_image_path) }}">
                        <div class="name">{{ $contract->signature->signer_name }}</div>
                    @else
                        <div class="name" style="font-style: italic;">{{ $contract->signature->signer_name }}</div>
                    @endif
                    <div class="sig-meta">
                        Signed {{ $contract->signature->signed_at->format('M j, Y \a\t g:i A') }}<br>
                        IP {{ $contract->signature->ip_address }}
                    </div>
                </td>
            </tr>
        </table>
    @endif

    <div class="footer-note">
        Canice Technologies &middot; Technology That Moves Business Forward.
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('Helvetica');
            $size = 9;
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 40;
            $pdf->page_text($x, $y, $text, $font, $size, [0.42, 0.45, 0.50]);
        }
    </script>
</body>
</html>
