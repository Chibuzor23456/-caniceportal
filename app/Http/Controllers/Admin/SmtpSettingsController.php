<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\TestSmtpConnectionAction;
use App\Http\Controllers\Controller;
use App\Support\EnvFileWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmtpSettingsController extends Controller
{
    public function show(): View
    {
        return view('admin.settings.smtp', [
            'settings' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.scheme') === 'smtps' ? 'ssl' : 'tls',
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
        ]);
    }

    public function test(Request $request, TestSmtpConnectionAction $action): JsonResponse
    {
        $data = $request->validate($this->rules());

        return response()->json($action->handle($data));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge($this->rules(), [
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ]));

        EnvFileWriter::set([
            'MAIL_MAILER' => 'smtp',
            'MAIL_SCHEME' => $data['encryption'] === 'ssl' ? 'smtps' : 'smtp',
            'MAIL_HOST' => $data['host'],
            'MAIL_PORT' => $data['port'],
            'MAIL_USERNAME' => $data['username'] ?? '',
            'MAIL_PASSWORD' => $data['password'] ?: config('mail.mailers.smtp.password'),
            'MAIL_FROM_ADDRESS' => $data['from_address'],
            'MAIL_FROM_NAME' => $data['from_name'],
        ]);

        return redirect()->route('admin.settings.smtp')->with('status', 'SMTP settings updated.');
    }

    private function rules(): array
    {
        return [
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'numeric'],
            'encryption' => ['required', 'in:tls,ssl'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
