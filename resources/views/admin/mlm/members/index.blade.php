@extends('layouts.admin-v3')

@section('title', 'จัดการสมาชิก MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-users" style="color: var(--arrow-x-accent)"></i>
                จัดการสมาชิก MLM
            </h1>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">ดูและจัดการสมาชิก MLM ทั้งหมด</p>
        </div>
        <a href="{{ route('admin.mlm.members.create') }}"
           class="text-white px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2"
           style="background: linear-gradient(to right, var(--arrow-x-accent), var(--arrow-x-primary-end))">
            <i class="fas fa-user-plus"></i>
            เพิ่มสมาชิกใหม่
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="rounded-xl shadow-lg p-6 text-white" style="background: var(--arrow-x-primary-gradient)">
            <div class="flex items-center justify-between">
                <div>
                    <p class="opacity-80 text-sm">สมาชิกทั้งหมด</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($members->total()) }}</p>
                </div>
                <div class="glass-fusion rounded-full p-3" border border-white/20 dark:border-white/10>
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, var(--arrow-x-success), var(--arrow-x-info))">
            <div class="flex items-center justify-between">
                <div>
                    <p class="opacity-80 text-sm">Active</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($members->where('status', 'active')->count()) }}</p>
                </div>
                <div class="glass-fusion rounded-full p-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, var(--arrow-x-accent), var(--arrow-x-primary-end))">
            <div class="flex items-center justify-between">
                <div>
                    <p class="opacity-80 text-sm">PV รวม</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($members->sum('total_pv')) }}</p>
                </div>
                <div class="glass-fusion rounded-full p-3">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, var(--arrow-x-accent), var(--arrow-x-error))">
            <div class="flex items-center justify-between">
                <div>
                    <p class="opacity-80 text-sm">รายได้รวม</p>
                    <p class="text-3xl font-bold mt-1">฿{{ number_format($members->sum('total_earnings')) }}</p>
                </div>
                <div class="glass-fusion rounded-full p-3" border border-white/20 dark:border-white/10>
                    <i class="fas fa-dollar-sign text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    <i class="fas fa-layer-group mr-1"></i>แผน MLM
                </label>
                <select name="plan_id" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    <i class="fas fa-toggle-on mr-1"></i>สถานะ
                </label>
                <select name="status" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    <i class="fas fa-search mr-1"></i>ค้นหา
                </label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อ, อีเมล, Member Code"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 dark:from-purple-500 dark:to-pink-500 dark:hover:from-purple-600 dark:hover:to-pink-600 text-white px-4 py-3 rounded-xl transition shadow-lg hover:shadow-xl">
                    <i class="fas fa-filter mr-2"></i>ค้นหา
                </button>
                @if(request()->hasAny(['plan_id', 'status', 'search']))
                    <a href="{{ route('admin.mlm.members.index') }}"
                       class="px-4 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-xl transition">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Members Table -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-user mr-2"></i>สมาชิก
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-layer-group mr-2"></i>แผน
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-chart-bar mr-2"></i>PV
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-money-bill-wave mr-2"></i>รายได้
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-user-friends mr-2"></i>ผู้แนะนำ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-info-circle mr-2"></i>สถานะ
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-cog mr-2"></i>จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($members as $member)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($member->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $member->user->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $member->member_code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                {{ $member->plan->display_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                            {{ number_format($member->total_pv, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-emerald-600 dark:text-emerald-400 font-bold">
                            ฿{{ number_format($member->total_earnings, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1 text-sm text-gray-900 dark:text-white">
                                <i class="fas fa-users text-purple-600 dark:text-purple-400"></i>
                                <span class="font-semibold">{{ $member->total_direct_referrals }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full
                                {{ $member->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                {{ $member->status === 'inactive' ? 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-400' : '' }}
                                {{ $member->status === 'suspended' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                {{ ucfirst($member->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.mlm.members.show', $member) }}"
                                   class="px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-xl transition">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.mlm.members.genealogy', $member) }}"
                                   class="px-3 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-xl transition">
                                    <i class="fas fa-sitemap"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p class="text-lg">ไม่พบข้อมูลสมาชิก</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($members->hasPages())
        <div class="mt-6">
            {{ $members->links() }}
        </div>
    @endif
</div>
@endsection
