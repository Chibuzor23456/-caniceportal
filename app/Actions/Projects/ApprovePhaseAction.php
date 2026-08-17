<?php

namespace App\Actions\Projects;

use App\Enums\PhaseStatus;
use App\Enums\ProjectStatus;
use App\Mail\PhaseApprovedMail;
use App\Mail\ProjectCompletedMail;
use App\Models\ActivityLog;
use App\Models\ProjectPhase;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ApprovePhaseAction
{
    /**
     * Returns true if this call is the one that actually recorded the
     * approval, false if the phase isn't in an approvable state (already
     * approved, or a near-simultaneous request beat this one to it).
     */
    public function handle(ProjectPhase $phase): bool
    {
        $approved = DB::transaction(function () use ($phase) {
            /** @var ProjectPhase $locked */
            $locked = ProjectPhase::query()->lockForUpdate()->findOrFail($phase->id);

            if (! in_array($locked->status, [PhaseStatus::PendingReview, PhaseStatus::InDiscussion], true)) {
                return false;
            }

            $locked->forceFill([
                'status' => PhaseStatus::Approved,
                'approved_at' => now(),
            ])->save();

            return true;
        });

        if (! $approved) {
            return false;
        }

        $phase->refresh();
        $project = $phase->project;

        ActivityLog::record(
            action: 'project.phase_approved',
            description: "Phase \"{$phase->name}\" was approved by {$project->client->company_name}.",
            subject: $phase,
            client: $project->client,
        );

        User::admins()->get()->each(function (User $admin) use ($phase, $project) {
            Mail::to($admin->email)->queue(new PhaseApprovedMail($phase));
            $admin->notify(new GenericNotification(
                title: 'Phase approved',
                body: "{$project->client->company_name} approved phase \"{$phase->name}\".",
                url: route('admin.projects.show', $project),
            ));
        });

        $allApproved = $project->phases()->where('status', '!=', PhaseStatus::Approved->value)->doesntExist();

        if ($allApproved) {
            $project->forceFill([
                'status' => ProjectStatus::Completed,
                'completion_date' => now(),
            ])->save();

            ActivityLog::record(
                action: 'project.completed',
                description: "Project \"{$project->title}\" was marked completed.",
                subject: $project,
                client: $project->client,
            );

            Mail::to($project->client->email)->queue(new ProjectCompletedMail($project, forAdmin: false));
            $project->client->user?->notify(new GenericNotification(
                title: 'Project completed',
                body: "\"{$project->title}\" is complete - every phase has been approved.",
                url: route('client.projects.show', $project),
            ));

            User::admins()->get()->each(function (User $admin) use ($project) {
                Mail::to($admin->email)->queue(new ProjectCompletedMail($project, forAdmin: true));
                $admin->notify(new GenericNotification(
                    title: 'Project completed',
                    body: "\"{$project->title}\" is complete - every phase has been approved.",
                    url: route('admin.projects.show', $project),
                ));
            });
        }

        return true;
    }
}
