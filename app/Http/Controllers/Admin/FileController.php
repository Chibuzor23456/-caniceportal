<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
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
            $url = Storage::disk('r2')->temporaryUrl($file->file_path, now()->addMinutes(10));
        } catch (\Throwable) {
            abort(503, 'File storage is temporarily unavailable. Try again shortly.');
        }

        return redirect()->away($url);
    }
}
