@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="userDetail()">
    {{-- Breadcrumb --}}
    <div class="mb-4">
        <a href="{{ route('admin.fortune.users.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
            &larr; กลับไปรายการผู้ใช้
        </a>
    </div>

    {{-- Header: User Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    {{ $userInfo['facebook_user_name'] }}
                </h1>
                <div class="flex flex-wrap gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-mono">{{ $userInfo['facebook_user_id'] }}</span>
                    @if($userInfo['platform'] === 'line')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">🟢 LINE</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">📘 Facebook</span>
                    @endif
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex gap-3">
                <button @click="showSendMessage = !showSendMessage"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    💬 ส่งข้อความ
                </button>
                <form action="{{ route('admin.fortune.users.quick-add-credits') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="facebook_user_id" value="{{ $userInfo['facebook_user_id'] }}">
                    <input type="hidden" name="platform" value="{{ $userInfo['platform'] }}">
                    <input type="hidden" name="facebook_user_name" value="{{ $userInfo['facebook_user_name'] }}">
                    <input type="hidden" name="amount" value="5">
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                        🎁 +5 เครดิต
                    </button>
                </form>
            </div>
        </div>

        {{-- User Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $userInfo['total_readings'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">ดูดวงทั้งหมด</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $userInfo['deep_readings'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">ดูดวงละเอียด</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($userInfo['total_spent'], 0) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">จ่ายรวม</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($userInfo['first_reading'])->format('d/m/Y') }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">ดูครั้งแรก</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($userInfo['last_reading'])->diffForHumans() }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">ดูล่าสุด</div>
            </div>
        </div>

        {{-- Credit Info --}}
        @if($credit)
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center gap-4 text-sm">
                <span class="font-medium text-blue-700 dark:text-blue-300">🎁 เครดิตพิเศษ:</span>
                @if($credit->isCurrentlyUnlimited())
                    <span class="text-purple-600 dark:text-purple-400 font-bold">🌟 ดูฟรีไม่จำกัด</span>
                @else
                    <span>เหลือ {{ $credit->getRemainingCredits() }} ครั้ง (ใช้ไป {{ $credit->credits_used }} / {{ $credit->bonus_credits }})</span>
                @endif
                <a href="{{ route('admin.fortune.credits.index', ['search' => $userInfo['facebook_user_id']]) }}"
                   class="text-blue-600 dark:text-blue-400 hover:underline ml-auto">จัดการ &rarr;</a>
            </div>
        @endif

        {{-- 🔒 (2026-05-04) Pay-Later Eligibility (Request-Before-Pay) --}}
        @isset($payLaterStatus)
            <div class="mt-4 p-4 rounded-lg border-2 {{ $payLaterStatus['eligible'] ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700' }}">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            💎 สิทธิ์ "ดูก่อนจ่ายทีหลัง" (Request-Before-Pay)
                        </h3>
                        @if($payLaterStatus['eligible'])
                            <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                                ✅ <strong>ยังไม่เคยใช้สิทธิ์</strong> — ลูกค้าจะได้ดูดวง 39฿ ก่อนจ่ายในรอบถัดไป
                            </p>
                        @else
                            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                                🔒 <strong>ใช้สิทธิ์ไปแล้ว</strong> — ลูกค้าต้องจ่ายก่อนในทุกบิลถัดไป
                            </p>
                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 grid grid-cols-2 gap-x-4 gap-y-1">
                                <div>📊 ใช้ทั้งหมด: <strong>{{ $payLaterStatus['usage_count'] }}</strong> ครั้ง</div>
                                <div>💰 จ่ายแล้ว: <strong class="text-green-600 dark:text-green-400">{{ $payLaterStatus['paid_count'] }}</strong></div>
                                <div>⚠️ ยังไม่จ่าย: <strong class="text-red-600 dark:text-red-400">{{ $payLaterStatus['unpaid_count'] }}</strong></div>
                                <div>🕐 ใช้ครั้งแรก: {{ $payLaterStatus['first_used_at']?->format('d/m/Y H:i') ?? '-' }}</div>
                            </div>
                        @endif
                    </div>

                    @if(!$payLaterStatus['eligible'])
                        <form action="{{ route('admin.fortune.users.reset-pay-later', ['platform' => $userInfo['platform'], 'userId' => $userInfo['facebook_user_id']]) }}"
                              method="POST"
                              onsubmit="return confirm('⚠️ ยืนยันรีเซ็ตสิทธิ์ดูก่อนจ่ายทีหลัง?\n\nลูกค้าจะใช้สิทธิ์ดูก่อนจ่ายได้อีกครั้ง (ในบิลถัดไป)\nflag จะถูกล้างจาก {{ $payLaterStatus['usage_count'] }} reading');"
                              class="shrink-0">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg shadow transition">
                                🔓 รีเซ็ตสิทธิ์
                            </button>
                        </form>
                    @endif
                </div>

                @if(!$payLaterStatus['eligible'] && $payLaterStatus['readings']->isNotEmpty())
                    <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-800">
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">📋 ประวัติการใช้:</p>
                        <div class="space-y-1 text-xs max-h-40 overflow-y-auto">
                            @foreach($payLaterStatus['readings'] as $r)
                                <div class="flex items-center justify-between gap-2 p-2 rounded bg-white dark:bg-gray-800/50">
                                    <code class="text-gray-700 dark:text-gray-300">{{ $r->bill_reference ?? '-' }}</code>
                                    <span class="text-gray-500 dark:text-gray-400">{{ $r->created_at->format('d/m/Y H:i') }}</span>
                                    @if($r->is_paid)
                                        <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 rounded font-semibold">จ่ายแล้ว ฿{{ number_format($r->amount_paid, 2) }}</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 rounded font-semibold">ยังไม่จ่าย</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endisset
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Send Message Form --}}
    <div x-show="showSendMessage" x-cloak
         class="mb-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-2 border-blue-200 dark:border-blue-800">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
            💬 ส่งข้อความถึง {{ $userInfo['facebook_user_name'] }}
        </h2>
        <form action="{{ route('admin.fortune.users.send-message') }}" method="POST">
            @csrf
            <input type="hidden" name="platform" value="{{ $userInfo['platform'] }}">
            <input type="hidden" name="facebook_user_id" value="{{ $userInfo['facebook_user_id'] }}">
            <div class="mb-4">
                <textarea name="message" rows="4" required maxlength="2000"
                          placeholder="พิมพ์ข้อความที่ต้องการส่ง..."
                          class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    ส่งข้อความ
                </button>
                <button type="button" @click="showSendMessage = false"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>

    {{-- Readings History --}}
    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
        📜 ประวัติการดูดวง
    </h2>

    <div class="space-y-4">
        @forelse($readings as $reading)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">
                            @if($reading->reading_type === 'deep') 💎 @else 🔮 @endif
                        </span>
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $reading->reading_type === 'deep' ? 'ดูดวงละเอียด' : 'ดูดวงพื้นฐาน' }}
                            </span>
                            @if($reading->is_paid)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                    ฿{{ number_format($reading->amount_paid, 0) }}
                                </span>
                            @endif
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $reading->created_at->format('d/m/Y H:i') }} ({{ $reading->created_at->diffForHumans() }})
                                @if($reading->ai_provider)
                                    &middot; {{ $reading->ai_provider }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 md:mt-0 flex items-center gap-2">
                        @if($reading->conversation_status)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                @switch($reading->conversation_status)
                                    @case('completed') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 @break
                                    @case('paid') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 @break
                                    @case('pending_payment') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 @break
                                    @default bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300
                                @endswitch">
                                {{ $reading->conversation_status }}
                            </span>
                        @endif
                        <a href="{{ route('admin.fortune.readings.show', $reading) }}"
                           class="text-blue-600 dark:text-blue-400 text-xs hover:underline">ดูเพิ่มเติม</a>
                    </div>
                </div>

                {{-- คำถาม --}}
                @if($reading->questions && is_array($reading->questions))
                    <div class="mb-3">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">คำถาม:</div>
                        @foreach($reading->questions as $q)
                            <div class="text-sm text-gray-700 dark:text-gray-300 pl-3 border-l-2 border-purple-300 dark:border-purple-600 mb-1">
                                {{ $q }}
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- คำตอบ (แสดงบางส่วน) --}}
                @if($reading->ai_response)
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">คำทำนาย:</div>
                        <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">
                            {{ Str::limit(strip_tags($reading->ai_response), 300) }}
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-12 text-center text-gray-500 dark:text-gray-400">
                <div class="text-4xl mb-3">📜</div>
                <p>ยังไม่มีประวัติการดูดวง</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($readings->hasPages())
        <div class="mt-6">
            {{ $readings->links() }}
        </div>
    @endif
</div>

<script>
function userDetail() {
    return {
        showSendMessage: false,
    }
}
</script>
@endsection
