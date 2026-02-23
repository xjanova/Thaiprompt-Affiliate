{{-- รายละเอียดผลทำนายฝัน --}}
@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.fortune.horoscope-public.dream.readings') }}"
           class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🔍 ผลทำนายฝัน #{{ $reading->id }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- คอลัมน์ซ้าย: ข้อมูลหลัก --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- เรื่องฝัน --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">💭 เรื่องฝันที่เล่า</h2>
                <div class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed whitespace-pre-line bg-gray-50 dark:bg-gray-700 rounded-lg p-4">{{ $reading->dream_description }}</div>
            </div>

            {{-- ผล AI --}}
            @if($reading->ai_interpretation)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">🤖 ผลทำนาย AI</h2>
                <div class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed whitespace-pre-line bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">{{ $reading->ai_interpretation }}</div>
            </div>
            @endif

            {{-- สัญลักษณ์ที่ตรงกัน --}}
            @if($matchedSymbols->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">🔑 สัญลักษณ์ที่ตรงกัน ({{ $matchedSymbols->count() }})</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($matchedSymbols as $symbol)
                        <div class="p-3 rounded-lg border {{ $symbol->is_good_omen ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span>{{ $symbol->category?->icon ?? '💭' }}</span>
                                <span class="font-bold text-sm text-gray-900 dark:text-white">{{ $symbol->keyword_th }}</span>
                                <span class="text-xs">{{ $symbol->is_good_omen ? '✅' : '⚠️' }}</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">{{ $symbol->meaning_th }}</p>
                            @if(!empty($symbol->lucky_numbers))
                                <div class="flex gap-1 mt-1">
                                    @foreach($symbol->lucky_numbers as $num)
                                        <span class="px-1.5 py-0.5 bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded text-[10px] font-mono font-bold">{{ $num }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- คอลัมน์ขวา: เมตาดาต้า --}}
        <div class="space-y-6">

            {{-- เลขเด็ด --}}
            @if(!empty($reading->lucky_numbers))
            <div class="bg-gradient-to-br from-amber-500/10 to-orange-500/10 dark:from-amber-900/30 dark:to-orange-900/30 rounded-xl p-6 border border-amber-200 dark:border-amber-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 text-center">🎯 เลขเด็ด</h3>
                <div class="flex flex-wrap justify-center gap-3">
                    @foreach($reading->lucky_numbers as $num)
                        <span class="w-12 h-12 flex items-center justify-center bg-amber-500 text-white rounded-xl font-mono font-black text-xl shadow-lg">{{ $num }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ข้อมูลเพิ่มเติม --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📊 ข้อมูล</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">ID</dt>
                        <dd class="text-gray-900 dark:text-white font-mono">#{{ $reading->id }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Session</dt>
                        <dd class="text-gray-900 dark:text-white font-mono text-xs">{{ Str::limit($reading->session_id, 15) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">ผู้ใช้</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $reading->user?->name ?? 'Guest' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">AI Provider</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $reading->ai_provider_used ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">ฟรี/เครดิต</dt>
                        <dd>
                            @if($reading->is_free)
                                <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded text-xs font-medium">🎁 ฟรี</span>
                            @else
                                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded text-xs font-medium">💎 เครดิต</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Views</dt>
                        <dd class="text-gray-900 dark:text-white">{{ number_format($reading->view_count) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">IP</dt>
                        <dd class="text-gray-900 dark:text-white font-mono text-xs">{{ $reading->ip_address ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $reading->created_at->locale('th')->translatedFormat('j M Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- ลิงก์ Frontend --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-2">🔗 ลิงก์ Frontend</h3>
                <a href="{{ route('horoscope.dream.result', $reading->id) }}" target="_blank"
                   class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                    {{ route('horoscope.dream.result', $reading->id) }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
