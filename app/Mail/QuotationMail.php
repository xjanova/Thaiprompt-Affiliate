<?php

namespace App\Mail;

use App\Models\SoftwareQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public SoftwareQuotation $quotation;

    /**
     * Create a new message instance.
     */
    public function __construct(SoftwareQuotation $quotation)
    {
        $this->quotation = $quotation;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('ใบเสนอราคา ' . $this->quotation->quotation_number)
            ->view('emails.quotation')
            ->with([
                'quotation' => $this->quotation,
                'items' => $this->quotation->items()->with('selectedOptions')->get(),
            ]);
    }
}
