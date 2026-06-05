@extends('layouts.admin-v3')

@section('title', 'ประวัติการตรวจสลิป (SlipOK)')

@php
    // 🎨 สีป้ายตามผลการตรวจ
    $badge = function ($decision) {
        return match (true) {
            $decision === 'approve' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            $decision === 'duplicate', $decision === 'no_bill_duplicate' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
            in_array($decision, ['reject_receiver', 'reject_amount', 'stale', 'error', 'no_bill_wrong_receiver']) => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
            in_array($decision, ['no_qr', 'not_slip', 'no_bill_unverified', 'no_slip_found']) => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            str_starts_with((string) $decision, 'no_bill') => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    };
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🧾 ประวัติการตรวจสลิป (SlipOK)</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ทุกการตรวจสลิป — ส่งไปเช็คจริงไหม / สลิปซ้ำไหม / บิลไหน</p>
        </div>
        <a href="{{ route('admin.fortune.celtic-cross.index') }}"
           class="text-sm px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-center">
            ← Celtic Cross
        </a>
    </div>

    {{-- 📊 (2026-06-05) โควตา SlipOK คงเหลือ — เห็นชัดว่าเหลือยิงตรวจได้กี่ครั้ง (กันโดนสลิปปลอมดูดหมด) --}}
    @php
        $q = $slipokQuota ?? [];
        $qOk = (bool) ($q['success'] ?? false);
        $qLeft = (int) ($q['quota'] ?? 0);
        // โทนสีตามจำนวนคงเหลือ: < 50 = แดง (ใกล้หมด) / < 200 = เหลือง / ปกติ = เขียว / เช็คไม่ได้ = เทา
        $qTone = ! $qOk ? 'gray' : ($qLeft < 50 ? 'red' : ($qLeft < 200 ? 'amber' : 'green'));
        $qToneMap = [
            'green' => 'border-green-200 dark:border-green-800 from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20',
            'amber' => 'border-amber-200 dark:border-amber-800 from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20',
            'red'   => 'border-red-200 dark:border-red-800 from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20',
            'gray'  => 'border-gray-200 dark:border-gray-700 from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800',
        ];
        $qNumColor = [
            'green' => 'text-green-600 dark:text-green-400',
            'amber' => 'text-amber-600 dark:text-amber-400',
            'red'   => 'text-red-600 dark:text-red-400',
            'gray'  => 'text-gray-500 dark:text-gray-400',
        ];
    @endphp
    <div class="bg-gradient-to-r {{ $qToneMap[$qTone] }} border rounded-xl shadow-sm p-4 mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="text-3xl">📊</div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">โควตา SlipOK คงเหลือ (ตรวจสลิปได้อีก)</div>
                @if ($qOk)
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold {{ $qNumColor[$qTone] }}">{{ number_format($qLeft) }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">ครั้ง</span>
                        @if ($qTone === 'red')
                            <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">⚠️ ใกล้หมด</span>
                        @endif
                    </div>
                @else
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        เช็คโควตาไม่ได้ — {{ $q['message'] ?? 'เชื่อมต่อ SlipOK ไม่สำเร็จ' }}
                    </div>
                @endif
            </div>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 text-left sm:text-right space-y-0.5">
            @if ($qOk)
                @if (! empty($q['overQuota']))
                    <div>เกินโควตา (over): <span class="font-medium text-gray-700 dark:text-gray-300">{{ number_format((int) $q['overQuota']) }}</span></div>
                @endif
                @if (! empty($q['specialQuota']))
                    <div>โควตาพิเศษ: <span class="font-medium text-gray-700 dark:text-gray-300">{{ number_format((int) $q['specialQuota']) }}</span></div>
                @endif
                @if (! empty($q['endDate']))
                    <div>หมดรอบ: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $q['endDate'] }}</span></div>
                @endif
            @else
                <a href="{{ route('admin.fortune.settings.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">→ ตั้งค่า SlipOK</a>
            @endif
            <div class="text-[10px] text-gray-400 dark:text-gray-500">อัปเดตทุก 5 นาที</div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        @foreach ([
            ['ทั้งหมด', $stats['total'], 'text-gray-900 dark:text-white'],
            ['วันนี้', $stats['today'], 'text-indigo-600 dark:text-indigo-400'],
            ['ส่ง SlipOK จริง', $stats['sent_slipok'], 'text-purple-600 dark:text-purple-400'],
            ['สลิปซ้ำ', $stats['duplicates'], 'text-red-600 dark:text-red-400'],
            ['ผ่าน (approve)', $stats['approved'], 'text-green-600 dark:text-green-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="text-2xl font-bold {{ $color }} mt-1">{{ number_format($value) }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหา user / transRef / ชื่อ / บิล"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm">

            <select name="decision" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm">
                <option value="">— ผลทั้งหมด —</option>
                @foreach ($decisions as $d)
                    <option value="{{ $d }}" @selected($filters['decision'] === $d)>{{ $d }}</option>
                @endforeach
            </select>

            <select name="platform" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm">
                <option value="">— ทุก platform —</option>
                <option value="line" @selected($filters['platform'] === 'line')>LINE</option>
                <option value="facebook" @selected($filters['platform'] === 'facebook')>Facebook</option>
            </select>

            <div class="flex items-center gap-3">
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                       class="w-full px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-xs">
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                       class="w-full px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-xs">
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4 mt-3">
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="dup_only" value="1" @checked($filters['dup_only']) class="rounded text-red-600">
                เฉพาะสลิปซ้ำ
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="sent_only" value="1" @checked($filters['sent_only']) class="rounded text-purple-600">
                เฉพาะที่ส่ง SlipOK จริง
            </label>
            <button type="submit" class="ml-auto px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">
                🔍 ค้นหา
            </button>
            <a href="{{ route('admin.fortune.slip-logs.index') }}" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:underline">ล้าง</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                        <th class="px-3 py-3">เวลา</th>
                        <th class="px-3 py-3">บิล</th>
                        <th class="px-3 py-3">ลูกค้า</th>
                        <th class="px-3 py-3 text-center">ส่ง SlipOK</th>
                        <th class="px-3 py-3">ผล</th>
                        <th class="px-3 py-3 text-center">ซ้ำ</th>
                        <th class="px-3 py-3 text-right">ยอด</th>
                        <th class="px-3 py-3">transRef</th>
                        <th class="px-3 py-3">บัญชีปลายทาง</th>
                        <th class="px-3 py-3">ผู้โอน</th>
                        <th class="px-3 py-3 text-center">รูปสลิป<br><span class="text-[9px] normal-case text-gray-400">ที่ส่งไปตรวจ</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                            <td class="px-3 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400 text-xs">
                                {{ $log->created_at?->format('d/m H:i:s') }}
                                <div class="text-[10px] text-gray-400">{{ $log->context }}</div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if ($log->fortune_reading_id)
                                    <a href="{{ route('admin.fortune.celtic-cross.show', $log->fortune_reading_id) }}"
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline">#{{ $log->fortune_reading_id }}</a>
                                    <div class="text-[10px] text-gray-400">{{ $log->reading?->bill_reference }}</div>
                                @else
                                    <span class="text-gray-400 text-xs">ไม่มีบิล</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="text-gray-700 dark:text-gray-300 text-xs">{{ $log->reading?->facebook_user_name ?? '—' }}</div>
                                <div class="text-[10px] text-gray-400 uppercase">{{ $log->platform }}</div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($log->sent_to_slipok)
                                    <span class="text-green-600 dark:text-green-400" title="ส่งไป SlipOK API จริง">✅</span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600" title="ไม่ได้ส่ง (pre-check ตัด / ไม่มีสลิป)">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $badge($log->decision) }}">
                                    {{ $log->decision }}
                                </span>
                                @if ($log->to_our_account === true)
                                    <span class="text-[10px] text-green-600 dark:text-green-400 block">เข้าบัญชีร้าน</span>
                                @elseif ($log->to_our_account === false)
                                    <span class="text-[10px] text-orange-500 block">บัญชีอื่น</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($log->is_duplicate)
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">ซ้ำ</span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap text-gray-700 dark:text-gray-300">
                                {{ $log->amount ? '฿'.number_format((float) $log->amount, 2) : '—' }}
                            </td>
                            <td class="px-3 py-3 font-mono text-[10px] text-gray-500 dark:text-gray-400 break-all max-w-[140px]">
                                {{ $log->trans_ref ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $log->receiver_account ?? '—' }}</td>
                            <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $log->sender_name ?? '—' }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($log->slip_image_path)
                                    {{-- ⚠️ (2026-06-05) onerror: ถ้าไฟล์ถูกลบ (404 — purge 30 วัน / โดน deploy เก่าลบ)
                                         แทนที่ลิงก์ด้วยป้าย "🚫 ลบแล้ว" แทนไอคอนรูปแตก ให้แอดมินเข้าใจว่าไฟล์หาย ไม่ใช่ระบบพัง --}}
                                    <a href="{{ route('admin.fortune.slip-logs.image', $log->id) }}" target="_blank" rel="noopener"
                                       title="เปิดรูปสลิปที่ส่งไปตรวจ (ดูว่า QR ชัดไหม)">
                                        <img src="{{ route('admin.fortune.slip-logs.image', $log->id) }}" loading="lazy" alt="slip"
                                             onerror="this.onerror=null;var s=document.createElement('span');s.className='text-[10px] text-gray-300 dark:text-gray-600';s.title='ไฟล์รูปถูกลบแล้ว (เกิน 30 วัน หรือถูก deploy ลบก่อนแพตช์ 2026-06-05)';s.textContent='🚫 ลบแล้ว';this.closest('a').replaceWith(s);"
                                             class="h-12 w-12 object-cover rounded border border-gray-200 dark:border-gray-600 inline-block hover:scale-[2.5] hover:shadow-xl transition origin-center relative z-10">
                                    </a>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600" title="ไม่มีรูป (ไม่ได้เก็บ / purge หลัง 30 วัน)">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-12 text-center text-gray-400 dark:text-gray-500">
                                ยังไม่มีประวัติการตรวจสลิป
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection
