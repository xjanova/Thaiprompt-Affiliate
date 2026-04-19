{{-- ดวงรายวันทั้งหมด --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">📊 ดวงรายวันทั้งหมด</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">รายการดวงรายวันที่สร้างจาก AI</p>
        </div>
        <a href="{{ route('admin.fortune.horoscope-public.zodiac.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← จัดการราศี
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- ตัวกรอง --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
            <div>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <select name="type"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">-- ทุกประเภท --</option>
                    <option value="zodiac" {{ request('type') === 'zodiac' ? 'selected' : '' }}>⭐ 12 ราศี</option>
                    <option value="birth_day" {{ request('type') === 'birth_day' ? 'selected' : '' }}>📅 7 วันเกิด</option>
                </select>
            </div>
            <div>
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="generated" {{ request('status') === 'generated' ? 'selected' : '' }}>✅ สร้างแล้ว</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ รอดำเนินการ</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>❌ ล้มเหลว</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">
                🔍 กรอง
            </button>
            <a href="{{ route('admin.fortune.horoscope-public.zodiac.predictions') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm transition">
                ล้าง
            </a>
        </form>
    </div>

    {{-- ตาราง --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ราศี/วันเกิด</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คะแนนรวม</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Views</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">AI</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
                    @endphp
                    @forelse($predictions as $pred)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($pred->target_date)->locale('th')->translatedFormat('j M Y') }}
                            </td>
                            <td class="px-4 py-4">
                                @if($pred->prediction_type === 'zodiac' && $pred->zodiacSign)
                                    <div class="flex items-center gap-2">
                                        <span>{{ $pred->zodiacSign->symbol_emoji }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $pred->zodiacSign->name_th }}</span>
                                    </div>
                                @elseif($pred->prediction_type === 'birth_day')
                                    <span class="text-sm text-gray-900 dark:text-white">📅 วัน{{ $dayNames[$pred->birth_day] ?? $pred->birth_day }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $pred->prediction_type === 'zodiac' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-300' }}">
                                    {{ $pred->prediction_type === 'zodiac' ? '⭐ ราศี' : '📅 วันเกิด' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <div class="w-2 h-2 rounded-full {{ $i <= ($pred->overall_score ?? 0) ? 'bg-indigo-500' : 'bg-gray-200 dark:bg-gray-600' }}"></div>
                                    @endfor
                                    <span class="ml-1 text-xs text-gray-500">{{ $pred->overall_score ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ number_format($pred->view_count) }}
                            </td>
                            <td class="px-4 py-4 text-center text-xs text-gray-500 dark:text-gray-400">
                                {{ $pred->ai_provider_used ?? '—' }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($pred->status === 'generated')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">✅</span>
                                @elseif($pred->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">⏳</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">❌</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <form action="{{ route('admin.fortune.horoscope-public.zodiac.predictions.destroy', $pred) }}" method="POST" class="inline"
                                      onsubmit="return confirm('ลบ prediction นี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-900 text-red-700 dark:text-red-300 rounded-lg text-xs transition">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ยังไม่มี prediction
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($predictions->hasPages())
        <div class="mt-6">{{ $predictions->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
