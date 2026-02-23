{{-- จัดการ 12 ราศี + Generate Daily --}}
@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="zodiacAdmin()">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                ⭐ จัดการ 12 ราศี
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                แก้ไขข้อมูลราศี และสั่ง generate ดวงรายวัน AI
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.fortune.horoscope-public.settings') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ⚙️ ตั้งค่า
            </a>
            <a href="{{ route('admin.fortune.horoscope-public.zodiac.predictions') }}"
               class="px-4 py-2 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded-lg text-sm font-medium hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition">
                📊 ดวงทั้งหมด
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- ==================== Generate Daily ==================== --}}
    <div class="bg-gradient-to-br from-indigo-500/10 to-purple-500/10 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-xl p-6 mb-6 border border-indigo-200 dark:border-indigo-800">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            🤖 สร้างดวงรายวัน AI
        </h2>
        <form action="{{ route('admin.fortune.horoscope-public.zodiac.generate-daily') }}" method="POST"
              class="flex flex-col sm:flex-row gap-3 items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">📅 วันที่</label>
                <input type="date" name="target_date" value="{{ today()->format('Y-m-d') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                <select name="type"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="both">🔮 ทั้งหมด (12 ราศี + 7 วันเกิด)</option>
                    <option value="zodiac">⭐ 12 ราศี เท่านั้น</option>
                    <option value="birth_day">📅 7 วันเกิด เท่านั้น</option>
                </select>
            </div>
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition font-semibold shadow-lg whitespace-nowrap"
                    onclick="return confirm('ต้องการสร้างดวงรายวัน AI? (อาจใช้เวลาสักครู่)')">
                🚀 สร้างดวง
            </button>
        </form>
    </div>

    {{-- ==================== ตาราง 12 ราศี ==================== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ราศี</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ช่วงวันเกิด</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ธาตุ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ดวงวันนี้</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Predictions</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($zodiacs as $zodiac)
                        @php
                            $todayPrediction = $todayPredictions->get($zodiac->id);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl"
                                         style="background: {{ $zodiac->color_hex }}20; border: 1px solid {{ $zodiac->color_hex }}40;">
                                        {{ $zodiac->symbol_emoji }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $zodiac->name_th }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $zodiac->name_en }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $zodiac->date_range_display_th ?? "{$zodiac->date_range_start} — {$zodiac->date_range_end}" }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($zodiac->element === 'ไฟ') bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300
                                    @elseif($zodiac->element === 'ดิน') bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300
                                    @elseif($zodiac->element === 'ลม') bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-300
                                    @else bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300
                                    @endif">
                                    {{ $zodiac->element }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($todayPrediction)
                                    <div class="flex items-center justify-center gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <div class="w-2 h-2 rounded-full {{ $i <= ($todayPrediction->overall_score ?? 0) ? 'bg-indigo-500' : 'bg-gray-200 dark:bg-gray-600' }}"></div>
                                        @endfor
                                        <span class="ml-1 text-xs text-gray-500">{{ $todayPrediction->overall_score ?? '-' }}/5</span>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">— ยังไม่มี —</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ number_format($zodiac->daily_predictions_count) }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($zodiac->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                        ✅ เปิด
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400">
                                        ⏸️ ปิด
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('admin.fortune.horoscope-public.zodiac.edit', $zodiac) }}"
                                   class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs transition"
                                   title="แก้ไข">
                                    ✏️ แก้ไข
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function zodiacAdmin() {
    return {};
}
</script>
@endsection
