@extends('layouts.admin-v3')

@section('title', 'รายละเอียดการทำนาย #' . $reading->id)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('admin.fortune.readings.index') }}"
           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mb-4 inline-block">
            ← กลับไปรายการ
        </a>
        <div class="flex items-start justify-between gap-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                รายละเอียดการทำนาย #{{ $reading->id }}
            </h1>
            <a href="{{ route('admin.fortune.readings.edit', $reading) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow transition">
                ✏️ แก้ไข
            </a>
        </div>
    </div>

    {{-- User Info + Conversation Status --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- User Info --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">👤 ข้อมูลผู้ใช้</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-gray-600 dark:text-gray-400 text-sm">ชื่อ:</span>
                    <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $reading->facebook_user_name ?? 'ไม่ระบุ' }}</span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Facebook ID:</span>
                    <span class="ml-2 text-gray-900 dark:text-white font-mono text-sm">{{ $reading->facebook_user_id }}</span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400 text-sm">วันที่:</span>
                    <span class="ml-2 text-gray-900 dark:text-white">{{ $reading->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400 text-sm">ช่องทาง:</span>
                    <span class="ml-2">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $reading->response_type === 'comment' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                            {{ $reading->response_type === 'comment' ? 'Comment' : 'Private Message' }}
                        </span>
                    </span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400 text-sm">ประเภทคำทำนาย:</span>
                    <span class="ml-2">
                        @if($reading->reading_type === 'deep')
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                🌟 เชิงลึก
                            </span>
                        @else
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200">
                                🔮 พื้นฐาน
                            </span>
                        @endif
                    </span>
                </div>
                @if($reading->birth_date)
                    <div>
                        <span class="text-gray-600 dark:text-gray-400 text-sm">วันเกิด:</span>
                        <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $reading->birth_date->format('d/m/Y') }}</span>
                    </div>
                @endif
                @if(!empty($reading->categories))
                    <div>
                        <span class="text-gray-600 dark:text-gray-400 text-sm">หมวดหมู่:</span>
                        <span class="ml-2">
                            @foreach($reading->categories as $cat)
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300">
                                    {{ $cat }}
                                </span>
                            @endforeach
                        </span>
                    </div>
                @endif
                @if($reading->bill_reference)
                    <div>
                        <span class="text-gray-600 dark:text-gray-400 text-sm">เลขที่บิล:</span>
                        <span class="ml-2 font-mono text-sm text-gray-900 dark:text-white">{{ $reading->bill_reference }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Conversation Timeline --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🔄 สถานะ Conversation</h3>
            @php
                $statuses = [
                    'new' => ['label' => 'ใหม่', 'icon' => '🆕', 'color' => 'blue'],
                    'awaiting_confirmation' => ['label' => 'รอยืนยัน', 'icon' => '⏳', 'color' => 'yellow'],
                    'basic_done' => ['label' => 'Basic เสร็จ', 'icon' => '🔮', 'color' => 'cyan'],
                    'collecting_birthdate' => ['label' => 'รับวันเกิด', 'icon' => '📅', 'color' => 'orange'],
                    'collecting_questions' => ['label' => 'รับคำถาม', 'icon' => '❓', 'color' => 'purple'],
                    'pending_payment' => ['label' => 'รอชำระ', 'icon' => '💳', 'color' => 'red'],
                    'paid' => ['label' => 'ชำระแล้ว', 'icon' => '✅', 'color' => 'green'],
                    'completed' => ['label' => 'เสร็จสิ้น', 'icon' => '🏁', 'color' => 'green'],
                ];
                $currentStatus = $reading->conversation_status ?? 'new';
                $statusOrder = array_keys($statuses);
                $currentIndex = array_search($currentStatus, $statusOrder);
            @endphp
            <div class="space-y-3">
                @foreach($statuses as $key => $status)
                    @php
                        $index = array_search($key, $statusOrder);
                        $isPast = $index < $currentIndex;
                        $isCurrent = $key === $currentStatus;
                    @endphp
                    <div class="flex items-center gap-3 {{ $isCurrent ? 'font-bold' : '' }}">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm
                            {{ $isCurrent ? 'bg-'.$status['color'].'-500 text-white ring-2 ring-'.$status['color'].'-300 shadow' : ($isPast ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-400') }}">
                            {{ $isPast ? '✓' : $status['icon'] }}
                        </span>
                        <span class="{{ $isCurrent ? 'text-gray-900 dark:text-white' : ($isPast ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500') }} text-sm">
                            {{ $status['label'] }}
                        </span>
                        @if($isCurrent)
                            <span class="ml-auto text-xs px-2 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded-full">ปัจจุบัน</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Questions --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">คำถาม</h3>
        <ol class="list-decimal list-inside space-y-2">
            @foreach($reading->questions as $question)
                <li class="text-gray-900 dark:text-white">{{ $question }}</li>
            @endforeach
        </ol>
    </div>

    {{-- รูปภาพจากผู้ใช้ --}}
    @if($reading->user_image_url)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">รูปภาพจากผู้ใช้</h3>
            <div class="max-w-md">
                <img src="{{ $reading->user_image_url }}" alt="รูปจากผู้ใช้"
                     class="rounded-lg shadow-md max-h-96 object-contain"
                     loading="lazy">
            </div>
        </div>
    @endif

    {{-- Birth Chart + AI Response --}}
    <div class="grid grid-cols-1 {{ $reading->reading_image_url ? 'lg:grid-cols-3' : '' }} gap-6 mb-6">
        {{-- Birth Chart Image --}}
        @if($reading->reading_image_url)
            <div class="bg-gradient-to-br from-purple-900 to-indigo-900 rounded-xl shadow-lg p-6 flex flex-col items-center justify-center">
                <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">🎨 Birth Chart</h3>
                <img src="{{ $reading->reading_image_url }}" alt="Birth Chart"
                     class="rounded-xl shadow-lg max-w-full max-h-80 object-contain border border-purple-500/30"
                     loading="lazy">
                <p class="text-purple-300 text-xs mt-2">ภาพดวงดาวที่ส่งให้ผู้ใช้</p>
            </div>
        @endif

        {{-- AI Response --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 {{ $reading->reading_image_url ? 'lg:col-span-2' : '' }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">🔮 คำทำนาย</h3>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ strtoupper($reading->ai_provider) }} / {{ $reading->ai_model }}
                    </span>
                    @if($reading->tokens_used)
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                            {{ number_format($reading->tokens_used) }} tokens
                        </span>
                    @endif
                </div>
            </div>
            <div class="prose dark:prose-invert max-w-none bg-gray-50 dark:bg-gray-700/50 rounded-xl p-5">
                <div class="whitespace-pre-wrap text-gray-900 dark:text-white leading-relaxed">{{ $reading->ai_response }}</div>
            </div>

            {{-- Basic Response --}}
            @if($reading->basic_response && $reading->basic_response !== $reading->ai_response)
                <div class="mt-4">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">📝 คำทำนายพื้นฐาน</h4>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 text-sm">
                        <div class="whitespace-pre-wrap text-gray-800 dark:text-gray-200">{{ Str::limit($reading->basic_response, 500) }}</div>
                    </div>
                </div>
            @endif

            {{-- 🌟 Deep Response — (2026-05-02) แสดงเต็ม + ปุ่มคัดลอก + scrollable --}}
            @if($reading->deep_response)
                <div class="mt-4" x-data="{ expanded: false }">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300">🌟 คำทำนายเชิงลึก
                            <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                ({{ number_format(mb_strlen($reading->deep_response)) }} ตัวอักษร)
                            </span>
                        </h4>
                        <div class="flex gap-2">
                            <button @click="expanded = !expanded"
                                    class="px-3 py-1.5 text-xs bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition flex items-center gap-1.5">
                                <span x-show="!expanded">📖 อ่านคำทำนายเต็ม</span>
                                <span x-show="expanded">📕 ย่อกลับ</span>
                            </button>
                            <button @click="navigator.clipboard.writeText($refs.deepText.textContent); $el.textContent='✅ คัดลอกแล้ว'; setTimeout(() => $el.textContent='📋 คัดลอก', 2000)"
                                    class="px-3 py-1.5 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition">
                                📋 คัดลอก
                            </button>
                        </div>
                    </div>

                    {{-- Preview (collapsed) --}}
                    <div x-show="!expanded"
                         class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 text-sm border-l-4 border-purple-400">
                        <div class="whitespace-pre-wrap text-gray-800 dark:text-gray-200 leading-relaxed">{{ Str::limit($reading->deep_response, 500) }}</div>
                        @if(mb_strlen($reading->deep_response) > 500)
                            <button @click="expanded = true"
                                    class="mt-2 text-purple-600 dark:text-purple-400 text-xs font-medium hover:underline">
                                ▼ กดอ่านส่วนที่เหลือ ({{ number_format(mb_strlen($reading->deep_response) - 500) }} ตัวอักษร)
                            </button>
                        @endif
                    </div>

                    {{-- Full (expanded) --}}
                    <div x-show="expanded" x-transition x-ref="deepFull"
                         class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-5 text-sm border-2 border-purple-300 dark:border-purple-700 shadow-md">
                        <div x-ref="deepText" class="whitespace-pre-wrap text-gray-900 dark:text-gray-100 leading-loose max-h-[600px] overflow-y-auto pr-2">{{ $reading->deep_response }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Payment Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ข้อมูลการชำระเงิน</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <span class="text-gray-600 dark:text-gray-400">สถานะ:</span>
                <span class="ml-2">
                    @if($reading->is_paid)
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">ชำระแล้ว</span>
                    @elseif($reading->conversation_status === 'pending_payment')
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">💳 รอชำระ</span>
                    @elseif($reading->reading_type === 'deep' && $reading->amount_paid > 0)
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">รอชำระ</span>
                    @else
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">ฟรี</span>
                    @endif

                    {{-- 🛑 (2026-05-07) Pay-Later removed — เหลือเฉพาะ pay-first badge --}}
                    @if($reading->reading_type === 'deep' && ! $reading->is_paid && $reading->amount_paid > 0)
                        <span class="ml-1 px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" title="ลูกค้าต้องจ่ายก่อนถึงจะได้รับคำทำนาย — UPA หมดอายุ 30 นาที">
                            💳 จ่ายก่อนดู
                        </span>
                    @endif
                </span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">จำนวนเงิน:</span>
                <span class="ml-2 text-gray-900 dark:text-white font-bold">฿{{ number_format($reading->amount_paid, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">วันที่ชำระ:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $reading->paid_at ? $reading->paid_at->format('d/m/Y H:i') : '-' }}</span>
            </div>
        </div>

        {{-- ปุ่ม Manual Action สำหรับ Admin (deep reading) --}}
        {{-- 🩹 (2026-05-04) เปิดให้กดได้รวม Pay-Later flow (is_paid=false แต่มี deep_response แล้ว)
             เคสจริงที่ user รายงาน: ลูกค้าทำ Pay-Later 39฿ คำทำนายเสร็จแต่ระบบส่งไม่ถึง
             (FB 24hr window expired / sendResponse fail) → admin ต้องส่งซ้ำได้ --}}
        @if($reading->reading_type === 'deep' && ($reading->is_paid || ! empty($reading->deep_response) || $reading->conversation_status === 'paid'))
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                {{-- 🛟 (2026-05-14) Pay-First incomplete data — ลูกค้าจ่ายแต่ยังไม่กรอกข้อมูล --}}
                @if($reading->is_paid && empty($reading->birth_date))
                    <div class="mb-3 px-4 py-3 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700">
                        <div class="flex items-start gap-2">
                            <span class="text-amber-500 text-lg">🛟</span>
                            <div class="flex-1">
                                <p class="text-amber-800 dark:text-amber-200 font-medium">รอข้อมูลจากลูกค้า — ยังสร้างคำทำนายไม่ได้</p>
                                <p class="text-amber-600 dark:text-amber-400 text-sm mt-1">
                                    ลูกค้า<strong>จ่าย{{ number_format($reading->amount_paid ?? 39, 0) }}฿แล้ว</strong> (Pay-First Deep) แต่ยังไม่กรอก
                                    <strong>วันเดือนปีเกิด</strong> → AI ทำนายไม่ได้
                                </p>
                                <ul class="text-amber-600 dark:text-amber-400 text-xs mt-2 ml-4 list-disc">
                                    <li>วันเกิด: {{ $reading->birth_date ? $reading->birth_date->format('d/m/Y') : '❌ ยังไม่กรอก' }}</li>
                                    <li>คำถาม: {{ is_array($reading->questions) && count($reading->questions) > 0 ? count($reading->questions).' คำถาม' : '❌ ยังไม่ถาม' }}</li>
                                </ul>
                                <p class="text-amber-700 dark:text-amber-300 text-sm mt-2">
                                    💡 ใช้ปุ่ม <strong>"🛟 ส่งขอวันเกิดใหม่"</strong> ด้านล่างเพื่อ push message ให้ลูกค้ากรอกข้อมูล
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ⚠️ Warning: ชำระเงินแล้วแต่ AI สร้างคำทำนายไม่สำเร็จ (มีวันเกิดแล้ว แต่ deep_response ว่าง) --}}
                @if($reading->is_paid && $reading->conversation_status === 'completed' && empty($reading->deep_response) && ! empty($reading->birth_date))
                    <div class="mb-3 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700">
                        <div class="flex items-start gap-2">
                            <span class="text-red-500 text-lg">⚠️</span>
                            <div>
                                <p class="text-red-800 dark:text-red-200 font-medium">ลูกค้าชำระเงินแล้วแต่ยังไม่มีคำทำนายเชิงลึก!</p>
                                <p class="text-red-600 dark:text-red-400 text-sm mt-1">อาจเกิดจาก: AI API quota หมด, API key หมดอายุ, หรือ provider ล่ม กรุณากดปุ่ม "สร้างคำทำนายเชิงลึก" ด้านล่าง</p>
                                <p class="text-red-500 dark:text-red-500 text-xs mt-1">ตรวจสอบ log: <code class="bg-red-100 dark:bg-red-900 px-1 rounded">storage/logs/fortune-deep-{{ $reading->id }}.log</code></p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-3 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">
                        {!! nl2br(e(session('success'))) !!}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-3 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">
                        {!! nl2br(e(session('error'))) !!}
                    </div>
                @endif
                @if(session('warning'))
                    <div class="mb-3 px-4 py-3 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 text-sm">
                        {!! nl2br(e(session('warning'))) !!}
                    </div>
                @endif
                @if(session('info'))
                    <div class="mb-3 px-4 py-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-sm">
                        {!! nl2br(e(session('info'))) !!}
                    </div>
                @endif

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">จัดการคำทำนายเชิงลึก (Manual)</p>

                {{-- สถานะ: กำลังสร้างคำทำนาย (conversation_status = paid แต่ยังไม่มี deep_response) --}}
                @if($reading->conversation_status === 'paid' && empty($reading->deep_response))
                    @php
                        // 🎯 (2026-05-02) คำนวณเวลา + provider เพื่อให้ admin เห็นชัด
                        $paidElapsedSec = $reading->updated_at?->diffInSeconds(now()) ?? 0;
                        $primaryProvider = strtoupper(\App\Models\FortuneTellingSetting::getSettings()->ai_provider ?? 'gemini');
                    @endphp
                    <div id="ai-processing-banner" class="mb-4 px-5 py-4 rounded-xl bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 border-2 border-purple-300 dark:border-purple-700 shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="animate-spin h-8 w-8 border-3 border-purple-600 border-t-transparent rounded-full"></div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-purple-900 dark:text-purple-100 font-bold text-lg">🔮 AI กำลังสร้างคำทำนาย</p>
                                    <span class="px-2 py-0.5 bg-purple-600 text-white text-xs font-bold rounded-full">{{ $primaryProvider }}</span>
                                </div>
                                <p class="text-purple-700 dark:text-purple-300 text-sm">
                                    ระบบกำลังประมวลผลคำทำนายเชิงลึก — <b>แอดมินไม่ต้องทำอะไร</b> หน้าจะรีเฟรชอัตโนมัติเมื่อเสร็จ
                                </p>
                                <p class="text-purple-600 dark:text-purple-400 text-xs mt-1">
                                    ⏱️ รอมาแล้ว <span id="ai-elapsed">{{ $paidElapsedSec }}</span> วินาที (ปกติใช้ 30-60 วินาที)
                                </p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="w-full bg-purple-200 dark:bg-purple-800 rounded-full h-2">
                                <div id="ai-progress-bar" class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full transition-all duration-1000" style="width: 5%"></div>
                            </div>
                        </div>
                    </div>
                    {{-- Polling: เช็ค status endpoint ทุก 3 วิ — reload เฉพาะตอน deep_response มี --}}
                    {{-- (เดิม reload ทุก 5 วิ — รบกวน scroll/copy ของ admin) --}}
                    <script>
                        (function() {
                            const statusUrl = '{{ route('admin.fortune.readings.status', $reading) }}';
                            const progressBar = document.getElementById('ai-progress-bar');
                            const banner = document.getElementById('ai-processing-banner');
                            const elapsedDisplay = document.getElementById('ai-elapsed');
                            const initialElapsed = {{ $paidElapsedSec ?? 0 }};
                            const startedAt = Date.now();
                            const maxWaitMs = 180 * 1000; // 3 นาที

                            const tick = setInterval(async () => {
                                const elapsedSec = (Date.now() - startedAt) / 1000;
                                const totalElapsed = Math.round(initialElapsed + elapsedSec);

                                // อัพเดท elapsed counter ทุก 1 วินาที
                                if (elapsedDisplay) {
                                    elapsedDisplay.textContent = totalElapsed;
                                }

                                // อัพเดท progress bar (visual only)
                                if (progressBar) {
                                    const progress = Math.min(95, (totalElapsed / 60) * 100);
                                    progressBar.style.width = progress + '%';
                                }

                                // ตรวจ timeout
                                if (Date.now() - startedAt > maxWaitMs) {
                                    clearInterval(tick);
                                    if (banner) {
                                        banner.innerHTML = '<p class="text-orange-600 dark:text-orange-400 font-medium">⚠️ AI ใช้เวลานานกว่าปกติ — กรุณารีเฟรชหน้าด้วยตัวเอง หรือกดปุ่มสร้างคำทำนายใหม่</p>';
                                    }
                                    return;
                                }

                                // เช็ค status (silent — ไม่รบกวน UI)
                                try {
                                    const res = await fetch(statusUrl, {
                                        headers: { 'Accept': 'application/json' },
                                        cache: 'no-store',
                                    });
                                    if (!res.ok) return;
                                    const data = await res.json();
                                    if (data.ready) {
                                        clearInterval(tick);
                                        if (progressBar) progressBar.style.width = '100%';
                                        if (banner) {
                                            banner.innerHTML = '<p class="text-green-700 dark:text-green-300 font-medium">✅ คำทำนายเสร็จแล้ว — กำลังโหลดผล...</p>';
                                        }
                                        // หน่วงสั้น ๆ ให้ user เห็น "เสร็จแล้ว" ก่อน reload
                                        setTimeout(() => window.location.reload(), 600);
                                    }
                                } catch (e) {
                                    // เงียบ — รอบหน้าจะลองใหม่
                                }
                            }, 3000);
                        })();
                    </script>
                @endif

                <div class="flex flex-wrap gap-3" x-data="{ submitting: false }">
                    @if(empty($reading->deep_response))
                        {{-- 🛟 (2026-05-14) ลูกค้าจ่ายแล้วแต่ยังไม่กรอกวันเกิด → ใช้ recover แทน retry --}}
                        @if($reading->is_paid && empty($reading->birth_date))
                            <form action="{{ route('admin.fortune.readings.recover-pay-first', $reading) }}"
                                  method="POST"
                                  @submit="if(!confirm('ส่งข้อความ \'ขอวันเกิด\' ให้ลูกค้าใหม่หรือไม่?\n\nระบบจะ push message ผ่าน POST_PURCHASE_UPDATE — ลูกค้าจะได้รับใน Messenger/LINE')) { $event.preventDefault(); return; } submitting = true;">
                                @csrf
                                <button type="submit"
                                        :disabled="submitting"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 hover:bg-amber-700 disabled:bg-amber-400 text-white text-sm font-medium rounded-lg shadow transition">
                                    <template x-if="!submitting">
                                        <span>🛟 ส่งขอวันเกิดใหม่</span>
                                    </template>
                                    <template x-if="submitting">
                                        <span class="flex items-center gap-2">
                                            <span class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                                            กำลังส่งคำสั่ง...
                                        </span>
                                    </template>
                                </button>
                            </form>
                            <p class="text-amber-700 dark:text-amber-300 text-xs ml-2 self-center">
                                💡 ยังสร้างคำทำนายไม่ได้ — รอลูกค้ากรอกวันเกิดก่อน
                            </p>
                        @else
                            {{-- มีข้อมูลครบ → ปุ่มสร้างใหม่ (เด่น) --}}
                            <form action="{{ route('admin.fortune.readings.retry-deep', $reading) }}"
                                  method="POST"
                                  @submit="if(!confirm('ต้องการสร้างคำทำนายเชิงลึกและส่งให้ลูกค้าหรือไม่?\n\nระบบจะสร้างคำทำนายใหม่โดย AI และส่งให้ลูกค้าอัตโนมัติ (ใช้เวลา 30-60 วินาที)')) { $event.preventDefault(); return; } submitting = true;">
                                @csrf
                                <button type="submit"
                                        :disabled="submitting"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white text-sm font-medium rounded-lg shadow transition">
                                    <template x-if="!submitting">
                                        <span>🔄 สร้างคำทำนายเชิงลึก + ส่งให้ลูกค้า</span>
                                    </template>
                                    <template x-if="submitting">
                                        <span class="flex items-center gap-2">
                                            <span class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                                            กำลังส่งคำสั่ง...
                                        </span>
                                    </template>
                                </button>
                            </form>
                        @endif
                    @else
                        {{-- มีคำทำนายแล้ว → ปุ่มส่งซ้ำ + ปุ่มสร้างใหม่ --}}
                        <form action="{{ route('admin.fortune.readings.resend-deep', $reading) }}"
                              method="POST"
                              @submit="if(!confirm('ส่งคำทำนายที่มีอยู่ให้ลูกค้าซ้ำหรือไม่?')) { $event.preventDefault(); return; } submitting = true;">
                            @csrf
                            <button type="submit"
                                    :disabled="submitting"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white text-sm font-medium rounded-lg shadow transition">
                                <template x-if="!submitting">
                                    <span>📨 ส่งคำทำนายซ้ำให้ลูกค้า</span>
                                </template>
                                <template x-if="submitting">
                                    <span class="flex items-center gap-2">
                                        <span class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                                        กำลังส่ง...
                                    </span>
                                </template>
                            </button>
                        </form>
                        <form action="{{ route('admin.fortune.readings.retry-deep', $reading) }}"
                              method="POST"
                              @submit="if(!confirm('ต้องการสร้างคำทำนายเชิงลึกใหม่ทั้งหมดหรือไม่?\n\n⚠️ คำทำนายเดิมจะถูกลบ และระบบจะสร้างคำทำนายใหม่โดย AI (ใช้เวลา 30-60 วินาที)')) { $event.preventDefault(); return; } submitting = true;">
                            @csrf
                            <button type="submit"
                                    :disabled="submitting"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 border-2 border-orange-500 text-orange-600 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 disabled:opacity-50 text-sm font-medium rounded-lg transition">
                                <template x-if="!submitting">
                                    <span>🔄 สร้างคำทำนายใหม่</span>
                                </template>
                                <template x-if="submitting">
                                    <span class="flex items-center gap-2">
                                        <span class="animate-spin h-4 w-4 border-2 border-orange-500 border-t-transparent rounded-full"></span>
                                        กำลังส่งคำสั่ง...
                                    </span>
                                </template>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Stats --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">สถิติ</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <span class="text-gray-600 dark:text-gray-400">จำนวนการดู:</span>
                <span class="ml-2 text-gray-900 dark:text-white font-bold">{{ number_format($reading->view_count) }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">คะแนน:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $reading->rating ? $reading->getRatingStars() : '-' }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">ตอบกลับเมื่อ:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $reading->responded_at ? $reading->responded_at->format('d/m/Y H:i') : 'ยังไม่ตอบ' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
