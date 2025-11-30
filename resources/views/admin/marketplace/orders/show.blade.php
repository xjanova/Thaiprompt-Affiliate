@extends('layouts.admin-v3')

@section('title', 'รายละเอียดออเดอร์ - ' . $order->external_order_id)

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .float-animation {
        animation: float 6s ease-in-out infinite;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-400/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>

        {{-- Floating Icon --}}
        <div class="absolute right-8 top-1/2 -translate-y-1/2 hidden lg:block">
            <div class="w-32 h-32 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center float-animation">
                <i class="fas fa-receipt text-5xl text-white/80"></i>
            </div>
        </div>

        <div class="relative">
            {{-- Back Button --}}
            <a href="{{ route('admin.marketplace.orders.index') }}"
               class="inline-flex items-center gap-2 text-white/80 hover:text-white transition mb-4">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปรายการ</span>
            </a>

            <h1 class="text-3xl font-bold text-white mb-2">ออเดอร์ #{{ $order->external_order_id }}</h1>
            <p class="text-white/80 font-mono">{{ $order->order_number }}</p>

            {{-- Status Badges --}}
            <div class="flex flex-wrap items-center gap-2 mt-4">
                @php
                    $statusConfig = [
                        'pending' => ['bg' => 'bg-amber-500', 'label' => 'รอดำเนินการ'],
                        'processing' => ['bg' => 'bg-blue-500', 'label' => 'กำลังดำเนินการ'],
                        'shipped' => ['bg' => 'bg-purple-500', 'label' => 'จัดส่งแล้ว'],
                        'delivered' => ['bg' => 'bg-cyan-500', 'label' => 'ส่งถึงแล้ว'],
                        'completed' => ['bg' => 'bg-emerald-500', 'label' => 'สำเร็จ'],
                        'cancelled' => ['bg' => 'bg-red-500', 'label' => 'ยกเลิก'],
                    ];
                    $currentStatus = $statusConfig[$order->order_status] ?? $statusConfig['pending'];
                @endphp
                <span class="px-4 py-1.5 {{ $currentStatus['bg'] }} text-white rounded-full text-sm font-medium shadow-lg">
                    {{ $currentStatus['label'] }}
                </span>

                @if($order->platform)
                    <span class="px-4 py-1.5 rounded-full text-sm font-medium shadow-lg
                        @if($order->platform->slug == 'lazada') bg-orange-500 text-white
                        @elseif($order->platform->slug == 'shopee') bg-red-500 text-white
                        @elseif($order->platform->slug == 'tiktok') bg-pink-500 text-white
                        @else bg-gray-500 text-white
                        @endif">
                        {{ $order->platform->name }}
                    </span>
                @endif

                @if($order->commission_status)
                    @php
                        $commissionConfig = [
                            'pending' => ['bg' => 'bg-gray-500', 'label' => 'รอคำนวณ'],
                            'calculated' => ['bg' => 'bg-amber-500', 'label' => 'คำนวณแล้ว'],
                            'approved' => ['bg' => 'bg-blue-500', 'label' => 'อนุมัติแล้ว'],
                            'paid' => ['bg' => 'bg-emerald-500', 'label' => 'จ่ายแล้ว'],
                        ];
                        $commStatus = $commissionConfig[$order->commission_status] ?? $commissionConfig['pending'];
                    @endphp
                    <span class="px-4 py-1.5 {{ $commStatus['bg'] }} text-white rounded-full text-sm font-medium shadow-lg">
                        คอมมิชชั่น: {{ $commStatus['label'] }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer Info Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-user text-blue-500"></i>
                        ข้อมูลลูกค้า
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ชื่อลูกค้า</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $order->customer_name ?? '-' }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">อีเมล</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $order->customer_email ?? '-' }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">โทรศัพท์</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $order->customer_phone ?? '-' }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Tracking Number</p>
                            <p class="font-mono font-semibold text-blue-600 dark:text-blue-400">{{ $order->tracking_number ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Order Items Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 dark:from-purple-500/20 dark:to-pink-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-shopping-basket text-purple-500"></i>
                        รายการสินค้า
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    @forelse($order->items as $item)
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                            <div class="w-20 h-20 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded-xl overflow-hidden flex-shrink-0 shadow">
                                @if($item->product_image_url)
                                    <img src="{{ $item->product_image_url }}" alt="{{ $item->product_name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="fas fa-box text-2xl"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $item->product_name }}</p>
                                @if($item->variant_name)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->variant_name }}</p>
                                @endif
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    ฿{{ number_format($item->unit_price, 2) }} × {{ $item->quantity }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-lg text-gray-900 dark:text-white">฿{{ number_format($item->total_amount, 2) }}</p>
                                @if($item->discount_amount > 0)
                                    <p class="text-sm text-emerald-600">
                                        <i class="fas fa-tag mr-1"></i>-฿{{ number_format($item->discount_amount, 2) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-box-open text-gray-400 text-2xl"></i>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">ไม่มีรายการสินค้า</p>
                        </div>
                    @endforelse

                    {{-- Order Summary --}}
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-xl p-4 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">ยอดสินค้า</span>
                                <span class="text-gray-900 dark:text-white">฿{{ number_format($order->subtotal ?? $order->total_amount, 2) }}</span>
                            </div>
                            @if($order->shipping_fee > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">ค่าจัดส่ง</span>
                                    <span class="text-gray-900 dark:text-white">฿{{ number_format($order->shipping_fee, 2) }}</span>
                                </div>
                            @endif
                            @if($order->discount_amount > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">ส่วนลด</span>
                                    <span class="text-emerald-600">-฿{{ number_format($order->discount_amount, 2) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-xl font-bold pt-3 border-t border-gray-200 dark:border-gray-600">
                                <span class="text-gray-900 dark:text-white">ยอดรวม</span>
                                <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                    ฿{{ number_format($order->total_amount, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Commissions Card --}}
            @if($order->commissions->count() > 0)
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 dark:from-emerald-500/20 dark:to-teal-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-coins text-emerald-500"></i>
                            คอมมิชชั่น
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ผู้รับ</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Level</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">จำนวน</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($order->commissions as $commission)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                                                    {{ strtoupper(substr($commission->user->name ?? 'U', 0, 1)) }}
                                                </div>
                                                <span class="text-sm text-gray-900 dark:text-white">{{ $commission->user->name ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                            {{ $commission->commission_type }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if($commission->mlm_level)
                                                <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded text-xs font-medium">
                                                    Level {{ $commission->mlm_level }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-emerald-600">
                                            ฿{{ number_format($commission->commission_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if($commission->status == 'paid')
                                                <span class="px-3 py-1 bg-gradient-to-r from-emerald-500 to-green-500 text-white rounded-full text-xs font-medium">จ่ายแล้ว</span>
                                            @elseif($commission->status == 'approved')
                                                <span class="px-3 py-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-full text-xs font-medium">อนุมัติ</span>
                                            @else
                                                <span class="px-3 py-1 bg-gradient-to-r from-gray-400 to-gray-500 text-white rounded-full text-xs font-medium">{{ $commission->status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-cyan-500/10 to-blue-500/10 dark:from-cyan-500/20 dark:to-blue-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-cog text-cyan-500"></i>
                        การจัดการ
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    @if($order->commission_status === 'pending')
                        <button type="button" onclick="calculateCommission()"
                                class="w-full px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl hover:shadow-lg hover:shadow-emerald-500/25 transition-all font-medium flex items-center justify-center gap-2">
                            <i class="fas fa-calculator"></i>
                            <span>คำนวณคอมมิชชั่น</span>
                        </button>
                    @endif

                    <form action="{{ route('admin.marketplace.orders.update-status', $order) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PUT')

                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">อัพเดทสถานะ</label>
                        <select name="order_status"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="pending" {{ $order->order_status == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                            <option value="processing" {{ $order->order_status == 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                            <option value="shipped" {{ $order->order_status == 'shipped' ? 'selected' : '' }}>จัดส่งแล้ว</option>
                            <option value="delivered" {{ $order->order_status == 'delivered' ? 'selected' : '' }}>ส่งถึงแล้ว</option>
                            <option value="completed" {{ $order->order_status == 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                            <option value="cancelled" {{ $order->order_status == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                        </select>

                        <button type="submit"
                                class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl hover:shadow-lg hover:shadow-blue-500/25 transition-all font-medium flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            <span>บันทึก</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Affiliate Info Card --}}
            @if($order->affiliateUser || $order->affiliateLink)
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 dark:from-purple-500/20 dark:to-pink-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-user-tag text-purple-500"></i>
                            ข้อมูล Affiliate
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @if($order->affiliateUser)
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ผู้แนะนำ</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-sm font-bold">
                                        {{ strtoupper(substr($order->affiliateUser->name, 0, 1)) }}
                                    </div>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $order->affiliateUser->name }}</span>
                                </div>
                            </div>
                        @endif
                        @if($order->affiliateLink)
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ลิงก์ Affiliate</p>
                                <p class="font-mono text-sm text-blue-600 dark:text-blue-400 break-all">{{ $order->affiliateLink->short_code }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Order Info Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-gray-500/10 to-slate-500/10 dark:from-gray-500/20 dark:to-slate-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-info-circle text-gray-500"></i>
                        ข้อมูลออเดอร์
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">บัญชี</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $order->account->account_name ?? '-' }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันที่สั่งซื้อ</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $order->ordered_at ? \Carbon\Carbon::parse($order->ordered_at)->format('d/m/Y H:i') : '-' }}
                        </p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Sync เมื่อ</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast Notification --}}
<div id="toast" class="fixed bottom-6 right-6 z-50 hidden">
    <div class="flex items-center gap-3 px-6 py-4 rounded-xl shadow-lg" id="toast-content">
        <i id="toast-icon" class="fas"></i>
        <span id="toast-message"></span>
    </div>
</div>

@push('scripts')
<script>
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        const icon = document.getElementById('toast-icon');
        const msg = document.getElementById('toast-message');

        content.className = 'flex items-center gap-3 px-6 py-4 rounded-xl shadow-lg ';
        icon.className = 'fas ';

        if (type === 'success') {
            content.className += 'bg-emerald-500 text-white';
            icon.className += 'fa-check-circle';
        } else if (type === 'error') {
            content.className += 'bg-red-500 text-white';
            icon.className += 'fa-exclamation-circle';
        }

        msg.textContent = message;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    function calculateCommission() {
        if (!confirm('ต้องการคำนวณคอมมิชชั่นสำหรับออเดอร์นี้?')) return;

        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลังคำนวณ...</span>';

        fetch(`{{ route('admin.marketplace.orders.calculate-commission', $order) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => showToast('เกิดข้อผิดพลาด: ' + error.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }
</script>
@endpush
@endsection
