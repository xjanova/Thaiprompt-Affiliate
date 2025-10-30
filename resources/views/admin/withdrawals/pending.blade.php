@extends('layouts.admin')

@section('title', 'คำขอถอนเงินรอดำเนินการ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-br from-yellow-500 via-orange-500 to-red-500 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">คำขอถอนเงินรอดำเนินการ</h2>
                <p class="text-orange-100 text-sm">รายการที่รอการอนุมัติจากคุณ</p>
            </div>
            <div class="text-right">
                <div class="text-6xl font-bold">{{ $withdrawals->total() }}</div>
                <div class="text-sm text-orange-100 mt-2">รายการรอดำเนินการ</div>
            </div>
        </div>
    </div>

    <!-- Batch Actions -->
    @if($withdrawals->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6" x-data="{ selectedIds: [] }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800">การจัดการแบบกลุ่ม</h3>
            <span class="text-sm text-gray-600" x-text="selectedIds.length > 0 ? `เลือก ${selectedIds.length} รายการ` : 'ยังไม่ได้เลือกรายการใด'"></span>
        </div>

        <form action="{{ route('admin.withdrawals.batch-approve') }}" method="POST" x-show="selectedIds.length > 0" x-cloak>
            @csrf
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="withdrawal_ids[]" :value="id">
            </template>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                ✅ อนุมัติที่เลือก (<span x-text="selectedIds.length"></span> รายการ)
            </button>
        </form>

        <!-- Pending List -->
        <div class="mt-6 space-y-4">
            @foreach($withdrawals as $withdrawal)
            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition" x-data="{ selected: false }">
                <div class="flex items-start space-x-4">
                    <!-- Checkbox -->
                    <input type="checkbox"
                           x-bind:checked="selectedIds.includes({{ $withdrawal->id }})"
                           x-on:change="if($event.target.checked) { selectedIds.push({{ $withdrawal->id }}) } else { selectedIds = selectedIds.filter(id => id !== {{ $withdrawal->id }}) }"
                           class="mt-1 h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">

                    <!-- Request Info -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="text-lg font-bold text-gray-900">{{ $withdrawal->request_id }}</h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium">ผู้ขอ:</span> {{ $withdrawal->user->name }} ({{ $withdrawal->user->email }})
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-indigo-600">{{ number_format($withdrawal->amount, 2) }}</div>
                                <div class="text-xs text-gray-500">THB</div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">ค่าธรรมเนียม:</span>
                                <div class="font-medium text-red-600">{{ number_format($withdrawal->fee, 2) }}</div>
                            </div>
                            <div>
                                <span class="text-gray-500">ภาษี:</span>
                                <div class="font-medium text-red-600">{{ number_format($withdrawal->tax, 2) }}</div>
                            </div>
                            <div>
                                <span class="text-gray-500">จำนวนสุทธิ:</span>
                                <div class="font-medium text-green-600">{{ number_format($withdrawal->net_amount, 2) }}</div>
                            </div>
                            <div>
                                <span class="text-gray-500">ช่องทาง:</span>
                                <div class="font-medium">
                                    @if($withdrawal->paymentMethod)
                                        {{ $withdrawal->paymentMethod->type_label }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($withdrawal->paymentMethod)
                        <div class="mt-4 bg-gray-50 rounded-lg p-4">
                            <h5 class="text-sm font-bold text-gray-700 mb-2">ข้อมูลบัญชีปลายทาง</h5>
                            <div class="text-sm space-y-1">
                                @if($withdrawal->paymentMethod->bank_name)
                                    <p><span class="text-gray-600">ธนาคาร:</span> {{ $withdrawal->paymentMethod->bank_name }}</p>
                                @endif
                                @if($withdrawal->paymentMethod->account_number)
                                    <p><span class="text-gray-600">เลขที่บัญชี:</span> {{ $withdrawal->paymentMethod->masked_account_number }}</p>
                                @endif
                                @if($withdrawal->paymentMethod->account_name)
                                    <p><span class="text-gray-600">ชื่อบัญชี:</span> {{ $withdrawal->paymentMethod->account_name }}</p>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if($withdrawal->user_note)
                        <div class="mt-4 bg-blue-50 border-l-4 border-blue-500 p-3">
                            <p class="text-sm"><span class="font-bold">หมายเหตุ:</span> {{ $withdrawal->user_note }}</p>
                        </div>
                        @endif

                        <div class="mt-4 text-xs text-gray-500">
                            <span>วันที่ขอ: {{ $withdrawal->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 pt-4 border-t border-gray-200 flex space-x-3">
                    <a href="{{ route('admin.withdrawals.show', $withdrawal->id) }}" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg text-center transition">
                        📋 ดูรายละเอียด
                    </a>
                    <form action="{{ route('admin.withdrawals.approve', $withdrawal->id) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                            ✅ อนุมัติ
                        </button>
                    </form>
                    <button x-on:click="$dispatch('show-reject-modal', { id: {{ $withdrawal->id }}, requestId: '{{ $withdrawal->request_id }}' })" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        ❌ ปฏิเสธ
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
        <div class="text-6xl mb-4">🎉</div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">ยินดีด้วย!</h3>
        <p class="text-gray-600">ไม่มีคำขอถอนเงินรอดำเนินการ</p>
        <a href="{{ route('admin.withdrawals.index') }}" class="inline-block mt-6 text-indigo-600 hover:text-indigo-800 font-semibold">
            ← กลับไปหน้ารายการทั้งหมด
        </a>
    </div>
    @endif

    <!-- Pagination -->
    @if($withdrawals->hasPages())
    <div class="bg-white rounded-xl shadow-lg p-6">
        {{ $withdrawals->links() }}
    </div>
    @endif
</div>

<!-- Reject Modal -->
<div x-data="{ showModal: false, withdrawalId: null, requestId: '' }"
     x-on:show-reject-modal.window="showModal = true; withdrawalId = $event.detail.id; requestId = $event.detail.requestId"
     x-show="showModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" x-on:click="showModal = false"></div>

        <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">ปฏิเสธคำขอถอนเงิน</h3>
            <p class="text-sm text-gray-600 mb-4">รหัสคำขอ: <span class="font-bold" x-text="requestId"></span></p>

            <form x-bind:action="`{{ route('admin.withdrawals.index') }}/${withdrawalId}/reject`" method="POST">
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
