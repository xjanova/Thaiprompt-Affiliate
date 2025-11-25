{{--
    Admin - รายละเอียดข้อร้องเรียน
--}}
@extends('layouts.admin')

@section('title', 'ข้อร้องเรียน #' . $dispute->dispute_number)

@section('content')
<div class="space-y-6" x-data="{ showResolveModal: false }">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    ข้อร้องเรียน #{{ $dispute->dispute_number }}
                </h1>
                @switch($dispute->priority)
                    @case('critical')
                        <span class="px-3 py-1 text-sm font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full animate-pulse">
                            <i class="fas fa-fire mr-1"></i>วิกฤต
                        </span>
                        @break
                    @case('high')
                        <span class="px-3 py-1 text-sm font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded-full">
                            สูง
                        </span>
                        @break
                @endswitch
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                สร้างเมื่อ {{ $dispute->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.anti-abuse.disputes') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-left"></i>
                กลับ
            </a>
            @if($dispute->isOpen())
                <button @click="showResolveModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-gavel"></i>
                    ตัดสิน
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Dispute Details --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-file-alt text-purple-500 mr-2"></i>
                    รายละเอียดข้อร้องเรียน
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">หัวข้อ</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $dispute->title }}</p>
                    </div>

                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">ประเภท</label>
                        <p class="text-gray-900 dark:text-white">{{ $dispute->getDisputeTypeLabel() }}</p>
                    </div>

                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">รายละเอียด</label>
                        <div class="mt-1 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $dispute->description }}</p>
                        </div>
                    </div>

                    {{-- Evidence Files --}}
                    @if($dispute->evidence_files && count($dispute->evidence_files) > 0)
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">หลักฐาน ({{ count($dispute->evidence_files) }} ไฟล์)</label>
                            <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-3">
                                @foreach($dispute->evidence_files as $file)
                                    <a href="{{ asset('storage/' . $file) }}" target="_blank"
                                       class="block aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 hover:opacity-80 transition-opacity">
                                        <img src="{{ asset('storage/' . $file) }}" alt="หลักฐาน" class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Booking Info --}}
            @if($dispute->booking)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-calendar-check text-blue-500 mr-2"></i>
                        ข้อมูลการจอง
                    </h2>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="text-gray-500 dark:text-gray-400">หมายเลข</label>
                            <p class="font-mono text-gray-900 dark:text-white">{{ $dispute->booking->booking_number }}</p>
                        </div>
                        <div>
                            <label class="text-gray-500 dark:text-gray-400">บริการ</label>
                            <p class="text-gray-900 dark:text-white">{{ $dispute->booking->service?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-gray-500 dark:text-gray-400">สถานะ</label>
                            <p class="text-gray-900 dark:text-white">{{ $dispute->booking->getStatusLabel() ?? $dispute->booking->status }}</p>
                        </div>
                        <div>
                            <label class="text-gray-500 dark:text-gray-400">ยอดรวม</label>
                            <p class="font-bold text-purple-600">฿{{ number_format($dispute->booking->total_amount, 2) }}</p>
                        </div>
                    </div>

                    @if($dispute->booking->customer_latitude && $dispute->booking->customer_longitude)
                        <div class="mt-4">
                            <a href="{{ route('admin.anti-abuse.location-history', $dispute->booking) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                                <i class="fas fa-map-marked-alt"></i>
                                ดูประวัติตำแหน่ง GPS
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Resolution (if resolved) --}}
            @if($dispute->resolved_at)
                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl shadow-lg p-6 border border-green-200 dark:border-green-800">
                    <h2 class="text-lg font-bold text-green-800 dark:text-green-300 mb-4">
                        <i class="fas fa-gavel mr-2"></i>
                        ผลการตัดสิน
                    </h2>

                    <div class="space-y-3">
                        <div>
                            <label class="text-sm text-green-600 dark:text-green-400">ผลการตัดสิน</label>
                            <p class="text-lg font-semibold text-green-800 dark:text-green-200">{{ $dispute->getStatusLabel() }}</p>
                        </div>

                        @if($dispute->resolution_notes)
                            <div>
                                <label class="text-sm text-green-600 dark:text-green-400">หมายเหตุ</label>
                                <p class="text-green-800 dark:text-green-200">{{ $dispute->resolution_notes }}</p>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            @if($dispute->refund_amount > 0)
                                <div>
                                    <label class="text-sm text-green-600 dark:text-green-400">ยอดคืนเงิน</label>
                                    <p class="text-lg font-bold text-green-700 dark:text-green-300">฿{{ number_format($dispute->refund_amount, 2) }}</p>
                                </div>
                            @endif
                            @if($dispute->penalty_amount > 0)
                                <div>
                                    <label class="text-sm text-green-600 dark:text-green-400">ค่าปรับ</label>
                                    <p class="text-lg font-bold text-orange-600">฿{{ number_format($dispute->penalty_amount, 2) }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="text-sm text-green-600 dark:text-green-400">
                            ตัดสินโดย {{ $dispute->handler?->name ?? 'N/A' }} เมื่อ {{ $dispute->resolved_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">สถานะ</h3>

                <div class="flex items-center gap-3 mb-4">
                    @switch($dispute->status)
                        @case('pending')
                            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-full">
                                <i class="fas fa-clock text-2xl text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-yellow-600">รอตรวจสอบ</p>
                                <p class="text-sm text-gray-500">รอ Admin ตรวจสอบ</p>
                            </div>
                            @break
                        @case('investigating')
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                <i class="fas fa-search text-2xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-blue-600">กำลังสอบสวน</p>
                                <p class="text-sm text-gray-500">อยู่ระหว่างการสอบสวน</p>
                            </div>
                            @break
                        @case('resolved_favor_reporter')
                        @case('resolved_favor_accused')
                        @case('resolved_mutual')
                            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                                <i class="fas fa-check-circle text-2xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-green-600">แก้ไขแล้ว</p>
                                <p class="text-sm text-gray-500">ตัดสินเรียบร้อยแล้ว</p>
                            </div>
                            @break
                        @default
                            <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full">
                                <i class="fas fa-folder text-2xl text-gray-500"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-600">{{ $dispute->getStatusLabel() }}</p>
                            </div>
                    @endswitch
                </div>

                @if($dispute->isOpen())
                    <form action="{{ route('admin.anti-abuse.disputes.update-status', $dispute) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <select name="status" onchange="this.form.submit()"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">-- เปลี่ยนสถานะ --</option>
                            <option value="investigating">กำลังสอบสวน</option>
                            <option value="awaiting_response">รอคำชี้แจง</option>
                        </select>
                    </form>
                @endif
            </div>

            {{-- Reporter --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-user-edit text-blue-500 mr-2"></i>
                    ผู้ร้องเรียน
                </h3>

                @if($dispute->reporter_type === 'user' && $dispute->reporterUser)
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-cyan-400 flex items-center justify-center text-white font-bold text-lg">
                            {{ substr($dispute->reporterUser->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $dispute->reporterUser->name }}</p>
                            <p class="text-sm text-gray-500">ลูกค้า</p>
                        </div>
                    </div>
                    @if($reporterTrustScore)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Trust Score</span>
                            <span class="font-bold {{ $reporterTrustScore->trust_score >= 60 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($reporterTrustScore->trust_score, 0) }}
                            </span>
                        </div>
                    @endif
                @elseif($dispute->reporter_type === 'system')
                    <p class="text-gray-500">ระบบสร้างอัตโนมัติ</p>
                @endif
            </div>

            {{-- Accused --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-user-times text-red-500 mr-2"></i>
                    ผู้ถูกร้องเรียน
                </h3>

                @if($dispute->accused_type === 'user' && $dispute->accusedUser)
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-400 to-orange-400 flex items-center justify-center text-white font-bold text-lg">
                            {{ substr($dispute->accusedUser->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $dispute->accusedUser->name }}</p>
                            <p class="text-sm text-gray-500">ลูกค้า</p>
                        </div>
                    </div>
                @elseif($dispute->accused_type === 'provider' && $dispute->accusedProvider)
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-lg">
                            {{ substr($dispute->accusedProvider->display_name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $dispute->accusedProvider->display_name }}</p>
                            <p class="text-sm text-gray-500">ผู้ให้บริการ</p>
                        </div>
                    </div>
                @endif

                @if($accusedTrustScore)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg mb-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Trust Score</span>
                        <span class="font-bold {{ $accusedTrustScore->trust_score >= 60 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($accusedTrustScore->trust_score, 0) }}
                        </span>
                    </div>
                    <a href="{{ route('admin.anti-abuse.trust-scores.show', $accusedTrustScore) }}"
                       class="text-sm text-purple-600 hover:text-purple-700">
                        ดูประวัติ Trust Score <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Resolve Modal --}}
    <div x-show="showResolveModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showResolveModal = false"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-gavel text-purple-500 mr-2"></i>
                    ตัดสินข้อร้องเรียน
                </h3>

                <form action="{{ route('admin.anti-abuse.disputes.update-status', $dispute) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ผลการตัดสิน</label>
                            <select name="status" required
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="">-- เลือกผลการตัดสิน --</option>
                                <option value="resolved_favor_reporter">ตัดสินให้ผู้ร้องเรียน (ผู้ถูกร้องผิด)</option>
                                <option value="resolved_favor_accused">ตัดสินให้ผู้ถูกร้อง (ผู้ร้องกล่าวเท็จ)</option>
                                <option value="resolved_mutual">ยอมความ/ไกล่เกลี่ย</option>
                                <option value="dismissed">ยกฟ้อง (หลักฐานไม่เพียงพอ)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมายเหตุ</label>
                            <textarea name="resolution_notes" rows="3"
                                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                      placeholder="บันทึกเหตุผลการตัดสิน..."></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ยอดคืนเงิน (฿)</label>
                                <input type="number" name="refund_amount" step="0.01" min="0" value="0"
                                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค่าปรับ (฿)</label>
                                <input type="number" name="penalty_amount" step="0.01" min="0" value="0"
                                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="resulted_in_ban" value="1" id="resulted_in_ban"
                                   class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
                            <label for="resulted_in_ban" class="text-sm text-gray-700 dark:text-gray-300">
                                แบนผู้ถูกร้องเรียน
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3 justify-end">
                        <button type="button" @click="showResolveModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                            <i class="fas fa-gavel mr-2"></i>
                            ยืนยันการตัดสิน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
