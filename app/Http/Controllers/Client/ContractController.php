<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractPdfService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $contracts = Contract::query()
            ->where('client_id', $request->user()->client?->id)
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->get();

        return view('client.contracts.index', ['contracts' => $contracts]);
    }

    public function show(Contract $contract, Request $request, ContractPdfService $pdf): View
    {
        abort_unless($contract->client_id === $request->user()->client?->id, 404);

        $contract->load('signature', 'client');

        return view('client.contracts.show', [
            'contract' => $contract,
            'pdfUrl' => $contract->status->value === 'accepted' ? $pdf->temporaryUrl($contract) : null,
        ]);
    }
}
