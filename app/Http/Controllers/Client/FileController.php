<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class FileController extends Controller
{
    public function index(Request $request): View
    {
        $files = ClientFile::query()
            ->where('client_id', $request->user()->client?->id)
            ->latest('created_at')
            ->get()
            ->groupBy(fn (ClientFile $file) => $file->category->value);

        return view('client.files.index', ['filesByCategory' => $files]);
    }

    public function download(ClientFile $file, Request $request): RedirectResponse
    {
        abort_unless($file->client_id === $request->user()->client?->id, 404);

        try {
            $url = URL::temporarySignedRoute('storage.local', now()->addMinutes(10), ['path' => $file->file_path]);
        } catch (\Throwable) {
            abort(503, 'File storage is temporarily unavailable. Try again shortly.');
        }

        return redirect()->away($url);
    }
}
