<?php

namespace App\Http\Controllers\Public;

use App\Actions\Quotations\AcceptQuotationAction;
use App\Actions\Quotations\RecordQuotationViewAction;
use App\Actions\Quotations\RejectQuotationAction;
use App\Enums\SignatureType;
use App\Http\Controllers\Controller;
use App\Mail\QuotationRevisionRequestedMail;
use App\Models\ActivityLog;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SecureQuotationController extends Controller
{
    public function show(string $token, RecordQuotationViewAction $recordView): View
    {
        $quotation = $this->resolveToken($token);

        $recordView->handle($quotation);

        return view('quotations.secure', ['quotation' => $quotation->fresh(['sections', 'lineItems', 'paymentPhases', 'signature', 'client'])]);
    }

    public function accept(Request $request, string $token, AcceptQuotationAction $accept): RedirectResponse
    {
        $quotation = $this->resolveToken($token);

        if (! $quotation->status->acceptsSignature()) {
            return redirect()->route('quotation.secure', $token);
        }

        $data = $request->validate([
            'signer_name' => ['required', 'string', 'max:255'],
            'signature_type' => ['required', 'in:typed,drawn'],
            'signature_data' => ['required_if:signature_type,drawn', 'nullable', 'string'],
        ]);

        $signatureType = SignatureType::from($data['signature_type']);
        $imagePath = null;

        if ($signatureType === SignatureType::Drawn) {
            $imagePath = $this->storeDrawnSignature($quotation, $data['signature_data']);
        }

        $accept->handle($quotation, $request, $data['signer_name'], $signatureType, $imagePath);

        return redirect()->route('quotation.secure', $token);
    }

    public function reject(Request $request, string $token, RejectQuotationAction $reject): RedirectResponse
    {
        $quotation = $this->resolveToken($token);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $reject->handle($quotation, $data['reason']);

        return redirect()->route('quotation.secure', $token);
    }

    public function requestRevision(string $token): RedirectResponse
    {
        $quotation = $this->resolveToken($token);

        ActivityLog::record(
            action: 'quotation.revision_requested',
            description: "{$quotation->client->company_name} requested a revision on expired quotation {$quotation->reference}.",
            subject: $quotation,
            client: $quotation->client,
        );

        User::admins()->get()->each(
            fn (User $admin) => Mail::to($admin->email)->queue(new QuotationRevisionRequestedMail($quotation))
        );

        return redirect()->route('quotation.secure', $token)->with('status', 'We\'ve let Canice Technologies know you\'d like a revised quotation.');
    }

    private function resolveToken(string $token): Quotation
    {
        return Quotation::query()->where('secure_token', $token)->firstOrFail();
    }

    private function storeDrawnSignature(Quotation $quotation, string $dataUrl): string
    {
        [, $encoded] = explode(',', $dataUrl, 2) + [null, $dataUrl];

        $path = "quotations/{$quotation->reference}/signature-".now()->timestamp.'.png';

        Storage::disk('r2')->put($path, base64_decode($encoded));

        return $path;
    }
}
