@extends('layouts.admin-v3')

@section('title', 'รายละเอียดคอมมิชชั่น')

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .float-animation {
        animation: float 6s ease-in-out infinite;
    }
    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); }
        50% { box-shadow: 0 0 40px rgba(16, 185, 129, 0.6); }
    }
    .glow-animation {
        animation: pulse-glow 2s ease-in-out infinite;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-teal-400/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>

        {{-- Floating Icon --}}
        <div class="absolute right-8 top-1/2 -translate-y-1/2 hidden lg:block">
            <div class="w-32 h-32 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center float-animation">
                <i class="fas fa-coins text-5xl text-white/80"></i>
            </div>
        </div>

        <div class="relative">
            {{-- Back Button --}}
            <a href="{{ route('admin.marketplace.commissions.index') }}"
               class="inline-flex items-center gap-2 text-white/80 hover:text-white transition mb-4">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปรายการ</span>
            </a>

            <h1 class="text-3xl font-bold text-white mb-2">รายละเอียดคอมมิชชั่น</h1>
            <p class="text-white/80">#{{ $commission->id }}</p>

            {{-- Status Badge --}}
            <div class="mt-4">
                @php
                    $statusConfig = [
                        'pending' => ['bg' => 'bg-amber-500', 'label' => 'รอดำเนินการ', 'icon' => 'fa-clock'],
                        'approved' => ['bg' => 'bg-blue-500', 'label' => 'อนุมัติแล้ว', 'icon' => 'fa-check'],
                        'paid' => ['bg' => 'bg-emerald-500', 'label' => 'จ่ายแล้ว', 'icon' => 'fa-money-bill'],
                        'rejected' => ['bg' => 'bg-red-500', 'label' => 'ปฏิเสธ', 'icon' => 'fa-times'],
                    ];
                    $currentStatus = $statusConfig[$commission->status] ?? $statusConfig['pending'];
                @endphp
                <span class="px-4 py-2 {{ $currentStatus['bg'] }} text-white rounded-full text-sm font-medium shadow-lg inline-flex items-center gap-2">
                    <i class="fas {{ $currentStatus['icon'] }}"></i>
                    {{ $currentStatus['label'] }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Commission Amount Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="p-8 text-center">
                    <p class="text-gray-500 dark:text-gray-400 mb-4 text-lg">จำนวนคอมมิชชั่น</p>
                    <div class="inline-block p-8 bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-emerald-900/30 dark:via-teal-900/30 dark:to-cyan-900/30 rounded-2xl {{ $commission->status == 'paid' ? 'glow-animation' : '' }}">
                        <p class="text-6xl font-bold bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 bg-clip-text text-transparent">
                            ฿{{ number_format($commission->commission_amount, 2) }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ประเภท</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $commission->commission_type == 'direct' ? 'Direct' : 'MLM' }}
                            </p>
                        </div>
                        @if($commission->mlm_level)
                            <div class="p-4 bg-purple-50 dark:bg-purple-900/30 rounded-xl">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">MLM Level</p>
                                <p class="font-bold text-purple-600 dark:text-purple-400">Level {{ $commission->mlm_level }}</p>
                            </div>
                        @endif
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">อัตรา</p>
                            <p class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($commission->commission_rate ?? 0, 2) }}%</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ยอดออเดอร์</p>
                            <p class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($commission->order_amount ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- User Info Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-user text-blue-500"></i>
                        ผู้รับคอมมิชชั่น
                    </h3>
                </div>
                <div class="p-6">
                    @if($commission->user)
                        <div class="flex items-center gap-5">
                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                                {{ strtoupper(substr($commission->user->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $commission->user->name }}</p>
                                <p class="text-gray-500 dark:text-gray-400">{{ $commission->user->email }}</p>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-user-slash text-gray-400 text-2xl"></i>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">ไม่พบข้อมูลผู้ใช้</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Order Info Card --}}
            @if($commission->order)
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 dark:from-purple-500/20 dark:to-pink-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-receipt text-purple-500"></i>
                            ข้อมูลออเดอร์
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">เลขออเดอร์</p>
                                <a href="{{ route('admin.marketplace.orders.show', $commission->order) }}"
                                   class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 hover:underline">
                                    {{ $commission->order->external_order_id }}
                                </a>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Platform</p>
                                @if($commission->order->platform)
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        @if($commission->order->platform->slug == 'lazada') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                        @elseif($commission->order->platform->slug == 'shopee') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                        @elseif($commission->order->platform->slug == 'tiktok') bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400
                                        @endif">
                                        {{ $commission->order->platform->name }}
                                    </span>
                                @endif
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ยอดรวมออเดอร์</p>
                                <p class="font-bold text-gray-900 dark:text-white">฿{{ number_format($commission->order->total_amount, 2) }}</p>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันที่สั่งซื้อ</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $commission->order->ordered_at ? \Carbon\Carbon::parse($commission->order->ordered_at)->format('d/m/Y H:i') : '-' }}
                                </p>
                            </div>
                        </div>

                        {{-- Order Items --}}
                        @if($commission->order->items && $commission->order->items->count() > 0)
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">รายการสินค้า</p>
                                <div class="space-y-2">
                                    @foreach($commission->order->items as $item)
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                            <div class="w-12 h-12 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                                                @if($item->product_image_url)
                                                    <img src="{{ $item->product_image_url }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                        <i class="fas fa-box text-xs"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-gray-900 dark:text-white truncate">{{ $item->product_name }}</p>
                                                <p class="text-xs text-gray-500">×{{ $item->quantity }}</p>
                                            </div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">฿{{ number_format($item->total_amount, 2) }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 dark:from-emerald-500/20 dark:to-teal-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-cog text-emerald-500"></i>
                        การจัดการ
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    {{-- 🚨 สถานะการเคลียร์เงินของลาซาด้า — ต้องเห็นก่อนกดอนุมัติ/จ่ายเสมอ
                         (เดิมหน้านี้โชว์ปุ่มเขียวเต็มที่ กดแล้วเด้ง 422 โดยไม่รู้สาเหตุ) --}}
                    @php $blocked = ! ($verdict['allowed'] ?? false); @endphp

                    <div class="rounded-xl px-4 py-3 text-sm
                                {{ $blocked ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300' : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300' }}">
                        <div class="font-semibold flex items-center gap-2">
                            <i class="fas {{ $blocked ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
                            <span>{{ $settle['label'] ?? ($blocked ? 'รอลาซาด้ายืนยัน' : 'ลาซาด้ายืนยันแล้ว') }}</span>
                        </div>
                        @if($blocked && ! empty($verdict['reason']))
                            <p class="mt-1 leading-relaxed">{{ $verdict['reason'] }}</p>
                        @endif
                    </div>

                    @if($commission->status === 'pending')
                        <button type="button" onclick="approveCommission()"
                                @disabled($blocked)
                                title="{{ $blocked ? ($verdict['reason'] ?? '') : 'อนุมัติค่าคอม' }}"
                                class="w-full px-4 py-3 rounded-xl transition-all font-medium flex items-center justify-center gap-2
                                       {{ $blocked
                                          ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                          : 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white hover:shadow-lg hover:shadow-blue-500/25' }}">
                            <i class="fas fa-check"></i>
                            <span>อนุมัติ</span>
                        </button>

                        <button type="button" onclick="rejectCommission()"
                                class="w-full px-4 py-3 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-xl hover:shadow-lg hover:shadow-red-500/25 transition-all font-medium flex items-center justify-center gap-2">
                            <i class="fas fa-times"></i>
                            <span>ปฏิเสธ</span>
                        </button>
                    @endif

                    @if($commission->status === 'approved')
                        <button type="button" onclick="payCommission()"
                                @disabled($blocked)
                                title="{{ $blocked ? ($verdict['reason'] ?? '') : 'จ่ายเข้าวอลเลต' }}"
                                class="w-full px-4 py-3 rounded-xl transition-all font-medium flex items-center justify-center gap-2
                                       {{ $blocked
                                          ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                          : 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white hover:shadow-lg hover:shadow-emerald-500/25' }}">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>จ่ายเงิน</span>
                        </button>
                    @endif

                    @if($commission->status !== 'paid')
                        <form action="{{ route('admin.marketplace.commissions.destroy', $commission) }}" method="POST"
                              onsubmit="return confirm('⚠️ ต้องการลบคอมมิชชั่นนี้?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center justify-center gap-2">
                                <i class="fas fa-trash"></i>
                                <span>ลบ</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Timeline Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 dark:from-purple-500/20 dark:to-pink-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-history text-purple-500"></i>
                        ประวัติ
                    </h3>
                </div>
                <div class="p-6">
                    <div class="relative space-y-6">
                        {{-- Timeline Line --}}
                        <div class="absolute left-[9px] top-3 bottom-3 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                        {{-- Created --}}
                        <div class="relative flex gap-4">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-br from-emerald-500 to-green-500 flex items-center justify-center z-10 shadow">
                                <i class="fas fa-plus text-white text-[8px]"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">สร้างคอมมิชชั่น</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $commission->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>

                        @if($commission->approved_at)
                            <div class="relative flex gap-4">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center z-10 shadow">
                                    <i class="fas fa-check text-white text-[8px]"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">อนุมัติ</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($commission->approved_at)->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        @endif

                        @if($commission->paid_at)
                            <div class="relative flex gap-4">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center z-10 shadow glow-animation">
                                    <i class="fas fa-money-bill text-white text-[8px]"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-emerald-600 dark:text-emerald-400">จ่ายเงินแล้ว</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($commission->paid_at)->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        @endif

                        @if($commission->rejected_at)
                            <div class="relative flex gap-4">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-red-500 to-rose-500 flex items-center justify-center z-10 shadow">
                                    <i class="fas fa-times text-white text-[8px]"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-red-600 dark:text-red-400">ปฏิเสธ</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($commission->rejected_at)->format('d/m/Y H:i') }}</p>
                                    @if($commission->notes)
                                        <p class="text-sm text-red-500 mt-1 p-2 bg-red-50 dark:bg-red-900/30 rounded-lg">{{ $commission->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
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

    function approveCommission() {
        if (!confirm('ต้องการอนุมัติคอมมิชชั่นนี้?')) return;

        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลังดำเนินการ...</span>';

        fetch(`{{ route('admin.marketplace.commissions.approve', $commission) }}`, {
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

    function payCommission() {
        if (!confirm('ต้องการจ่ายคอมมิชชั่นนี้?')) return;

        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลังดำเนินการ...</span>';

        fetch(`{{ route('admin.marketplace.commissions.pay', $commission) }}`, {
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

    function rejectCommission() {
        const reason = prompt('ระบุเหตุผลในการปฏิเสธ:');
        if (!reason) return;

        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลังดำเนินการ...</span>';

        fetch(`{{ route('admin.marketplace.commissions.reject', $commission) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ reason })
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
