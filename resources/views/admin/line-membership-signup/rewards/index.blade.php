@extends('layouts.admin')

@section('title', 'จัดการรางวัลการสมัครสมาชิก')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                จัดการรางวัลการสมัครสมาชิก
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                กำหนดรางวัลที่ผู้ใช้จะได้รับเมื่อสมัครสมาชิกผ่าน LINE OA
            </p>
        </div>
        <a href="{{ route('admin.line-membership-signup.rewards.create') }}"
           class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition shadow-lg hover:shadow-xl">
            <i class="fas fa-plus mr-2"></i>
            สร้างรางวัลใหม่
        </a>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-6 p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
        <form method="GET" action="{{ route('admin.line-membership-signup.rewards.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Signup Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ประเภทการสมัคร
                </label>
                <select name="signup_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="free" {{ request('signup_type') == 'free' ? 'selected' : '' }}>ฟรี</option>
                    <option value="package" {{ request('signup_type') == 'package' ? 'selected' : '' }}>แพคเกจ</option>
                    <option value="both" {{ request('signup_type') == 'both' ? 'selected' : '' }}>ทั้งสอง</option>
                </select>
            </div>

            {{-- Reward Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ประเภทรางวัล
                </label>
                <select name="reward_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="wallet_points" {{ request('reward_type') == 'wallet_points' ? 'selected' : '' }}>แต้ม Wallet</option>
                    <option value="tpix_tokens" {{ request('reward_type') == 'tpix_tokens' ? 'selected' : '' }}>เหรียญ TPIX</option>
                    <option value="coupon" {{ request('reward_type') == 'coupon' ? 'selected' : '' }}>คูปอง</option>
                    <option value="rank_points" {{ request('reward_type') == 'rank_points' ? 'selected' : '' }}>คะแนน Rank</option>
                    <option value="special_benefit" {{ request('reward_type') == 'special_benefit' ? 'selected' : '' }}>สิทธิพิเศษ</option>
                    <option value="bonus_item" {{ request('reward_type') == 'bonus_item' ? 'selected' : '' }}>ของแถม</option>
                    <option value="free_downlines" {{ request('reward_type') == 'free_downlines' ? 'selected' : '' }}>ดาวน์ไลน์ฟรี</option>
                    <option value="experience_points" {{ request('reward_type') == 'experience_points' ? 'selected' : '' }}>คะแนนประสบการณ์</option>
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    สถานะ
                </label>
                <div class="flex gap-2">
                    <select name="is_active" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        <option value="">ทั้งหมด</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>เปิดใช้งาน</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>ปิดใช้งาน</option>
                    </select>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Rewards List --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        @if($rewards->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ลำดับ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                รางวัล
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ประเภทการสมัคร
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ประเภทรางวัล
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                จำนวน
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                รับแล้ว
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($rewards as $reward)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $reward->display_order }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg" style="background-color: {{ $reward->badge_color }}20">
                                            {!! $reward->getIconHtml() !!}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $reward->name }}
                                            </div>
                                            @if($reward->description)
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ Str::limit($reward->description, 50) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($reward->signup_type == 'free')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            ฟรี
                                        </span>
                                    @elseif($reward->signup_type == 'package')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                            แพคเกจ
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                            ทั้งสอง
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $reward->getDisplayText() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ number_format($reward->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ number_format($reward->total_claimed) }}
                                    @if($reward->max_claims)
                                        <span class="text-gray-500 dark:text-gray-400">
                                            / {{ number_format($reward->max_claims) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($reward->is_active)
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            เปิดใช้งาน
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            ปิดใช้งาน
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.line-membership-signup.rewards.edit', $reward) }}"
                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.line-membership-signup.rewards.destroy', $reward) }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรางวัลนี้?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $rewards->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                    <i class="fas fa-gift text-3xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                    ยังไม่มีรางวัล
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    เริ่มต้นสร้างรางวัลสำหรับผู้สมัครสมาชิกใหม่
                </p>
                <a href="{{ route('admin.line-membership-signup.rewards.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>
                    สร้างรางวัลแรก
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
