{{-- Emergency Recovery — Celtic Cross --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                    🚨 Emergency Recovery
                </h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    กู้บิล Celtic Cross ที่ลูกค้าจ่ายแล้วแต่บอทเงียบ — ใส่เลขบิลแล้ว re-push ทันที
                </p>
            </div>
            <a href="{{ route('admin.fortune.celtic-cross.index') }}"
               class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition">
                ← กลับไป Celtic Cross
            </a>
        </div>
    </div>

    {{-- Flash --}}
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

    {{-- Stuck count badge --}}
    <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="text-3xl">⏳</div>
            <div>
                <div class="text-sm text-amber-800 dark:text-amber-200">
                    ตอนนี้มี <span class="font-bold text-2xl">{{ $stuckCount }}</span> reading ค้าง (paid + ยังไม่ได้ใช้สิทธิ์ + ค้าง ≥ 5 นาที)
                </div>
                <div class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                    Auto-scanner รันทุก 5 นาที — ถ้าเร่งด่วนใช้ปุ่ม "กู้ทั้งหมด" ด้านล่าง
                </div>
            </div>
        </div>
    </div>

    {{-- Form: Mode A — by bills --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
            📋 โหมด A — กู้ตามเลขบิล
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            วางเลขบิล (เช่น <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-900 rounded text-xs">FTU-260505-J1439</code>) หรือ reading ID — แยกบรรทัด/comma/เว้นวรรค ก็ได้
        </p>

        <form method="POST" action="{{ route('admin.fortune.celtic-cross.emergency-recover.action') }}">
            @csrf
            <input type="hidden" name="mode" value="bills">

            <textarea name="bills"
                      rows="4"
                      placeholder="FTU-260505-J1439&#10;FTU-260505-K2345&#10;หรือ reading ID เช่น 12345"
                      class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:outline-none"
                      required></textarea>

            <div class="mt-3">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                    ข้อความหัวเรื่อง (เปล่า = ใช้ข้อความ default "ขออภัยที่ทำให้รอนะคะ...")
                </label>
                <input type="text"
                       name="notify_message"
                       placeholder="(optional) เช่น: ขอบคุณที่อดทนรอนะคะ ระบบกลับมาแล้ว 🙏"
                       class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:outline-none">
            </div>

            <button type="submit"
                    onclick="return confirm('🚨 ยืนยันกู้บิลตามรายการ? ลูกค้าจะได้รับ message ใหม่ทันที')"
                    class="mt-4 w-full md:w-auto px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition shadow-sm">
                🚨 กู้บิลตามรายการ
            </button>
        </form>
    </div>

    {{-- Form: Mode B — auto scan --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
            🔄 โหมด B — กู้ทั้งหมด (auto-scan)
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            สแกน Celtic readings ที่ <strong>paid + questions_used=0 + ค้าง ≥ N นาที</strong> แล้วกู้ทั้งหมด
        </p>

        <form method="POST" action="{{ route('admin.fortune.celtic-cross.emergency-recover.action') }}">
            @csrf
            <input type="hidden" name="mode" value="auto">

            <div class="flex items-center gap-3 mb-4">
                <label class="text-sm text-gray-700 dark:text-gray-300">เกณฑ์เวลาที่ค้าง:</label>
                <input type="number"
                       name="minutes"
                       value="5"
                       min="1"
                       max="1440"
                       class="w-24 px-3 py-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100">
                <span class="text-sm text-gray-600 dark:text-gray-400">นาที</span>
            </div>

            <div class="mb-4">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                    ข้อความหัวเรื่อง (optional)
                </label>
                <input type="text"
                       name="notify_message"
                       placeholder="(optional)"
                       class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>

            <button type="submit"
                    onclick="return confirm('🔄 ยืนยันสแกน + กู้ทั้งหมด? ลูกค้าทุกคนที่ค้างจะได้รับ message ใหม่')"
                    class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition shadow-sm">
                🔄 สแกน + กู้ทั้งหมด
            </button>
        </form>
    </div>

    {{-- Results --}}
    @if(! empty($results))
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    📊 ผลการกู้
                </h2>
                <div class="flex items-center gap-2 text-sm">
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full">
                        ✅ สำเร็จ {{ $okCount ?? 0 }}
                    </span>
                    @if(($failCount ?? 0) > 0)
                        <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full">
                            ❌ ล้มเหลว {{ $failCount }}
                        </span>
                    @endif
                </div>
            </div>

            @if(! empty($notFound))
                <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg text-sm text-yellow-800 dark:text-yellow-200">
                    ⚠️ บิลที่ไม่พบ: <code>{{ implode(', ', $notFound) }}</code>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">ID</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">บิล</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">ลูกค้า</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Platform</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Status (ก่อน → หลัง)</th>
                            <th class="px-3 py-2 text-center font-semibold text-gray-700 dark:text-gray-300">เปิดไปแล้ว</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">ผล</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($results as $row)
                            <tr class="{{ $row['ok'] ? '' : 'bg-red-50 dark:bg-red-900/10' }}">
                                <td class="px-3 py-2 font-mono text-gray-900 dark:text-gray-100">
                                    <a href="{{ route('admin.fortune.celtic-cross.show', $row['id']) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        #{{ $row['id'] }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row['bill'] }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $row['user'] }}</td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $row['platform'] }}</td>
                                <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <code>{{ $row['status_before'] }}</code>
                                    @if(! empty($row['status_after']) && $row['status_after'] !== $row['status_before'])
                                        <span class="text-gray-400">→</span>
                                        <code class="text-green-700 dark:text-green-400">{{ $row['status_after'] }}</code>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center text-gray-600 dark:text-gray-400">{{ $row['picked'] }}/10</td>
                                <td class="px-3 py-2 {{ $row['ok'] ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                    {{ $row['msg'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(! empty($summary))
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ $summary }}</div>
            @endif
        </div>
    @endif

    {{-- Help --}}
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl">
        <h3 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">💡 การกู้ทำอะไร</h3>
        <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-1 list-disc list-inside">
            <li>เคส <code>celtic_pending_payment</code> + paid → transition → CELTIC_PICKING + ส่ง prompt ใบ 1</li>
            <li>เคส <code>new</code> + paid (slip transition fail) → 🚨 force-promote → CELTIC_PICKING + ส่ง prompt ใบ 1</li>
            <li>เคส <code>celtic_picking</code> ค้างกลางทาง → re-push prompt ใบที่กำลังจะเปิด (ไม่ reset ไพ่ที่เปิดแล้ว)</li>
            <li>เคส <code>celtic_awaiting_question</code> → re-push prompt "ถามคำถามได้เลย"</li>
        </ul>
        <h3 class="font-semibold text-blue-900 dark:text-blue-200 mt-3 mb-2">⚙️ CLI fallback</h3>
        <code class="block px-3 py-2 bg-blue-100 dark:bg-blue-900/40 rounded text-xs text-blue-900 dark:text-blue-200">
            php artisan fortune:celtic-recover FTU-260505-J1439<br>
            php artisan fortune:celtic-recover --auto
        </code>
    </div>

</div>
@endsection
