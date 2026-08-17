<?php

namespace App\Mail\Transport;

use App\Enums\EmailStatus;
use App\Models\EmailLog;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

/**
 * Sends mail through PHPMailer over SMTP (PRD Section 3) while keeping every
 * other part of the app on Laravel's Mail facade. Application code never
 * touches PHPMailer directly, only this transport does, so swapping it later
 * means changing config, not call sites.
 */
class PHPMailerTransport extends AbstractTransport
{
    public function __construct(private readonly array $config)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();

        if (! $original instanceof Email) {
            throw new TransportException('PHPMailerTransport can only send Symfony\Component\Mime\Email messages.');
        }

        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $this->config['host'] ?? '127.0.0.1';
            $mailer->Port = (int) ($this->config['port'] ?? 587);
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;

            if (! empty($this->config['username'])) {
                $mailer->SMTPAuth = true;
                $mailer->Username = $this->config['username'];
                $mailer->Password = (string) ($this->config['password'] ?? '');
            }

            $mailer->SMTPSecure = match ($this->config['scheme'] ?? 'smtp') {
                'smtps' => PHPMailer::ENCRYPTION_SMTPS,
                default => PHPMailer::ENCRYPTION_STARTTLS,
            };

            if ($from = $original->getFrom()[0] ?? null) {
                $mailer->setFrom($from->getAddress(), $from->getName());
            }

            foreach ($original->getReplyTo() as $address) {
                $mailer->addReplyTo($address->getAddress(), $address->getName());
            }

            foreach ($original->getTo() as $address) {
                $mailer->addAddress($address->getAddress(), $address->getName());
            }

            foreach ($original->getCc() as $address) {
                $mailer->addCC($address->getAddress(), $address->getName());
            }

            foreach ($original->getBcc() as $address) {
                $mailer->addBCC($address->getAddress(), $address->getName());
            }

            $mailer->Subject = (string) $original->getSubject();

            if ($html = $original->getHtmlBody()) {
                $mailer->isHTML(true);
                $mailer->Body = $html;
                $mailer->AltBody = (string) $original->getTextBody();
            } else {
                $mailer->isHTML(false);
                $mailer->Body = (string) $original->getTextBody();
            }

            foreach ($original->getAttachments() as $attachment) {
                $headers = $attachment->getPreparedHeaders();
                $filename = $headers->getHeaderParameter('Content-Disposition', 'filename') ?? 'attachment';

                $mailer->addStringAttachment(
                    $attachment->getBody(),
                    $filename,
                    PHPMailer::ENCODING_BASE64,
                    $attachment->getMediaType().'/'.$attachment->getMediaSubtype(),
                );
            }

            $mailer->send();

            // Section 12's Email Log - this is the one chokepoint every
            // outgoing email already passes through, so no existing
            // Mailable needs to change to get logged.
            EmailLog::create([
                'recipient' => $this->recipientList($original),
                'subject' => (string) $original->getSubject(),
                'message_id' => $mailer->getLastMessageID() ?: null,
                'status' => EmailStatus::Sent,
            ]);
        } catch (PHPMailerException $e) {
            EmailLog::create([
                'recipient' => $this->recipientList($original),
                'subject' => (string) $original->getSubject(),
                'status' => EmailStatus::Failed,
                'error_message' => $mailer->ErrorInfo,
            ]);

            throw new TransportException("PHPMailer failed to send: {$mailer->ErrorInfo}", previous: $e);
        }
    }

    private function recipientList(Email $message): string
    {
        return collect($message->getTo())
            ->map(fn ($address) => $address->getAddress())
            ->implode(', ');
    }

    public function __toString(): string
    {
        return 'phpmailer://'.($this->config['host'] ?? '');
    }
}
