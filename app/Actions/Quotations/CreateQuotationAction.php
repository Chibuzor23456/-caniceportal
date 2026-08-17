<?php

namespace App\Actions\Quotations;

use App\Enums\SectionType;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Quotation;
use App\Models\QuotationTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateQuotationAction
{
    public function handle(Client $client, ?QuotationTemplate $template = null): Quotation
    {
        return DB::transaction(function () use ($client, $template) {
            $quotation = Quotation::create([
                'client_id' => $client->id,
                'template_id' => $template?->id,
                'created_by' => auth()->id(),
                'reference' => $this->nextReference(),
                'slug' => $this->uniqueSlug($client->company_name),
                // Explicit, not left to the DB column defaults: Eloquent
                // doesn't reflect DB-level defaults back onto the in-memory
                // model after create(), so any code touching these
                // attributes before a fresh reload (e.g. this same request)
                // would see null instead of the real value.
                'status' => \App\Enums\QuotationStatus::Draft,
                'version' => 1,
                'currency' => CompanySetting::current()->default_currency,
                'issue_date' => now()->toDateString(),
            ]);

            if ($template) {
                $this->copyFromTemplate($quotation, $template);
            }

            return $quotation->load('sections', 'lineItems', 'paymentPhases');
        });
    }

    protected function copyFromTemplate(Quotation $quotation, QuotationTemplate $template): void
    {
        foreach ($template->sections as $section) {
            $quotation->sections()->create([
                'type' => $section->type,
                'title' => $section->title,
                'body' => $section->body,
                'order' => $section->order,
            ]);
        }

        foreach ($template->lineItems as $item) {
            $quotation->lineItems()->create([
                'service_name' => $item->service_name,
                'service_category' => $item->service_category,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
                'tax' => $item->tax,
                'line_total' => (float) $item->quantity * (float) $item->unit_price - (float) $item->discount + (float) $item->tax,
                'order' => $item->order,
            ]);
        }

        foreach ($template->paymentPhases as $phase) {
            $quotation->paymentPhases()->create([
                'description' => $phase->description,
                'amount' => $phase->amount,
                'due_condition' => $phase->due_condition,
                'order' => $phase->order,
            ]);
        }
    }

    protected function nextReference(): string
    {
        $year = now()->year;

        do {
            $count = Quotation::withTrashed()->whereYear('created_at', $year)->count() + 1;
            $reference = sprintf('Q-%d-%04d', $year, $count);
            $exists = Quotation::withTrashed()->where('reference', $reference)->exists();
        } while ($exists);

        return $reference;
    }

    protected function uniqueSlug(string $companyName): string
    {
        $base = Str::slug($companyName);
        $slug = $base;
        $suffix = 2;

        while (Quotation::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
