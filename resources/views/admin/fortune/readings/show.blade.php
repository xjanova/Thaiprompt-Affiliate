@extends('layouts.admin')

@section('title', 'รายละเอียดการทำนาย #' . $reading->id)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('admin.fortune.readings.index') }}" 
           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mb-4 inline-block">
            ← กลับไปรายการ
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            รายละเอียดการทำนาย #{{ $reading->id }}
        </h1>
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

            {{-- Deep Response --}}
            @if($reading->deep_response)
                <div class="mt-4">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">🌟 คำทำนายเชิงลึก</h4>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 text-sm">
                        <div class="whitespace-pre-wrap text-gray-800 dark:text-gray-200">{{ Str::limit($reading->deep_response, 500) }}</div>
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
                    @else
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">ฟรี</span>
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

        {{-- ปุ่ม Manual Action สำหรับ Admin (เฉพาะ deep reading ที่ชำระเงินแล้ว) --}}
        @if($reading->reading_type === 'deep' && $reading->is_paid)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-3 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-3 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">จัดการคำทำนายเชิงลึก (Manual)</p>
                <div class="flex flex-wrap gap-3">
                    @if(empty($reading->deep_response))
                        {{-- ไม่มีคำทำนาย → ปุ่มสร้างใหม่ (เด่น) --}}
                        <form action="{{ route('admin.fortune.readings.retry-deep', $reading) }}"
                              method="POST"
                              onsubmit="return confirm('ต้องการสร้างคำทำนายเชิงลึกและส่งให้ลูกค้าหรือไม่?\n\nระบบจะสร้างคำทำนายใหม่โดย AI และส่งให้ลูกค้าอัตโนมัติ')">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg shadow transition">
                                🔄 สร้างคำทำนายเชิงลึก + ส่งให้ลูกค้า
                            </button>
                        </form>
                    @else
                        {{-- มีคำทำนายแล้ว → ปุ่มส่งซ้ำ + ปุ่มสร้างใหม่ --}}
                        <form action="{{ route('admin.fortune.readings.resend-deep', $reading) }}"
                              method="POST"
                              onsubmit="return confirm('ส่งคำทำนายที่มีอยู่ให้ลูกค้าซ้ำหรือไม่?')">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow transition">
                                📨 ส่งคำทำนายซ้ำให้ลูกค้า
                            </button>
                        </form>
                        <form action="{{ route('admin.fortune.readings.retry-deep', $reading) }}"
                              method="POST"
                              onsubmit="return confirm('ต้องการสร้างคำทำนายเชิงลึกใหม่ทั้งหมดหรือไม่?\n\n⚠️ คำทำนายเดิมจะถูกลบ และระบบจะสร้างคำทำนายใหม่โดย AI')">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 border-2 border-orange-500 text-orange-600 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 text-sm font-medium rounded-lg transition">
                                🔄 สร้างคำทำนายใหม่
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
