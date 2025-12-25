@extends('layouts.admin-v3')

@section('title', 'จัดการบทบาทและสิทธิ์')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-800 dark:via-purple-800 dark:to-pink-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-user-shield text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">จัดการบทบาทและสิทธิ์</h1>
                        <p class="text-indigo-100 text-lg mt-1">กำหนดและจัดการบทบาทและสิทธิ์ของผู้ใช้ในระบบ</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('admin.roles.create') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                        <i class="fas fa-plus group-hover:rotate-90 transition-transform duration-300"></i>
                        สร้างบทบาทใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-bar" style="color: var(--arrow-x-primary-start)"></i>
            สถิติภาพรวม
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Total Roles --}}
            <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-indigo-500 to-purple-600 dark:from-indigo-600 dark:to-purple-800">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-user-tag text-9xl text-white"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">🔐</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">Total</span>
                    </div>
                    <p class="text-indigo-100 text-sm mb-1">จำนวนบทบาท</p>
                    <p class="text-3xl font-bold text-white">{{ $roles->count() }}</p>
                    <p class="text-xs text-indigo-100 mt-2">Total Roles</p>
                </div>
            </div>

            {{-- System Roles --}}
            <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-amber-500 to-orange-600 dark:from-amber-600 dark:to-orange-800">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-cog text-9xl text-white"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">⚙️</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">System</span>
                    </div>
                    <p class="text-amber-100 text-sm mb-1">บทบาทระบบ</p>
                    <p class="text-3xl font-bold text-white">{{ $roles->where('is_system_role', true)->count() }}</p>
                    <p class="text-xs text-amber-100 mt-2">System Roles</p>
                </div>
            </div>

            {{-- Total Users --}}
            <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-users text-9xl text-white"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">👥</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">Users</span>
                    </div>
                    <p class="text-green-100 text-sm mb-1">ผู้ใช้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-white">{{ $roles->sum('users_count') }}</p>
                    <p class="text-xs text-green-100 mt-2">Total Users</p>
                </div>
            </div>
        </div>
    </div>

<div class="max-w-7xl mx-auto">

    <!-- Roles List -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            บทบาท
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            คำอธิบาย
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            สิทธิ์
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($roles as $role)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">{{ substr($role->display_name, 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $role->display_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $role->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                {{ $role->description ?? '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $role->permissions_count }} สิทธิ์
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                {{ $role->users_count }} คน
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($role->is_system_role)
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                ระบบ
                            </span>
                            @else
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-900 dark:text-gray-200">
                                กำหนดเอง
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.roles.show', $role) }}"
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                   title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.roles.edit', $role) }}"
                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                   title="แก้ไข">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.roles.permissions', $role) }}"
                                   class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                                   title="จัดการสิทธิ์">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </a>
                                @if($role->users_count == 0 && !$role->is_system_role)
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline-block"
                                      onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบบทบาทนี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="ลบ">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <span class="text-6xl mb-4">🔐</span>
                                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-lg mb-2">ยังไม่มีบทบาทในระบบ</p>
                                <p class="text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm mb-4">เริ่มต้นสร้างบทบาทเพื่อกำหนดสิทธิ์ให้กับผู้ใช้</p>
                                <a href="{{ route('admin.roles.create') }}"
                                   class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-200">
                                    สร้างบทบาทแรก
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(session('success'))
    <div class="mt-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-xl">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mt-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl">
        {{ session('error') }}
    </div>
    @endif
</div>
</div>
@endsection
