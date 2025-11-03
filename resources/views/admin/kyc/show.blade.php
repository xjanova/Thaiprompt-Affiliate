@extends('layouts.admin')

@section('title', 'ตรวจสอบการยืนยันตัวตน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.kyc.index') }}"
           class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตรวจสอบการยืนยันตัวตน</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">ตรวจสอบและอนุมัติ/ปฏิเสธการยืนยันตัวตน</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- User Info Card -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-user mr-2"></i>ข้อมูลผู้ใช้
            </h2>

            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-gradient-primary flex items-center justify-center text-white text-xl font-bold">
                        {{ strtoupper(substr($kycVerification->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $kycVerification->user->name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $kycVerification->user->email }}</div>
                    </div>
                </div>

                <div class="border-t pt-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">User ID:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $kycVerification->user->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">บทบาท:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($kycVerification->user->role) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">วันที่สมัคร:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $kycVerification->user->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KYC Status Card -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-clipboard-check mr-2"></i>สถานะการยืนยัน
            </h2>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">สถานะ:</span>
                    @if($kycVerification->status === 'pending')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>รอตรวจสอบ
                        </span>
                    @elseif($kycVerification->status === 'approved')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>อนุมัติแล้ว
                        </span>
                    @elseif($kycVerification->status === 'rejected')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>ปฏิเสธ
                        </span>
                    @endif
                </div>

                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">วันที่ส่ง:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $kycVerification->submitted_at ? $kycVerification->submitted_at->format('d/m/Y H:i') : '-' }}
                    </span>
                </div>

                @if($kycVerification->reviewed_at)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">วันที่ตรวจสอบ:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $kycVerification->reviewed_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                @endif

                @if($kycVerification->reviewer)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ตรวจสอบโดย:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $kycVerification->reviewer->name }}
                        </span>
                    </div>
                @endif

                @if($kycVerification->status === 'rejected' && $kycVerification->rejection_reason)
                    <div class="border-t pt-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">เหตุผลในการปฏิเสธ:</p>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                            <p class="text-sm text-red-800 dark:text-red-300">{{ $kycVerification->rejection_reason }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Documents Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-images mr-2"></i>เอกสารที่ส่ง
        </h2>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- ID Card Image -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fas fa-id-card mr-1"></i>รูปบัตรประชาชน
                </h3>
                <div class="border dark:border-slate-700 rounded-lg overflow-hidden">
                    <img src="{{ asset('storage/' . $kycVerification->id_card_image) }}"
                         alt="ID Card"
                         class="w-full h-auto cursor-pointer hover:opacity-90 transition"
                         onclick="openImageModal(this.src)">
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                    <i class="fas fa-search-plus mr-1"></i>คลิกเพื่อขยาย
                </p>
            </div>

            <!-- Selfie Image -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fas fa-user-circle mr-1"></i>รูปถ่ายตัวเองพร้อมบัตรประชาชน
                </h3>
                <div class="border dark:border-slate-700 rounded-lg overflow-hidden">
                    <img src="{{ asset('storage/' . $kycVerification->selfie_image) }}"
                         alt="Selfie with ID Card"
                         class="w-full h-auto cursor-pointer hover:opacity-90 transition"
                         onclick="openImageModal(this.src)">
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                    <i class="fas fa-search-plus mr-1"></i>คลิกเพื่อขยาย
                </p>
            </div>
        </div>
    </div>

    <!-- Actions Card -->
    @if($kycVerification->status === 'pending')
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-gavel mr-2"></i>การดำเนินการ
            </h2>

            <div class="grid md:grid-cols-2 gap-4">
                <!-- Approve Form -->
                <form action="{{ route('admin.kyc.approve', $kycVerification) }}" method="POST"
                      onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการอนุมัติการยืนยันตัวตนนี้?')">
                    @csrf
                    <button type="submit"
                            class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span>อนุมัติการยืนยันตัวตน</span>
                    </button>
                </form>

                <!-- Reject Button (opens modal) -->
                <button type="button"
                        onclick="openRejectModal()"
                        class="w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-times-circle"></i>
                    <span>ปฏิเสธการยืนยันตัวตน</span>
                </button>
            </div>
        </div>
    @endif

    <!-- Delete Form (for admin with manage_kyc permission) -->
    @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_kyc'))
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-red-600 mb-4">
                <i class="fas fa-trash mr-2"></i>ลบข้อมูล
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">การลบข้อมูลการยืนยันตัวตนนี้จะไม่สามารถกู้คืนได้</p>
            <form action="{{ route('admin.kyc.destroy', $kycVerification) }}" method="POST"
                  onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลการยืนยันตัวตนนี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-trash mr-2"></i>ลบข้อมูลการยืนยันตัวตน
                </button>
            </form>
        </div>
    @endif
</div>

<!-- Reject Modal -->
<div id="rejectModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4"
     onclick="if(event.target === this) closeRejectModal()">
    <div class="bg-white dark:bg-slate-800 rounded-lg max-w-md w-full p-6" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">ปฏิเสธการยืนยันตัวตน</h3>
            <button onclick="closeRejectModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form action="{{ route('admin.kyc.reject', $kycVerification) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="rejection_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    เหตุผลในการปฏิเสธ <span class="text-red-500">*</span>
                </label>
                <textarea name="rejection_reason"
                          id="rejection_reason"
                          rows="4"
                          required
                          placeholder="กรุณาระบุเหตุผลในการปฏิเสธ..."
                          class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-slate-700 dark:text-white"></textarea>
                @error('rejection_reason')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="button"
                        onclick="closeRejectModal()"
                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    ปฏิเสธ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal"
     class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden items-center justify-center p-4"
     onclick="closeImageModal()">
    <div class="relative max-w-6xl max-h-full">
        <button onclick="closeImageModal()"
                class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10 bg-black bg-opacity-50 w-10 h-10 rounded-full flex items-center justify-center">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" alt="Full Size" class="max-w-full max-h-screen rounded-lg">
    </div>
</div>

@push('scripts')
<script>
function openRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

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
