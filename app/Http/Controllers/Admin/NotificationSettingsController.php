<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    /**
     * Every category GenericNotification is fired under (App\Notifications\GenericNotification::via()).
     */
    public const CATEGORIES = [
        'quotation' => 'Quotations',
        'contract' => 'Contracts',
        'invoice' => 'Invoices',
        'project' => 'Projects & Phases',
        'message' => 'Messages',
        'file' => 'Files',
        'testimonial' => 'Testimonials',
        'client' => 'New Clients',
        'system' => 'System (deploys)',
    ];

    public function show(Request $request): View
    {
        $preferences = $request->user()->notification_preferences ?? [];

        return view('admin.settings.notifications', [
            'categories' => self::CATEGORIES,
            'preferences' => $preferences,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $enabled = $request->input('enabled', []);

        $preferences = collect(self::CATEGORIES)
            ->keys()
            ->mapWithKeys(fn (string $key) => [$key => in_array($key, $enabled, true)])
            ->all();

        $request->user()->update(['notification_preferences' => $preferences]);

        return redirect()->route('admin.settings.notifications')->with('status', 'Notification preferences updated.');
    }
}
