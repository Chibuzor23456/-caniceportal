<?php

namespace App\Mail;

use App\Models\ProjectPhase;

class PhaseApprovedMail extends TemplatedMail
{
    public function __construct(public ProjectPhase $phase) {}

    protected function type(): string
    {
        return 'phase_approved';
    }

    protected function fallbackSubject(): string
    {
        return "Phase approved: {$this->phase->project->title} - {$this->phase->name}";
    }

    protected function mailView(): string
    {
        return 'emails.projects.phase-approved';
    }

    protected function viewData(): array
    {
        return [
            'phase' => $this->phase,
            'url' => route('admin.projects.show', $this->phase->project),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'project_title' => $this->phase->project->title,
            'phase_name' => $this->phase->name,
            'client_name' => $this->phase->project->client->company_name,
            'url' => route('admin.projects.show', $this->phase->project),
        ];
    }
}
