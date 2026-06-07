@extends('layouts.admin-v3')

@section('title', 'จัดการไพ่ทาโร่ต์')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="tarotCardManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-cards text-white text-xl"></i>
                </div>
                จัดการไพ่ทาโร่ต์
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">จัดการไพ่ทั้งหมด {{ $cards->total() }} ใบ</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.tarot.cards.create') }}"
               class="inline-flex items-center px-5 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl transition transform hover:scale-[1.02] shadow-lg">
                <i class="fas fa-plus mr-2"></i>
                เพิ่มไพ่ใหม่
            </a>
            <a href="{{ route('admin.tarot.index') }}"
               class="inline-flex items-center px-5 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับ
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-6 py-4 rounded-xl mb-6 flex items-center gap-3">
            <i class="fas fa-check-circle text-xl"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Filter & View Toggle --}}
    <div class="glass-fusion rounded-xl shadow p-4 mb-6 border border-white/20 dark:border-white/10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            {{-- Filters --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Type Filter --}}
                <select x-model="filterType" @change="applyFilter()"
                        class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">ทุกประเภท</option>
                    <option value="major_arcana">Major Arcana</option>
                    <option value="minor_arcana">Minor Arcana</option>
                </select>

                {{-- Suit Filter --}}
                <select x-model="filterSuit" @change="applyFilter()"
                        class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">ทุกชุด</option>
                    <option value="wands">Wands (ไม้เท้า)</option>
                    <option value="cups">Cups (ถ้วย)</option>
                    <option value="swords">Swords (ดาบ)</option>
                    <option value="pentacles">Pentacles (เหรียญ)</option>
                </select>

                {{-- Search --}}
                <div class="relative">
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="applyFilter()"
                           placeholder="ค้นหาไพ่..."
                           class="pl-10 pr-4 py-2 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-purple-500 focus:border-purple-500 w-48">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            {{-- View Toggle --}}
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-800 rounded-xl p-1">
                <button @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow text-purple-600 dark:text-purple-400' : 'text-gray-500 dark:text-gray-400'"
                        class="px-4 py-2 rounded-lg transition font-medium">
                    <i class="fas fa-th-large mr-2"></i>Grid
                </button>
                <button @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-gray-700 shadow text-purple-600 dark:text-purple-400' : 'text-gray-500 dark:text-gray-400'"
                        class="px-4 py-2 rounded-lg transition font-medium">
                    <i class="fas fa-list mr-2"></i>Table
                </button>
            </div>
        </div>
    </div>

    {{-- Grid View --}}
    <div x-show="viewMode === 'grid'" x-transition class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        @foreach($cards as $card)
            <div class="group glass-fusion rounded-xl shadow overflow-hidden border border-white/20 dark:border-white/10 hover:shadow-xl transition-all duration-300 hover:scale-[1.03] cursor-pointer"
                 @click="window.location.href='{{ route('admin.tarot.cards.edit', $card->id) }}'">
                {{-- Card Image --}}
                <div class="relative aspect-[9/16] bg-gradient-to-br from-purple-900/20 to-indigo-900/20 overflow-hidden">
                    @if($card->image_url)
                        <img src="{{ $card->image_url }}" alt="{{ $card->name_th }}"
                             class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <i class="fas fa-image text-4xl"></i>
                        </div>
                    @endif

                    {{-- Status Badge --}}
                    <div class="absolute top-2 left-2">
                        @if($card->is_active)
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-500 text-white shadow">
                                <i class="fas fa-check mr-1"></i>Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-500 text-white shadow">
                                <i class="fas fa-pause mr-1"></i>ปิด
                            </span>
                        @endif
                    </div>

                    {{-- Type Badge --}}
                    <div class="absolute top-2 right-2">
                        @if($card->type == 'major_arcana')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-500 text-white shadow">
                                Major
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-500 text-white shadow">
                                {{ ucfirst($card->suit ?? 'Minor') }}
                            </span>
                        @endif
                    </div>

                    {{-- Hover Overlay with Actions --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-3">
                        <div class="flex gap-2" @click.stop>
                            <a href="{{ route('admin.tarot.cards.edit', $card->id) }}"
                               class="flex-1 py-2 bg-purple-600 hover:bg-purple-700 text-white text-center rounded-lg text-sm font-medium transition">
                                <i class="fas fa-edit mr-1"></i>แก้ไข
                            </a>
                            <a href="{{ route('admin.tarot.interpretations.edit', $card->id) }}"
                               class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-center rounded-lg text-sm font-medium transition">
                                <i class="fas fa-book mr-1"></i>คำทำนาย
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Card Info --}}
                <div class="p-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm truncate">{{ $card->name_th }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $card->name_en }}</p>
                    @if($card->number !== null)
                        <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                            #{{ $card->number }}
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Table View --}}
    <div x-show="viewMode === 'table'" x-transition class="glass-fusion rounded-xl shadow overflow-hidden border border-white/20 dark:border-white/10">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">รูป</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ชื่อ</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ชุด</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($cards as $card)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            <td class="px-4 py-3">
                                <div class="w-9 h-16 rounded-lg overflow-hidden bg-gradient-to-br from-purple-900/20 to-indigo-900/20 shadow">
                                    @if($card->image_url)
                                        <img src="{{ $card->image_url }}" alt="{{ $card->name_th }}" class="w-full h-full object-contain">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $card->name_th }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $card->name_en }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($card->type == 'major_arcana')
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300">
                                        Major Arcana
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                        Minor Arcana
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                @if($card->suit)
                                    {{ ucfirst($card->suit) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($card->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                        <i class="fas fa-check-circle mr-1"></i>Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-pause-circle mr-1"></i>ปิด
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.tarot.cards.edit', $card->id) }}"
                                       class="inline-flex items-center px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition"
                                       title="แก้ไขไพ่">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('admin.tarot.interpretations.edit', $card->id) }}"
                                       class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition"
                                       title="แก้ไขคำทำนาย">
                                        <i class="fas fa-book"></i>
                                    </a>
                                    <form action="{{ route('admin.tarot.cards.destroy', $card->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ยืนยันการลบไพ่ {{ $card->name_th }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition"
                                                title="ลบไพ่">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($cards->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $cards->links() }}
            </div>
        @endif
    </div>

    {{-- Grid View Pagination --}}
    <div x-show="viewMode === 'grid'" class="mt-6">
        {{ $cards->links() }}
    </div>

    {{-- Empty State --}}
    @if($cards->isEmpty())
        <div class="glass-fusion rounded-xl shadow p-12 text-center border border-white/20 dark:border-white/10">
            <div class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-cards text-purple-600 dark:text-purple-400 text-3xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มีไพ่</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">เริ่มต้นเพิ่มไพ่ทาโร่ต์ใบแรกของคุณ</p>
            <a href="{{ route('admin.tarot.cards.create') }}"
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl transition shadow-lg">
                <i class="fas fa-plus mr-2"></i>
                เพิ่มไพ่ใบแรก
            </a>
        </div>
    @endif
</div>

@push('scripts')
<script>
function tarotCardManager() {
    return {
        viewMode: localStorage.getItem('tarotViewMode') || 'grid',
        filterType: new URLSearchParams(window.location.search).get('type') || '',
        filterSuit: new URLSearchParams(window.location.search).get('suit') || '',
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',

        init() {
            this.$watch('viewMode', (value) => {
                localStorage.setItem('tarotViewMode', value);
            });
        },

        applyFilter() {
            const params = new URLSearchParams();
            if (this.filterType) params.set('type', this.filterType);
            if (this.filterSuit) params.set('suit', this.filterSuit);
            if (this.searchQuery) params.set('search', this.searchQuery);

            const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.location.href = url;
        }
    }
}
</script>
@endpush
@endsection
