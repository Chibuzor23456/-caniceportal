<?php

namespace App\Http\Controllers\Client;

use App\Actions\Testimonials\SubmitTestimonialAction;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function store(Testimonial $testimonial, Request $request, SubmitTestimonialAction $action): RedirectResponse
    {
        abort_unless($testimonial->client_id === $request->user()->client?->id, 404);
        abort_if($testimonial->submitted_at !== null, 404);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->handle($testimonial, $validated['rating'], $validated['comment'] ?? null);

        return back()->with('status', 'Thanks for your feedback!');
    }
}
