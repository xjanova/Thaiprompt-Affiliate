{{-- Celtic Cross Reading Detail (admin view) --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('admin.fortune.celtic-cross.index') }}" class="text-sm text-purple-600 hover:underline">← กลับไปหน้ารวม</a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                🔮 Celtic Cross #{{ $reading->id }}
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                Bill: <code>{{ $reading->bill_reference ?? '-' }}</code> •
                {{ $reading->facebook_user_name ?? '-' }} •
                {{ $reading->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            @if($reading->celtic_summary_image_path)
                <a href="{{ asset('storage/' . $reading->celtic_summary_image_path) }}" target="_blank"
                   class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm">
                    🖼️ เปิดภาพ Spread
                </a>
            @endif
            @if($reading->is_paid)
                {{-- 🔄 (2026-05-03) Admin reset — ใช้เมื่อ flow ไม่สมบูรณ์ --}}
                <form action="{{ route('admin.fortune.celtic-cross.reset', $reading) }}" method="POST"
                      onsubmit="return confirm('ยืนยัน reset reading #{{ $reading->id }}?\n\nจะล้างไพ่ + Q&A ทั้งหมด แล้วให้ลูกค้าเริ่มเปิดไพ่ใหม่ (ไม่ต้องจ่ายซ้ำ)');"
                      class="inline">
                    @csrf
                    <input type="hidden" name="notify" value="1">
                    <button type="submit"
                            class="px-4 py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-900/50 rounded-lg text-sm font-semibold">
                        🔄 Reset + แจ้งลูกค้า
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-6 rounded-r-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-6 rounded-r-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Status Card --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">สถานะ</p>
            <p class="text-base font-bold text-gray-900 dark:text-white mt-1">{{ $reading->conversation_status }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">ค่าครู</p>
            <p class="text-base font-bold {{ $reading->is_paid ? 'text-green-600' : 'text-gray-400' }} mt-1">
                {{ $reading->is_paid ? '฿' . number_format($reading->amount_paid, 0) . ' ✓' : 'รอ' }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">คำถามใช้ไป</p>
            <p class="text-base font-bold text-purple-600 mt-1">{{ $reading->celtic_questions_used }}/{{ $maxQuestions > 0 ? $maxQuestions : '∞' }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Q1 ตอบเมื่อ</p>
            <p class="text-base font-bold text-gray-900 dark:text-white mt-1 text-xs">
                {{ $reading->celtic_first_answered_at?->format('d/m H:i') ?? '—' }}
            </p>
        </div>
    </div>

    {{-- Spread Image --}}
    @if($reading->celtic_summary_image_path)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🖼️ Celtic Cross Spread</h2>
            <img src="{{ asset('storage/' . $reading->celtic_summary_image_path) }}"
                 alt="Celtic Cross Spread"
                 class="max-w-full mx-auto rounded-lg shadow">
        </div>
    @endif

    {{-- 10 Cards --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🃏 ไพ่ทั้ง 10 ใบ</h2>

        @if(empty($cards))
            <p class="text-center text-gray-500 py-4">ยังไม่ได้เลือกไพ่</p>
        @else
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                @for($pos = 1; $pos <= 10; $pos++)
                    @php $card = $cards[$pos] ?? null; @endphp
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-gray-50 dark:bg-gray-900/50">
                        <p class="text-xs text-purple-600 dark:text-purple-400 font-semibold mb-1">
                            [{{ $pos }}] {{ $positions[$pos]['name'] ?? '?' }}
                        </p>
                        @if($card)
                            @if(!empty($card['image_url']))
                                <img src="{{ $card['image_url'] }}" alt="{{ $card['card_name_th'] ?? '' }}"
                                     class="w-full rounded mb-2 {{ !empty($card['is_reversed']) ? 'rotate-180' : '' }}">
                            @endif
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $card['card_name_th'] ?? '?' }}</p>
                            <p class="text-xs text-gray-500">{{ $card['card_name_en'] ?? '' }}</p>
                            <p class="text-xs {{ !empty($card['is_reversed']) ? 'text-red-600' : 'text-green-600' }} mt-1">
                                {{ !empty($card['is_reversed']) ? '⤵ กลับหัว' : '↑ ตั้งตรง' }}
                            </p>
                        @else
                            <p class="text-xs text-gray-400 italic mt-2">ยังไม่เลือก</p>
                        @endif
                    </div>
                @endfor
            </div>
        @endif
    </div>

    {{-- Q&A History --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">💬 คำถาม & คำตอบ</h2>

        @if($reading->celticQuestions->isEmpty())
            <p class="text-center text-gray-500 py-4">ยังไม่มีคำถาม</p>
        @else
            @foreach($reading->celticQuestions as $q)
                <div class="border-l-4 {{ $q->sequence === 1 ? 'border-purple-500' : 'border-blue-400' }} bg-gray-50 dark:bg-gray-900/50 p-4 mb-4 rounded-r-lg">
                    <div class="flex justify-between items-start mb-2">
                        <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded text-xs font-semibold">
                            Q{{ $q->sequence }} {{ $q->sequence === 1 ? '(Main)' : '(Follow-up)' }}
                        </span>
                        <span class="text-xs text-gray-500">
                            {{ $q->answered_at?->format('d/m H:i:s') ?? 'รอ' }}
                            @if($q->ai_response_time_ms) • {{ round($q->ai_response_time_ms / 1000, 1) }}s @endif
                            @if($q->ai_tokens_used) • {{ number_format($q->ai_tokens_used) }} tokens @endif
                        </span>
                    </div>

                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">❓ {{ $q->question }}</p>

                    @if($q->response)
                        <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-sans bg-white dark:bg-gray-800 p-3 rounded mt-2">{{ $q->response }}</pre>
                    @else
                        <p class="text-xs text-red-500 italic">ยังไม่มีคำตอบ (อาจ AI ล้มเหลว)</p>
                    @endif

                    @if($q->ai_provider)
                        <p class="text-xs text-gray-500 mt-2">via {{ $q->ai_provider }} • {{ $q->ai_model }}</p>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
