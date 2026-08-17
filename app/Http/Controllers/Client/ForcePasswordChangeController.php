<?php

namespace App\Http\Controllers\Client;

use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    use PasswordValidationRules;

    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('client.dashboard');
        }

        return view('client.password.change');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => $this->passwordRules(),
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        ActivityLog::record(
            action: 'client.password_changed',
            description: 'Set a new password after first login.',
            subject: $request->user(),
            client: $request->user()->client,
        );

        return redirect()->route('client.dashboard');
    }
}
