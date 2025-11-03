@extends('layouts.app')

@section('title', 'ตลาดบอท AI')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50">
    <!-- Hero Section -->
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 shadow-2xl">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        <div class="container mx-auto px-4 py-16 md:py-24 relative">
            <div class="max-w-4xl mx-auto text-center text-white">
                <div class="inline-flex items-center justify-center w-20 h-20 md:w-24 md:h-24 bg-white/20 backdrop-blur-lg rounded-3xl shadow-2xl mb-6 border-4 border-white/30">
                    <span class="text-5xl md:text-6xl">🤖</span>
                </div>
                <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight drop-shadow-lg">
                    ตลาดบอท AI
                </h1>
                <p class="text-xl md:text-2xl text-blue-100 mb-8 font-medium">
                    เช่าบอท AI คุณภาพสูงจากผู้สร้างทั่วโลก พร้อมใช้งานทันที
                </p>
                <div class="flex flex-wrap gap-4 justify-center text-sm md:text-base">
                    <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-6 py-3 rounded-full border border-white/30">
                        <span class="text-2xl">⚡</span>
                        <span class="font-semibold">ใช้งานได้ทันที</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-6 py-3 rounded-full border border-white/30">
                        <span class="text-2xl">💎</span>
                        <span class="font-semibold">คุณภาพสูง</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-6 py-3 rounded-full border border-white/30">
                        <span class="text-2xl">🔒</span>
                        <span class="font-semibold">ปลอดภัย</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-slate-50 to-transparent"></div>
    </div>

    <div class="container mx-auto px-4 py-8 -mt-10 relative z-10">
        <!-- Search & Filters Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-6 md:p-8 mb-8 border border-gray-100">
            <form method="GET" action="{{ route('marketplace.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">🔍 ค้นหาบอท</label>
                        <input type="text"
                               name="search"
                               class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition-all"
                               placeholder="ค้นหาชื่อหรือคำอธิบาย..."
                               value="{{ request('search') }}">
                    </div>

                    <!-- Rental Type -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">💳 ประเภท</label>
                        <select name="rental_type" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition-all">
                            <option value="">ทั้งหมด</option>
                            <option value="monthly" {{ request('rental_type') == 'monthly' ? 'selected' : '' }}>รายเดือน</option>
                            <option value="per_message" {{ request('rental_type') == 'per_message' ? 'selected' : '' }}>ต่อข้อความ</option>
                        </select>
                    </div>

                    <!-- Provider Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">🏢 ผู้ให้บริการ</label>
                        <select name="provider" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition-all">
                            <option value="">ทั้งหมด</option>
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" {{ request('provider') == $provider->id ? 'selected' : '' }}>
                                    {{ $provider->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sort -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">📊 เรียงตาม</label>
                        <select name="sort_by" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition-all">
                            <option value="newest" {{ request('sort_by') == 'newest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                            <option value="popular" {{ request('sort_by') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                            <option value="price_low" {{ request('sort_by') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                            <option value="price_high" {{ request('sort_by') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                            กรอง
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg">
                    {{ $bots->total() }}
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-800">พบ {{ $bots->total() }} บอท</p>
                    <p class="text-sm text-gray-500">พร้อมให้บริการ</p>
                </div>
            </div>
        </div>

        <!-- Bots Grid -->
        @if($bots->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                @foreach($bots as $bot)
                    <div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 overflow-hidden border border-gray-100">
                        <!-- Bot Avatar -->
                        <div class="relative bg-gradient-to-br from-blue-50 to-purple-50 p-8">
                            <div class="absolute top-4 right-4 flex gap-2">
                                @if($bot->enable_knowledge_base)
                                    <span class="px-3 py-1 bg-blue-500 text-white text-xs font-bold rounded-full shadow-lg">
                                        📚 RAG
                                    </span>
                                @endif
                            </div>

                            <div class="flex justify-center">
                                @if($bot->avatar_url)
                                    <img src="{{ $bot->avatar_url }}"
                                         alt="{{ $bot->name }}"
                                         class="w-24 h-24 rounded-2xl object-cover shadow-xl ring-4 ring-white transform group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-4xl shadow-xl ring-4 ring-white transform group-hover:scale-110 transition-transform duration-300">
                                        🤖
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Bot Info -->
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2 truncate group-hover:text-purple-600 transition-colors">
                                {{ $bot->name }}
                            </h3>

                            <!-- Description -->
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2 h-10">
                                {{ Str::limit($bot->description, 80) }}
                            </p>

                            <!-- Provider & Owner -->
                            <div class="space-y-2 mb-4 pb-4 border-b border-gray-100">
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span class="w-6 h-6 bg-blue-100 rounded-lg flex items-center justify-center">🏢</span>
                                    <span class="font-medium truncate">{{ $bot->provider->name }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span class="w-6 h-6 bg-purple-100 rounded-lg flex items-center justify-center">👤</span>
                                    <span class="font-medium truncate">{{ $bot->owner->name }}</span>
                                </div>
                            </div>

                            <!-- Pricing -->
                            <div class="mb-4">
                                @if($bot->rental_price_per_month)
                                    <div class="flex items-baseline justify-between mb-2 bg-purple-50 px-4 py-3 rounded-xl">
                                        <span class="text-sm text-purple-600 font-semibold">รายเดือน</span>
                                        <div>
                                            <span class="text-2xl font-black text-purple-700">฿{{ number_format($bot->rental_price_per_month, 0) }}</span>
                                            <span class="text-sm text-purple-600">/เดือน</span>
                                        </div>
                                    </div>
                                @endif
                                @if($bot->rental_price_per_message)
                                    <div class="flex items-baseline justify-between bg-green-50 px-4 py-3 rounded-xl">
                                        <span class="text-sm text-green-600 font-semibold">ต่อข้อความ</span>
                                        <div>
                                            <span class="text-2xl font-black text-green-700">฿{{ number_format($bot->rental_price_per_message, 2) }}</span>
                                            <span class="text-sm text-green-600">/ข้อความ</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Stats -->
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                                <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-lg">
                                    <span>👥</span>
                                    <span class="font-semibold">{{ $bot->rentals_count ?? 0 }} คน</span>
                                </div>
                                <div class="flex items-center gap-2 bg-yellow-50 px-3 py-2 rounded-lg">
                                    <span>⭐</span>
                                    <span class="font-semibold">4.8</span>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <a href="{{ route('marketplace.show', $bot->id) }}"
                               class="block w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold text-center rounded-xl shadow-lg hover:shadow-xl transform group-hover:scale-105 transition-all duration-200">
                                ดูรายละเอียด
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-3xl shadow-2xl p-12 text-center">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 rounded-full mb-6">
                    <span class="text-5xl">🔍</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบบอทที่ตรงกับเงื่อนไข</h3>
                <p class="text-gray-600 mb-6">ลองปรับเปลี่ยนตัวกรองหรือค้นหาด้วยคำค้นอื่น</p>
                <a href="{{ route('marketplace.index') }}" class="inline-block px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    ดูบอททั้งหมด
                </a>
            </div>
        @endif

        <!-- Pagination -->
        @if($bots->hasPages())
            <div class="flex justify-center">
                <div class="bg-white rounded-2xl shadow-lg p-4">
                    {{ $bots->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
