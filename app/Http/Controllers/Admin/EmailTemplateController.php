<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public const LABELS = [
        'client_welcome' => 'Client Welcome',
        'contract_accepted' => 'Contract Accepted',
        'contract_rejected' => 'Contract Declined',
        'contract_sent' => 'Contract Sent',
        'contract_viewed_admin' => 'Contract Viewed (Admin)',
        'deliverable_uploaded' => 'Deliverable Uploaded',
        'invoice_paid' => 'Invoice Paid',
        'invoice_sent' => 'Invoice Sent',
        'message_received_client' => 'New Message (Client)',
        'message_received_admin' => 'New Message (Admin)',
        'new_client_admin' => 'New Client (Admin)',
        'payment_proof_uploaded' => 'Payment Proof Uploaded',
        'phase_approved' => 'Phase Approved',
        'phase_comment' => 'New Phase Comment',
        'project_completed' => 'Project Completed',
        'quotation_accepted' => 'Quotation Accepted',
        'quotation_expired' => 'Quotation Expired',
        'quotation_rejected' => 'Quotation Declined',
        'quotation_reminder' => 'Quotation Reminder',
        'quotation_revision_requested' => 'Quotation Revision Requested',
        'quotation_sent' => 'Quotation Sent',
        'quotation_viewed_admin' => 'Quotation Viewed (Admin)',
    ];

    public function index(): View
    {
        $templates = EmailTemplate::orderBy('type')->get();

        return view('admin.settings.email-templates.index', [
            'templates' => $templates,
            'labels' => self::LABELS,
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.settings.email-templates.edit', [
            'template' => $emailTemplate,
            'label' => self::LABELS[$emailTemplate->type] ?? $emailTemplate->type,
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
        ]);

        $emailTemplate->update([
            'subject' => $data['subject'],
            'body' => $data['body'] ?: null,
        ]);

        return redirect()->route('admin.settings.email-templates.edit', $emailTemplate)
            ->with('status', 'Email template updated.');
    }
}
