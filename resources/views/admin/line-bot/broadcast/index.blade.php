@extends('layouts.admin-v3')

@section('title', 'Broadcast Messages')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    filterStatus: 'all',
    filterDate: '',
    searchQuery: ''
}">
    {{-- Header Section พร้อม Animated Background --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-[#06C755] via-emerald-600 to-teal-700 p-8 shadow-2xl">
        {{-- Animated Background Pattern --}}
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-30"></div>

        {{-- Floating Particles Effect --}}
        <div class="absolute inset-0">
            <div class="absolute top-10 left-20 w-2 h-2 bg-white/30 rounded-full animate-ping"></div>
            <div class="absolute top-32 right-32 w-3 h-3 bg-cyan-300/40 rounded-full animate-pulse"></div>
            <div class="absolute bottom-16 left-1/4 w-2 h-2 bg-teal-300/30 rounded-full animate-bounce"></div>
        </div>

        <div class="relative flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-white/25 to-white/10 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/20">
                        <i class="fas fa-bullhorn text-white text-3xl drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg tracking-tight">📢 Broadcast Messages</h1>
                        <p class="text-emerald-100 text-lg font-medium">ส่งข้อความถึงผู้ใช้หลายคนพร้อมกัน</p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-xs glass-fusion backdrop-blur-sm px-3 py-1 rounded-full text-white font-semibold border border-white/30">
                                Mass Messaging • Scheduled Send
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.line-bot.broadcast.create') }}"
               class="px-8 py-3 bg-gradient-to-r from-white to-cyan-50 text-[#06C755] rounded-xl hover:from-cyan-50 hover:to-white transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 font-bold flex items-center gap-2">
                <i class="fas fa-plus-circle"></i>
                <span>สร้าง Broadcast ใหม่</span>
            </a>
        </div>
    </div>

    {{-- Alert Messages พร้อม animation --}}
    @if(session('success'))
        <div class="mb-6 rounded-2xl glass-fusion border-2 border-green-200 dark:border-green-800 p-6 shadow-xl animate-fade-in">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-green-900 dark:text-green-100 mb-1">สำเร็จ!</h4>
                    <p class="text-green-800 dark:text-green-300 text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Broadcasts --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-purple-700 p-6 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i class="fas fa-bullhorn text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-white/80 font-semibold px-3 py-1 bg-white/20 rounded-full">ทั้งหมด</span>
                </div>
                <h3 class="text-4xl font-black text-white mb-1" x-data="{ count: 0 }" x-init="setInterval(() => { if(count < {{ $broadcasts->total() }}) count++ }, 20)">
                    <span x-text="count.toLocaleString()">0</span>
                </h3>
                <p class="text-purple-100 text-sm font-medium">Total Broadcasts</p>
            </div>
        </div>

        {{-- Sent Broadcasts --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 to-emerald-700 p-6 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-white/80 font-semibold px-3 py-1 bg-white/20 rounded-full">สำเร็จ</span>
                </div>
                <h3 class="text-4xl font-black text-white mb-1">
                    {{ $broadcasts->where('status', 'completed')->count() }}
                </h3>
                <p class="text-green-100 text-sm font-medium">Sent Successfully</p>
            </div>
        </div>

        {{-- Scheduled Broadcasts --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 to-yellow-600 p-6 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-white/80 font-semibold px-3 py-1 bg-white/20 rounded-full animate-pulse">รอส่ง</span>
                </div>
                <h3 class="text-4xl font-black text-white mb-1">
                    {{ $broadcasts->where('status', 'scheduled')->count() }}
                </h3>
                <p class="text-orange-100 text-sm font-medium">Scheduled</p>
            </div>
        </div>

        {{-- Draft Broadcasts --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-500 to-gray-700 p-6 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i class="fas fa-file-alt text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-white/80 font-semibold px-3 py-1 bg-white/20 rounded-full">แบบร่าง</span>
                </div>
                <h3 class="text-4xl font-black text-white mb-1">
                    {{ $broadcasts->where('status', 'draft')->count() }}
                </h3>
                <p class="text-gray-100 text-sm font-medium">Drafts</p>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="mb-6 glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-search mr-1"></i> ค้นหา
                </label>
                <input type="text"
                       x-model="searchQuery"
                       placeholder="ค้นหาชื่อ broadcast..."
                       class="w-full px-4 py-3 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all">
            </div>

            {{-- Filter by Status --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-filter mr-1"></i> สถานะ
                </label>
                <select x-model="filterStatus"
                        class="w-full px-4 py-3 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all">
                    <option value="all">ทั้งหมด</option>
                    <option value="draft">แบบร่าง</option>
                    <option value="scheduled">ตั้งเวลาแล้ว</option>
                    <option value="sending">กำลังส่ง</option>
                    <option value="completed">ส่งแล้ว</option>
                    <option value="failed">ล้มเหลว</option>
                </select>
            </div>

            {{-- Filter by Date --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar mr-1"></i> วันที่
                </label>
                <input type="date"
                       x-model="filterDate"
                       class="w-full px-4 py-3 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all">
            </div>
        </div>
    </div>

    {{-- Broadcasts List พร้อม V3 Design --}}
    <div class="space-y-6">
        @forelse($broadcasts as $broadcast)
            <div class="group glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1"
                 x-show="(filterStatus === 'all' || '{{ $broadcast->status }}' === filterStatus) &&
                         (searchQuery === '' || '{{ strtolower($broadcast->name) }}'.includes(searchQuery.toLowerCase()))"
                 x-transition>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br
                                @if($broadcast->status === 'completed') from-green-500 to-emerald-600
                                @elseif($broadcast->status === 'sending') from-blue-500 to-cyan-600 animate-pulse
                                @elseif($broadcast->status === 'scheduled') from-orange-500 to-yellow-600
                                @elseif($broadcast->status === 'failed') from-red-500 to-pink-600
                                @else from-gray-500 to-gray-600
                                @endif
                                flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas
                                    @if($broadcast->status === 'completed') fa-check-circle
                                    @elseif($broadcast->status === 'sending') fa-paper-plane
                                    @elseif($broadcast->status === 'scheduled') fa-clock
                                    @elseif($broadcast->status === 'failed') fa-exclamation-triangle
                                    @else fa-file-alt
                                    @endif
                                    text-white text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ $broadcast->name }}</h3>
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow-sm
                                        @if($broadcast->status === 'completed') bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/40 dark:to-emerald-900/40 text-green-800 dark:text-green-300
                                        @elseif($broadcast->status === 'sending') bg-gradient-to-r from-blue-100 to-cyan-100 dark:from-blue-900/40 dark:to-cyan-900/40 text-blue-800 dark:text-blue-300 animate-pulse
                                        @elseif($broadcast->status === 'scheduled') bg-gradient-to-r from-orange-100 to-yellow-100 dark:from-orange-900/40 dark:to-yellow-900/40 text-orange-800 dark:text-orange-300
                                        @elseif($broadcast->status === 'failed') bg-gradient-to-r from-red-100 to-pink-100 dark:from-red-900/40 dark:to-pink-900/40 text-red-800 dark:text-red-300
                                        @else bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-700 dark:text-gray-300
                                        @endif">
                                        <i class="fas fa-circle text-xs mr-1"></i>{{ strtoupper($broadcast->status) }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    สร้างเมื่อ {{ $broadcast->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 leading-relaxed line-clamp-2">{{ $broadcast->message ?? $broadcast->content ?? 'ไม่มีข้อความ' }}</p>

                        {{-- Stats Badges --}}
                        <div class="flex flex-wrap items-center gap-3 text-sm mb-4">
                            {{-- Recipient Count --}}
                            <span class="flex items-center gap-2 glass-fusion px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20">
                                <i class="fas fa-users text-purple-600 dark:text-purple-400"></i>
                                <span class="font-bold text-purple-900 dark:text-purple-100">{{ number_format($broadcast->total_recipients ?? 0) }}</span>
                                <span class="text-xs text-purple-600 dark:text-purple-400">ผู้รับ</span>
                            </span>

                            {{-- Target Type --}}
                            <span class="flex items-center gap-2 glass-fusion px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700">
                                <i class="fas fa-bullseye text-cyan-500"></i>
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ ucfirst($broadcast->target_type ?? 'all') }}</span>
                            </span>

                            {{-- Schedule --}}
                            @if($broadcast->scheduled_at)
                                <span class="flex items-center gap-2 glass-fusion px-3 py-2 rounded-lg border border-orange-200 dark:border-orange-700 bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20">
                                    <i class="fas fa-clock text-orange-600 dark:text-orange-400"></i>
                                    <span class="text-xs text-orange-900 dark:text-orange-100">{{ $broadcast->scheduled_at->format('d M Y, H:i') }}</span>
                                </span>
                            @endif

                            {{-- Sent Count --}}
                            @if($broadcast->sent_count > 0)
                                <span class="flex items-center gap-2 glass-fusion px-3 py-2 rounded-lg border border-green-200 dark:border-green-700 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                                    <i class="fas fa-paper-plane text-green-600 dark:text-green-400"></i>
                                    <span class="font-bold text-green-900 dark:text-green-100">{{ number_format($broadcast->sent_count) }}</span>
                                    <span class="text-xs text-green-600 dark:text-green-400">ส่งแล้ว</span>
                                </span>
                            @endif
                        </div>

                        {{-- Progress Bar (สำหรับ sending/completed) --}}
                        @if(($broadcast->status === 'sending' || $broadcast->status === 'completed') && ($broadcast->total_recipients ?? 0) > 0)
                            @php
                                $progress = ($broadcast->sent_count / $broadcast->total_recipients) * 100;
                            @endphp
                            <div class="mb-3">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="text-gray-600 dark:text-gray-400 font-semibold">ความคืบหน้า</span>
                                    <span class="text-gray-900 dark:text-white font-bold">{{ number_format($progress, 1) }}%</span>
                                </div>
                                <div class="relative w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                                    <div class="absolute inset-0 bg-gradient-to-r
                                        @if($broadcast->status === 'completed') from-green-500 to-emerald-600
                                        @else from-blue-500 to-cyan-600 animate-pulse
                                        @endif
                                        h-full rounded-full transition-all duration-500 shadow-lg"
                                         style="width: {{ $progress }}%">
                                        <div class="absolute inset-0 bg-white/20 animate-shimmer"></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Quick Actions --}}
                    <div class="flex flex-col gap-2">
                        {{-- View Details --}}
                        <a href="{{ route('admin.line-bot.broadcast.show', $broadcast->id) }}"
                           class="px-4 py-2.5 glass-fusion border-2 border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 hover:border-blue-400 dark:hover:border-blue-500 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg text-sm font-bold flex items-center gap-2 whitespace-nowrap group/btn">
                            <i class="fas fa-eye group-hover/btn:scale-110 transition-transform"></i>
                            <span>ดู</span>
                        </a>

                        {{-- Edit (Draft/Scheduled only) --}}
                        @if($broadcast->status === 'draft' || $broadcast->status === 'scheduled')
                            <a href="{{ route('admin.line-bot.broadcast.edit', $broadcast->id) }}"
                               class="px-4 py-2.5 glass-fusion border-2 border-purple-200 dark:border-purple-700 text-purple-700 dark:text-purple-300 hover:border-purple-400 dark:hover:border-purple-500 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg text-sm font-bold flex items-center gap-2 whitespace-nowrap group/btn">
                                <i class="fas fa-edit group-hover/btn:scale-110 transition-transform"></i>
                                <span>แก้ไข</span>
                            </a>
                        @endif

                        {{-- Send Now (Draft/Scheduled only) --}}
                        @if($broadcast->status === 'draft' || $broadcast->status === 'scheduled')
                            <form method="POST" action="{{ route('admin.line-bot.broadcast.send', $broadcast->id) }}">
                                @csrf
                                <button type="submit" onclick="return confirm('ยืนยันการส่ง broadcast นี้เลยหรือไม่?\n\nจะส่งไปยัง {{ number_format($broadcast->total_recipients ?? 0) }} คน')"
                                        class="w-full px-4 py-2.5 bg-gradient-to-r from-[#06C755] to-emerald-600 hover:from-emerald-600 hover:to-[#06C755] text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 text-sm font-bold flex items-center gap-2 whitespace-nowrap group/btn">
                                    <i class="fas fa-paper-plane group-hover/btn:rotate-12 transition-transform"></i>
                                    <span>ส่งเลย</span>
                                </button>
                            </form>
                        @endif

                        {{-- Duplicate --}}
                        <button type="button"
                                onclick="duplicateBroadcast({{ $broadcast->id }})"
                                class="px-4 py-2.5 glass-fusion border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg text-sm font-bold flex items-center gap-2 whitespace-nowrap group/btn">
                            <i class="fas fa-copy group-hover/btn:scale-110 transition-transform"></i>
                            <span>ทำซ้ำ</span>
                        </button>

                        {{-- Delete (Draft only) --}}
                        @if($broadcast->status === 'draft')
                            <form method="POST" action="{{ route('admin.line-bot.broadcast.destroy', $broadcast->id) }}" onsubmit="return confirm('ลบ broadcast นี้หรือไม่?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full px-4 py-2.5 glass-fusion border-2 border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 hover:border-red-400 dark:hover:border-red-500 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg text-sm font-bold flex items-center gap-2 whitespace-nowrap group/btn">
                                    <i class="fas fa-trash group-hover/btn:scale-110 transition-transform"></i>
                                    <span>ลบ</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-fusion rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-16 text-center">
                <div class="w-32 h-32 rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 flex items-center justify-center mx-auto mb-8 shadow-xl">
                    <i class="fas fa-bullhorn text-emerald-500 dark:text-emerald-400 text-5xl"></i>
                </div>
                <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-3">ยังไม่มี Broadcast</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-8 text-lg">เริ่มสร้าง broadcast message แรกของคุณเลย!</p>
                <a href="{{ route('admin.line-bot.broadcast.create') }}"
                   class="inline-block px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 font-bold text-lg">
                    <i class="fas fa-plus-circle mr-2"></i>สร้าง Broadcast ใหม่
                </a>
            </div>
        @endforelse
    </div>

    @if($broadcasts->hasPages())
        <div class="mt-8">{{ $broadcasts->links() }}</div>
    @endif
</div>

@push('scripts')
<script>
/**
 * Duplicate broadcast function
 *
 * @param {number} broadcastId - ID ของ broadcast ที่จะทำซ้ำ
 */
function duplicateBroadcast(broadcastId) {
    if (!confirm('ต้องการทำซ้ำ broadcast นี้หรือไม่?\n\nจะสร้างสำเนาใหม่เป็นแบบร่าง')) {
        return;
    }

    // TODO: Implement duplicate API call
    // fetch(`/admin/line-bot/broadcast/${broadcastId}/duplicate`, { method: 'POST' })
    //     .then(response => response.json())
    //     .then(data => {
    //         if (data.success) {
    //             window.location.href = `/admin/line-bot/broadcast/${data.broadcast.id}/edit`;
    //         }
    //     });

    alert('ฟีเจอร์นี้กำลังพัฒนา');
}
</script>
@endpush
@endsection
