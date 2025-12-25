@extends('layouts.admin')

@section('title', 'รายละเอียดนักพัฒนา - ' . $developer->display_name)

@section('content')
<div class="container-fluid px-6 py-8" x-data="{
    showApproveModal: false,
    showRejectModal: false,
    showSuspendModal: false,
    rejectReason: '',
    suspendReason: ''
}">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <a href="{{ route('admin.developers.index') }}"
               class="text-blue-600 dark:text-blue-400 hover:underline mb-2 inline-block">
                ← กลับไปรายการนักพัฒนา
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                รายละเอียดนักพัฒนา
            </h1>
        </div>

        {{-- Action Buttons --}}
        @if($developer->status === 'pending')
            <div class="flex gap-3">
                <button @click="showApproveModal = true"
                        class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition shadow-lg">
                    ✅ อนุมัติ
                </button>
                <button @click="showRejectModal = true"
                        class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition shadow-lg">
                    ❌ ปฏิเสธ
                </button>
            </div>
        @elseif($developer->status === 'approved')
            <button @click="showSuspendModal = true"
                    class="px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition shadow-lg">
                ⏸️ ระงับบัญชี
            </button>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">ข้อมูลพื้นฐาน</h2>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-center gap-4">
                        @if($developer->avatar_url)
                            <img src="{{ $developer->avatar_url }}"
                                 alt="{{ $developer->display_name }}"
                                 class="w-24 h-24 rounded-full object-cover shadow-lg">
                        @else
                            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                                {{ strtoupper(substr($developer->display_name, 0, 1)) }}
                            </div>
                        @endif

                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $developer->display_name }}
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">{{ $developer->user->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                สมัครเมื่อ {{ $developer->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div class="border-l-4 border-blue-500 pl-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">อีเมล</div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $developer->email }}</div>
                        </div>

                        @if($developer->phone)
                            <div class="border-l-4 border-green-500 pl-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">เบอร์โทร</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $developer->phone }}</div>
                            </div>
                        @endif

                        @if($developer->line_id)
                            <div class="border-l-4 border-green-500 pl-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">LINE ID</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $developer->line_id }}</div>
                            </div>
                        @endif

                        @if($developer->facebook_profile)
                            <div class="border-l-4 border-blue-600 pl-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Facebook</div>
                                <a href="{{ $developer->facebook_profile }}"
                                   target="_blank"
                                   class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                    ดูโปรไฟล์ →
                                </a>
                            </div>
                        @endif
                    </div>

                    @if($developer->bio)
                        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">ประวัติ/ผลงาน</div>
                            <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ $developer->bio }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Company/Business Info --}}
            @if($developer->company_name || $developer->company_address || $developer->tax_id)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white">ข้อมูลบริษัท/ธุรกิจ</h2>
                    </div>

                    <div class="p-6 space-y-4">
                        @if($developer->company_name)
                            <div class="border-l-4 border-purple-500 pl-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">ชื่อบริษัท</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $developer->company_name }}</div>
                            </div>
                        @endif

                        @if($developer->tax_id)
                            <div class="border-l-4 border-purple-500 pl-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">เลขประจำตัวผู้เสียภาษี</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $developer->tax_id }}</div>
                            </div>
                        @endif

                        @if($developer->company_address)
                            <div class="border-l-4 border-purple-500 pl-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">ที่อยู่บริษัท</div>
                                <div class="font-medium text-gray-900 dark:text-white whitespace-pre-wrap">{{ $developer->company_address }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- KYC Documents --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 to-orange-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">เอกสาร KYC</h2>
                </div>

                <div class="p-6">
                    @if($developer->id_card_image)
                        <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">บัตรประชาชน/Passport</div>
                            <img src="{{ Storage::url($developer->id_card_image) }}"
                                 alt="ID Card"
                                 class="max-w-full h-auto rounded shadow-lg cursor-pointer"
                                 onclick="window.open('{{ Storage::url($developer->id_card_image) }}', '_blank')">
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">คลิกเพื่อดูรูปขนาดเต็ม</p>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            ไม่มีเอกสาร KYC
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">สถานะ</h3>

                @php
                    $statusConfig = [
                        'pending' => [
                            'class' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 border-yellow-500',
                            'icon' => '⏳',
                            'text' => 'รออนุมัติ'
                        ],
                        'approved' => [
                            'class' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-500',
                            'icon' => '✅',
                            'text' => 'อนุมัติแล้ว'
                        ],
                        'rejected' => [
                            'class' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-500',
                            'icon' => '❌',
                            'text' => 'ปฏิเสธ'
                        ],
                        'suspended' => [
                            'class' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300 border-gray-500',
                            'icon' => '⏸️',
                            'text' => 'ระงับ'
                        ],
                    ];
                    $config = $statusConfig[$developer->status] ?? $statusConfig['pending'];
                @endphp

                <div class="border-l-4 {{ $config['class'] }} p-4 rounded">
                    <div class="text-3xl mb-2">{{ $config['icon'] }}</div>
                    <div class="font-bold text-lg">{{ $config['text'] }}</div>
                </div>

                @if($developer->approved_at)
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        อนุมัติเมื่อ: {{ $developer->approved_at->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>

            {{-- Commission Rate --}}
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg shadow-lg p-6 text-white">
                <h3 class="font-bold mb-2">อัตราค่าคอมมิชชั่น</h3>
                <div class="text-4xl font-bold">{{ number_format($developer->commission_rate, 2) }}%</div>
                <p class="text-sm opacity-90 mt-2">นักพัฒนาได้รับจากการขาย</p>
            </div>

            {{-- Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">สถิติ</h3>

                <div class="space-y-3">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">จำนวนสินค้า</span>
                        <span class="font-bold text-gray-900 dark:text-white">0</span>
                    </div>
                    <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">ยอดขายรวม</span>
                        <span class="font-bold text-gray-900 dark:text-white">฿0.00</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">รายได้รวม</span>
                        <span class="font-bold text-green-600 dark:text-green-400">฿0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Approve Modal --}}
    <div x-show="showApproveModal"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showApproveModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full mx-4">
            <div class="bg-green-600 px-6 py-4 rounded-t-lg">
                <h3 class="text-xl font-bold text-white">✅ ยืนยันการอนุมัติ</h3>
            </div>

            <form method="POST" action="{{ route('admin.developers.approve', $developer) }}" class="p-6">
                @csrf
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    คุณต้องการอนุมัติบัญชีนักพัฒนาของ <strong>{{ $developer->display_name }}</strong> ใช่หรือไม่?
                </p>

                <div class="flex justify-end gap-3">
                    <button type="button"
                            @click="showApproveModal = false"
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition">
                        ยืนยันการอนุมัติ
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div x-show="showRejectModal"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showRejectModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full mx-4">
            <div class="bg-red-600 px-6 py-4 rounded-t-lg">
                <h3 class="text-xl font-bold text-white">❌ ยืนยันการปฏิเสธ</h3>
            </div>

            <form method="POST" action="{{ route('admin.developers.reject', $developer) }}" class="p-6">
                @csrf
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    คุณต้องการปฏิเสธบัญชีนักพัฒนาของ <strong>{{ $developer->display_name }}</strong> ใช่หรือไม่?
                </p>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เหตุผลในการปฏิเสธ
                    </label>
                    <textarea name="reason"
                              x-model="rejectReason"
                              rows="4"
                              required
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-red-500"
                              placeholder="กรุณาระบุเหตุผล..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button"
                            @click="showRejectModal = false"
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded transition">
                        ยืนยันการปฏิเสธ
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Suspend Modal --}}
    <div x-show="showSuspendModal"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showSuspendModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full mx-4">
            <div class="bg-yellow-600 px-6 py-4 rounded-t-lg">
                <h3 class="text-xl font-bold text-white">⏸️ ยืนยันการระงับบัญชี</h3>
            </div>

            <form method="POST" action="{{ route('admin.developers.suspend', $developer) }}" class="p-6">
                @csrf
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    คุณต้องการระงับบัญชีนักพัฒนาของ <strong>{{ $developer->display_name }}</strong> ใช่หรือไม่?
                </p>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เหตุผลในการระงับ
                    </label>
                    <textarea name="reason"
                              x-model="suspendReason"
                              rows="4"
                              required
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-yellow-500"
                              placeholder="กรุณาระบุเหตุผล..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button"
                            @click="showSuspendModal = false"
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded transition">
                        ยืนยันการระงับ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
