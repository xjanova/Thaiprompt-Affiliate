@extends('layouts.admin')

@section('title', 'รายละเอียด Affiliate - ' . $affiliate->user->name)

@section('content')
<div class="space-y-6">
    <!-- Header with Breadcrumb -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.affiliates.index') }}" class="text-indigo-600 hover:text-indigo-900 flex items-center gap-2 mb-2">
                ← กลับไปรายการ Affiliates
            </a>
            <h1 class="text-3xl font-bold text-gray-900">รายละเอียด Affiliate</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.affiliates.tree', $affiliate) }}"
               class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow-md">
                <span class="text-xl">🌳</span>
                <span>ดู Tree</span>
            </a>
            <a href="{{ route('admin.affiliates.edit', $affiliate) }}"
               class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md">
                <span class="text-xl">✏️</span>
                <span>แก้ไข</span>
            </a>
        </div>
    </div>

    <!-- Profile Header Card -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center gap-6">
            <!-- Avatar -->
            <div class="w-24 h-24 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-4xl font-bold border-4 border-white/30 shadow-xl">
                {{ strtoupper(substr($affiliate->user->name, 0, 2)) }}
            </div>

            <!-- User Info -->
            <div class="flex-1">
                <h2 class="text-3xl font-bold mb-1">{{ $affiliate->user->name }}</h2>
                <p class="text-indigo-100 mb-2">{{ $affiliate->user->email }}</p>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-white/20 backdrop-blur rounded-full text-sm font-medium">
                        Level {{ $affiliate->level }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        @if($affiliate->status === 'active') bg-green-400/30 border border-green-300
                        @elseif($affiliate->status === 'inactive') bg-gray-400/30 border border-gray-300
                        @else bg-red-400/30 border border-red-300
                        @endif">
                        {{ ucfirst($affiliate->status) }}
                    </span>
                    <span class="px-3 py-1 bg-white/20 backdrop-blur rounded-full text-sm font-medium">
                        {{ $affiliate->user->role ?? 'user' }}
                    </span>
                </div>
            </div>

            <!-- Referral Code Card -->
            <div class="bg-white/10 backdrop-blur rounded-xl p-4 border border-white/20">
                <p class="text-sm text-indigo-100 mb-2">รหัสแนะนำ</p>
                <div class="flex items-center gap-2">
                    <code class="text-2xl font-mono font-bold" id="referral-code">{{ $affiliate->referral_code }}</code>
                    <button onclick="copyToClipboard('{{ $affiliate->referral_code }}')"
                            class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded-lg transition text-sm">
                        📋 Copy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Referrals -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Total Referrals</h3>
                <div class="text-3xl">👥</div>
            </div>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($affiliate->total_referrals) }}</p>
            <p class="text-sm text-gray-500 mt-1">เครือข่ายทั้งหมด</p>
        </div>

        <!-- Direct Referrals -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Direct Referrals</h3>
                <div class="text-3xl">🎯</div>
            </div>
            <p class="text-3xl font-bold text-purple-600">{{ $affiliate->children->count() }}</p>
            <p class="text-sm text-gray-500 mt-1">ผู้ใช้ที่แนะนำโดยตรง</p>
        </div>

        <!-- Total Earnings -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Total Earnings</h3>
                <div class="text-3xl">💰</div>
            </div>
            <p class="text-3xl font-bold text-green-600">฿{{ number_format($affiliate->total_earnings, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">รายได้ทั้งหมด</p>
        </div>

        <!-- Commissions Count -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Commissions</h3>
                <div class="text-3xl">📊</div>
            </div>
            <p class="text-3xl font-bold text-orange-600">{{ $affiliate->commissions->count() }}</p>
            <p class="text-sm text-gray-500 mt-1">จำนวนคอมมิชชั่น</p>
        </div>
    </div>

    <!-- Additional Stats -->
    @php
        $paidCommissions = $affiliate->commissions->where('status', 'paid');
        $pendingCommissions = $affiliate->commissions->where('status', 'pending');
        $totalPaid = $paidCommissions->sum('amount');
        $totalPending = $pendingCommissions->sum('amount');
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Paid Commissions -->
        <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-600">คอมมิชชั่นที่จ่ายแล้ว</h3>
                <div class="text-2xl">✅</div>
            </div>
            <p class="text-2xl font-bold text-green-600">฿{{ number_format($totalPaid, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $paidCommissions->count() }} รายการ</p>
        </div>

        <!-- Pending Commissions -->
        <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-600">คอมมิชชั่นรอจ่าย</h3>
                <div class="text-2xl">⏳</div>
            </div>
            <p class="text-2xl font-bold text-yellow-600">฿{{ number_format($totalPending, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $pendingCommissions->count() }} รายการ</p>
        </div>

        <!-- Network Performance -->
        <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-600">ประสิทธิภาพเครือข่าย</h3>
                <div class="text-2xl">📈</div>
            </div>
            @php
                $avgPerReferral = $affiliate->total_referrals > 0 ? $affiliate->total_earnings / $affiliate->total_referrals : 0;
            @endphp
            <p class="text-2xl font-bold text-indigo-600">฿{{ number_format($avgPerReferral, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">เฉลี่ยต่อ referral</p>
        </div>
    </div>

    <!-- Information Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Affiliate Information -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-2xl">📋</span>
                ข้อมูล Affiliate
            </h3>
            <dl class="space-y-4">
                @if($affiliate->parent)
                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">แนะนำโดย (Upline)</dt>
                    <dd class="text-sm">
                        <a href="{{ route('admin.affiliates.show', $affiliate->parent) }}"
                           class="flex items-center gap-2 text-indigo-600 hover:text-indigo-900 font-medium">
                            <span>{{ $affiliate->parent->user->name }}</span>
                            <code class="px-2 py-1 bg-indigo-50 rounded text-xs">{{ $affiliate->parent->referral_code }}</code>
                            <span class="text-xs">→</span>
                        </a>
                    </dd>
                </div>
                @else
                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">แนะนำโดย (Upline)</dt>
                    <dd class="text-sm text-gray-500 italic">ไม่มี (Root Affiliate)</dd>
                </div>
                @endif

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">ระดับในเครือข่าย</dt>
                    <dd class="text-sm">
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-semibold">Level {{ $affiliate->level }}</span>
                    </dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">สถานะ</dt>
                    <dd class="text-sm">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                            @if($affiliate->status === 'active') bg-green-100 text-green-800
                            @elseif($affiliate->status === 'inactive') bg-gray-100 text-gray-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($affiliate->status) }}
                        </span>
                    </dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">วันที่สมัคร</dt>
                    <dd class="text-sm text-gray-900 font-medium">
                        {{ $affiliate->created_at->format('d/m/Y H:i') }}
                        <span class="text-xs text-gray-500">({{ $affiliate->created_at->diffForHumans() }})</span>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 mb-1">อัพเดทล่าสุด</dt>
                    <dd class="text-sm text-gray-900">
                        {{ $affiliate->updated_at->format('d/m/Y H:i') }}
                        <span class="text-xs text-gray-500">({{ $affiliate->updated_at->diffForHumans() }})</span>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- User Information -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-2xl">👤</span>
                ข้อมูลผู้ใช้
            </h3>
            <dl class="space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">ชื่อผู้ใช้</dt>
                    <dd class="text-sm text-gray-900 font-medium">{{ $affiliate->user->name }}</dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">อีเมล</dt>
                    <dd class="text-sm">
                        <a href="mailto:{{ $affiliate->user->email }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $affiliate->user->email }}
                        </a>
                    </dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">บทบาท</dt>
                    <dd class="text-sm">
                        <span class="px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded-full uppercase">
                            {{ $affiliate->user->role ?? 'user' }}
                        </span>
                    </dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">สร้างบัญชีเมื่อ</dt>
                    <dd class="text-sm text-gray-900 font-medium">
                        {{ $affiliate->user->created_at->format('d/m/Y H:i') }}
                        <span class="text-xs text-gray-500">({{ $affiliate->user->created_at->diffForHumans() }})</span>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 mb-1">ดูแดชบอร์ด</dt>
                    <dd class="text-sm">
                        <a href="{{ route('admin.users.dashboard', $affiliate->user) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition font-medium">
                            <span>🔍</span>
                            <span>เข้าสู่แดชบอร์ดผู้ใช้</span>
                        </a>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Commission History -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">💵</span>
            ประวัติคอมมิชชั่น
            @if($affiliate->commissions->count() > 0)
                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">
                    {{ $affiliate->commissions->count() }} รายการ
                </span>
            @endif
        </h3>

        @if($affiliate->commissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดขาย</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เปอร์เซ็นต์</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">คอมมิชชั่น</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($affiliate->commissions->sortByDesc('created_at')->take(15) as $commission)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="font-medium">{{ $commission->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $commission->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    ฿{{ number_format($commission->sale_amount ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded font-semibold">
                                        {{ $commission->commission_rate ?? 0 }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                                    ฿{{ number_format($commission->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        @if($commission->status === 'paid') bg-green-100 text-green-800
                                        @elseif($commission->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($commission->status === 'approved') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($commission->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($commission->description)
                                        <span class="text-gray-600" title="{{ $commission->description }}">
                                            {{ \Illuminate\Support\Str::limit($commission->description, 30) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 italic">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 font-bold">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right text-sm text-gray-700">รวมทั้งหมด:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-bold">
                                ฿{{ number_format($affiliate->commissions->sum('amount'), 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($affiliate->commissions->count() > 15)
                <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200 text-center">
                    <p class="text-sm text-blue-900">
                        แสดง 15 รายการล่าสุด จากทั้งหมด <strong>{{ $affiliate->commissions->count() }}</strong> รายการ
                    </p>
                </div>
            @endif
        @else
            <div class="text-center py-12 text-gray-500">
                <div class="text-6xl mb-4">💸</div>
                <p class="text-xl font-semibold mb-2">ยังไม่มีคอมมิชชั่น</p>
                <p class="text-sm">เมื่อมี referral ซื้อสินค้า คอมมิชชั่นจะแสดงที่นี่</p>
            </div>
        @endif
    </div>

    <!-- Direct Referrals -->
    @if($affiliate->children->count() > 0)
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">👨‍👩‍👧‍👦</span>
            ผู้ใช้ที่แนะนำโดยตรง (Direct Referrals)
            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-semibold">
                {{ $affiliate->children->count() }}
            </span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($affiliate->children->sortByDesc('created_at') as $child)
                <div class="border-2 border-gray-200 rounded-xl p-4 hover:shadow-lg hover:border-indigo-300 transition">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold shadow-md">
                                {{ strtoupper(substr($child->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $child->user->name }}</h4>
                                <code class="text-xs text-gray-500 font-mono">{{ $child->referral_code }}</code>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full font-semibold
                            @if($child->status === 'active') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $child->status }}
                        </span>
                    </div>

                    <!-- Email -->
                    <p class="text-sm text-gray-600 mb-3 truncate">{{ $child->user->email }}</p>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-2 mb-3 text-xs">
                        <div class="bg-blue-50 rounded p-2">
                            <div class="text-gray-600">Level</div>
                            <div class="font-bold text-blue-600">{{ $child->level }}</div>
                        </div>
                        <div class="bg-green-50 rounded p-2">
                            <div class="text-gray-600">Referrals</div>
                            <div class="font-bold text-green-600">{{ $child->total_referrals }}</div>
                        </div>
                        <div class="bg-purple-50 rounded p-2 col-span-2">
                            <div class="text-gray-600">Earnings</div>
                            <div class="font-bold text-purple-600">฿{{ number_format($child->total_earnings, 2) }}</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="{{ route('admin.affiliates.show', $child) }}"
                           class="flex-1 text-center px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition font-medium">
                            ดูรายละเอียด
                        </a>
                        <a href="{{ route('admin.affiliates.tree', $child) }}"
                           class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                            🌳
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">👨‍👩‍👧‍👦</span>
            ผู้ใช้ที่แนะนำโดยตรง (Direct Referrals)
        </h3>
        <div class="text-center py-12 text-gray-500">
            <div class="text-6xl mb-4">👥</div>
            <p class="text-xl font-semibold mb-2">ยังไม่มีผู้ใช้ที่แนะนำโดยตรง</p>
            <p class="text-sm">เมื่อมีคนใช้รหัสแนะนำสมัครสมาชิก จะแสดงที่นี่</p>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('success', 'คัดลอก ' + text + ' แล้ว!');
    }, function(err) {
        showNotification('error', 'ไม่สามารถคัดลอกได้');
    });
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-slide-in`;
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <span class="text-2xl">${type === 'success' ? '✅' : '❌'}</span>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
</script>

<style>
@keyframes slide-in {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-slide-in {
    animation: slide-in 0.3s ease-out;
}
</style>
@endpush
@endsection
