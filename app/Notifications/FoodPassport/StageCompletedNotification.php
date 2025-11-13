<?php

namespace App\Notifications\FoodPassport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StageCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $data) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'journey_stage_completed',
            'title' => 'ขั้นตอนการเดินทางเสร็จสมบูรณ์',
            'message' => "ขั้นตอน {$this->data['stage']} เสร็จสมบูรณ์แล้ว",
            'data' => $this->data,
            'icon' => 'map-pin',
            'color' => 'success',
        ];
    }
}
