<?php

namespace App\Mail;

use App\Models\User;
use App\Services\AccountEmailChangeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * ลิงก์ยืนยันอีเมลบัญชี (ส่งหลังเปลี่ยนอีเมล หรือเมื่อลูกค้ากดขอลิงก์ใหม่ในหน้าโปรไฟล์)
 *
 * ลิงก์เป็น signed URL หมดอายุใน 24 ชม. และผูกกับอีเมลปัจจุบัน (hash) — เปลี่ยนอีเมลอีกรอบ ลิงก์เก่าใช้ไม่ได้
 */
class VerifyAccountEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ยืนยันอีเมลบัญชีของคุณ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account.verify-email',
            with: [
                'userName' => $this->user->name,
                'verifyUrl' => $this->verifyUrl(),
                'hours' => AccountEmailChangeService::VERIFY_LINK_HOURS,
            ],
        );
    }

    public function verifyUrl(): string
    {
        return URL::temporarySignedRoute('account.email.verify', now()->addHours(AccountEmailChangeService::VERIFY_LINK_HOURS), [
            'id' => $this->user->id,
            'hash' => AccountEmailChangeService::verificationHash($this->user),
        ]);
    }
}
