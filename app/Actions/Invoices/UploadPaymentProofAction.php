<?php

namespace App\Actions\Invoices;

use App\Mail\PaymentProofUploadedMail;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

class UploadPaymentProofAction
{
    public function handle(Invoice $invoice, UploadedFile $file): Invoice
    {
        $path = $file->store("invoices/{$invoice->id}/proof", 'r2');

        $invoice->forceFill([
            'payment_proof_path' => $path,
            'payment_proof_uploaded_at' => now(),
        ])->save();

        ActivityLog::record(
            action: 'invoice.payment_proof_uploaded',
            description: "{$invoice->client->company_name} uploaded payment proof for invoice {$invoice->reference}.",
            subject: $invoice,
            client: $invoice->client,
        );

        User::admins()->get()->each(function (User $admin) use ($invoice) {
            Mail::to($admin->email)->queue(new PaymentProofUploadedMail($invoice));
            $admin->notify(new GenericNotification(
                title: 'Payment proof uploaded',
                body: "{$invoice->client->company_name} uploaded proof of payment for invoice {$invoice->reference}.",
                url: route('admin.invoices.show', $invoice),
                type: 'invoice',
            ));
        });

        return $invoice;
    }
}
