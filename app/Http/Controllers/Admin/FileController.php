<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class FileController extends Controller
{
    public function index(): View
    {
        return view('admin.files.index');
    }

    public function download(ClientFile $file): RedirectResponse
    {
        try {
            $url = URL::temporarySignedRoute('storage.local', now()->addMinutes(10), ['path' => $file->file_path]);
        } catch (\Throwable) {
            abort(503, 'File storage is temporarily unavailable. Try again shortly.');
        }

        return redirect()->away($url);
    }
}
