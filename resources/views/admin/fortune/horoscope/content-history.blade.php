{{-- resources/views/admin/fortune/horoscope/content-history.blade.php --}}
{{-- ประวัติเนื้อหาที่ AI สร้าง (7 วันเกิด) --}}

@extends('layouts.admin')

@section('title', 'เนื้อหาดวง - ' . $campaign->name)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.fortune.horoscope.index') }}"
                   class="px-3 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition text-sm">
                    ← กลับ
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    🤖 เนื้อหาที่ AI สร้าง
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                แคมเปญ: <span class="font-semibold text-purple-600 dark:text-purple-400">{{ $campaign->name }}</span>
            </p>
        </div>

        {{-- เลือกวันที่ --}}
        <form method="GET" action="{{ route('admin.fortune.horoscope.content-history', $campaign) }}" class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-400">วันที่:</label>
            <input type="date" name="date"
                   value="{{ $selectedDate->format('Y-m-d') }}"
                   class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 text-sm"
                   onchange="this.form.submit()">
        </form>
    </div>

    {{-- วันที่แสดง --}}
    <div class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-xl">
        <div class="flex items-center gap-3">
            <span class="text-2xl">📅</span>
            <div>
                <p class="font-bold text-purple-800 dark:text-purple-200 text-lg">
                    วันที่ {{ $selectedDate->format('d/m/') }}{{ $selectedDate->year + 543 }}
                </p>
                <p class="text-sm text-purple-600 dark:text-purple-400">
                    พบเนื้อหา {{ $contents->count() }} รายการ จาก 7 วันเกิด
                </p>
            </div>
        </div>
    </div>

    {{-- สร้างเนื้อหาทันที --}}
    @if($contents->isEmpty())
        <div class="mb-6 p-8 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl text-center">
            <div class="text-4xl mb-3">🔮</div>
            <p class="text-lg font-medium text-yellow-800 dark:text-yellow-200 mb-2">
                ยังไม่มีเนื้อหาสำหรับวันนี้
            </p>
            <p class="text-sm text-yellow-600 dark:text-yellow-400 mb-4">
                กดปุ่มด้านล่างเพื่อให้ AI สร้างคำทำนายทันที
            </p>
            <form action="{{ route('admin.fortune.horoscope.generate-now', $campaign) }}" method="POST" class="inline"
                  onsubmit="return confirm('ต้องการสร้างเนื้อหา AI ทันทีหรือไม่? อาจใช้เวลา 2-5 นาที')">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate->format('Y-m-d') }}">
                <button type="submit"
                        class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition shadow-lg">
                    ⚡ สร้างเนื้อหา AI ทันที
                </button>
            </form>
        </div>
    @endif

    {{-- เนื้อหา 7 วันเกิด --}}
    @if($contents->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @php
                $dayEmojis = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣'];
                $dayColors = [
                    'from-red-500 to-orange-500',
                    'from-yellow-500 to-amber-500',
                    'from-pink-500 to-rose-500',
                    'from-green-500 to-emerald-500',
                    'from-orange-500 to-amber-500',
                    'from-blue-500 to-cyan-500',
                    'from-purple-500 to-violet-500',
                ];
            @endphp

            @foreach($contents as $content)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition">
                    {{-- Header --}}
                    <div class="p-4 bg-gradient-to-r {{ $dayColors[$content->birth_day] ?? 'from-gray-500 to-gray-600' }} text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-2xl">{{ $dayEmojis[$content->birth_day] ?? '⭐' }}</span>
                                <span class="text-lg font-bold ml-2">วัน{{ $content->birth_day_name }}</span>
                            </div>
                            {{-- สถานะ --}}
                            @switch($content->status)
                                @case('generated')
                                    <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs">✅ สำเร็จ</span>
                                    @break
                                @case('generating')
                                    <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs animate-pulse">⏳ กำลังสร้าง</span>
                                    @break
                                @case('pending')
                                    <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs">⏸ รอ</span>
                                    @break
                                @case('failed')
                                    <span class="px-2 py-0.5 bg-red-400/50 rounded-full text-xs">❌ ล้มเหลว</span>
                                    @break
                            @endswitch
                        </div>
                    </div>

                    {{-- รูปภาพ --}}
                    @if($content->image_url)
                        <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-gray-700">
                            <img src="{{ $content->image_url }}"
                                 alt="ดวงวัน{{ $content->birth_day_name }}"
                                 class="w-full h-full object-cover"
                                 loading="lazy"
                                 onerror="this.parentElement.innerHTML='<div class=\'flex items-center justify-center h-full text-gray-400\'><span class=\'text-4xl\'>🖼</span></div>'">
                        </div>
                    @endif

                    {{-- เนื้อหาคำทำนาย --}}
                    <div class="p-4">
                        @if($content->ai_prediction)
                            <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-4">
                                {{ $content->ai_prediction }}
                            </p>
                        @else
                            <p class="text-sm text-gray-400 italic">ยังไม่มีคำทำนาย</p>
                        @endif

                        {{-- Lucky info --}}
                        @if($content->lucky_color || $content->lucky_number || $content->lucky_direction)
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2">
                                @if($content->lucky_color)
                                    <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-400">
                                        🎨 {{ $content->lucky_color }}
                                    </span>
                                @endif
                                @if($content->lucky_number)
                                    <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-400">
                                        🔢 {{ $content->lucky_number }}
                                    </span>
                                @endif
                                @if($content->lucky_direction)
                                    <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-400">
                                        🧭 {{ $content->lucky_direction }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- Error --}}
                        @if($content->error_message)
                            <div class="mt-3 p-2 bg-red-50 dark:bg-red-900/20 rounded text-xs text-red-600 dark:text-red-400">
                                ⚠️ {{ Str::limit($content->error_message, 100) }}
                            </div>
                        @endif
                    </div>

                    {{-- Footer: Astrology info --}}
                    @if($content->chaochana_data)
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @php $chaochana = $content->chaochana_data; @endphp
                                @if(isset($chaochana['main_planet']))
                                    ดาว: <span class="font-semibold">{{ $chaochana['main_planet'] }}</span>
                                @endif
                                @if(isset($chaochana['element']))
                                    | ธาตุ: <span class="font-semibold">{{ $chaochana['element'] }}</span>
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Quick Actions --}}
        <div class="mt-8 flex flex-wrap gap-4">
            <form action="{{ route('admin.fortune.horoscope.generate-now', $campaign) }}" method="POST" class="inline"
                  onsubmit="return confirm('สร้างเนื้อหาใหม่จะเขียนทับเนื้อหาที่มีอยู่ ต้องการดำเนินการ?')">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate->format('Y-m-d') }}">
                <button type="submit"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition text-sm">
                    🔄 สร้างเนื้อหาใหม่
                </button>
            </form>

            <form action="{{ route('admin.fortune.horoscope.publish-now', $campaign) }}" method="POST" class="inline"
                  onsubmit="return confirm('ต้องการโพสเนื้อหาทันทีหรือไม่?')">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition text-sm">
                    🚀 โพสทันที
                </button>
            </form>

            <a href="{{ route('admin.fortune.horoscope.post-history', $campaign) }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-sm">
                📤 ดูประวัติโพส
            </a>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mt-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="mt-6 p-4 bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-700 rounded-xl text-yellow-800 dark:text-yellow-200">
            {{ session('warning') }}
        </div>
    @endif
</div>
@endsection
