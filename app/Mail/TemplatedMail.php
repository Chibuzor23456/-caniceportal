<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Base class every trigger-event Mailable extends (Section 15's Email
 * Templates retrofit). An admin-edited EmailTemplate row can override the
 * subject and, if it has a body, replace the hand-built Blade view entirely
 * with a generic markdown-rendered wrapper - until a row's body is set, the
 * original Blade view keeps rendering exactly as before, so nothing visually
 * changes until an admin actually edits one.
 */
abstract class TemplatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    abstract protected function type(): string;

    abstract protected function fallbackSubject(): string;

    abstract protected function mailView(): string;

    abstract protected function viewData(): array;

    /**
     * @return array<string,string> flat scalar values usable as {{ tags }}
     * in an admin-edited subject/body.
     */
    protected function templateVariables(): array
    {
        return [];
    }

    public function build(): self
    {
        $template = EmailTemplate::where('type', $this->type())->first();

        $subject = $template
            ? $this->renderPlaceholders($template->subject)
            : $this->fallbackSubject();

        if ($template?->body) {
            return $this->subject($subject)->markdown('emails.templated', [
                'body' => $this->renderPlaceholders($template->body),
            ]);
        }

        return $this->subject($subject)->markdown($this->mailView(), $this->viewData());
    }

    private function renderPlaceholders(string $text): string
    {
        $variables = $this->templateVariables();

        return preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            fn (array $match) => $variables[$match[1]] ?? $match[0],
            $text,
        );
    }
}
