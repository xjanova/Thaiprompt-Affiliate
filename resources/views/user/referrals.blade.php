@extends('layouts.user')

@section('title', 'ผู้แนะนำ')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900">ผู้แนะนำของฉัน</h1>
        <p class="text-sm text-gray-600 mt-1">ดูรายการผู้ที่คุณแนะนำทั้งหมด</p>
    </div>

    @if($affiliate)
        <!-- Referral Link Card -->
        <div class="bg-gradient-primary text-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">ลิงก์แนะนำของคุณ</h2>
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text"
                       value="{{ url('/register?ref=' . $affiliate->referral_code) }}"
                       readonly
                       id="referralLink"
                       class="flex-1 px-4 py-3 border-2 border-white/20 rounded-lg bg-white/10 text-white placeholder-white/60 text-sm">
                <button onclick="copyReferralLink()"
                        class="px-6 py-3 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-gray-100 transition whitespace-nowrap">
                    คัดลอก
                </button>
            </div>
            <p class="text-white/90 text-sm mt-3">รหัสแนะนำ: <strong>{{ $affiliate->referral_code }}</strong></p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">ผู้แนะนำทั้งหมด</p>
                        <p class="text-3xl font-bold text-indigo-600 mt-2">{{ $referrals->count() }}</p>
                    </div>
                    <div class="text-4xl">👥</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">รายได้จากการแนะนำ</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">
                            ฿{{ number_format($affiliate->total_earnings ?? 0, 0) }}
                        </p>
                    </div>
                    <div class="text-4xl">💰</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">ระดับ</p>
                        <p class="text-3xl font-bold text-purple-600 mt-2">{{ $affiliate->level ?? 1 }}</p>
                    </div>
                    <div class="text-4xl">⭐</div>
                </div>
            </div>
        </div>

        <!-- Referrals List -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">รายการผู้แนะนำ</h2>
            </div>

            @if($referrals->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ผู้ใช้
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    รหัสแนะนำ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ระดับ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ผู้แนะนำ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    รายได้รวม
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    สถานะ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    วันที่เข้าร่วม
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($referrals as $referral)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-gradient-primary flex items-center justify-center text-white font-bold text-sm">
                                                {{ strtoupper(substr($referral->user->name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900">{{ $referral->user->name ?? 'ไม่ระบุ' }}</p>
                                                <p class="text-xs text-gray-500">{{ $referral->user->email ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <code class="px-2 py-1 bg-gray-100 rounded text-xs">{{ $referral->referral_code }}</code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        Level {{ $referral->level }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $referral->children()->count() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                        ฿{{ number_format($referral->total_earnings ?? 0, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($referral->status === 'active') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $referral->status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $referral->created_at->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">👥</div>
                    <p class="text-gray-600 text-lg">ยังไม่มีผู้แนะนำ</p>
                    <p class="text-gray-500 text-sm mt-2">แชร์ลิงก์แนะนำของคุณเพื่อเริ่มสร้างทีม</p>
                </div>
            @endif
        </div>
    @else
        <!-- No Affiliate Account -->
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <div class="text-6xl mb-4">🚫</div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">ไม่มีบัญชี Affiliate</h2>
            <p class="text-gray-600 mb-6">คุณยังไม่มีบัญชี Affiliate กรุณาติดต่อผู้ดูแลระบบ</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
function copyReferralLink() {
    const input = document.getElementById('referralLink');
    input.select();
    input.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(input.value).then(() => {
        alert('คัดลอกลิงก์สำเร็จ!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}
</script>
@endpush
@endsection
