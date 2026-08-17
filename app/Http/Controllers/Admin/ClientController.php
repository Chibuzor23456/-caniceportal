<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Client::class);

        return view('admin.clients.index');
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('admin.clients.form', ['client' => null]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('admin.clients.form', ['client' => $client]);
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        $client->load('tags', 'user');

        return view('admin.clients.show', ['client' => $client]);
    }

    public function export(): Response
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::orderBy('company_name')->get();

        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['Company', 'Contact Person', 'Email', 'Phone', 'Industry', 'Status', 'Date Joined']);

        foreach ($clients as $client) {
            fputcsv($csv, [
                $client->company_name,
                $client->contact_person,
                $client->email,
                $client->phone,
                $client->industry,
                $client->status->label(),
                $client->date_joined?->format('Y-m-d'),
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="clients.csv"',
        ]);
    }
}
