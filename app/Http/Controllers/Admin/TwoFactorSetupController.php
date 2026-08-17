<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TwoFactorSetupController extends Controller
{
    public function show(): View
    {
        return view('admin.security.two-factor-setup');
    }
}
