<?php

namespace App\Mail;

use App\Models\ProjectPhase;
use App\Models\ProjectPhaseDeliverable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeliverableUploadedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectPhase $phase,
        public ProjectPhaseDeliverable $deliverable,
    ) {}

    public function build(): self
    {
        return $this->subject("New deliverable for {$this->phase->project->title} - {$this->phase->name}")
            ->markdown('emails.projects.deliverable-uploaded', [
                'phase' => $this->phase,
                'deliverable' => $this->deliverable,
                'url' => route('client.projects.show', $this->phase->project),
            ]);
    }
}
