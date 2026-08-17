<?php

namespace App\Actions\Projects;

use App\Enums\PhaseStatus;
use App\Mail\DeliverableUploadedMail;
use App\Models\ActivityLog;
use App\Models\ProjectPhase;
use App\Models\ProjectPhaseDeliverable;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

class UploadDeliverableAction
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function handle(ProjectPhase $phase, User $uploader, array $files, ?string $link, ?string $notes): ProjectPhaseDeliverable
    {
        $deliverable = $phase->deliverables()->create([
            'uploaded_by' => $uploader->id,
            'link' => $link,
            'notes' => $notes,
        ]);

        foreach ($files as $file) {
            $path = $file->store("projects/{$phase->project_id}/phases/{$phase->id}", 'r2');

            $deliverable->files()->create([
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $phase->forceFill(['status' => PhaseStatus::PendingReview])->save();

        ActivityLog::record(
            action: 'project.deliverable_uploaded',
            description: "A new deliverable was uploaded for phase \"{$phase->name}\".",
            subject: $phase,
            client: $phase->project->client,
        );

        Mail::to($phase->project->client->email)->queue(new DeliverableUploadedMail($phase, $deliverable));

        $phase->project->client->user?->notify(new GenericNotification(
            title: 'New deliverable',
            body: "A new deliverable is ready for review on \"{$phase->name}\".",
            url: route('client.projects.show', $phase->project),
            type: 'project',
        ));

        return $deliverable;
    }
}
