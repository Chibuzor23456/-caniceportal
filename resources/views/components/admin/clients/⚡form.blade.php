<?php

use App\Actions\Clients\CreateClientAction;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Tag;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public ?Client $client = null;

    public string $company_name = '';
    public string $contact_person = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $industry = '';
    public string $notes = '';
    public string $referral_source = '';
    public string $tagsInput = '';

    public function mount(?Client $client = null): void
    {
        $this->client = $client;

        if ($client) {
            $this->company_name = $client->company_name;
            $this->contact_person = $client->contact_person;
            $this->email = $client->email;
            $this->phone = (string) $client->phone;
            $this->address = (string) $client->address;
            $this->industry = (string) $client->industry;
            $this->notes = (string) $client->notes;
            $this->referral_source = (string) $client->referral_source;
            $this->tagsInput = $client->tags->pluck('name')->implode(', ');
        }
    }

    public function save(CreateClientAction $createClient)
    {
        $data = $this->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('clients', 'email')->ignore($this->client?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'referral_source' => ['nullable', 'string', 'max:255'],
        ]);

        $tags = collect(explode(',', $this->tagsInput))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->values()
            ->all();

        if ($this->client) {
            $this->client->update($data);

            $tagIds = collect($tags)->map(fn (string $name) => Tag::firstOrCreate(['name' => $name])->id);
            $this->client->tags()->sync($tagIds);

            ActivityLog::record('client.updated', "Client \"{$this->client->company_name}\" was updated.", $this->client);

            session()->flash('status', 'Client updated.');

            $this->redirect(route('admin.clients.show', $this->client), navigate: false);

            return;
        }

        $client = $createClient->handle([...$data, 'tags' => $tags]);

        session()->flash('status', 'Client created. A welcome email with login details has been queued.');

        $this->redirect(route('admin.clients.show', $client), navigate: false);
    }
};
?>

<div class="w-full rounded-2xl bg-white p-8 shadow-sm">
    <form wire:submit="save" class="space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">Company Name</label>
                <input type="text" wire:model="company_name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                @error('company_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Contact Person</label>
                <input type="text" wire:model="contact_person" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                @error('contact_person') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" wire:model="email" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @unless ($client)
                    <p class="mt-1 text-xs text-slate-400">Login credentials are generated and emailed to this address automatically.</p>
                @endunless
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Phone</label>
                <input type="text" wire:model="phone" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Industry</label>
                <input type="text" wire:model="industry" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Referral Source</label>
                <input type="text" wire:model="referral_source" placeholder="e.g. referral, Google, Instagram" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Address</label>
            <input type="text" wire:model="address" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Tags</label>
            <input type="text" wire:model="tagsInput" placeholder="e.g. healthcare, retail" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
            <p class="mt-1 text-xs text-slate-400">Comma-separated. Freeform, used for filtering the client list.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Notes</label>
            <textarea wire:model="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ $client ? route('admin.clients.show', $client) : route('admin.clients.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
            <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                {{ $client ? 'Save Changes' : 'Create Client' }}
            </button>
        </div>
    </form>
</div>
