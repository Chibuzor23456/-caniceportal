<?php

namespace App\Mail;

use App\Models\ProjectPhase;
use App\Models\ProjectPhaseComment;

class PhaseCommentMail extends TemplatedMail
{
    public function __construct(
        public ProjectPhase $phase,
        public ProjectPhaseComment $comment,
    ) {}

    protected function type(): string
    {
        return 'phase_comment';
    }

    protected function fallbackSubject(): string
    {
        return "New comment on {$this->phase->project->title} - {$this->phase->name}";
    }

    protected function mailView(): string
    {
        return 'emails.projects.phase-comment';
    }

    protected function viewData(): array
    {
        return [
            'phase' => $this->phase,
            'comment' => $this->comment,
            // The recipient is whichever party didn't just comment.
            'url' => $this->url(),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'project_title' => $this->phase->project->title,
            'phase_name' => $this->phase->name,
            'author_name' => $this->comment->author->name,
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return $this->comment->author->isAdmin()
            ? route('client.projects.show', $this->phase->project)
            : route('admin.projects.show', $this->phase->project);
    }
}
