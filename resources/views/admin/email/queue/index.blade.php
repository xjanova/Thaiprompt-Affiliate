@extends('layouts.admin-v3')

@section('title', 'คิวการส่งอีเมล')

@section('content')
{{-- ระบบจัดการคิวการส่งอีเมล --}}
<div class="space-y-6" x-data="queueManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>⏳</span>
                <span>คิวการส่งอีเมล</span>
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                ติดตามและจัดการคิวการส่งอีเมลในระบบ
            </p>
        </div>
        <button @click="clearFailedQueue"
                class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            ล้างคิวที่ล้มเหลว
        </button>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-yellow-200/50 dark:border-yellow-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">รอส่ง</p>
                    <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['pending']) }}</p>
                </div>
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <span class="text-xl">⏳</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-purple-200/50 dark:border-purple-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">กำลังส่ง</p>
                    <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['sending']) }}</p>
                </div>
                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <span class="text-xl">📤</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-green-200/50 dark:border-green-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">ส่งแล้ว</p>
                    <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['sent']) }}</p>
                </div>
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <span class="text-xl">✅</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-red-200/50 dark:border-red-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">ล้มเหลว</p>
                    <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['failed']) }}</p>
                </div>
                <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                    <span class="text-xl">❌</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-orange-200/50 dark:border-orange-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">ตีกลับ</p>
                    <p class="mt-1 text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($stats['bounced']) }}</p>
                </div>
                <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                    <span class="text-xl">↩️</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50">
        <form action="{{ route('admin.email.queue.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            {{-- Search --}}
            <div class="flex-1">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="🔍 ค้นหาอีเมล..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
            </div>

            {{-- Status Filter --}}
            <select name="status"
                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                <option value="">ทุกสถานะ</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอส่ง</option>
                <option value="sending" {{ request('status') === 'sending' ? 'selected' : '' }}>กำลังส่ง</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>ส่งแล้ว</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>ส่งถึง</option>
                <option value="opened" {{ request('status') === 'opened' ? 'selected' : '' }}>เปิดอ่าน</option>
                <option value="clicked" {{ request('status') === 'clicked' ? 'selected' : '' }}>คลิก</option>
                <option value="bounced" {{ request('status') === 'bounced' ? 'selected' : '' }}>ตีกลับ</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
            </select>

            {{-- Campaign Filter --}}
            <select name="campaign_id"
                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                <option value="">ทุกแคมเปญ</option>
                @foreach($activeCampaigns as $campaign)
                    <option value="{{ $campaign->id }}" {{ request('campaign_id') == $campaign->id ? 'selected' : '' }}>
                        {{ $campaign->name }}
                    </option>
                @endforeach
            </select>

            {{-- Buttons --}}
            <button type="submit"
                    class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors">
                ค้นหา
            </button>
            <a href="{{ route('admin.email.queue.index') }}"
               class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors">
                รีเซ็ต
            </a>
        </form>
    </div>

    {{-- Queue Table --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl shadow-xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50/80 dark:bg-gray-900/80">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            อีเมล
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            แคมเปญ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            เวลา
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            การเปิดอ่าน
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($queue as $recipient)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            {{-- Email --}}
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $recipient->email }}
                                    </div>
                                    @if($recipient->user)
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $recipient->user->name }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Campaign --}}
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.email.campaigns.show', $recipient->campaign) }}"
                                   class="text-sm text-purple-600 dark:text-purple-400 hover:underline">
                                    {{ $recipient->campaign->name }}
                                </a>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4">
                                @php
                                    $statusConfig = [
                                        'pending' => ['label' => 'รอส่ง', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
                                        'sending' => ['label' => 'กำลังส่ง', 'class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'],
                                        'sent' => ['label' => 'ส่งแล้ว', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
                                        'delivered' => ['label' => 'ส่งถึง', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
                                        'opened' => ['label' => 'เปิดอ่าน', 'class' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200'],
                                        'clicked' => ['label' => 'คลิก', 'class' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200'],
                                        'bounced' => ['label' => 'ตีกลับ', 'class' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'],
                                        'failed' => ['label' => 'ล้มเหลว', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
                                    ];
                                    $config = $statusConfig[$recipient->status] ?? ['label' => $recipient->status, 'class' => 'bg-gray-100 text-gray-800'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $config['class'] }}">
                                    {{ $config['label'] }}
                                </span>
                            </td>

                            {{-- Time --}}
                            <td class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                @if($recipient->sent_at)
                                    <div>{{ $recipient->sent_at->format('d/m/Y H:i') }}</div>
                                @elseif($recipient->created_at)
                                    <div class="text-xs text-gray-500 dark:text-gray-500">
                                        สร้างเมื่อ {{ $recipient->created_at->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>

                            {{-- Open/Click Stats --}}
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    @if($recipient->opened_at)
                                        <span class="text-green-600 dark:text-green-400" title="เปิดอ่าน {{ $recipient->open_count }} ครั้ง">
                                            👁️ {{ $recipient->open_count }}
                                        </span>
                                    @endif
                                    @if($recipient->clicked_at)
                                        <span class="text-blue-600 dark:text-blue-400" title="คลิก {{ $recipient->click_count }} ครั้ง">
                                            🖱️ {{ $recipient->click_count }}
                                        </span>
                                    @endif
                                    @if(!$recipient->opened_at && !$recipient->clicked_at)
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($recipient->isFailed())
                                        <form action="{{ route('admin.email.queue.retry', $recipient) }}"
                                              method="POST"
                                              class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="p-2 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 rounded-lg transition-all"
                                                    title="ลองส่งใหม่">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.email.queue.show', $recipient) }}"
                                       class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-all"
                                       title="ดูรายละเอียด">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-6xl mb-4">📭</span>
                                    <p class="text-gray-600 dark:text-gray-400 text-lg">ไม่มีคิวการส่ง</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($queue->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $queue->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/**
 * Queue Manager Component
 * จัดการคิวการส่งอีเมล
 */
function queueManager() {
    return {
        // Methods
        init() {
            console.log('Queue Manager initialized');
        },

        /**
         * ล้างคิวที่ล้มเหลว
         */
        clearFailedQueue() {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะล้างคิวที่ล้มเหลวทั้งหมด?')) {
                return;
            }

            // ส่ง request ไปลบคิวที่ล้มเหลว
            fetch('{{ route('admin.email.queue.clear-failed') }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                alert('ล้างคิวที่ล้มเหลวเรียบร้อยแล้ว!');
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            });
        }
    }
}
</script>
@endpush
@endsection
