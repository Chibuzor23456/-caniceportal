<?php

namespace App\Mail;

use App\Models\Project;

class ProjectCompletedMail extends TemplatedMail
{
    public function __construct(
        public Project $project,
        public bool $forAdmin = false,
    ) {}

    protected function type(): string
    {
        return 'project_completed';
    }

    protected function fallbackSubject(): string
    {
        return "Project completed: {$this->project->title}";
    }

    protected function mailView(): string
    {
        return 'emails.projects.completed';
    }

    protected function viewData(): array
    {
        return [
            'project' => $this->project,
            'forAdmin' => $this->forAdmin,
            'url' => $this->url(),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'project_title' => $this->project->title,
            'client_name' => $this->project->client->company_name,
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return $this->forAdmin
            ? route('admin.projects.show', $this->project)
            : route('client.projects.show', $this->project);
    }
}
