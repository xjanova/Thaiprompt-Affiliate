@extends('layouts.app')

@section('title', 'MLM Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">MLM Dashboard</h1>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100">ระดับ MLM</p>
                    <p class="text-3xl font-bold mt-2">{{ $mlmStats['level'] ?? 1 }}</p>
                </div>
                <div class="bg-blue-400 bg-opacity-30 p-3 rounded-full">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100">สมาชิกโดยตรง</p>
                    <p class="text-3xl font-bold mt-2">{{ $mlmStats['direct_referrals'] ?? 0 }}</p>
                </div>
                <div class="bg-green-400 bg-opacity-30 p-3 rounded-full">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100">ทีมทั้งหมด</p>
                    <p class="text-3xl font-bold mt-2">{{ $mlmStats['total_team'] ?? 0 }}</p>
                </div>
                <div class="bg-purple-400 bg-opacity-30 p-3 rounded-full">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100">ค่าคอมมิชชั่น</p>
                    <p class="text-3xl font-bold mt-2">฿{{ number_format($mlmStats['total_commissions'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-yellow-400 bg-opacity-30 p-3 rounded-full">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Referral Link -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">ลิงก์แนะนำของคุณ</h2>
        <div class="flex items-center space-x-4">
            <input type="text" value="{{ route('register', ['code' => Auth::user()->referral_code]) }}" readonly
                id="referralLink"
                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
            <button onclick="copyReferralLink()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                คัดลอก
            </button>
            <button onclick="shareReferralLink()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                แชร์
            </button>
        </div>
        <p class="mt-2 text-sm text-gray-600">รหัสแนะนำ: <code class="bg-gray-100 px-2 py-1 rounded">{{ Auth::user()->referral_code }}</code></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Commissions -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">ค่าคอมมิชชั่นล่าสุด</h2>
                    <a href="{{ route('mlm.commissions') }}" class="text-sm text-indigo-600 hover:text-indigo-500">ดูทั้งหมด</a>
                </div>
            </div>
            <div class="p-6">
                @if(isset($recentCommissions) && $recentCommissions->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentCommissions as $commission)
                            <div class="flex justify-between items-center pb-4 border-b last:border-b-0">
                                <div>
                                    <p class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $commission->type)) }}</p>
                                    <p class="text-sm text-gray-500">{{ $commission->created_at->format('d M Y H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-green-600">+฿{{ number_format($commission->amount, 2) }}</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($commission->status === 'paid') bg-green-100 text-green-800
                                        @elseif($commission->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($commission->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">ยังไม่มีค่าคอมมิชชั่น</p>
                @endif
            </div>
        </div>

        <!-- Team Members -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">สมาชิกในทีม</h2>
                    <a href="{{ route('mlm.network') }}" class="text-sm text-indigo-600 hover:text-indigo-500">ดูโครงสร้าง</a>
                </div>
            </div>
            <div class="p-6">
                @if(isset($teamMembers) && $teamMembers->count() > 0)
                    <div class="space-y-4">
                        @foreach($teamMembers as $member)
                            <div class="flex items-center space-x-4 pb-4 border-b last:border-b-0">
                                <img src="{{ $member->avatar ?? asset('images/default-avatar.png') }}"
                                     alt="{{ $member->name }}"
                                     class="w-10 h-10 rounded-full">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ $member->name }}</p>
                                    <p class="text-sm text-gray-500">Level {{ $member->mlm_level ?? 1 }} • {{ $member->created_at->format('d M Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">{{ $member->referrals_count ?? 0 }} referrals</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">ยังไม่มีสมาชิกในทีม<br>เริ่มต้นแชร์ลิงก์แนะนำของคุณเลย!</p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyReferralLink() {
    const input = document.getElementById('referralLink');
    input.select();
    document.execCommand('copy');
    alert('คัดลอกลิงก์แนะนำแล้ว!');
}

function shareReferralLink() {
    const link = document.getElementById('referralLink').value;
    if (navigator.share) {
        navigator.share({
            title: 'สมัครสมาชิก {{ config("app.name") }}',
            text: 'สมัครผ่านลิงก์ของฉันและรับสิทธิพิเศษ!',
            url: link
        });
    } else {
        copyReferralLink();
    }
}
</script>
@endpush
@endsection
