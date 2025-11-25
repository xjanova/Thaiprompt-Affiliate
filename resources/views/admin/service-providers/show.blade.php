@extends('layouts.admin')

@section('title', $pageTitle ?? 'รายละเอียดผู้ให้บริการ')

@section('content')
<div class="max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <div class="flex justify-between items-start">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center text-white text-3xl font-bold">
                    {{ substr($provider->name ?? $provider->display_name ?? 'P', 0, 1) }}
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $provider->name ?? $provider->display_name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $provider->user->name ?? 'N/A' }} - {{ $provider->user->email ?? '-' }}</p>
                    <div class="flex items-center gap-4 mt-2">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-500"></i>
                            <span class="font-bold">{{ number_format($provider->average_rating ?? 0, 1) }}</span>
                            <span class="text-gray-500">({{ $provider->total_reviews ?? 0 }} รีวิว)</span>
                        </div>
                        @if($provider->verification_status == 'pending')
                            <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800">รอตรวจสอบ</span>
                        @elseif($provider->verification_status == 'approved')
                            <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800">อนุมัติแล้ว</span>
                        @else
                            <span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800">ปฏิเสธ</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.service-providers.edit', $provider) }}" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-edit mr-2"></i>แก้ไข
                </a>
                <a href="{{ route('admin.service-providers.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Info --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white"><i class="fas fa-info-circle mr-2 text-blue-500"></i>ข้อมูลติดต่อ</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">เบอร์โทร</dt>
                    <dd class="font-semibold">{{ $provider->phone }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">อีเมล</dt>
                    <dd class="font-semibold">{{ $provider->email ?? $provider->user->email ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">เลขบัตรประชาชน</dt>
                    <dd class="font-semibold font-mono">{{ $provider->id_card_number ? Str::mask($provider->id_card_number, '*', 3, -4) : '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">วันที่สมัคร</dt>
                    <dd class="font-semibold">{{ $provider->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Stats --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white"><i class="fas fa-chart-bar mr-2 text-green-500"></i>สถิติ</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20">
                    <p class="text-sm text-gray-600 dark:text-gray-400">งานทั้งหมด</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $provider->total_bookings ?? 0 }}</p>
                </div>
                <div class="p-4 rounded-xl bg-green-50 dark:bg-green-900/20">
                    <p class="text-sm text-gray-600 dark:text-gray-400">รายได้รวม</p>
                    <p class="text-2xl font-bold text-green-600">฿{{ number_format($provider->total_earnings ?? 0, 0) }}</p>
                </div>
                <div class="p-4 rounded-xl bg-purple-50 dark:bg-purple-900/20">
                    <p class="text-sm text-gray-600 dark:text-gray-400">อัตราการรับงาน</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $provider->acceptance_rate ?? 0 }}%</p>
                </div>
                <div class="p-4 rounded-xl bg-orange-50 dark:bg-orange-900/20">
                    <p class="text-sm text-gray-600 dark:text-gray-400">อัตราสำเร็จ</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $provider->completion_rate ?? 0 }}%</p>
                </div>
            </div>
        </div>

        {{-- Bank Info --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white"><i class="fas fa-university mr-2 text-purple-500"></i>ข้อมูลบัญชีธนาคาร</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">ธนาคาร</dt>
                    <dd class="font-semibold">{{ $provider->bank_name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">เลขบัญชี</dt>
                    <dd class="font-semibold font-mono">{{ $provider->bank_account_number ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">ชื่อบัญชี</dt>
                    <dd class="font-semibold">{{ $provider->bank_account_name ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Actions --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white"><i class="fas fa-cogs mr-2 text-gray-500"></i>การดำเนินการ</h2>
            <div class="space-y-3">
                @if($provider->verification_status == 'pending')
                    <form action="{{ route('admin.service-providers.verify', $provider) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition" onclick="return confirm('อนุมัติผู้ให้บริการนี้?')">
                            <i class="fas fa-check-circle mr-2"></i>อนุมัติผู้ให้บริการ
                        </button>
                    </form>
                    <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-times-circle mr-2"></i>ปฏิเสธ
                    </button>
                @endif
                <form action="{{ route('admin.service-providers.toggle-active', $provider) }}" method="POST">
                    @csrf
                    @if($provider->is_active)
                        <button type="submit" class="w-full px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-toggle-off mr-2"></i>ปิดใช้งาน
                        </button>
                    @else
                        <button type="submit" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-toggle-on mr-2"></i>เปิดใช้งาน
                        </button>
                    @endif
                </form>
                <form action="{{ route('admin.service-providers.destroy', $provider) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-3 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition" onclick="return confirm('ลบผู้ให้บริการนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                        <i class="fas fa-trash mr-2"></i>ลบผู้ให้บริการ
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Recent Bookings --}}
    @if(isset($provider->bookings) && $provider->bookings->count() > 0)
    <div class="mt-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white"><i class="fas fa-history mr-2 text-blue-500"></i>งานล่าสุด</h2>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">เลขที่</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">บริการ</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ลูกค้า</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($provider->bookings as $booking)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-2 text-sm font-mono">{{ $booking->booking_number }}</td>
                    <td class="px-4 py-2 text-sm">{{ $booking->service->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-sm">{{ $booking->user->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-sm">{{ $booking->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 text-xs rounded-full {{ $booking->status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $booking->status }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">ปฏิเสธผู้ให้บริการ</h3>
        <form action="{{ route('admin.service-providers.reject', $provider) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">เหตุผลที่ปฏิเสธ</label>
                <textarea name="reason" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700" required></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">ยืนยันปฏิเสธ</button>
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>
@endsection
