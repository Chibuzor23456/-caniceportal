<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds one row per Mailable type (App\Mail\TemplatedMail subclasses) with
 * the exact subject text each Mailable already hardcodes, as a {{ tag }}
 * template. Body is left null on every row - until an admin edits one in
 * Settings > Email Templates, the Mailable's original hand-built Blade view
 * keeps rendering exactly as it did before this retrofit.
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            EmailTemplate::firstOrCreate(['type' => $template['type']], $template);
        }
    }

    private function templates(): array
    {
        return [
            [
                'type' => 'client_welcome',
                'subject' => 'Welcome to the Canice Technologies Client Portal',
                'variables' => ['client_name', 'login_url', 'temporary_password'],
            ],
            [
                'type' => 'contract_accepted',
                'subject' => 'Contract {{ reference }} was accepted',
                'variables' => ['reference', 'client_name', 'url'],
            ],
            [
                'type' => 'contract_rejected',
                'subject' => 'Contract {{ reference }} was declined',
                'variables' => ['reference', 'client_name', 'url'],
            ],
            [
                'type' => 'contract_sent',
                'subject' => 'New Contract from Canice Technologies ({{ reference }})',
                'variables' => ['reference', 'client_name', 'secure_url'],
            ],
            [
                'type' => 'contract_viewed_admin',
                'subject' => 'Contract {{ reference }} was viewed',
                'variables' => ['reference', 'client_name', 'url'],
            ],
            [
                'type' => 'deliverable_uploaded',
                'subject' => 'New deliverable for {{ project_title }} - {{ phase_name }}',
                'variables' => ['project_title', 'phase_name', 'url'],
            ],
            [
                'type' => 'invoice_paid',
                'subject' => 'Payment received - {{ reference }}',
                'variables' => ['reference', 'url'],
            ],
            [
                'type' => 'invoice_sent',
                'subject' => 'New Invoice from Canice Technologies ({{ reference }})',
                'variables' => ['reference', 'url'],
            ],
            [
                'type' => 'message_received_client',
                'subject' => 'New message from Canice Technologies',
                'variables' => ['url'],
            ],
            [
                'type' => 'message_received_admin',
                'subject' => 'New message from {{ client_name }}',
                'variables' => ['client_name', 'url'],
            ],
            [
                'type' => 'new_client_admin',
                'subject' => 'New client: {{ client_name }}',
                'variables' => ['client_name', 'url'],
            ],
            [
                'type' => 'payment_proof_uploaded',
                'subject' => 'Payment proof uploaded for {{ reference }}',
                'variables' => ['reference', 'client_name', 'url'],
            ],
            [
                'type' => 'phase_approved',
                'subject' => 'Phase approved: {{ project_title }} - {{ phase_name }}',
                'variables' => ['project_title', 'phase_name', 'client_name', 'url'],
            ],
            [
                'type' => 'phase_comment',
                'subject' => 'New comment on {{ project_title }} - {{ phase_name }}',
                'variables' => ['project_title', 'phase_name', 'author_name', 'url'],
            ],
            [
                'type' => 'project_completed',
                'subject' => 'Project completed: {{ project_title }}',
                'variables' => ['project_title', 'client_name', 'url'],
            ],
            [
                'type' => 'quotation_accepted',
                'subject' => 'Quotation {{ reference }} was accepted',
                'variables' => ['reference', 'client_name', 'url'],
            ],
            [
                'type' => 'quotation_expired',
                'subject' => 'Quotation {{ reference }} has expired',
                'variables' => ['reference', 'client_name'],
            ],
            [
                'type' => 'quotation_rejected',
                'subject' => 'Quotation {{ reference }} was declined',
                'variables' => ['reference', 'client_name', 'admin_url'],
            ],
            [
                'type' => 'quotation_reminder',
                'subject' => 'Quotation {{ reference }} expires {{ days_phrase }}',
                'variables' => ['reference', 'days_phrase', 'secure_url'],
            ],
            [
                'type' => 'quotation_revision_requested',
                'subject' => 'Revision requested for expired quotation {{ reference }}',
                'variables' => ['reference', 'client_name', 'admin_url'],
            ],
            [
                'type' => 'quotation_sent',
                'subject' => 'New Quotation from Canice Technologies ({{ reference }})',
                'variables' => ['reference', 'client_name', 'secure_url'],
            ],
            [
                'type' => 'quotation_viewed_admin',
                'subject' => 'Quotation {{ reference }} was just viewed',
                'variables' => ['reference', 'client_name', 'admin_url'],
            ],
        ];
    }
}
