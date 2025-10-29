@extends('layouts.admin')

@section('title', 'รายละเอียดคอมมิชชั่น')

@section('content')
<div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.commissions.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="text-2xl font-bold text-gray-800">รายละเอียดคอมมิชชั่น #{{ $commission->id }}</h2>
        </div>

        <div class="flex gap-2">
            @if($commission->status === 'pending')
                <form action="{{ route('admin.commissions.approve', $commission) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        ✅ อนุมัติ
                    </button>
                </form>
                <form action="{{ route('admin.commissions.reject', $commission) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        ❌ ปฏิเสธ
                    </button>
                </form>
            @elseif($commission->status === 'approved')
                <form action="{{ route('admin.commissions.pay', $commission) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        💰 จ่ายเงิน
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Commission Details Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">ข้อมูลคอมมิชชั่น</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-600">จำนวนเงิน</label>
                        <div class="text-2xl font-bold text-indigo-600">{{ number_format($commission->amount, 2) }} ฿</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">เปอร์เซ็นต์</label>
                        <div class="text-2xl font-bold text-purple-600">{{ $commission->percentage }}%</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">ประเภท</label>
                        <div>
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ ucfirst($commission->type) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">สถานะ</label>
                        <div>
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                                {{ $commission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $commission->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $commission->status === 'paid' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $commission->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($commission->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                @if($commission->reference)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <label class="text-sm text-gray-600">Reference</label>
                        <div class="text-sm text-gray-900 font-mono">{{ $commission->reference }}</div>
                    </div>
                @endif

                @if($commission->notes)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <label class="text-sm text-gray-600">หมายเหตุ</label>
                        <div class="text-sm text-gray-900">{{ $commission->notes }}</div>
                    </div>
                @endif
            </div>

            <!-- Affiliate Information Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">ข้อมูล Affiliate</h3>
                <div class="flex items-center gap-4 mb-4">
                    <img src="{{ $commission->affiliate->user->profile_picture_url }}"
                         alt="{{ $commission->affiliate->user->name }}"
                         class="w-16 h-16 rounded-full object-cover border-2 border-indigo-500">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">{{ $commission->affiliate->user->name }}</div>
                        <div class="text-sm text-gray-600">{{ $commission->affiliate->user->email }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                    <div>
                        <label class="text-sm text-gray-600">รหัส Affiliate</label>
                        <div class="text-sm font-mono text-gray-900">{{ $commission->affiliate->code }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">ระดับ</label>
                        <div class="text-sm text-gray-900">Level {{ $commission->affiliate->level }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">สถานะ</label>
                        <div>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                {{ $commission->affiliate->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($commission->affiliate->status) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">คอมมิชชั่นเริ่มต้น</label>
                        <div class="text-sm text-gray-900">{{ $commission->affiliate->commission_rate }}%</div>
                    </div>
                </div>
            </div>

            @if($commission->user)
            <!-- Customer Information Card (if exists) -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">ข้อมูลลูกค้า</h3>
                <div class="flex items-center gap-4">
                    <img src="{{ $commission->user->profile_picture_url }}"
                         alt="{{ $commission->user->name }}"
                         class="w-12 h-12 rounded-full object-cover">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">{{ $commission->user->name }}</div>
                        <div class="text-sm text-gray-600">{{ $commission->user->email }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Timeline and Activity Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Timeline Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Timeline</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-blue-600">📝</span>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">สร้างคอมมิชชั่น</div>
                            <div class="text-xs text-gray-600">{{ $commission->created_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-gray-500">{{ $commission->created_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    @if($commission->approved_at)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-green-600">✅</span>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">อนุมัติ</div>
                            <div class="text-xs text-gray-600">{{ $commission->approved_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-gray-500">{{ $commission->approved_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endif

                    @if($commission->paid_at)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-blue-600">💰</span>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">จ่ายเงิน</div>
                            <div class="text-xs text-gray-600">{{ $commission->paid_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-gray-500">{{ $commission->paid_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endif

                    @if($commission->rejected_at)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-red-600">❌</span>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">ปฏิเสธ</div>
                            <div class="text-xs text-gray-600">{{ $commission->rejected_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-gray-500">{{ $commission->rejected_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endif

                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-gray-600">🔄</span>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">อัปเดตล่าสุด</div>
                            <div class="text-xs text-gray-600">{{ $commission->updated_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-gray-500">{{ $commission->updated_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-md p-6 text-white">
                <h3 class="text-lg font-semibold mb-4">สถิติด่วน</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm opacity-90">อายุคอมมิชชั่น</span>
                        <span class="font-semibold">{{ $commission->created_at->diffInDays(now()) }} วัน</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm opacity-90">ยอดรวม Affiliate</span>
                        <span class="font-semibold">{{ number_format($commission->affiliate->commissions()->sum('amount'), 2) }} ฿</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm opacity-90">คอมมิชชั่นทั้งหมด</span>
                        <span class="font-semibold">{{ $commission->affiliate->commissions()->count() }} รายการ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
