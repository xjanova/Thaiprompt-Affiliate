<?php

namespace App\Notifications\FoodPassport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class QualityExcellentNotification extends Notification implements ShouldQueue
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
            'type' => 'quality_excellent',
            'title' => '🌟 คุณภาพยอดเยี่ยม!',
            'message' => "ผลิตภัณฑ์ของคุณได้คะแนนคุณภาพ {$this->data['score']}/100",
            'data' => $this->data,
            'icon' => 'star',
            'color' => 'warning',
        ];
    }
}
