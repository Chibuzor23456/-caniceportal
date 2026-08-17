<?php

namespace App\Actions\Settings;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class TestSmtpConnectionAction
{
    /**
     * @param  array{host:string,port:int|string,encryption:string,username?:?string,password?:?string}  $data
     * @return array{success:bool,message:string}
     */
    public function handle(array $data): array
    {
        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $data['host'];
            $mailer->Port = (int) $data['port'];
            $mailer->SMTPSecure = $data['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

            if (! empty($data['username'])) {
                $mailer->SMTPAuth = true;
                $mailer->Username = $data['username'];
                $mailer->Password = $data['password'] ?? '';
            }

            $mailer->Timeout = 10;

            if (! $mailer->smtpConnect()) {
                return ['success' => false, 'message' => $mailer->ErrorInfo ?: 'Could not connect.'];
            }

            $mailer->smtpClose();

            return ['success' => true, 'message' => 'Connected and authenticated successfully.'];
        } catch (PHPMailerException $e) {
            return ['success' => false, 'message' => $mailer->ErrorInfo ?: $e->getMessage()];
        }
    }
}
