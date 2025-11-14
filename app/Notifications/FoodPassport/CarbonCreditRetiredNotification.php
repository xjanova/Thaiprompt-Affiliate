<?php

namespace App\Notifications\FoodPassport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CarbonCreditRetiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $data) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $impact = $this->data['environmental_impact'];

        return (new MailMessage)
            ->subject('🌍 Carbon Credit ของคุณถูกยกเลิกเพื่อสิ่งแวดล้อม!')
            ->greeting("สวัสดีครับ คุณ{$notifiable->name}")
            ->line("ขอบคุณที่ยกเลิก Carbon Credit เพื่อสิ่งแวดล้อม!")
            ->line("จำนวน: {$this->data['amount']} TPCC")
            ->line('')
            ->line('🌳 ผลกระทบต่อสิ่งแวดล้อม:')
            ->line("• CO2 ที่ชดเชย: {$impact['co2_offset_tonnes']} ตัน")
            ->line("• เทียบเท่ากับต้นไม้: {$impact['trees_equivalent']} ต้น/ปี")
            ->line("• รถยนต์หยุดวิ่ง: {$impact['cars_off_road_days']} วัน")
            ->line("• ใช้พลังงานบ้าน: {$impact['homes_powered_days']} วัน")
            ->action('ดูประวัติการยกเลิก', url('/carbon-credits/retired'))
            ->line('ขอบคุณที่ช่วยโลกของเรา! 🌍💚');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'credit_retired',
            'title' => '🌍 ยกเลิก Carbon Credit เพื่อโลก',
            'message' => "ยกเลิก {$this->data['amount']} TPCC เพื่อชดเชยการปล่อยคาร์บอน",
            'data' => $this->data,
            'icon' => 'globe',
            'color' => 'success',
        ];
    }
}
