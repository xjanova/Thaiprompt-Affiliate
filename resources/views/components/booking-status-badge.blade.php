@props(['status'])

@php
$statusConfig = [
    'pending' => [
        'label' => 'รอดำเนินการ',
        'icon' => 'clock',
        'bg' => 'bg-gray-100 dark:bg-gray-700',
        'text' => 'text-gray-700 dark:text-gray-300',
    ],
    'paid' => [
        'label' => 'ชำระเงินแล้ว',
        'icon' => 'check-circle',
        'bg' => 'bg-blue-100 dark:bg-blue-900/30',
        'text' => 'text-blue-700 dark:text-blue-400',
    ],
    'notifying_provider' => [
        'label' => 'กำลังแจ้งเตือน',
        'icon' => 'bell',
        'bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
        'text' => 'text-yellow-700 dark:text-yellow-400',
    ],
    'waiting_provider' => [
        'label' => 'รอผู้ให้บริการ',
        'icon' => 'hourglass-half',
        'bg' => 'bg-orange-100 dark:bg-orange-900/30',
        'text' => 'text-orange-700 dark:text-orange-400',
    ],
    'provider_accepted' => [
        'label' => 'รับงานแล้ว',
        'icon' => 'user-check',
        'bg' => 'bg-green-100 dark:bg-green-900/30',
        'text' => 'text-green-700 dark:text-green-400',
    ],
    'provider_rejected' => [
        'label' => 'ปฏิเสธ',
        'icon' => 'times-circle',
        'bg' => 'bg-red-100 dark:bg-red-900/30',
        'text' => 'text-red-700 dark:text-red-400',
    ],
    'provider_on_way' => [
        'label' => 'กำลังเดินทาง',
        'icon' => 'route',
        'bg' => 'bg-purple-100 dark:bg-purple-900/30',
        'text' => 'text-purple-700 dark:text-purple-400',
    ],
    'in_progress' => [
        'label' => 'กำลังให้บริการ',
        'icon' => 'cog fa-spin',
        'bg' => 'bg-indigo-100 dark:bg-indigo-900/30',
        'text' => 'text-indigo-700 dark:text-indigo-400',
    ],
    'completed' => [
        'label' => 'เสร็จสิ้น',
        'icon' => 'check-double',
        'bg' => 'bg-green-100 dark:bg-green-900/30',
        'text' => 'text-green-700 dark:text-green-400',
    ],
    'cancelled' => [
        'label' => 'ยกเลิก',
        'icon' => 'ban',
        'bg' => 'bg-red-100 dark:bg-red-900/30',
        'text' => 'text-red-700 dark:text-red-400',
    ],
];

$config = $statusConfig[$status] ?? [
    'label' => $status,
    'icon' => 'question',
    'bg' => 'bg-gray-100',
    'text' => 'text-gray-700',
];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold {$config['bg']} {$config['text']}"]) }}>
    <i class="fas fa-{{ $config['icon'] }}"></i>
    <span>{{ $config['label'] }}</span>
</span>
