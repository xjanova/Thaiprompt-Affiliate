@extends('layouts.user')

@section('title', 'แดชบอร์ด')

@section('content')
<div class="space-y-4 pb-20 lg:pb-6">
    <!-- Welcome Card -->
    <div class="bg-gradient-primary text-white rounded-xl shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-2">สวัสดี, {{ $user->name }}!</h1>
        <p class="opacity-90">ยินดีต้อนรับสู่ระบบ Affiliate ของคุณ</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Total Earnings -->
        <div class="bg-white rounded-xl shadow-md p-6 transform transition hover:scale-105">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">รายได้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">
                        ฿{{ number_format($totalEarnings, 2) }}
                    </p>
                </div>
                <div class="text-5xl">💰</div>
            </div>
            <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500">ยอดที่ได้รับอนุมัติแล้ว</p>
            </div>
        </div>

        <!-- Pending Earnings -->
        <div class="bg-white rounded-xl shadow-md p-6 transform transition hover:scale-105">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">รอการอนุมัติ</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">
                        ฿{{ number_format($pendingEarnings, 2) }}
                    </p>
                </div>
                <div class="text-5xl">⏳</div>
            </div>
            <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500">รอการตรวจสอบ</p>
            </div>
        </div>

        <!-- Total Referrals -->
        <div class="bg-white rounded-xl shadow-md p-6 transform transition hover:scale-105">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">ผู้แนะนำทั้งหมด</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-2">
                        {{ $totalReferrals }}
                    </p>
                </div>
                <div class="text-5xl">👥</div>
            </div>
            <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500">จำนวนคนที่แนะนำ</p>
            </div>
        </div>
    </div>

    <!-- Referral Link -->
    @if($affiliate)
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">ลิงก์แนะนำของคุณ</h2>
        <div class="flex flex-col sm:flex-row gap-3">
            <input type="text"
                   value="{{ url('/register?ref=' . $affiliate->referral_code) }}"
                   readonly
                   id="referralLink"
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-sm">
            <button onclick="copyReferralLink()"
                    class="px-6 py-3 bg-gradient-primary text-white font-semibold rounded-lg hover:opacity-90 transition whitespace-nowrap">
                คัดลอก
            </button>
        </div>
        <p class="text-sm text-gray-600 mt-3">แชร์ลิงก์นี้กับเพื่อนของคุณเพื่อรับคอมมิชชั่น</p>
    </div>
    @endif

    <!-- Recent Commissions -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">คอมมิชชั่นล่าสุด</h2>
            <a href="{{ route('user.commissions') }}" class="text-indigo-600 text-sm hover:underline">
                ดูทั้งหมด →
            </a>
        </div>

        @if($commissions->count() > 0)
            <div class="space-y-3">
                @foreach($commissions as $commission)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-800">฿{{ number_format($commission->amount, 2) }}</p>
                            <p class="text-sm text-gray-600">{{ $commission->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            @if($commission->status === 'approved') bg-green-100 text-green-800
                            @elseif($commission->status === 'pending') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            @if($commission->status === 'approved') อนุมัติแล้ว
                            @elseif($commission->status === 'pending') รอดำเนินการ
                            @else ปฏิเสธ
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <p class="text-4xl mb-3">📊</p>
                <p>ยังไม่มีคอมมิชชั่น</p>
            </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('user.referrals') }}" class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition">
            <div class="text-4xl mb-2">👥</div>
            <p class="text-sm font-medium text-gray-700">ผู้แนะนำ</p>
        </a>

        <a href="{{ route('user.commissions') }}" class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition">
            <div class="text-4xl mb-2">💰</div>
            <p class="text-sm font-medium text-gray-700">คอมมิชชั่น</p>
        </a>

        <a href="{{ route('user.profile') }}" class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition">
            <div class="text-4xl mb-2">👤</div>
            <p class="text-sm font-medium text-gray-700">โปรไฟล์</p>
        </a>

        <a href="#" class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition">
            <div class="text-4xl mb-2">📈</div>
            <p class="text-sm font-medium text-gray-700">รายงาน</p>
        </a>
    </div>
</div>

@push('scripts')
<script>
function copyReferralLink() {
    const input = document.getElementById('referralLink');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices

    navigator.clipboard.writeText(input.value).then(() => {
        // Show success message
        alert('คัดลอกลิงก์สำเร็จ!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}
</script>
@endpush
@endsection
