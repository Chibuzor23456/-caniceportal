<?php

namespace App\Http\Controllers\Public;

use App\Actions\Contracts\AcceptContractAction;
use App\Actions\Contracts\RecordContractViewAction;
use App\Actions\Contracts\RejectContractAction;
use App\Enums\SignatureType;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SecureContractController extends Controller
{
    public function show(string $token, RecordContractViewAction $recordView): View
    {
        $contract = $this->resolveToken($token);

        $recordView->handle($contract);

        return view('contracts.secure', ['contract' => $contract->fresh(['signature', 'client'])]);
    }

    public function accept(Request $request, string $token, AcceptContractAction $accept): RedirectResponse
    {
        $contract = $this->resolveToken($token);

        if (! $contract->status->acceptsSignature()) {
            return redirect()->route('contract.secure', $token);
        }

        $data = $request->validate([
            'signer_name' => ['required', 'string', 'max:255'],
            'signature_type' => ['required', 'in:typed,drawn'],
            'signature_data' => ['required_if:signature_type,drawn', 'nullable', 'string'],
        ]);

        $signatureType = SignatureType::from($data['signature_type']);
        $imagePath = null;

        if ($signatureType === SignatureType::Drawn) {
            $imagePath = $this->storeDrawnSignature($contract, $data['signature_data']);
        }

        $accept->handle($contract, $request, $data['signer_name'], $signatureType, $imagePath);

        return redirect()->route('contract.secure', $token);
    }

    public function reject(Request $request, string $token, RejectContractAction $reject): RedirectResponse
    {
        $contract = $this->resolveToken($token);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $reject->handle($contract, $data['reason']);

        return redirect()->route('contract.secure', $token);
    }

    private function resolveToken(string $token): Contract
    {
        return Contract::query()->where('secure_token', $token)->firstOrFail();
    }

    private function storeDrawnSignature(Contract $contract, string $dataUrl): string
    {
        [, $encoded] = explode(',', $dataUrl, 2) + [null, $dataUrl];

        $path = "contracts/{$contract->reference}/signature-".now()->timestamp.'.png';

        Storage::disk('local')->put($path, base64_decode($encoded));

        return $path;
    }
}
