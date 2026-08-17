<?php

namespace App\Actions\Testimonials;

use App\Models\ActivityLog;
use App\Models\Testimonial;
use App\Models\User;
use App\Notifications\GenericNotification;

class SubmitTestimonialAction
{
    public function handle(Testimonial $testimonial, int $rating, ?string $comment = null): Testimonial
    {
        $testimonial->forceFill([
            'rating' => $rating,
            'comment' => $comment,
            'submitted_at' => now(),
        ])->save();

        ActivityLog::record(
            action: 'testimonial.submitted',
            description: "{$testimonial->client->company_name} left a {$rating}-star testimonial for \"{$testimonial->project->title}\".",
            subject: $testimonial,
            client: $testimonial->client,
        );

        User::admins()->get()->each(function (User $admin) use ($testimonial, $rating) {
            $admin->notify(new GenericNotification(
                title: 'New testimonial',
                body: "{$testimonial->client->company_name} left a {$rating}-star testimonial.",
                url: route('admin.testimonials.index'),
            ));
        });

        return $testimonial;
    }
}
