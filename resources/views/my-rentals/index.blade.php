@extends('layouts.app')

@section('title', 'การเช่าของฉัน')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50 to-pink-50">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 shadow-2xl">
        <div class="container mx-auto px-4 py-12 md:py-16">
            <div class="max-w-4xl">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-lg rounded-2xl flex items-center justify-center text-4xl md:text-5xl shadow-xl border-4 border-white/30">
                        💼
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-5xl font-black text-white mb-2">การเช่าของฉัน</h1>
                        <p class="text-purple-100 text-sm md:text-lg">จัดการและติดตามการเช่าบอทของคุณ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 -mt-12 relative z-10">
            <!-- Active Rentals -->
            <div class="bg-white rounded-2xl shadow-xl p-6 border-2 border-purple-100 transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-3xl shadow-lg">
                        🤖
                    </div>
                    <span class="text-sm font-semibold text-purple-600 bg-purple-50 px-3 py-1 rounded-full">Active</span>
                </div>
                <h3 class="text-3xl font-black text-gray-800 mb-1">{{ $stats['active_rentals'] }}</h3>
                <p class="text-sm text-gray-600 font-medium">การเช่าที่ใช้งานอยู่</p>
            </div>

            <!-- Total Spent -->
            <div class="bg-white rounded-2xl shadow-xl p-6 border-2 border-green-100 transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center text-3xl shadow-lg">
                        💰
                    </div>
                    <span class="text-sm font-semibold text-green-600 bg-green-50 px-3 py-1 rounded-full">Total</span>
                </div>
                <h3 class="text-3xl font-black text-gray-800 mb-1">฿{{ number_format($stats['total_spent'], 2) }}</h3>
                <p class="text-sm text-gray-600 font-medium">ใช้จ่ายทั้งหมด</p>
            </div>

            <!-- Total Messages -->
            <div class="bg-white rounded-2xl shadow-xl p-6 border-2 border-blue-100 transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-3xl shadow-lg">
                        💬
                    </div>
                    <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">Messages</span>
                </div>
                <h3 class="text-3xl font-black text-gray-800 mb-1">{{ number_format($stats['total_messages']) }}</h3>
                <p class="text-sm text-gray-600 font-medium">ข้อความทั้งหมด</p>
            </div>

            <!-- Monthly Cost -->
            <div class="bg-white rounded-2xl shadow-xl p-6 border-2 border-orange-100 transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center text-3xl shadow-lg">
                        📅
                    </div>
                    <span class="text-sm font-semibold text-orange-600 bg-orange-50 px-3 py-1 rounded-full">Monthly</span>
                </div>
                <h3 class="text-3xl font-black text-gray-800 mb-1">฿{{ number_format($stats['monthly_cost'], 2) }}</h3>
                <p class="text-sm text-gray-600 font-medium">ค่าใช้จ่ายรายเดือน</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-8 border border-gray-100">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">📊 สถานะ</label>
                    <select name="status" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition-all">
                        <option value="">ทั้งหมด</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">💳 ประเภท</label>
                    <select name="type" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition-all">
                        <option value="">ทั้งหมด</option>
                        <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>รายเดือน</option>
                        <option value="per_message" {{ request('type') == 'per_message' ? 'selected' : '' }}>ต่อข้อความ</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        กรอง
                    </button>
                </div>
                <div>
                    <a href="{{ route('my-rentals.transactions') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white hover:bg-gray-50 border-2 border-gray-300 text-gray-700 font-bold rounded-xl shadow hover:shadow-lg transform hover:scale-105 transition-all duration-200">
                        <span>📊</span>
                        <span>ประวัติธุรกรรม</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Rentals List -->
        @if($rentals->count() > 0)
            <div class="space-y-6 mb-8">
                @foreach($rentals as $rental)
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 group">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row gap-6">
                                <!-- Bot Info -->
                                <div class="flex items-center gap-4 flex-1">
                                    @if($rental->botProfile->avatar_url)
                                        <img src="{{ $rental->botProfile->avatar_url }}"
                                             class="w-20 h-20 rounded-2xl object-cover shadow-lg ring-4 ring-gray-100 group-hover:ring-purple-200 transition-all"
                                             alt="{{ $rental->botProfile->name }}">
                                    @else
                                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-3xl shadow-lg ring-4 ring-gray-100 group-hover:ring-purple-200 transition-all">
                                            🤖
                                        </div>
                                    @endif

                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-purple-600 transition-colors">
                                            {{ $rental->botProfile->name }}
                                        </h3>
                                        <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                                            <span class="w-6 h-6 bg-purple-100 rounded-lg flex items-center justify-center">👤</span>
                                            <span class="font-medium">{{ $rental->botProfile->owner->name }}</span>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @if($rental->rental_type === 'monthly')
                                                <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">
                                                    💳 รายเดือน
                                                </span>
                                            @else
                                                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">
                                                    💬 ต่อข้อความ
                                                </span>
                                            @endif

                                            @if($rental->status === 'active')
                                                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full animate-pulse">
                                                    ✓ ACTIVE
                                                </span>
                                            @elseif($rental->status === 'expired')
                                                <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full">
                                                    ⏰ หมดอายุ
                                                </span>
                                            @elseif($rental->status === 'cancelled')
                                                <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full">
                                                    ✕ ยกเลิก
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Rental Details -->
                                <div class="flex items-center gap-6">
                                    <div class="text-center bg-purple-50 px-6 py-4 rounded-xl">
                                        <div class="text-2xl font-black text-purple-700 mb-1">
                                            ฿{{ number_format($rental->price, 2) }}
                                        </div>
                                        <div class="text-xs text-purple-600 font-semibold">
                                            @if($rental->rental_type === 'monthly')
                                                /เดือน
                                            @else
                                                /ข้อความ
                                            @endif
                                        </div>
                                    </div>

                                    <div class="text-center bg-blue-50 px-6 py-4 rounded-xl">
                                        @if($rental->rental_type === 'monthly')
                                            <div class="text-2xl font-black text-blue-700 mb-1">{{ $rental->days_remaining }}</div>
                                            <div class="text-xs text-blue-600 font-semibold">วันเหลือ</div>
                                        @else
                                            <div class="text-2xl font-black text-blue-700 mb-1">{{ number_format($rental->total_messages) }}</div>
                                            <div class="text-xs text-blue-600 font-semibold">ข้อความ</div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('my-rentals.show', $rental->id) }}"
                                       class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center gap-2">
                                        <span>👁️</span>
                                        <span>ดูรายละเอียด</span>
                                    </a>
                                    @if($rental->isActive())
                                        <button type="button"
                                                class="px-4 py-3 bg-red-50 hover:bg-red-100 text-red-600 font-bold rounded-xl border-2 border-red-200 hover:border-red-300 shadow hover:shadow-lg transform hover:scale-105 transition-all duration-200"
                                                onclick="cancelRental({{ $rental->id }})">
                                            ✕
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Additional Info for Monthly -->
                            @if($rental->rental_type === 'monthly' && $rental->isActive())
                                <div class="mt-6 pt-6 border-t border-gray-100">
                                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                                        <div class="flex items-center gap-4 text-sm">
                                            <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg">
                                                <span>📅</span>
                                                <span class="font-semibold text-gray-700">เริ่ม:</span>
                                                <span class="text-gray-600">{{ $rental->start_date->format('d/m/Y') }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg">
                                                <span>⏰</span>
                                                <span class="font-semibold text-gray-700">หมดอายุ:</span>
                                                <span class="text-gray-600">{{ $rental->end_date->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                        <form method="POST"
                                              action="{{ route('my-rentals.toggle-auto-renew', $rental->id) }}"
                                              class="flex items-center">
                                            @csrf
                                            <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-xl border-2 border-gray-200 hover:border-gray-300 transition-all">
                                                @if($rental->auto_renew)
                                                    <span class="text-2xl">🔄</span>
                                                    <span class="text-sm font-semibold text-green-700">ต่ออายุอัตโนมัติ: เปิด</span>
                                                @else
                                                    <span class="text-2xl opacity-50">🔄</span>
                                                    <span class="text-sm font-semibold text-gray-600">ต่ออายุอัตโนมัติ: ปิด</span>
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-3xl shadow-2xl p-12 text-center">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 rounded-full mb-6">
                    <span class="text-5xl">💼</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">คุณยังไม่มีการเช่าบอท</h3>
                <p class="text-gray-600 mb-6">เริ่มต้นเช่าบอท AI คุณภาพสูงจากตลาดของเรา</p>
                <a href="{{ route('marketplace.index') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    <span class="text-2xl">🛒</span>
                    <span>ไปตลาดบอท</span>
                </a>
            </div>
        @endif

        <!-- Pagination -->
        @if($rentals->hasPages())
            <div class="flex justify-center">
                <div class="bg-white rounded-2xl shadow-lg p-4">
                    {{ $rentals->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Cancel Modal -->
<div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden" id="cancelModal">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
            <form id="cancelForm" method="POST">
                @csrf
                <div class="bg-gradient-to-r from-red-600 to-pink-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold">ยกเลิกการเช่า</h3>
                        <button type="button" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center transition-all" onclick="closeModal()">
                            ✕
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 mb-4">คุณแน่ใจหรือไม่ที่จะยกเลิกการเช่านี้?</p>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">เหตุผล (ไม่บังคับ)</label>
                        <textarea name="reason" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-red-500 focus:ring-4 focus:ring-red-100 transition-all" rows="3" placeholder="บอกเราถึงเหตุผลที่ยกเลิก..."></textarea>
                    </div>
                </div>
                <div class="flex gap-3 p-6 bg-gray-50">
                    <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 bg-white hover:bg-gray-100 text-gray-700 font-bold rounded-xl border-2 border-gray-300 shadow hover:shadow-lg transition-all">
                        ปิด
                    </button>
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        ยืนยันยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cancelRental(rentalId) {
    const modal = document.getElementById('cancelModal');
    const form = document.getElementById('cancelForm');
    form.action = `/my-rentals/${rentalId}/cancel`;
    modal.classList.remove('hidden');
}

function closeModal() {
    const modal = document.getElementById('cancelModal');
    modal.classList.add('hidden');
}

// Close modal on outside click
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endsection
