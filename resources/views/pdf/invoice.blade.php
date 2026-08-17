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

        .status-pill { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 9px; text-transform: uppercase; font-weight: bold; }

        table.pricing { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.pricing th { background: #0a0f1c; color: #fff; padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: left; }
        table.pricing td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        table.pricing tr.total td { border-top: 2px solid #0a0f1c; font-weight: bold; font-size: 12px; }

        .bank-box { background: #f3f4f6; padding: 12px 16px; margin-top: 24px; }
        .bank-box .label { color: #6b7280; font-size: 9px; text-transform: uppercase; }

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
                <div class="doc-title">INVOICE</div>
                <div class="meta">
                    {{ $invoice->reference }}<br>
                    Issued {{ $invoice->issue_date?->format('M j, Y') }}<br>
                    @if ($invoice->due_date)
                        Due {{ $invoice->due_date->format('M j, Y') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="client-box">
        <div class="label">Bill To</div>
        <strong>{{ $invoice->client->company_name }}</strong><br>
        {{ $invoice->client->contact_person }}<br>
        {{ $invoice->client->email }}
        @if ($invoice->client->address)
            <br>{{ $invoice->client->address }}
        @endif
    </div>

    <table class="pricing">
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->description }}</td>
                <td>{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr class="total">
                <td style="text-align: right;">Total Due</td>
                <td>{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p>Status: <span class="status-pill" style="background: #e5e7eb; color: #374151;">{{ $invoice->status->label() }}</span></p>

    @if ($company->bank_details)
        <div class="bank-box">
            <div class="label">Payment / Bank Details</div>
            {!! nl2br(e($company->bank_details)) !!}
        </div>
    @endif

    <p class="footer-note">Canice Technologies &middot; {{ config('app.name') }}</p>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $pdf->page_text(270, 812, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 8, array(0.6, 0.6, 0.6));
        }
    </script>
</body>
</html>
