<?php

namespace App\Mail;

use App\Models\ProjectPhase;
use App\Models\ProjectPhaseComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PhaseCommentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectPhase $phase,
        public ProjectPhaseComment $comment,
    ) {}

    public function build(): self
    {
        $authorIsAdmin = $this->comment->author->isAdmin();

        return $this->subject("New comment on {$this->phase->project->title} - {$this->phase->name}")
            ->markdown('emails.projects.phase-comment', [
                'phase' => $this->phase,
                'comment' => $this->comment,
                // The recipient is whichever party didn't just comment.
                'url' => $authorIsAdmin
                    ? route('client.projects.show', $this->phase->project)
                    : route('admin.projects.show', $this->phase->project),
            ]);
    }
}
