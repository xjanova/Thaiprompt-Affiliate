@extends('layouts.admin-v3')

@section('title', 'จัดการคำขวัญ')

@section('content')
<div class="max-w-7xl mx-auto" x-data="sloganManager()">
    <!-- Header Section -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6 border border-white/20 dark:border-white/10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    💬 จัดการคำขวัญ
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    จัดการคำขวัญ/คำคมสำหรับแสดงใน Dashboard และส่วนต่างๆ ของระบบ
                </p>
            </div>
            <a href="{{ route('admin.slogans.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 text-white rounded-xl transition-all duration-200 flex items-center gap-2 w-fit">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                เพิ่มคำขวัญใหม่
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">💬</span>
                </div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เปิดใช้งาน</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['active'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900 dark:to-emerald-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ปิดใช้งาน</p>
                    <p class="text-2xl font-bold text-gray-600 dark:text-gray-400 mt-1">{{ $stats['inactive'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">⏸️</span>
                </div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">แสดงรวม</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ number_format($stats['total_displays'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900 dark:to-pink-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">👁️</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-4 mb-6 border border-white/20 dark:border-white/10">
        <form action="{{ route('admin.slogans.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาคำขวัญหรือผู้แต่ง..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div>
                <select name="category"
                        class="w-full md:w-48 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach($categories as $key => $name)
                        <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status"
                        class="w-full md:w-36 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white rounded-lg hover:bg-gray-700 dark:hover:bg-gray-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
                <a href="{{ route('admin.slogans.index') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    <!-- Slogans List -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-white/20 dark:border-white/10">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100/50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            คำขวัญ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            หมวดหมู่
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Priority
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            แสดงแล้ว
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($slogans as $slogan)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-start gap-3">
                                <span class="text-2xl flex-shrink-0">{{ $slogan->icon }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-white line-clamp-2">{{ $slogan->content }}</p>
                                    @if($slogan->author)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">— {{ $slogan->author }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $slogan->category_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $slogan->priority / 2)
                                        <span class="text-yellow-500">★</span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">★</span>
                                    @endif
                                @endfor
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $slogan->priority }}/10</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($slogan->display_count) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <button @click="toggleActive({{ $slogan->id }})"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2"
                                    :class="activeStates[{{ $slogan->id }}] ?? {{ $slogan->is_active ? 'true' : 'false' }} ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                                    x-init="activeStates[{{ $slogan->id }}] = {{ $slogan->is_active ? 'true' : 'false' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-lg transition-transform"
                                      :class="activeStates[{{ $slogan->id }}] ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.slogans.edit', $slogan) }}"
                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.slogans.destroy', $slogan) }}" method="POST" class="inline-block"
                                      onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบคำขวัญนี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <span class="text-6xl mb-4">💬</span>
                                <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">ยังไม่มีคำขวัญในระบบ</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mb-4">เริ่มต้นเพิ่มคำขวัญเพื่อสร้างแรงบันดาลใจ</p>
                                <a href="{{ route('admin.slogans.create') }}"
                                   class="px-4 py-2 bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 text-white rounded-xl transition-all duration-200">
                                    เพิ่มคำขวัญแรก
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($slogans->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $slogans->links() }}
        </div>
        @endif
    </div>

    <!-- Category Stats -->
    @if(!empty($stats['by_category']))
    <div class="mt-6 glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 border border-white/20 dark:border-white/10">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 สถิติตามหมวดหมู่</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($stats['by_category'] as $cat => $count)
            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $count }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $categories[$cat] ?? $cat }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function sloganManager() {
    return {
        activeStates: {},

        async toggleActive(id) {
            try {
                const response = await fetch(`{{ url('admin/slogans') }}/${id}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.activeStates[id] = data.is_active;
                    // แสดงข้อความสำเร็จ
                    this.showNotification(data.message, 'success');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
        },

        showNotification(message, type = 'success') {
            // สร้าง notification element
            const notification = document.createElement('div');
            notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // ลบ notification หลัง 3 วินาที
            setTimeout(() => {
                notification.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    }
}
</script>
@endpush
