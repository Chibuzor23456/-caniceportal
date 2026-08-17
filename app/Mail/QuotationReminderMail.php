<?php

namespace App\Mail;

use App\Models\Quotation;

class QuotationReminderMail extends TemplatedMail
{
    public function __construct(
        public Quotation $quotation,
        public int $daysRemaining,
    ) {}

    protected function type(): string
    {
        return 'quotation_reminder';
    }

    protected function fallbackSubject(): string
    {
        return $this->daysRemaining <= 0
            ? "Quotation {$this->quotation->reference} expires today"
            : "Quotation {$this->quotation->reference} expires {$this->daysPhrase()}";
    }

    protected function mailView(): string
    {
        return 'emails.quotations.reminder';
    }

    protected function viewData(): array
    {
        return [
            'quotation' => $this->quotation,
            'daysRemaining' => $this->daysRemaining,
            'secureUrl' => route('quotation.secure', $this->quotation->secure_token),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->quotation->reference,
            'days_phrase' => $this->daysPhrase(),
            'secure_url' => route('quotation.secure', $this->quotation->secure_token),
        ];
    }

    private function daysPhrase(): string
    {
        if ($this->daysRemaining <= 0) {
            return 'today';
        }

        return "in {$this->daysRemaining} day".($this->daysRemaining === 1 ? '' : 's');
    }
}
