<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user()->client;

        abort_unless($client, 404);

        return view('client.messages.index', ['client' => $client]);
    }
}
