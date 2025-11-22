@extends('layouts.admin-v3')

@section('title', 'Intents - NLP Analysis')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="intentsManager()" x-init="init()">
    {{-- Header --}}
    <div class="mb-8 flex justify-between items-center">
        <div>
            <a href="{{ route('admin.line-bot.keywords.nlp-analysis.index') }}"
               class="inline-flex items-center gap-2 text-[#06C755] hover:text-emerald-600 dark:text-green-400 dark:hover:text-green-300 font-semibold mb-4 transition-colors">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>

            <h1 class="text-4xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-5xl">🎯</span>
                <span>Message Intents</span>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                {{ number_format($intents->total()) }} intents พบทั้งหมด
            </p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 mb-8 border border-white/20 dark:border-gray-700/30">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Intent Type --}}
            <div>
                <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-tag mr-2 text-[#06C755]"></i>Intent Type
                </label>
                <select name="intent"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    @foreach($intentTypes as $type)
                        <option value="{{ $type }}" {{ request('intent') === $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Department --}}
            <div>
                <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-building mr-2 text-blue-500"></i>Department
                </label>
                <select name="department"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>
                            {{ $dept }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Priority --}}
            <div>
                <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-exclamation-circle mr-2 text-orange-500"></i>Priority
                </label>
                <select name="priority"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="LOW" {{ request('priority') === 'LOW' ? 'selected' : '' }}>ต่ำ</option>
                    <option value="NORMAL" {{ request('priority') === 'NORMAL' ? 'selected' : '' }}>ปกติ</option>
                    <option value="HIGH" {{ request('priority') === 'HIGH' ? 'selected' : '' }}>สูง</option>
                    <option value="URGENT" {{ request('priority') === 'URGENT' ? 'selected' : '' }}>เร่งด่วน</option>
                </select>
            </div>

            {{-- Urgent Only --}}
            <div class="flex items-end">
                <label class="flex items-center gap-2 px-4 py-3 bg-red-50 dark:bg-red-900/20 rounded-xl border-2 border-red-200 dark:border-red-800 cursor-pointer hover:bg-red-100 dark:hover:bg-red-900/30 transition-all">
                    <input type="checkbox" name="urgent" value="1" {{ request('urgent') ? 'checked' : '' }}
                           class="w-5 h-5 text-red-600 rounded focus:ring-2 focus:ring-red-500">
                    <span class="text-sm font-bold text-red-900 dark:text-red-200">เฉพาะเร่งด่วน</span>
                </label>
            </div>

            {{-- Submit --}}
            <div class="flex items-end">
                <button type="submit"
                        class="w-full px-6 py-3 bg-gradient-to-r from-[#06C755] to-emerald-600 text-white rounded-xl font-bold shadow-lg hover:shadow-2xl hover:scale-105 transition-all">
                    <i class="fas fa-search mr-2"></i>ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Intents Table --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl overflow-hidden border border-white/20 dark:border-gray-700/30">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Primary Intent
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Department
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Priority
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Confidence
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Secondary
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Created
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($intents as $intent)
                        <tr class="hover:bg-white/80 dark:hover:bg-gray-700/50 transition-all group">
                            <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    {{ $intent->primary_intent }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-full text-xs font-bold">
                                    <i class="fas fa-building"></i>
                                    {{ $intent->suggested_department ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @php
                                    $priorityColors = [
                                        'LOW' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-800 dark:text-green-200', 'icon' => 'fa-check-circle'],
                                        'NORMAL' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-800 dark:text-yellow-200', 'icon' => 'fa-minus-circle'],
                                        'HIGH' => ['bg' => 'bg-orange-100 dark:bg-orange-900/30', 'text' => 'text-orange-800 dark:text-orange-200', 'icon' => 'fa-exclamation-circle'],
                                        'URGENT' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-800 dark:text-red-200', 'icon' => 'fa-exclamation-triangle'],
                                    ];
                                    $colors = $priorityColors[$intent->priority_level] ?? $priorityColors['NORMAL'];
                                @endphp
                                <span class="inline-flex items-center gap-2 px-4 py-2 {{ $colors['bg'] }} {{ $colors['text'] }} rounded-full text-xs font-bold">
                                    <i class="fas {{ $colors['icon'] }}"></i>
                                    {{ $intent->priority_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden max-w-[80px]">
                                        <div class="h-full bg-gradient-to-r from-blue-400 to-cyan-600 rounded-full transition-all duration-500"
                                             style="width: {{ $intent->primary_confidence * 100 }}%"></div>
                                    </div>
                                    <span class="text-sm font-black text-gray-900 dark:text-white min-w-[45px]">
                                        {{ round($intent->primary_confidence * 100) }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($intent->hasSecondaryIntents())
                                    <div class="flex flex-wrap gap-1 max-w-[200px]">
                                        @foreach($intent->secondary_intents as $secondary)
                                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs font-medium">
                                                {{ $secondary }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar-alt text-gray-400"></i>
                                    {{ $intent->created_at->format('d M Y H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <button @click="deleteIntent({{ $intent->id }})"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/80 hover:bg-red-600 text-white rounded-lg font-semibold transition-all hover:scale-105 opacity-0 group-hover:opacity-100">
                                    <i class="fas fa-trash"></i>
                                    ลบ
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                        <i class="fas fa-inbox text-4xl text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">
                                        ไม่พบ intents
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($intents->hasPages())
            <div class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $intents->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/**
 * Intents Manager Component
 *
 * จัดการการแสดงผลและลบ intents
 */
function intentsManager() {
    return {
        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Intents Manager initialized');
        },

        /**
         * ลบ intent
         *
         * @param {number} intentId
         */
        deleteIntent(intentId) {
            if (!confirm('ต้องการลบ intent นี้ใช่หรือไม่?')) return;

            fetch(`/admin/line-bot/keywords/nlp-analysis/intents/${intentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('ลบสำเร็จ');
                    location.reload();
                } else {
                    alert('ข้อผิดพลาด: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด');
            });
        }
    };
}
</script>
@endpush
@endsection
