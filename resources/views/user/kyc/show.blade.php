@extends('layouts.user')

@section('title', 'รายละเอียดการยืนยันตัวตน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('user.kyc.index') }}"
               class="text-gray-600 hover:text-gray-900 transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">รายละเอียดการยืนยันตัวตน</h1>
                <p class="text-sm text-gray-600 mt-1">ตรวจสอบสถานะและรายละเอียดการยืนยันตัวตน</p>
            </div>
        </div>
    </div>

    <!-- Status Card -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">สถานะการยืนยันตัวตน</h2>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">สถานะ:</span>
                @if($kycVerification->status === 'pending')
                    <span class="inline-block px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                        <i class="fas fa-clock mr-1"></i>รอการตรวจสอบ
                    </span>
                @elseif($kycVerification->status === 'approved')
                    <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                        <i class="fas fa-check-circle mr-1"></i>อนุมัติแล้ว
                    </span>
                @elseif($kycVerification->status === 'rejected')
                    <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                        <i class="fas fa-times-circle mr-1"></i>ไม่ผ่านการตรวจสอบ
                    </span>
                @endif
            </div>

            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">วันที่ส่ง:</span>
                <span class="text-sm font-medium text-gray-900">
                    {{ $kycVerification->submitted_at ? $kycVerification->submitted_at->format('d/m/Y H:i') : '-' }}
                </span>
            </div>

            @if($kycVerification->reviewed_at)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">วันที่ตรวจสอบ:</span>
                    <span class="text-sm font-medium text-gray-900">
                        {{ $kycVerification->reviewed_at->format('d/m/Y H:i') }}
                    </span>
                </div>
            @endif

            @if($kycVerification->reviewer)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">ตรวจสอบโดย:</span>
                    <span class="text-sm font-medium text-gray-900">
                        {{ $kycVerification->reviewer->name }}
                    </span>
                </div>
            @endif

            @if($kycVerification->status === 'rejected' && $kycVerification->rejection_reason)
                <div class="pt-4 border-t">
                    <p class="text-sm text-gray-600 mb-2">เหตุผลในการปฏิเสธ:</p>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-sm text-red-800">{{ $kycVerification->rejection_reason }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Documents Card -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">เอกสารที่ส่ง</h2>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- ID Card Image -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-id-card mr-1"></i>รูปบัตรประชาชน
                </h3>
                <div class="border rounded-lg overflow-hidden">
                    <img src="{{ asset('storage/' . $kycVerification->id_card_image) }}"
                         alt="ID Card"
                         class="w-full h-auto cursor-pointer hover:opacity-90 transition"
                         onclick="openImageModal(this.src)">
                </div>
            </div>

            <!-- Selfie Image -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-user-circle mr-1"></i>รูปถ่ายตัวเองพร้อมบัตรประชาชน
                </h3>
                <div class="border rounded-lg overflow-hidden">
                    <img src="{{ asset('storage/' . $kycVerification->selfie_image) }}"
                         alt="Selfie with ID Card"
                         class="w-full h-auto cursor-pointer hover:opacity-90 transition"
                         onclick="openImageModal(this.src)">
                </div>
            </div>
        </div>

        <p class="text-xs text-gray-500 mt-4 text-center">
            <i class="fas fa-info-circle mr-1"></i>คลิกที่รูปภาพเพื่อดูขนาดเต็म
        </p>
    </div>

    @if($kycVerification->status === 'rejected')
        <div class="bg-white rounded-xl shadow-md p-6 text-center">
            <p class="text-gray-600 mb-4">หากคุณต้องการส่งเอกสารใหม่ กรุณากดปุ่มด้านล่าง</p>
            <a href="{{ route('user.kyc.create') }}"
               class="inline-flex items-center px-6 py-3 bg-gradient-primary text-white rounded-lg hover:opacity-90 transition">
                <i class="fas fa-redo mr-2"></i>
                ส่งเอกสารใหม่
            </a>
        </div>
    @endif
</div>

<!-- Image Modal -->
<div id="imageModal"
     class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden items-center justify-center p-4"
     onclick="closeImageModal()">
    <div class="relative max-w-4xl max-h-full">
        <button onclick="closeImageModal()"
                class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" alt="Full Size" class="max-w-full max-h-screen rounded-lg">
    </div>
</div>

@push('scripts')
<script>
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    const modal = document.getElementById('imageModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush
@endsection
