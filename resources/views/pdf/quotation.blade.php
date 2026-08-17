<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 90px 50px 70px 50px;
        }

        /* DejaVu Sans (bundled with DomPDF), not Helvetica: the base-14 PDF
           fonts don't cover the Naira sign (or most non-Latin-1 glyphs) an
           admin might type into a section, DejaVu does. */
        body { font-family: 'DejaVu Sans', sans-serif; color: #0a0f1c; font-size: 11px; line-height: 1.5; }

        .header { width: 100%; margin-bottom: 24px; }
        .header td { vertical-align: top; }
        .logo { height: 32px; }
        .doc-title { font-size: 20px; font-weight: bold; color: #0a0f1c; text-align: right; }
        .meta { text-align: right; color: #6b7280; font-size: 10px; }

        .client-box { background: #f3f4f6; padding: 12px 16px; margin-bottom: 20px; }
        .client-box .label { color: #6b7280; font-size: 9px; text-transform: uppercase; }

        h2.section-title { font-size: 13px; color: #0a0f1c; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin: 20px 0 8px 0; }

        table.toc { width: 100%; margin-bottom: 20px; }
        table.toc td { padding: 3px 0; font-size: 10px; color: #374151; }

        table.pricing { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.pricing th { background: #0a0f1c; color: #fff; padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: left; }
        table.pricing td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        table.pricing tr.total td { border-top: 2px solid #0a0f1c; font-weight: bold; font-size: 12px; }

        .footer-note { font-size: 9px; color: #9ca3af; margin-top: 30px; }

        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 50%; vertical-align: top; padding-right: 20px; }
        .signatures img.sig { height: 40px; }
        .signatures .name { font-weight: bold; margin-top: 4px; }
        .signatures .sig-meta { text-align: left; color: #6b7280; font-size: 9px; }

        .qr-box { text-align: center; margin-top: 20px; }
        .qr-box img { width: 90px; height: 90px; }
        .qr-box .ref { font-size: 9px; color: #6b7280; margin-top: 4px; }

        .watermark {
            position: fixed;
            top: 300px;
            left: 90px;
            font-size: 80px;
            color: #0a0f1c;
            opacity: 0.06;
            transform: rotate(-30deg);
        }
    </style>
</head>
<body>
    @if ($quotation->watermark_text)
        <div class="watermark">{{ $quotation->watermark_text }}</div>
    @endif

    <table class="header">
        <tr>
            <td style="width: 50%;">
                <img class="logo" src="{{ public_path('images/brand/logo-full.png') }}" alt="Canice Technologies">
            </td>
            <td style="width: 50%;">
                <div class="doc-title">QUOTATION</div>
                <div class="meta">
                    {{ $quotation->reference }}<br>
                    Issued {{ $quotation->issue_date?->format('M j, Y') }}<br>
                    @if ($quotation->expiry_date)
                        Valid until {{ $quotation->expiry_date->format('M j, Y') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="client-box">
        <div class="label">Prepared For</div>
        <strong>{{ $quotation->client->company_name }}</strong><br>
        {{ $quotation->client->contact_person }}<br>
        {{ $quotation->client->email }}
        @if ($quotation->client->address)
            <br>{{ $quotation->client->address }}
        @endif
    </div>

    @if ($quotation->sections->count() > 5)
        <h2 class="section-title">Table of Contents</h2>
        <table class="toc">
            @foreach ($quotation->sections as $i => $section)
                <tr><td>{{ $i + 1 }}. {{ $section->title }}</td></tr>
            @endforeach
        </table>
    @endif

    @foreach ($quotation->sections as $section)
        <h2 class="section-title">{{ $section->title }}</h2>

        @if ($section->type === \App\Enums\SectionType::PricingTable)
            <table class="pricing">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->lineItems as $item)
                        <tr>
                            <td>{{ $item->service_name }}</td>
                            <td>{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                            <td>{{ $quotation->currency }} {{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ $quotation->currency }} {{ number_format($item->discount, 2) }}</td>
                            <td>{{ $quotation->currency }} {{ number_format($item->tax, 2) }}</td>
                            <td>{{ $quotation->currency }} {{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="5" style="text-align: right;">Grand Total</td>
                        <td>{{ $quotation->currency }} {{ number_format($quotation->grandTotal(), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @elseif ($section->type === \App\Enums\SectionType::PaymentSchedule)
            <table class="pricing">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Due Condition</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->paymentPhases as $phase)
                        <tr>
                            <td>{{ $phase->description }}</td>
                            <td>{{ $quotation->currency }} {{ number_format($phase->amount, 2) }}</td>
                            <td>{{ $phase->due_condition }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="2" style="text-align: right;">Total</td>
                        <td>{{ $quotation->currency }} {{ number_format($quotation->paymentPhases->sum('amount'), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            {!! $section->body !!}
        @endif
    @endforeach

    @if ($quotation->signature)
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
                    <div class="label">{{ $quotation->client->company_name }}</div>
                    @if ($quotation->signature->signature_type === \App\Enums\SignatureType::Drawn && $quotation->signature->signature_image_path)
                        <img class="sig" src="{{ \App\Support\Pdf::embedImage($quotation->signature->signature_image_path) }}">
                        <div class="name">{{ $quotation->signature->signer_name }}</div>
                    @else
                        <div class="name" style="font-style: italic;">{{ $quotation->signature->signer_name }}</div>
                    @endif
                    <div class="sig-meta">
                        Signed {{ $quotation->signature->signed_at->format('M j, Y \a\t g:i A') }}<br>
                        IP {{ $quotation->signature->ip_address }}
                    </div>
                </td>
            </tr>
        </table>
    @endif

    <div class="qr-box">
        <img src="{{ \App\Support\QrCode::pngDataUri($verifyUrl) }}" alt="Verification QR code">
        <div class="ref">Scan to verify &middot; {{ $quotation->reference }}</div>
    </div>

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
