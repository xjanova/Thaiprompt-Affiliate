@extends('layouts.admin')

@section('title', 'จัดการคอมมิชชั่น')

@section('content')
<div class="mb-6 flex gap-4">
    <select onchange="window.location.href='?status='+this.value" class="rounded-lg border-gray-300">
        <option value="">ทุกสถานะ</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
    </select>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Affiliate</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เปอร์เซ็นต์</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การกระทำ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($commissions as $commission)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $commission->affiliate->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $commission->affiliate->user->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ number_format($commission->amount, 2) }}฿
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $commission->percentage }}%
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $commission->type }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            @if($commission->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($commission->status === 'approved') bg-green-100 text-green-800
                            @elseif($commission->status === 'paid') bg-blue-100 text-blue-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ $commission->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $commission->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.commissions.show', $commission) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">ดู</a>
                        @if($commission->status === 'pending')
                            <form action="{{ route('admin.commissions.approve', $commission) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-900 mr-3">อนุมัติ</button>
                            </form>
                            <form action="{{ route('admin.commissions.reject', $commission) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-900">ปฏิเสธ</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">ยังไม่มีข้อมูล</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="px-6 py-4">
        {{ $commissions->links() }}
    </div>
</div>
@endsection
