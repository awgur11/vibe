<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CallbackRequested extends Mailable implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public string $name, public string $phone) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Заявка на обратный звонок'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.callback-requested');
    }
}
