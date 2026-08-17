<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View
    {
        $clients = Client::query()
            ->with(['messages' => fn ($q) => $q->latest('created_at')->limit(1)])
            ->get()
            ->map(function (Client $client) {
                $client->unread_count = $client->messages()
                    ->whereNull('read_at')
                    ->whereHas('sender', fn ($q) => $q->where('role', 'client'))
                    ->count();

                return $client;
            })
            ->sortByDesc(fn (Client $client) => $client->messages->first()?->created_at ?? $client->created_at)
            ->values();

        return view('admin.messages.index', ['clients' => $clients]);
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        return view('admin.messages.show', ['client' => $client]);
    }
}
