<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\SoftwareLicense;
use App\Models\SoftwareProduct;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * SoftwarePurchasedMail
 *
 * อีเมลแจ้งเตือนการซื้อซอฟต์แวร์สำเร็จ
 */
class SoftwarePurchasedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User
     */
    public $buyer;

    /**
     * @var SoftwareProduct
     */
    public $product;

    /**
     * @var SoftwareLicense
     */
    public $license;

    /**
     * @var Order|null
     */
    public $order;

    /**
     * สร้าง message instance ใหม่
     */
    public function __construct(
        User $buyer,
        SoftwareProduct $product,
        SoftwareLicense $license,
        ?Order $order = null
    ) {
        $this->buyer = $buyer;
        $this->product = $product;
        $this->license = $license;
        $this->order = $order;
    }

    /**
     * Get the message envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ขอบคุณที่ซื้อซอฟต์แวร์ '.$this->product->name,
        );
    }

    /**
     * Get the message content definition
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.software.purchased',
            with: [
                'buyerName' => $this->buyer->name,
                'productName' => $this->product->name,
                'licenseKey' => $this->license->license_key,
                'activationCode' => $this->license->activation_code,
                'downloadUrl' => route('software.download', ['product' => $this->product->id]),
                'expiresAt' => $this->license->expires_at?->format('d/m/Y'),
                'maxActivations' => $this->license->max_activations,
                'maxDownloads' => $this->license->max_downloads === -1 ? 'ไม่จำกัด' : $this->license->max_downloads,
            ],
        );
    }

    /**
     * Get the attachments for the message
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
