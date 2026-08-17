<?php

namespace App\Http\Controllers\Client;

use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Not a new upload mechanism - an aggregate read-only view over every signed
 * quotation, signed contract, and paid invoice (Section 14: "accessible from
 * one dedicated tab" without digging through client_files/email).
 */
class DocumentArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $clientId = $request->user()->client?->id;

        $quotations = Quotation::where('client_id', $clientId)
            ->where('status', QuotationStatus::Accepted)
            ->get()
            ->map(fn (Quotation $q) => [
                'type' => 'Quotation',
                'title' => $q->reference,
                'date' => $q->accepted_at,
                'url' => route('client.quotations.show', $q),
            ]);

        $contracts = Contract::where('client_id', $clientId)
            ->where('status', ContractStatus::Accepted)
            ->get()
            ->map(fn (Contract $c) => [
                'type' => 'Contract',
                'title' => $c->title,
                'date' => $c->accepted_at,
                'url' => route('client.contracts.show', $c),
            ]);

        $invoices = Invoice::where('client_id', $clientId)
            ->where('status', InvoiceStatus::Paid)
            ->get()
            ->map(fn (Invoice $i) => [
                'type' => 'Invoice',
                'title' => $i->reference,
                'date' => $i->paid_at,
                'url' => route('client.invoices.show', $i),
            ]);

        /** @var Collection $documents */
        $documents = $quotations->concat($contracts)->concat($invoices)->sortByDesc('date')->values();

        return view('client.documents.index', ['documents' => $documents]);
    }
}
