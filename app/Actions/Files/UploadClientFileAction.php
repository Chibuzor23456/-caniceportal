<?php

namespace App\Actions\Files;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientFile;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Http\UploadedFile;

class UploadClientFileAction
{
    public function handle(Client $client, User $uploader, UploadedFile $file, string $category, ?string $description = null): ClientFile
    {
        $path = $file->store("files/{$client->id}", 'local');

        $clientFile = $client->files()->create([
            'uploaded_by' => $uploader->id,
            'category' => $category,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'description' => $description,
        ]);

        ActivityLog::record(
            action: 'file.uploaded',
            description: "A new file (\"{$clientFile->original_filename}\") was uploaded for {$client->company_name}.",
            subject: $clientFile,
            client: $client,
        );

        $client->user?->notify(new GenericNotification(
            title: 'New file',
            body: "A new file (\"{$clientFile->original_filename}\") was uploaded to your account.",
            url: route('client.files.index'),
            type: 'file',
        ));

        return $clientFile;
    }
}
