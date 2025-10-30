@extends('layouts.admin')

@section('title', 'รายละเอียดคำขอถอนเงิน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">รายละเอียดคำขอถอนเงิน</h2>
            <p class="text-gray-600 mt-1">รหัสคำขอ: {{ $withdrawal->request_id }}</p>
        </div>
        <a href="{{ route('admin.withdrawals.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
            ← กลับ
        </a>
    </div>

    <!-- Status Banner -->
    <div class="rounded-xl p-6
        @if($withdrawal->status == 'pending') bg-gradient-to-r from-yellow-400 to-orange-500
        @elseif($withdrawal->status == 'approved') bg-gradient-to-r from-green-400 to-emerald-500
        @elseif($withdrawal->status == 'completed') bg-gradient-to-r from-blue-400 to-indigo-500
        @elseif($withdrawal->status == 'rejected') bg-gradient-to-r from-red-400 to-pink-500
        @else bg-gradient-to-r from-gray-400 to-gray-500
        @endif text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold mb-2">สถานะ: {{ $withdrawal->status_label }}</h3>
                <p class="text-sm opacity-90">
                    @if($withdrawal->status == 'pending')
                        รอการอนุมัติจากแอดมิน
                    @elseif($withdrawal->status == 'approved')
                        อนุมัติแล้ว รอดำเนินการโอนเงิน
                    @elseif($withdrawal->status == 'completed')
                        โอนเงินเรียบร้อยแล้ว
                    @elseif($withdrawal->status == 'rejected')
                        คำขอถูกปฏิเสธ
                    @endif
                </p>
            </div>
            <div class="text-6xl">
                @if($withdrawal->status == 'pending') ⏳
                @elseif($withdrawal->status == 'approved') ✅
                @elseif($withdrawal->status == 'completed') ✨
                @elseif($withdrawal->status == 'rejected') ❌
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Amount Details -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">รายละเอียดจำนวนเงิน</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600">จำนวนที่ขอถอน</span>
                        <span class="text-2xl font-bold text-indigo-600">{{ number_format($withdrawal->amount, 2) }} THB</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t">
                        <span class="text-gray-600">ค่าธรรมเนียม</span>
                        <span class="text-lg font-semibold text-red-600">- {{ number_format($withdrawal->fee, 2) }} THB</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t">
                        <span class="text-gray-600">ภาษี ({{ $withdrawal->tax_percentage }}%)</span>
                        <span class="text-lg font-semibold text-red-600">- {{ number_format($withdrawal->tax, 2) }} THB</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-t-2 border-gray-300 bg-green-50 -mx-6 px-6">
                        <span class="text-gray-900 font-bold">จำนวนสุทธิที่จะได้รับ</span>
                        <span class="text-3xl font-bold text-green-600">{{ number_format($withdrawal->net_amount, 2) }} THB</span>
                    </div>
                </div>
            </div>

            <!-- User Info -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลผู้ขอถอน</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-500">ชื่อ</span>
                        <p class="font-semibold text-gray-900">{{ $withdrawal->user->name }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">อีเมล</span>
                        <p class="font-semibold text-gray-900">{{ $withdrawal->user->email }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">กระเป๋าเงิน</span>
                        <p class="font-semibold text-gray-900">{{ $withdrawal->wallet->wallet_address }}</p>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            @if($withdrawal->paymentMethod)
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">ช่องทางรับเงิน</h3>
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4 space-y-2">
                    <div>
                        <span class="text-sm text-gray-600">ประเภท</span>
                        <p class="font-bold text-gray-900">{{ $withdrawal->paymentMethod->type_label }}</p>
                    </div>
                    @if($withdrawal->paymentMethod->bank_name)
                    <div>
                        <span class="text-sm text-gray-600">ธนาคาร</span>
                        <p class="font-semibold text-gray-900">{{ $withdrawal->paymentMethod->bank_name }}</p>
                    </div>
                    @endif
                    @if($withdrawal->paymentMethod->account_number)
                    <div>
                        <span class="text-sm text-gray-600">เลขที่บัญชี</span>
                        <p class="font-semibold text-gray-900">{{ $withdrawal->paymentMethod->account_number }}</p>
                    </div>
                    @endif
                    @if($withdrawal->paymentMethod->account_name)
                    <div>
                        <span class="text-sm text-gray-600">ชื่อบัญชี</span>
                        <p class="font-semibold text-gray-900">{{ $withdrawal->paymentMethod->account_name }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Notes -->
            @if($withdrawal->user_note)
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">หมายเหตุจากผู้ขอ</h3>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                    <p class="text-gray-900">{{ $withdrawal->user_note }}</p>
                </div>
            </div>
            @endif

            <!-- Rejection Info -->
            @if($withdrawal->status == 'rejected')
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-red-600 mb-4">เหตุผลในการปฏิเสธ</h3>
                <div class="bg-red-50 border-l-4 border-red-500 p-4">
                    <p class="text-gray-900 mb-2">{{ $withdrawal->rejection_reason }}</p>
                    @if($withdrawal->rejectedBy)
                    <p class="text-sm text-gray-600">ปฏิเสธโดย: {{ $withdrawal->rejectedBy->name }}</p>
                    <p class="text-xs text-gray-500">{{ $withdrawal->rejected_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Transfer Info -->
            @if($withdrawal->status == 'completed')
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-green-600 mb-4">ข้อมูลการโอนเงิน</h3>
                @if($withdrawal->transfer_slip)
                <div class="mb-4">
                    <span class="text-sm text-gray-600 block mb-2">สลิปการโอน</span>
                    <img src="{{ asset($withdrawal->transfer_slip) }}" alt="Transfer Slip" class="max-w-md rounded-lg shadow-md">
                </div>
                @endif
                @if($withdrawal->transfer_note)
                <div class="bg-green-50 border-l-4 border-green-500 p-4">
                    <p class="text-sm text-gray-600 mb-1">หมายเหตุจากแอดมิน</p>
                    <p class="text-gray-900">{{ $withdrawal->transfer_note }}</p>
                </div>
                @endif
                <p class="text-sm text-gray-600 mt-4">
                    โอนเรียบร้อยเมื่อ: {{ $withdrawal->transfer_completed_at->format('d/m/Y H:i') }}
                </p>
            </div>
            @endif
        </div>

        <!-- Sidebar Actions -->
        <div class="space-y-6">
            <!-- Timeline -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">ไทม์ไลน์</h3>
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-sm">📝</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">ส่งคำขอ</p>
                            <p class="text-xs text-gray-500">{{ $withdrawal->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    @if($withdrawal->approved_at)
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="text-sm">✅</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">อนุมัติ</p>
                            <p class="text-xs text-gray-500">{{ $withdrawal->approved_at->format('d/m/Y H:i') }}</p>
                            @if($withdrawal->approvedBy)
                            <p class="text-xs text-gray-600">โดย: {{ $withdrawal->approvedBy->name }}</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($withdrawal->transfer_completed_at)
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                            <span class="text-sm">✨</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">โอนเรียบร้อย</p>
                            <p class="text-xs text-gray-500">{{ $withdrawal->transfer_completed_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($withdrawal->rejected_at)
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <span class="text-sm">❌</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">ปฏิเสธ</p>
                            <p class="text-xs text-gray-500">{{ $withdrawal->rejected_at->format('d/m/Y H:i') }}</p>
                            @if($withdrawal->rejectedBy)
                            <p class="text-xs text-gray-600">โดย: {{ $withdrawal->rejectedBy->name }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            @if($withdrawal->isPending())
            <div class="bg-white rounded-xl shadow-lg p-6 space-y-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">การจัดการ</h3>

                <form action="{{ route('admin.withdrawals.approve', $withdrawal->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                        ✅ อนุมัติคำขอ
                    </button>
                </form>

                <button x-on:click="$dispatch('show-reject-modal')" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                    ❌ ปฏิเสธคำขอ
                </button>
            </div>
            @endif

            @if($withdrawal->isApproved())
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">ดำเนินการโอนเงิน</h3>
                <form action="{{ route('admin.withdrawals.complete', $withdrawal->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">อัปโหลดสลิปการโอน <span class="text-red-500">*</span></label>
                        <input type="file" name="transfer_slip" accept="image/*" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ</label>
                        <textarea name="transfer_note" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                        ✨ ยืนยันการโอนเงิน
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div x-data="{ showModal: false }"
     x-on:show-reject-modal.window="showModal = true"
     x-show="showModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" x-on:click="showModal = false"></div>

        <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">ปฏิเสธคำขอถอนเงิน</h3>
            <p class="text-sm text-gray-600 mb-4">รหัสคำขอ: {{ $withdrawal->request_id }}</p>

            <form action="{{ route('admin.withdrawals.reject', $withdrawal->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">เหตุผลในการปฏิเสธ <span class="text-red-500">*</span></label>
                    <textarea name="rejection_reason" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="กรุณาระบุเหตุผล..."></textarea>
                </div>

                <div class="flex space-x-3">
                    <button type="button" x-on:click="showModal = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg transition">
                        ยกเลิก
                    </button>
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        ยืนยันการปฏิเสธ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
