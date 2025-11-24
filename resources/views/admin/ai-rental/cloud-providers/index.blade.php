@extends('layouts.admin-v3')

@section('title', 'Cloud GPU Providers Management')

@section('content')
<div class="space-y-6" x-data="providersManager()">
    {{-- Page Header --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-purple-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-cloud text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                        Cloud GPU Providers
                    </h1>
                    <p class="text-white/80 mt-1">
                        จัดการ Cloud Providers ทั้งหมด
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.ai-rental.cloud-providers.create') }}"
               class="px-6 py-3 bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold rounded-xl hover:scale-105 transition shadow-lg">
                <i class="fas fa-plus"></i> เพิ่ม Provider ใหม่
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-database text-2xl text-blue-400"></i>
                </div>
                <div>
                    <div class="text-white/60 text-sm">Total Providers</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-green-400"></i>
                </div>
                <div>
                    <div class="text-white/60 text-sm">Active</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-gift text-2xl text-purple-400"></i>
                </div>
                <div>
                    <div class="text-white/60 text-sm">Free Tier</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['free'] }}</div>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-2xl text-orange-400"></i>
                </div>
                <div>
                    <div class="text-white/60 text-sm">Paid</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['paid'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-search"></i> ค้นหา
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ค้นหาชื่อ provider..."
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400">
            </div>

            {{-- Tier Filter --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-filter"></i> Tier
                </label>
                <select name="tier"
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <option value="">ทั้งหมด</option>
                    <option value="free" {{ request('tier') === 'free' ? 'selected' : '' }}>Free</option>
                    <option value="paid" {{ request('tier') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>

            {{-- Sort --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    <i class="fas fa-sort"></i> เรียงตาม
                </label>
                <select name="sort"
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <option value="order" {{ request('sort') === 'order' ? 'selected' : '' }}>ลำดับ</option>
                    <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>ชื่อ</option>
                    <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>ความนิยม</option>
                    <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>คะแนน</option>
                </select>
            </div>

            {{-- Submit --}}
            <div class="md:col-span-4 flex gap-2">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl hover:scale-105 transition">
                    <i class="fas fa-filter"></i> กรอง
                </button>
                <a href="{{ route('admin.ai-rental.cloud-providers.index') }}"
                   class="px-6 py-3 glass-neu text-white font-bold rounded-xl hover:bg-white/20 transition">
                    <i class="fas fa-redo"></i> รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    {{-- Providers Table --}}
    <div class="glass-fusion rounded-2xl border border-white/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-white/5 border-b border-white/10">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-bold text-white">
                            <i class="fas fa-grip-vertical text-white/50"></i>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-white">Provider</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white">Tier</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white">Price Range</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white">Features</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white">Rating</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-white">Status</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-white">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($providers as $provider)
                    <tr class="hover:bg-white/5 transition">
                        {{-- Drag Handle --}}
                        <td class="px-6 py-4">
                            <div class="cursor-move text-white/50 hover:text-white">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                        </td>

                        {{-- Provider Info --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-cyan-500/20 to-purple-500/20 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-server text-xl text-cyan-400"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-white flex items-center gap-2">
                                        {{ $provider->name }}
                                        @if($provider->is_recommended)
                                        <span class="px-2 py-0.5 bg-yellow-500/20 text-yellow-400 text-xs rounded">
                                            ⭐ แนะนำ
                                        </span>
                                        @endif
                                    </div>
                                    <div class="text-white/60 text-sm">{{ $provider->slug }}</div>
                                    @if($provider->description)
                                    <div class="text-white/50 text-xs mt-1">{{ Str::limit($provider->description, 50) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Tier --}}
                        <td class="px-6 py-4 text-center">
                            @if($provider->tier === 'free')
                                <span class="px-3 py-1 bg-green-500/20 text-green-300 text-sm font-bold rounded-full border border-green-500/30">
                                    <i class="fas fa-gift"></i> FREE
                                </span>
                            @elseif($provider->tier === 'paid')
                                <span class="px-3 py-1 bg-orange-500/20 text-orange-300 text-sm font-bold rounded-full border border-orange-500/30">
                                    <i class="fas fa-dollar-sign"></i> PAID
                                </span>
                            @else
                                <span class="px-3 py-1 bg-purple-500/20 text-purple-300 text-sm font-bold rounded-full border border-purple-500/30">
                                    <i class="fas fa-star"></i> BOTH
                                </span>
                            @endif
                        </td>

                        {{-- Price Range --}}
                        <td class="px-6 py-4 text-center">
                            @if($provider->isFree())
                                <div class="text-green-400 font-bold">FREE</div>
                                @if($provider->free_tier_hours_per_month)
                                <div class="text-white/60 text-xs">{{ $provider->free_tier_hours_per_month }} hrs/mo</div>
                                @endif
                            @elseif($provider->min_price_per_hour)
                                <div class="text-white font-bold">
                                    ${{ number_format($provider->min_price_per_hour, 2) }}
                                    @if($provider->max_price_per_hour)
                                        - ${{ number_format($provider->max_price_per_hour, 2) }}
                                    @endif
                                </div>
                                <div class="text-white/60 text-xs">per hour</div>
                            @else
                                <div class="text-white/50">-</div>
                            @endif
                        </td>

                        {{-- Features --}}
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-1">
                                @if($provider->supports_huggingface)
                                <span class="w-6 h-6 bg-orange-500/20 text-orange-400 rounded flex items-center justify-center text-xs" title="Hugging Face">
                                    🤗
                                </span>
                                @endif
                                @if($provider->supports_docker)
                                <span class="w-6 h-6 bg-blue-500/20 text-blue-400 rounded flex items-center justify-center text-xs" title="Docker">
                                    🐳
                                </span>
                                @endif
                                @if($provider->supports_jupyter)
                                <span class="w-6 h-6 bg-purple-500/20 text-purple-400 rounded flex items-center justify-center text-xs" title="Jupyter">
                                    📓
                                </span>
                                @endif
                                @if($provider->supports_ssh)
                                <span class="w-6 h-6 bg-green-500/20 text-green-400 rounded flex items-center justify-center text-xs" title="SSH">
                                    🔑
                                </span>
                                @endif
                            </div>
                        </td>

                        {{-- Rating --}}
                        <td class="px-6 py-4 text-center">
                            @if($provider->average_rating)
                            <div class="flex items-center justify-center gap-1">
                                <i class="fas fa-star text-yellow-400"></i>
                                <span class="text-white font-bold">{{ number_format($provider->average_rating, 1) }}</span>
                            </div>
                            @else
                            <span class="text-white/50">-</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 text-center">
                            @if($provider->is_active)
                                <span class="px-3 py-1 bg-green-500/20 text-green-300 text-sm font-medium rounded-full">
                                    <i class="fas fa-check-circle"></i> Active
                                </span>
                            @else
                                <span class="px-3 py-1 bg-red-500/20 text-red-300 text-sm font-medium rounded-full">
                                    <i class="fas fa-times-circle"></i> Inactive
                                </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                {{-- View --}}
                                <a href="{{ route('admin.ai-rental.cloud-providers.show', $provider) }}"
                                   class="w-8 h-8 bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 rounded-lg flex items-center justify-center transition"
                                   title="ดูรายละเอียด">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>

                                {{-- Edit --}}
                                <a href="{{ route('admin.ai-rental.cloud-providers.edit', $provider) }}"
                                   class="w-8 h-8 bg-yellow-500/20 text-yellow-400 hover:bg-yellow-500/30 rounded-lg flex items-center justify-center transition"
                                   title="แก้ไข">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>

                                {{-- Toggle Status --}}
                                @if($provider->is_active)
                                <form action="{{ route('admin.ai-rental.cloud-providers.deactivate', $provider) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="w-8 h-8 bg-orange-500/20 text-orange-400 hover:bg-orange-500/30 rounded-lg flex items-center justify-center transition"
                                            title="ปิดใช้งาน"
                                            onclick="return confirm('ต้องการปิดใช้งาน {{ $provider->name }} หรือไม่?')">
                                        <i class="fas fa-pause text-sm"></i>
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('admin.ai-rental.cloud-providers.activate', $provider) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="w-8 h-8 bg-green-500/20 text-green-400 hover:bg-green-500/30 rounded-lg flex items-center justify-center transition"
                                            title="เปิดใช้งาน">
                                        <i class="fas fa-play text-sm"></i>
                                    </button>
                                </form>
                                @endif

                                {{-- Delete --}}
                                <form action="{{ route('admin.ai-rental.cloud-providers.destroy', $provider) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="w-8 h-8 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-lg flex items-center justify-center transition"
                                            title="ลบ"
                                            onclick="return confirm('ต้องการลบ {{ $provider->name }} หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้!')">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="text-white/50 text-lg">ไม่พบ Cloud Providers</div>
                            <a href="{{ route('admin.ai-rental.cloud-providers.create') }}"
                               class="inline-block mt-4 px-6 py-3 bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold rounded-xl hover:scale-105 transition">
                                <i class="fas fa-plus"></i> เพิ่ม Provider ใหม่
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($providers->hasPages())
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        {{ $providers->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
function providersManager() {
    return {
        // ฟังก์ชันสำหรับ drag & drop (ถ้าต้องการเพิ่มในอนาคต)
        init() {
            console.log('Providers manager initialized');
        }
    }
}
</script>
@endpush
@endsection
