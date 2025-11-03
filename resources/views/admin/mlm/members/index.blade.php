@extends('layouts.admin')

@section('title', 'จัดการสมาชิก MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">จัดการสมาชิก MLM</h1>
            <p class="text-gray-600 mt-1">ดูและจัดการสมาชิก MLM ทั้งหมด</p>
        </div>
        <a href="{{ route('admin.mlm.members.create') }}"
           class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-2.5 rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            เพิ่มสมาชิกใหม่
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">แผน MLM</label>
                <select name="plan_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">สถานะ</label>
                <select name="status" class="w-full border-gray-300 rounded-lg">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อ, อีเมล, Member Code" class="w-full border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Members Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">สมาชิก</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">แผน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">PV</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">รายได้</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ผู้แนะนำ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($members as $member)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $member->user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $member->member_code }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                            {{ $member->plan->display_name }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ number_format($member->total_pv, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                        ฿{{ number_format($member->total_earnings, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $member->total_direct_referrals }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            {{ $member->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $member->status === 'inactive' ? 'bg-gray-100 text-gray-800' : '' }}
                            {{ $member->status === 'suspended' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($member->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.mlm.members.show', $member) }}" class="text-blue-600 hover:text-blue-900 mr-3">ดูข้อมูล</a>
                        <a href="{{ route('admin.mlm.members.genealogy', $member) }}" class="text-purple-600 hover:text-purple-900">ผังสายงาน</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        ไม่พบข้อมูลสมาชิก
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $members->links() }}
    </div>
</div>
@endsection
