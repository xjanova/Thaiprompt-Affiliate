@extends('layouts.admin-v3')

@section('title', 'จัดการสมาชิก MLM')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-500"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Title Section --}}
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-users text-4xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">จัดการสมาชิก MLM</h1>
                            <p class="text-blue-100 text-lg mt-1">ดูและจัดการสมาชิก MLM ทั้งหมดในระบบ</p>
                        </div>
                    </div>
                </div>

                {{-- Add Member Button --}}
                <div>
                    <a href="{{ route('admin.mlm.members.create') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                        <i class="fas fa-user-plus group-hover:scale-110 transition-transform"></i>
                        เพิ่มสมาชิกใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-bar text-blue-600 dark:text-blue-400"></i>
            สถิติภาพรวม
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Members --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-users text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">สมาชิกทั้งหมด</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-user-friends text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($members->total()) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-database text-blue-200"></i>
                        <span class="opacity-90">Total Members</span>
                    </div>
                </div>
            </div>

            {{-- Active Members --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-check-circle text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">สมาชิก Active</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-user-check text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($members->where('status', 'active')->count()) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-arrow-up text-green-200"></i>
                        <span class="opacity-90">Active Status</span>
                    </div>
                </div>
            </div>

            {{-- Total PV --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-star text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">PV รวม</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-gem text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($members->sum('total_pv')) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-chart-line text-purple-200"></i>
                        <span class="opacity-90">Point Value</span>
                    </div>
                </div>
            </div>

            {{-- Total Earnings --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 dark:from-orange-600 dark:to-red-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-coins text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">รายได้รวม</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">฿{{ number_format($members->sum('total_earnings')) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-trophy text-orange-200"></i>
                        <span class="opacity-90">Total Earnings</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-purple-600 dark:text-purple-400"></i>
            ตัวกรองข้อมูล
        </h3>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Plan Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-layer-group mr-1 text-purple-600 dark:text-purple-400"></i>
                    แผน MLM
                </label>
                <select name="plan_id"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-toggle-on mr-1 text-green-600 dark:text-green-400"></i>
                    สถานะ
                </label>
                <select name="status"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            {{-- Search Input --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-search mr-1 text-blue-600 dark:text-blue-400"></i>
                    ค้นหา
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ชื่อ, อีเมล, Member Code"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 dark:from-purple-500 dark:to-pink-500 dark:hover:from-purple-600 dark:hover:to-pink-600 text-white px-4 py-3 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                    <i class="fas fa-filter mr-2"></i>กรองข้อมูล
                </button>
                @if(request()->hasAny(['plan_id', 'status', 'search']))
                    <a href="{{ route('admin.mlm.members.index') }}"
                       class="px-4 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl"
                       title="ล้างตัวกรอง">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Members Table --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                {{-- Table Header --}}
                <thead class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-user mr-2"></i>สมาชิก
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-layer-group mr-2"></i>แผน
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-chart-bar mr-2"></i>PV
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-money-bill-wave mr-2"></i>รายได้
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-user-friends mr-2"></i>ผู้แนะนำ
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-info-circle mr-2"></i>สถานะ
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-cog mr-2"></i>จัดการ
                        </th>
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($members as $member)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        {{-- Member Info --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    {{ strtoupper(substr($member->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $member->user->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $member->member_code }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Plan --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 shadow">
                                <i class="fas fa-layer-group mr-1"></i>
                                {{ $member->plan->display_name }}
                            </span>
                        </td>

                        {{-- PV --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1">
                                <i class="fas fa-gem text-purple-600 dark:text-purple-400 text-xs"></i>
                                {{ number_format($member->total_pv, 2) }}
                            </div>
                        </td>

                        {{-- Earnings --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <i class="fas fa-baht-sign text-xs"></i>
                                {{ number_format($member->total_earnings, 2) }}
                            </div>
                        </td>

                        {{-- Direct Referrals --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-lg">
                                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-sm"></i>
                                </div>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $member->total_direct_referrals }}
                                </span>
                            </div>
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($member->status === 'active')
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 shadow">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Active
                                </span>
                            @elseif($member->status === 'inactive')
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 shadow">
                                    <i class="fas fa-minus-circle mr-1"></i>
                                    Inactive
                                </span>
                            @elseif($member->status === 'suspended')
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 shadow">
                                    <i class="fas fa-ban mr-1"></i>
                                    Suspended
                                </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.mlm.members.show', $member) }}"
                                   class="px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-xl transition-all duration-300 shadow hover:shadow-lg transform hover:scale-105"
                                   title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.mlm.members.genealogy', $member) }}"
                                   class="px-3 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-xl transition-all duration-300 shadow hover:shadow-lg transform hover:scale-105"
                                   title="ดู Genealogy">
                                    <i class="fas fa-sitemap"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-3">
                                <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-full">
                                    <i class="fas fa-inbox text-5xl text-gray-400 dark:text-gray-500"></i>
                                </div>
                                <div>
                                    <p class="text-lg font-semibold text-gray-600 dark:text-gray-400">ไม่พบข้อมูลสมาชิก</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">ลองเปลี่ยนตัวกรองหรือเพิ่มสมาชิกใหม่</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($members->hasPages())
        <div class="flex justify-center">
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-4">
                {{ $members->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
