{{--
    Admin Rider Settings - ตั้งค่าระบบไรเดอร์ (ค่าส่ง / ส่วนแบ่ง / การกระจายงาน / COD / GPS)

    ตัวแปรจาก Admin\RiderController::settings():
      $settings     ค่าปัจจุบัน [field => value]
      $spec         RiderController::SETTINGS_SPEC [field => {key, type, min?, max?, options?, label, unit?}]
      $feeExamples  ตัวอย่างค่าส่งตามระยะ (DeliveryFeeCalculator::quoteForDistance)
      $updateUrl    route('admin.riders.settings.update') — POST ฟิลด์ settings[<field>]
--}}

@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'ตั้งค่าระบบไรเดอร์')

@php
    // จัดกลุ่มฟิลด์ให้อ่านง่าย — ฟิลด์ที่ไม่อยู่ในกลุ่มไหนจะไปอยู่ "อื่นๆ"
    $groups = [
        'fees' => [
            'title' => 'ค่าส่งและส่วนแบ่งรายได้',
            'icon' => 'fa-coins',
            'fields' => ['base_fee', 'per_km_fee', 'free_km', 'min_fee', 'max_distance_km', 'road_factor', 'rider_share_percent'],
        ],
        'dispatch' => [
            'title' => 'การกระจายงาน',
            'icon' => 'fa-bullhorn',
            'fields' => ['dispatch_mode', 'offer_radius_km', 'max_offer_radius_km', 'max_dispatch_rounds', 'rebroadcast_interval_minutes', 'offer_timeout_seconds', 'pending_timeout_minutes', 'max_release_count'],
        ],
        'cod' => [
            'title' => 'เก็บเงินปลายทาง (COD) และเงินประกัน',
            'icon' => 'fa-hand-holding-dollar',
            'fields' => ['max_cod_amount', 'require_deposit'],
        ],
        'gps' => [
            'title' => 'GPS และลิงก์ติดตาม',
            'icon' => 'fa-location-crosshairs',
            'fields' => ['location_fresh_minutes', 'location_retention_days', 'tracking_expiry_hours', 'tracking_grace_minutes', 'avg_speed_kmh'],
        ],
    ];

    $grouped = collect($groups)->flatMap(fn ($g) => $g['fields'])->all();
    $others = array_values(array_diff(array_keys($spec), $grouped));
    if ($others !== []) {
        $groups['others'] = ['title' => 'อื่นๆ', 'icon' => 'fa-sliders', 'fields' => $others];
    }

    $optionLabels = [
        'dispatch_mode' => [
            'broadcast' => 'แจ้งทุกคนในรัศมี (ใครกดรับก่อนได้งาน)',
            'cascade' => 'เสนอทีละคน (รอตัดสินใจทีละคน)',
        ],
    ];
@endphp

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl border border-gray-200/50 dark:border-gray-700/50">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent flex items-center">
                    <i class="fas fa-gears mr-3 text-blue-600"></i>ตั้งค่าระบบไรเดอร์
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">ค่าส่ง ส่วนแบ่งไรเดอร์ การกระจายงาน วงเงินเก็บปลายทาง และการติดตาม GPS</p>
            </div>
            <a href="{{ route('admin.riders.index') }}"
               class="px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium transition flex items-center justify-center">
                <i class="fas fa-arrow-left mr-2"></i>กลับหน้าจัดการไรเดอร์
            </a>
        </div>
    </div>

    {{-- ผลการบันทึก --}}
    @if (session('success'))
        <div class="p-4 rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 flex items-start gap-3">
            <i class="fas fa-circle-check mt-0.5"></i><span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 flex items-start gap-3">
            <i class="fas fa-circle-exclamation mt-0.5"></i><span>{{ session('error') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200">
            <p class="font-semibold mb-2"><i class="fas fa-triangle-exclamation mr-2"></i>บันทึกไม่สำเร็จ กรุณาตรวจสอบค่าต่อไปนี้</p>
            <ul class="list-disc list-inside space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $updateUrl }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        @foreach ($groups as $groupKey => $group)
            <section class="p-6 rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-200/60 dark:border-gray-700/60">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas {{ $group['icon'] }} mr-2 text-blue-600 dark:text-blue-400"></i>{{ $group['title'] }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($group['fields'] as $field)
                        @continue(! isset($spec[$field]))
                        @php
                            $item = $spec[$field];
                            $name = "settings[{$field}]";
                            $current = old("settings.{$field}", $settings[$field] ?? null);
                            $hasError = $errors->has("settings.{$field}");
                        @endphp

                        <div class="flex flex-col">
                            @if ($item['type'] === 'boolean')
                                <input type="hidden" name="{{ $name }}" value="0">
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer min-h-[44px]">
                                    <input type="checkbox" name="{{ $name }}" value="1"
                                           class="w-5 h-5 rounded text-blue-600 border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-blue-500"
                                           @checked(filter_var($current, FILTER_VALIDATE_BOOLEAN))>
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $item['label'] }}</span>
                                </label>
                            @elseif ($item['type'] === 'string')
                                <label for="setting-{{ $field }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $item['label'] }}</label>
                                <select id="setting-{{ $field }}" name="{{ $name }}"
                                        class="w-full px-4 py-2.5 rounded-xl border {{ $hasError ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @foreach ($item['options'] ?? [] as $option)
                                        <option value="{{ $option }}" @selected((string) $current === (string) $option)>
                                            {{ $optionLabels[$field][$option] ?? $option }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <label for="setting-{{ $field }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $item['label'] }}
                                    @isset($item['unit'])
                                        <span class="text-gray-400 dark:text-gray-500 font-normal">({{ $item['unit'] }})</span>
                                    @endisset
                                </label>
                                <input id="setting-{{ $field }}" type="number" name="{{ $name }}"
                                       value="{{ $current }}"
                                       step="{{ $item['type'] === 'integer' ? '1' : '0.01' }}"
                                       @isset($item['min']) min="{{ $item['min'] }}" @endisset
                                       @isset($item['max']) max="{{ $item['max'] }}" @endisset
                                       inputmode="decimal"
                                       class="w-full px-4 py-2.5 rounded-xl border {{ $hasError ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @isset($item['min'], $item['max'])
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">ช่วงที่ตั้งได้ {{ $item['min'] }} – {{ number_format((float) $item['max']) }}</span>
                                @endisset
                            @endif

                            @error("settings.{$field}")
                                <span class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
            <button type="submit"
                    :disabled="submitting"
                    class="w-full sm:w-auto px-8 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 disabled:opacity-60 text-white font-semibold shadow-lg shadow-blue-500/30 transition active:scale-95">
                <span x-show="!submitting"><i class="fas fa-floppy-disk mr-2"></i>บันทึกการตั้งค่า</span>
                <span x-show="submitting" x-cloak><i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...</span>
            </button>
        </div>
    </form>

    {{-- ตัวอย่างค่าส่งตามระยะ (คำนวณจากค่าที่บันทึกแล้ว) --}}
    <section class="p-6 rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-200/60 dark:border-gray-700/60">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1 flex items-center">
            <i class="fas fa-calculator mr-2 text-blue-600 dark:text-blue-400"></i>ตัวอย่างค่าส่งจากค่าที่ตั้งไว้
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">คำนวณจากค่าที่บันทึกล่าสุด (บันทึกแล้วตัวเลขจะอัปเดต)</p>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">ระยะทาง</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-300">ค่าส่งที่ลูกค้าจ่าย</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-300">ไรเดอร์ได้</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-300">แพลตฟอร์มได้</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-300">เวลาโดยประมาณ</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-300">ในพื้นที่บริการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($feeExamples as $example)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ number_format((float) $example['distance_km'], 1) }} กม.</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">฿{{ number_format((float) $example['total_fee'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-green-700 dark:text-green-400">฿{{ number_format((float) $example['rider_earnings'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-blue-700 dark:text-blue-400">฿{{ number_format((float) $example['platform_fee'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ (int) $example['estimated_duration_minutes'] }} นาที</td>
                            <td class="px-4 py-3 text-center">
                                @if (! empty($example['within_service_area']))
                                    <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">ส่งได้</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">เกินระยะ</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">ยังคำนวณตัวอย่างไม่ได้</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
