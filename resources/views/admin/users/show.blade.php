@extends('layouts.admin')

@section('title', 'รายละเอียดผู้ใช้ - ' . $user->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-900">
        ← กลับไปรายการผู้ใช้
    </a>
</div>

<!-- User Header Card -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="ml-4">
                <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                <p class="text-gray-600">{{ $user->email }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            @if($user->affiliate)
                <a href="{{ route('admin.affiliates.show', $user->affiliate) }}"
                   class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    ดู Affiliate
                </a>
            @endif
            <a href="{{ route('admin.users.edit', $user) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                แก้ไข
            </a>
        </div>
    </div>

    <!-- Role Badge -->
    <div class="flex gap-2 mb-4">
        @if($user->is_super_admin)
            <span class="px-3 py-1 text-sm font-semibold bg-purple-100 text-purple-800 rounded-full">
                🔐 Super Admin
            </span>
        @endif
        <span class="px-3 py-1 text-sm font-semibold rounded-full
            @if($user->role === 'admin') bg-blue-100 text-blue-800
            @elseif($user->role === 'super_admin') bg-purple-100 text-purple-800
            @else bg-gray-100 text-gray-800
            @endif">
            {{ ucfirst($user->role ?? 'user') }}
        </span>
        @if($user->email_verified_at)
            <span class="px-3 py-1 text-sm font-semibold bg-green-100 text-green-800 rounded-full">
                ✓ Email Verified
            </span>
        @else
            <span class="px-3 py-1 text-sm font-semibold bg-yellow-100 text-yellow-800 rounded-full">
                ⚠ Email Not Verified
            </span>
        @endif
    </div>
</div>

<!-- Details Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- User Information -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลผู้ใช้</h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-600">ชื่อ</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->name }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600">อีเมล</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600">Role</dt>
                <dd class="mt-1">
                    <span class="px-2 py-1 text-xs font-medium rounded
                        @if($user->role === 'admin') bg-blue-100 text-blue-800
                        @elseif($user->role === 'super_admin') bg-purple-100 text-purple-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($user->role ?? 'user') }}
                    </span>
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600">สมัครเมื่อ</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600">อัพเดทล่าสุด</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->updated_at->format('d/m/Y H:i') }}</dd>
            </div>

            @if($user->email_verified_at)
                <div>
                    <dt class="text-sm font-medium text-gray-600">ยืนยันอีเมลเมื่อ</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->email_verified_at->format('d/m/Y H:i') }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <!-- Affiliate Information -->
    @if($user->affiliate)
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูล Affiliate</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-600">รหัสแนะนำ</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-gray-100 rounded text-sm font-mono">
                            {{ $user->affiliate->referral_code }}
                        </code>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600">ระดับ</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->affiliate->level }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600">Total Referrals</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->affiliate->total_referrals }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600">Total Earnings</dt>
                    <dd class="mt-1 text-lg font-bold text-green-600">
                        {{ number_format($user->affiliate->total_earnings, 2) }}฿
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600">สถานะ</dt>
                    <dd class="mt-1">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($user->affiliate->status === 'active') bg-green-100 text-green-800
                            @elseif($user->affiliate->status === 'inactive') bg-gray-100 text-gray-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($user->affiliate->status) }}
                        </span>
                    </dd>
                </div>

                <div class="pt-4 border-t">
                    <a href="{{ route('admin.affiliates.show', $user->affiliate) }}"
                       class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                        ดูรายละเอียด Affiliate →
                    </a>
                </div>
            </dl>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูล Affiliate</h3>
            <div class="text-center py-8 text-gray-500">
                <p>ผู้ใช้นี้ยังไม่ได้เป็น Affiliate</p>
            </div>
        </div>
    @endif
</div>

<!-- Commission History -->
@if($user->commissions && $user->commissions->count() > 0)
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">ประวัติคอมมิชชั่น</h3>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ยอดขาย</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">เปอร์เซ็นต์</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">คอมมิชชั่น</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($user->commissions->take(10) as $commission)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $commission->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($commission->sale_amount ?? 0, 2) }}฿
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $commission->commission_rate ?? 0 }}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                            {{ number_format($commission->amount, 2) }}฿
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                @if($commission->status === 'paid') bg-green-100 text-green-800
                                @elseif($commission->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($commission->status) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($user->commissions->count() > 10)
        <p class="mt-4 text-sm text-gray-600 text-center">
            แสดง 10 รายการล่าสุด จากทั้งหมด {{ $user->commissions->count() }} รายการ
        </p>
    @endif
</div>
@endif

<!-- Activity Summary -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">สถานะบัญชี</h3>
        <p class="text-2xl font-bold text-indigo-600">
            {{ $user->email_verified_at ? 'Active' : 'Pending' }}
        </p>
    </div>

    @if($user->affiliate)
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Total Referrals</h3>
        <p class="text-2xl font-bold text-blue-600">{{ $user->affiliate->total_referrals }}</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Total Earnings</h3>
        <p class="text-2xl font-bold text-green-600">{{ number_format($user->affiliate->total_earnings, 2) }}฿</p>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Commissions</h3>
        <p class="text-2xl font-bold text-yellow-600">{{ $user->commissions->count() ?? 0 }}</p>
    </div>
</div>
@endsection
