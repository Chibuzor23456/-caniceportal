<x-layouts.admin page-title="New Contract" title="New Contract">
    <div class="w-full rounded-2xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.contracts.store') }}" enctype="multipart/form-data" x-data="{ mode: 'write' }" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Client</label>
                    <select name="client_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Select a client&hellip;</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="Website Development Service Agreement" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <div class="flex gap-2">
                    <button type="button" @click="mode = 'write'" :class="mode === 'write' ? 'bg-brand text-white' : 'bg-slate-100 text-slate-500'" class="rounded-full px-3 py-1.5 text-sm font-medium">Write content</button>
                    <button type="button" @click="mode = 'upload'" :class="mode === 'upload' ? 'bg-brand text-white' : 'bg-slate-100 text-slate-500'" class="rounded-full px-3 py-1.5 text-sm font-medium">Upload a file</button>
                </div>
                <input type="hidden" name="mode" :value="mode">

                <div
                    x-show="mode === 'write'"
                    class="mt-4"
                    x-data="{
                        quill: null,
                        init() {
                            this.quill = new Quill(this.$refs.editor, {
                                theme: 'snow',
                                modules: { toolbar: [['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['link'],['clean']] },
                            });
                            this.quill.on('text-change', () => {
                                this.$refs.bodyInput.value = this.quill.root.innerHTML;
                            });
                        }
                    }"
                >
                    <div x-ref="editor" style="min-height: 240px;"></div>
                    <textarea x-ref="bodyInput" name="body" class="hidden"></textarea>
                    @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="mode === 'upload'" x-cloak class="mt-4">
                    <input type="file" name="file" accept=".pdf,.doc,.docx" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-200 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-300">
                    @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                Create Contract
            </button>
        </form>
    </div>
</x-layouts.admin>
