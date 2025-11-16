@extends('layouts.admin-v3')

@section('title', 'แก้ไข Affiliate - ' . $affiliate->user->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.affiliates.show', $affiliate) }}" style="color: var(--color-theme-primary);" onmouseover="this.style.color='var(--color-theme-hover)'" onmouseout="this.style.color='var(--color-theme-primary)'">
        ← กลับไปรายละเอียด
    </a>
</div>

<div class="max-w-2xl mx-auto" x-data="affiliateEdit()">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">แก้ไข Affiliate</h2>

        <!-- Affiliate Info (Read-only) -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <h3 class="text-sm font-medium text-gray-600 mb-3">ข้อมูล Affiliate</h3>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600">ชื่อ:</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $affiliate->user->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600">อีเมล:</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $affiliate->user->email }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600">รหัสแนะนำ:</dt>
                    <dd class="text-sm font-mono font-medium text-gray-900">{{ $affiliate->referral_code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600">Total Referrals:</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $affiliate->total_referrals }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600">Total Earnings:</dt>
                    <dd class="text-sm font-medium text-green-600">{{ number_format($affiliate->total_earnings, 2) }}฿</dd>
                </div>
            </dl>
        </div>

        <!-- Edit Form -->
        <form action="{{ route('admin.affiliates.update', $affiliate) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    สถานะ <span class="text-red-500">*</span>
                </label>
                <select id="status"
                        name="status"
                        required
                        x-model="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 @error('status') border-red-500 @enderror">
                    <option value="active" {{ $affiliate->status === 'active' ? 'selected' : '' }}>
                        Active - ใช้งานปกติ
                    </option>
                    <option value="inactive" {{ $affiliate->status === 'inactive' ? 'selected' : '' }}>
                        Inactive - ไม่ได้ใช้งาน
                    </option>
                    <option value="suspended" {{ $affiliate->status === 'suspended' ? 'selected' : '' }}>
                        Suspended - ถูกระงับ
                    </option>
                </select>

                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <p class="mt-2 text-sm text-gray-500">
                    <strong>คำอธิบาย:</strong><br>
                    • <strong>Active:</strong> Affiliate สามารถใช้งานได้ปกติ และได้รับคอมมิชชั่น<br>
                    • <strong>Inactive:</strong> ไม่ได้ใช้งาน แต่ยังคงข้อมูลไว้<br>
                    • <strong>Suspended:</strong> ถูกระงับการใช้งาน ไม่ได้รับคอมมิชชั่น
                </p>
            </div>

            <!-- Warning for Suspended -->
            <div x-show="status === 'suspended'" x-cloak class="p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>คำเตือน:</strong> การระงับ (Suspend) affiliate จะทำให้:
                        </p>
                        <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                            <li>รหัสแนะนำจะไม่สามารถใช้งานได้</li>
                            <li>จะไม่ได้รับคอมมิชชั่นใหม่</li>
                            <li>Downline อาจได้รับผลกระทบ</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <a href="{{ route('admin.affiliates.show', $affiliate) }}"
                   class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    ยกเลิก
                </a>

                <button type="submit"
                        class="px-6 py-2 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style="background-color: var(--color-theme-primary);"
                        onmouseover="this.style.backgroundColor='var(--color-theme-hover)'"
                        onmouseout="this.style.backgroundColor='var(--color-theme-primary)'">
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>

        <!-- Danger Zone -->
        <div class="mt-8 pt-6 border-t border-red-200">
            <h3 class="text-lg font-semibold text-red-600 mb-4">Danger Zone</h3>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-medium text-red-900">ลบ Affiliate</h4>
                        <p class="mt-1 text-sm text-red-700">
                            การลบ affiliate จะไม่สามารถกู้คืนได้ โปรดใช้ความระมัดระวัง
                        </p>
                    </div>
                    <form action="{{ route('admin.affiliates.destroy', $affiliate) }}"
                          method="POST"
                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบ affiliate นี้? การกระทำนี้ไม่สามารถย้อนกลับได้!')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2">
                            ลบ Affiliate
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function affiliateEdit() {
    return {
        status: '{{ $affiliate->status }}',

        init() {
            // Component initialized
        }
    }
}
</script>
@endpush
@endsection
