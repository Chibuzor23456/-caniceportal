<?php

namespace App\Actions\Email;

use App\Enums\EmailStatus;
use App\Models\EmailLog;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message as ImapMessage;

/**
 * Runs every 5 minutes (see routes/console.php): polls the dedicated bounce
 * mailbox over IMAP for non-delivery reports and matches them back to the
 * original send via Message-ID (Section 12). No-ops cleanly when IMAP_HOST
 * isn't configured yet, the same graceful-degradation shape already used
 * for R2 in QuotationPdfService.
 */
class PollBouncesAction
{
    public function handle(): void
    {
        if (empty(config('services.imap.host'))) {
            return;
        }

        try {
            $client = (new ClientManager)->make([
                'host' => config('services.imap.host'),
                'port' => config('services.imap.port', 993),
                'encryption' => config('services.imap.encryption', 'ssl'),
                'validate_cert' => true,
                'username' => config('services.imap.username'),
                'password' => config('services.imap.password'),
                'protocol' => 'imap',
            ]);

            $client->connect();

            $messages = $client->getFolder('INBOX')->messages()->unseen()->get();

            foreach ($messages as $message) {
                $this->processMessage($message);
            }
        } catch (\Throwable) {
            // A misconfigured or unreachable mailbox degrades to "no bounces
            // found this run" rather than breaking the scheduled job.
        }
    }

    private function processMessage(ImapMessage $message): void
    {
        if (! $this->looksLikeBounce($message)) {
            return;
        }

        $originalMessageId = $this->extractOriginalMessageId($message);

        if (! $originalMessageId) {
            return;
        }

        EmailLog::query()
            ->where('message_id', $originalMessageId)
            ->where('status', EmailStatus::Sent->value)
            ->update([
                'status' => EmailStatus::Bounced,
                'bounced_at' => now(),
            ]);

        $message->setFlag('Seen');
    }

    private function looksLikeBounce(ImapMessage $message): bool
    {
        $header = $message->getHeader();

        $contentType = (string) $header?->get('content_type');

        if (str_contains($contentType, 'multipart/report')) {
            return true;
        }

        if ($header?->has('x-failed-recipients')) {
            return true;
        }

        $subject = (string) $message->subject;

        return (bool) preg_match(
            '/undelivered|delivery status notification|mail delivery failed|returned to sender/i',
            $subject,
        );
    }

    /**
     * Standard bounce (DSN) messages reference the original via In-Reply-To
     * or References headers; failing that, most mail servers still embed
     * the original Message-ID as readable text in the report body.
     */
    private function extractOriginalMessageId(ImapMessage $message): ?string
    {
        $header = $message->getHeader();

        foreach (['in_reply_to', 'references'] as $field) {
            $value = (string) $header?->get($field);

            if ($value && preg_match('/<[^>]+>/', $value, $matches)) {
                return $matches[0];
            }
        }

        $body = $message->getTextBody() ?: $message->getHTMLBody();

        if ($body && preg_match('/Message-ID:\s*(<[^>]+>)/i', $body, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
