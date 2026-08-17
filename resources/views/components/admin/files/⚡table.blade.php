<?php

use App\Actions\Files\UploadClientFileAction;
use App\Enums\FileCategory;
use App\Models\Client;
use App\Models\ClientFile;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads, WithPagination;

    #[Url]
    public string $category = '';

    public ?int $uploadClientId = null;

    public string $uploadCategory = 'document';

    public string $uploadDescription = '';

    public $file;

    public function upload(UploadClientFileAction $action): void
    {
        $this->validate([
            'uploadClientId' => ['required', 'exists:clients,id'],
            'file' => ['required', 'file', 'max:51200'],
            'uploadCategory' => ['required'],
        ]);

        $action->handle(
            Client::findOrFail($this->uploadClientId),
            auth()->user(),
            $this->file,
            $this->uploadCategory,
            $this->uploadDescription ?: null,
        );

        $this->reset('uploadClientId', 'file', 'uploadDescription');
        session()->flash('status', 'File uploaded.');
    }

    public function with(): array
    {
        $files = ClientFile::query()
            ->with('client')
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->latest('created_at')
            ->paginate(15);

        return [
            'files' => $files,
            'clients' => Client::orderBy('company_name')->get(),
            'categories' => FileCategory::cases(),
        ];
    }
};
?>

<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Upload File</h3>
        <form wire:submit="upload" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
            <select wire:model="uploadClientId" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <option value="">Select client&hellip;</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                @endforeach
            </select>
            @error('uploadClientId') <p class="text-xs text-red-600 sm:col-span-4">{{ $message }}</p> @enderror

            <select wire:model="uploadCategory" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                @foreach ($categories as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>

            <input type="text" wire:model="uploadDescription" placeholder="Description (optional)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">

            <input type="file" wire:model="file" class="rounded-xl border border-slate-200 px-2 py-1.5 text-sm">
            @error('file') <p class="text-xs text-red-600 sm:col-span-4">{{ $message }}</p> @enderror

            <div wire:loading wire:target="file" class="text-xs text-slate-400 sm:col-span-4">Uploading&hellip;</div>

            <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis sm:col-span-4">
                Upload
            </button>
        </form>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-2">
        <button wire:click="$set('category', '')" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $category === '' ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">All</button>
        @foreach ($categories as $case)
            <button wire:click="$set('category', '{{ $case->value }}')" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $category === $case->value ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">
                {{ $case->label() }}
            </button>
        @endforeach
    </div>

    <div class="mt-4 overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[680px] text-left text-sm">
            <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-3">File</th>
                    <th class="px-5 py-3">Client</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Uploaded</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($files as $clientFile)
                    <tr wire:key="file-{{ $clientFile->id }}">
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $clientFile->original_filename }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $clientFile->client->company_name }}</td>
                        <td class="px-5 py-3"><x-ui.pill color="blue">{{ $clientFile->category->label() }}</x-ui.pill></td>
                        <td class="px-5 py-3 text-slate-500">{{ $clientFile->created_at->diffForHumans() }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.files.download', $clientFile) }}" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200">Download</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No files uploaded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $files->links() }}
    </div>
</div>
