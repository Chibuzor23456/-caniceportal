<?php

namespace App\Mail;

use App\Models\ProjectPhase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PhaseApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProjectPhase $phase) {}

    public function build(): self
    {
        return $this->subject("Phase approved: {$this->phase->project->title} - {$this->phase->name}")
            ->markdown('emails.projects.phase-approved', [
                'phase' => $this->phase,
                'url' => route('admin.projects.show', $this->phase->project),
            ]);
    }
}
