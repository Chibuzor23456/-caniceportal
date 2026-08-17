<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public bool $forAdmin = false,
    ) {}

    public function build(): self
    {
        return $this->subject("Project completed: {$this->project->title}")
            ->markdown('emails.projects.completed', [
                'project' => $this->project,
                'forAdmin' => $this->forAdmin,
                'url' => $this->forAdmin
                    ? route('admin.projects.show', $this->project)
                    : route('client.projects.show', $this->project),
            ]);
    }
}
