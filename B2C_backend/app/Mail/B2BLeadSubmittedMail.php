<?php

namespace App\Mail;

use App\Models\B2BLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class B2BLeadSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly B2BLead $lead,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New B2B lead submitted: '.$this->lead->inquiry_type
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.b2b-leads.submitted'
        );
    }
}
