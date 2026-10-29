<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public Order $order, public bool $forAdmin = false)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __($this->forAdmin ? 'Новый заказ №:number' : 'Ваш заказ №:number', ['number' => $this->order->id]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.placed');
    }
}
