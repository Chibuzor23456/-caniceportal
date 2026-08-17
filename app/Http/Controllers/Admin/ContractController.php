<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Contracts\CreateContractAction;
use App\Actions\Contracts\SendContractAction;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Project;
use App\Services\ContractPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Contract::class);

        return view('admin.contracts.index');
    }

    public function create(): View
    {
        $this->authorize('viewAny', Contract::class);

        return view('admin.contracts.create', [
            'clients' => Client::orderBy('company_name')->get(),
        ]);
    }

    public function store(Request $request, CreateContractAction $action): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'mode' => ['required', 'in:write,upload'],
            'body' => ['required_if:mode,write', 'nullable', 'string'],
            'file' => ['required_if:mode,upload', 'nullable', 'file', 'max:10240'],
        ]);

        $contract = $action->handle(
            client: Client::findOrFail($data['client_id']),
            title: $data['title'],
            body: $data['mode'] === 'write' ? $data['body'] : null,
            file: $data['mode'] === 'upload' ? $request->file('file') : null,
            project: isset($data['project_id']) ? Project::find($data['project_id']) : null,
        );

        return redirect()->route('admin.contracts.show', $contract)->with('status', 'Contract created.');
    }

    public function show(Contract $contract, ContractPdfService $pdf): View
    {
        $this->authorize('view', $contract);

        $contract->load('client', 'project', 'signature');

        return view('admin.contracts.show', [
            'contract' => $contract,
            'pdfUrl' => in_array($contract->status->value, ['sent', 'viewed', 'accepted'], true) ? $pdf->temporaryUrl($contract) : null,
        ]);
    }

    public function send(Contract $contract, SendContractAction $action): RedirectResponse
    {
        abort_unless($contract->status->value === 'draft', 422, 'Only draft contracts can be sent.');

        $action->handle($contract);

        return redirect()->route('admin.contracts.show', $contract)->with('status', 'Contract sent.');
    }
}
