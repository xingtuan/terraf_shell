<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericEmailMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $emailSubject,
        private readonly string $htmlBody,
        private readonly ?string $textBody = null,
        private readonly ?string $replyToAddress = null,
        private readonly ?string $replyToName = null,
    ) {}

    public function build(): self
    {
        $message = $this
            ->subject($this->emailSubject)
            ->html($this->htmlBody);

        if (filled($this->textBody)) {
            $message->text('emails.generic-text', [
                'body' => $this->textBody,
            ]);
        }

        if (filled($this->replyToAddress)) {
            $message->replyTo($this->replyToAddress, $this->replyToName);
        }

        return $message;
    }
}
