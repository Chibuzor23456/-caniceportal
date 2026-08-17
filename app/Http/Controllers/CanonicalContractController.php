<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\ContractPdfService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CanonicalContractController extends Controller
{
    public function __invoke(string $slug, Request $request, ContractPdfService $pdf): View
    {
        $contract = Contract::query()
            ->where('slug', $slug)
            ->with(['signature', 'client'])
            ->firstOrFail();

        $user = $request->user();
        $allowed = $user->isAdmin() || ($user->isClient() && $user->client?->id === $contract->client_id);

        if (! $allowed) {
            throw new AccessDeniedHttpException;
        }

        return view('contracts.canonical', [
            'contract' => $contract,
            'pdfUrl' => $contract->status->value === 'accepted' ? $pdf->temporaryUrl($contract) : null,
        ]);
    }
}
