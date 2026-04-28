<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $purchaseOrder)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo pedido creado #' . $this->purchaseOrder->id
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-created'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
