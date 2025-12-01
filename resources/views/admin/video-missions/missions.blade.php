@extends('layouts.admin-v3')

@section('title', 'จัดการภารกิจดูคลิป')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                🎬 จัดการภารกิจดูคลิป
            </h1>
            <p class="text-gray-600 dark:text-gray-400">รายการภารกิจทั้งหมด {{ $missions->total() }} รายการ</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.video-missions.index') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
                ← กลับ Dashboard
            </a>
            <a href="{{ route('admin.video-missions.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all shadow-lg">
                + สร้างภารกิจใหม่
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 mb-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ชื่อภารกิจ..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                    <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>กำหนดเวลา</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่</label>
                <select name="category" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-medium transition">
                🔍 ค้นหา
            </button>
        </form>
    </div>

    {{-- Missions Table --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ภารกิจ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รางวัล</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ความถี่</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ทำสำเร็จ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($missions as $mission)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center text-2xl">
                                    {{ $mission->icon ?? '🎬' }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $mission->display_title }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $mission->video_type }} • {{ $mission->required_watch_seconds }}s
                                        @if($mission->is_featured)
                                        <span class="ml-1 px-1.5 py-0.5 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 rounded">แนะนำ</span>
                                        @endif
                                        @if($mission->is_premium)
                                        <span class="ml-1 px-1.5 py-0.5 text-xs bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 rounded">Premium</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="text-sm">
                                @if($mission->reward_money > 0)
                                <span class="text-green-600 dark:text-green-400">฿{{ number_format($mission->reward_money, 2) }}</span>
                                @endif
                                @if($mission->reward_coins > 0)
                                <span class="text-yellow-600 dark:text-yellow-400 ml-1">🪙{{ number_format($mission->reward_coins) }}</span>
                                @endif
                                @if($mission->reward_exp > 0)
                                <span class="text-blue-600 dark:text-blue-400 ml-1">⭐{{ number_format($mission->reward_exp) }}XP</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $mission->frequency === 'once' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                {{ $mission->frequency === 'daily' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' : '' }}
                                {{ $mission->frequency === 'weekly' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300' : '' }}
                                {{ $mission->frequency === 'monthly' ? 'bg-pink-100 text-pink-800 dark:bg-pink-900/50 dark:text-pink-300' : '' }}
                                {{ $mission->frequency === 'unlimited' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : '' }}
                            ">
                                {{ $mission->frequency_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($mission->total_completions) }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <button onclick="toggleMissionStatus({{ $mission->id }})"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $mission->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                    id="toggle-{{ $mission->id }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $mission->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.video-missions.show', $mission) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/50 rounded-lg transition"
                                   title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.video-missions.edit', $mission) }}"
                                   class="p-2 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/50 rounded-lg transition"
                                   title="แก้ไข">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.video-missions.destroy', $mission) }}" method="POST" class="inline"
                                      onsubmit="return confirm('ยืนยันการลบภารกิจนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/50 rounded-lg transition"
                                            title="ลบ">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <div class="text-5xl mb-4">📭</div>
                            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีภารกิจ</p>
                            <a href="{{ route('admin.video-missions.create') }}"
                               class="inline-block mt-4 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                                สร้างภารกิจแรก
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($missions->hasPages())
    <div class="mt-6">
        {{ $missions->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
function toggleMissionStatus(missionId) {
    fetch(`{{ url('admin/video-missions') }}/${missionId}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const toggle = document.getElementById(`toggle-${missionId}`);
            const span = toggle.querySelector('span');
            if (data.is_active) {
                toggle.classList.remove('bg-gray-300', 'dark:bg-gray-600');
                toggle.classList.add('bg-green-500');
                span.classList.remove('translate-x-1');
                span.classList.add('translate-x-6');
            } else {
                toggle.classList.remove('bg-green-500');
                toggle.classList.add('bg-gray-300', 'dark:bg-gray-600');
                span.classList.remove('translate-x-6');
                span.classList.add('translate-x-1');
            }
        }
    });
}
</script>
@endpush
@endsection
