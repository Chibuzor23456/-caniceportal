<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ComingSoonController extends Controller
{
    public function __invoke(Request $request): View
    {
        $isAdmin = $request->routeIs('admin.*');

        $label = Str::of($request->segment(2) ?? 'Feature')
            ->replace(['-', '_'], ' ')
            ->title();

        return view('shared.coming-soon', [
            'layout' => $isAdmin ? 'layouts.admin' : 'layouts.client',
            'label' => $label,
        ]);
    }
}
