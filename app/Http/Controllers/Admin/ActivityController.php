<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'activity');

        $activity = $tab === 'activity'
            ? ActivityLog::with('causer', 'client')->latest('created_at')->paginate(20)
            : null;

        $emailLogs = $tab === 'email'
            ? EmailLog::latest('created_at')->paginate(20)
            : null;

        return view('admin.activity.index', [
            'tab' => $tab,
            'activity' => $activity,
            'emailLogs' => $emailLogs,
        ]);
    }
}
