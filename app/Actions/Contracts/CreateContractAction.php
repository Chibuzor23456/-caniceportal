<?php

namespace App\Actions\Contracts;

use App\Enums\ContractStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateContractAction
{
    public function handle(
        Client $client,
        string $title,
        ?string $body = null,
        ?UploadedFile $file = null,
        ?Project $project = null,
    ): Contract {
        // Exactly one of body/file - the controller's form validation
        // already enforces this, this is the defensive backstop.
        abort_if(($body === null) === ($file === null), 422, 'A contract needs either a written body or an uploaded file, not both or neither.');

        return DB::transaction(function () use ($client, $title, $body, $file, $project) {
            $uploadedPath = $file?->store("contracts/{$client->id}", 'r2');

            return Contract::create([
                'client_id' => $client->id,
                'project_id' => $project?->id,
                'created_by' => auth()->id(),
                'reference' => $this->nextReference(),
                'slug' => $this->uniqueSlug($client->company_name),
                'title' => $title,
                'status' => ContractStatus::Draft,
                'body' => $body,
                'uploaded_file_path' => $uploadedPath,
                'issue_date' => now()->toDateString(),
            ]);
        });
    }

    private function nextReference(): string
    {
        $year = now()->year;

        do {
            $count = Contract::withTrashed()->whereYear('created_at', $year)->count() + 1;
            $reference = sprintf('C-%d-%04d', $year, $count);
            $exists = Contract::withTrashed()->where('reference', $reference)->exists();
        } while ($exists);

        return $reference;
    }

    private function uniqueSlug(string $companyName): string
    {
        $base = Str::slug($companyName).'-contract';
        $slug = $base;
        $suffix = 2;

        while (Contract::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
