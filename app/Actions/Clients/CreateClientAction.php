<?php

namespace App\Actions\Clients;

use App\Enums\ClientStatus;
use App\Enums\UserRole;
use App\Mail\ClientWelcomeMail;
use App\Mail\NewClientAdminMail;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateClientAction
{
    /**
     * @param  array{company_name:string,contact_person:string,email:string,phone?:string,address?:string,industry?:string,notes?:string,referral_source?:string,tags?:array<int,string>}  $data
     */
    public function handle(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $temporaryPassword = Str::password(14);

            $user = User::create([
                'name' => $data['contact_person'],
                'email' => $data['email'],
                'password' => Hash::make($temporaryPassword),
                'role' => UserRole::Client,
                'must_change_password' => true,
            ]);

            $client = Client::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'contact_person' => $data['contact_person'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'industry' => $data['industry'] ?? null,
                'notes' => $data['notes'] ?? null,
                'date_joined' => now()->toDateString(),
                'status' => ClientStatus::Active,
                'referral_source' => $data['referral_source'] ?? null,
            ]);

            if (! empty($data['tags'])) {
                $tagIds = collect($data['tags'])
                    ->map(fn (string $name) => \App\Models\Tag::firstOrCreate(['name' => trim($name)])->id);

                $client->tags()->sync($tagIds);
            }

            ActivityLog::record(
                action: 'client.created',
                description: "Client \"{$client->company_name}\" was created and onboarded.",
                subject: $client,
                client: $client,
            );

            Mail::to($client->email)->queue(
                new ClientWelcomeMail($client, $temporaryPassword)
            );

            User::admins()->get()->each(function (User $admin) use ($client) {
                Mail::to($admin->email)->queue(new NewClientAdminMail($client));
                $admin->notify(new GenericNotification(
                    title: 'New client',
                    body: "{$client->company_name} was added and onboarded.",
                    url: route('admin.clients.show', $client),
                    type: 'client',
                ));
            });

            return $client;
        });
    }
}
