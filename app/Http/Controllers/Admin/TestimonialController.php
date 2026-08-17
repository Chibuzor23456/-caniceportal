<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(): View
    {
        $testimonials = Testimonial::with(['client', 'project'])
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->paginate(15);

        return view('admin.testimonials.index', ['testimonials' => $testimonials]);
    }
}
