<?php

namespace App\Actions\Messages;

use App\Mail\MessageReceivedMail;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Message;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

class SendMessageAction
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function handle(Client $client, User $sender, ?string $body, array $files = []): Message
    {
        $message = $client->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        foreach ($files as $file) {
            $path = $file->store("messages/{$client->id}", 'r2');

            $message->attachments()->create([
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        ActivityLog::record(
            action: 'message.sent',
            description: "{$sender->name} sent a message to {$client->company_name}.",
            subject: $message,
            client: $client,
        );

        if ($sender->isAdmin()) {
            if ($client->user) {
                Mail::to($client->user->email)->queue(new MessageReceivedMail($message, forAdmin: false));
                $client->user->notify(new GenericNotification(
                    title: 'New message',
                    body: 'You have a new message from Canice Technologies.',
                    url: route('client.messages.index'),
                ));
            }
        } else {
            User::admins()->get()->each(function (User $admin) use ($message, $client) {
                Mail::to($admin->email)->queue(new MessageReceivedMail($message, forAdmin: true));
                $admin->notify(new GenericNotification(
                    title: 'New message',
                    body: "{$client->company_name} sent a new message.",
                    url: route('admin.messages.show', $client),
                ));
            });
        }

        return $message;
    }
}
