<?php

namespace App\Mail;

use App\Models\User;
use App\Support\AccountEmail;
use App\Support\ContactInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * แจ้งอีเมลเดิมว่าอีเมลบัญชีถูกเปลี่ยน — ถ้าเจ้าของไม่ได้ทำเอง จะได้รู้ตัวและติดต่อทีมงานทัน
 */
class AccountEmailChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $oldEmail) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'อีเมลบัญชีของคุณถูกเปลี่ยน',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account.email-changed',
            with: [
                'userName' => $this->user->name,
                'oldEmail' => $this->oldEmail,
                // ปิดบางส่วน — อีเมลเดิมอาจถูกคนอื่นเข้าถึงอยู่ ไม่ควรเปิดเผยอีเมลใหม่เต็มๆ
                'newEmailMasked' => AccountEmail::mask((string) $this->user->email),
                'changedAt' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'supportEmail' => ContactInfo::supportEmail(),
                'profileUrl' => route('user.profile'),
            ],
        );
    }
}
