<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user()->client;

        $activity = $client
            ? ActivityLog::where('client_id', $client->id)->latest('created_at')->paginate(20)
            : ActivityLog::whereRaw('1 = 0')->paginate(20);

        return view('client.activity.index', ['activity' => $activity]);
    }
}
