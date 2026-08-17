<?php

namespace App\Mail;

use App\Models\ProjectPhase;
use App\Models\ProjectPhaseDeliverable;

class DeliverableUploadedMail extends TemplatedMail
{
    public function __construct(
        public ProjectPhase $phase,
        public ProjectPhaseDeliverable $deliverable,
    ) {}

    protected function type(): string
    {
        return 'deliverable_uploaded';
    }

    protected function fallbackSubject(): string
    {
        return "New deliverable for {$this->phase->project->title} - {$this->phase->name}";
    }

    protected function mailView(): string
    {
        return 'emails.projects.deliverable-uploaded';
    }

    protected function viewData(): array
    {
        return [
            'phase' => $this->phase,
            'deliverable' => $this->deliverable,
            'url' => route('client.projects.show', $this->phase->project),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'project_title' => $this->phase->project->title,
            'phase_name' => $this->phase->name,
            'url' => route('client.projects.show', $this->phase->project),
        ];
    }
}
