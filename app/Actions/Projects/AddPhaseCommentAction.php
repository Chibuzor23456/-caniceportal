<?php

namespace App\Actions\Projects;

use App\Enums\PhaseStatus;
use App\Mail\PhaseCommentMail;
use App\Models\ActivityLog;
use App\Models\ProjectPhase;
use App\Models\ProjectPhaseComment;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\Mail;

class AddPhaseCommentAction
{
    public function handle(ProjectPhase $phase, User $author, string $body): ProjectPhaseComment
    {
        $comment = $phase->comments()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);

        // Approved threads are locked read-only (Section 13); a comment
        // can't reopen one because the UI never offers the box to post it.
        if ($phase->status !== PhaseStatus::Approved) {
            $phase->forceFill(['status' => PhaseStatus::InDiscussion])->save();
        }

        ActivityLog::record(
            action: 'project.phase_commented',
            description: "{$author->name} commented on phase \"{$phase->name}\".",
            subject: $phase,
            client: $phase->project->client,
        );

        if ($author->isAdmin()) {
            Mail::to($phase->project->client->email)->queue(new PhaseCommentMail($phase, $comment));
            $phase->project->client->user?->notify(new GenericNotification(
                title: 'New comment',
                body: "{$author->name} commented on phase \"{$phase->name}\".",
                url: route('client.projects.show', $phase->project),
            ));
        } else {
            User::admins()->get()->each(function (User $admin) use ($phase, $comment, $author) {
                Mail::to($admin->email)->queue(new PhaseCommentMail($phase, $comment));
                $admin->notify(new GenericNotification(
                    title: 'New comment',
                    body: "{$author->name} commented on phase \"{$phase->name}\".",
                    url: route('admin.projects.show', $phase->project),
                ));
            });
        }

        return $comment;
    }
}
