<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function show(): View
    {
        $settings = CompanySetting::current();
        $signatureUrl = null;

        if ($settings->signature_image_path) {
            try {
                $signatureUrl = URL::temporarySignedRoute('storage.local', now()->addMinutes(10), ['path' => $settings->signature_image_path]);
            } catch (\Throwable) {
                $signatureUrl = null;
            }
        }

        return view('admin.settings.company', ['settings' => $settings, 'signatureUrl' => $signatureUrl]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'signatory_name' => ['required', 'string', 'max:255'],
            'signatory_position' => ['required', 'string', 'max:255'],
            'default_currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'timezone'],
            'bank_details' => ['nullable', 'string', 'max:2000'],
            'signature_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $settings = CompanySetting::current();

        if ($request->hasFile('signature_image')) {
            if ($settings->signature_image_path) {
                Storage::disk('local')->delete($settings->signature_image_path);
            }

            $data['signature_image_path'] = $request->file('signature_image')->store('company', 'local');
        }

        unset($data['signature_image']);

        $settings->update($data);

        return redirect()->route('admin.settings.company')->with('status', 'Company settings updated.');
    }
}
