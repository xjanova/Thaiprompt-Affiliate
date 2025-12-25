@extends('layouts.admin-v3')

@section('title', 'รายละเอียดบทบาท')

@section('content')
<div class="space-y-6">
    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 dark:bg-white/5 dark:hover:bg-white/10 backdrop-blur-sm rounded-lg transition-all text-white">
            <i class="fas fa-arrow-left"></i>
            กลับไปรายการบทบาท
        </a>
    </div>

    {{-- Premium Hero Header (Purple-Pink-Red for View) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 dark:from-purple-800 dark:via-pink-800 dark:to-red-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-user-shield text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">{{ $role->display_name }}</h1>
                        <p class="text-purple-100 text-lg mt-1">รายละเอียดบทบาทและสิทธิ์การเข้าถึง</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.roles.permissions', $role) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-shield-alt"></i>
                        จัดการสิทธิ์
                    </a>
                    <a href="{{ route('admin.roles.edit', $role) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-edit"></i>
                        แก้ไข
                    </a>
                </div>
            </div>
        </div>
    </div>

<div class="max-w-5xl mx-auto">

    {{-- Stats Cards with Gradients --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        {{-- Total Permissions Card (Indigo-Purple) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-indigo-500 to-purple-600 dark:from-indigo-600 dark:to-purple-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-shield-alt text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">🔐</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $role->permissions->count() }}</p>
                <p class="text-sm text-indigo-100">สิทธิ์ทั้งหมด</p>
            </div>
        </div>

        {{-- Total Users Card (Green-Emerald) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-users text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">👥</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $role->users->count() }}</p>
                <p class="text-sm text-green-100">ผู้ใช้ที่มีบทบาทนี้</p>
            </div>
        </div>

        {{-- Role Type Card (Amber-Orange) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-amber-500 to-orange-600 dark:from-amber-600 dark:to-orange-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-cog text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">⚙️</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-cog text-white text-xl"></i>
                    </div>
                </div>
                <div class="mt-2">
                    @if($role->is_system_role)
                        <p class="text-2xl font-bold text-white mb-1">ระบบ</p>
                        <p class="text-sm text-amber-100">บทบาทระบบหลัก</p>
                    @else
                        <p class="text-2xl font-bold text-white mb-1">กำหนดเอง</p>
                        <p class="text-sm text-amber-100">บทบาทที่กำหนดเอง</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Basic Info --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8 mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-info-circle text-indigo-600 dark:text-indigo-400"></i>
            ข้อมูลพื้นฐาน
        </h3>
        <div class="space-y-4">
            <div class="flex items-start gap-3 p-4 bg-white/60 dark:bg-white/10 backdrop-blur-sm rounded-xl">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-tag text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div class="flex-1">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">ชื่อบทบาท (ระบบ)</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $role->name }}</span>
                </div>
            </div>
            <div class="flex items-start gap-3 p-4 bg-white/60 dark:bg-white/10 backdrop-blur-sm rounded-xl">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-heading text-purple-600 dark:text-purple-400"></i>
                </div>
                <div class="flex-1">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">ชื่อที่แสดง</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $role->display_name }}</span>
                </div>
            </div>
            @if($role->description)
            <div class="flex items-start gap-3 p-4 bg-white/60 dark:bg-white/10 backdrop-blur-sm rounded-xl">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-align-left text-pink-600 dark:text-pink-400"></i>
                </div>
                <div class="flex-1">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">คำอธิบาย</span>
                    <p class="text-gray-900 dark:text-white">{{ $role->description }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Permissions --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8 mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-shield-alt text-indigo-600 dark:text-indigo-400"></i>
            สิทธิ์การเข้าถึง
        </h3>
        @if($role->permissions->count() > 0)
            @php
                $groupedPermissions = $role->permissions->groupBy('category');
            @endphp
            <div class="space-y-4">
                @foreach($groupedPermissions as $category => $categoryPermissions)
                    <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm border-2 border-gray-200 dark:border-white/20 rounded-xl p-6 shadow-sm">
                        <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            {{ \App\Models\Permission::getCategoryDisplayName($category) }}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($categoryPermissions as $permission)
                                <div class="flex items-start gap-3 p-3 bg-white/50 dark:bg-white/5 backdrop-blur-sm rounded-lg hover:bg-white/80 dark:hover:bg-white/10 transition-all">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <i class="fas fa-check-circle text-green-500 text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $permission->display_name }}
                                        </div>
                                        @if($permission->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $permission->description }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-shield-alt text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">บทบาทนี้ยังไม่มีสิทธิ์ใดๆ</p>
            </div>
        @endif
    </div>

    {{-- Users with this role --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-users text-green-600 dark:text-green-400"></i>
            ผู้ใช้ที่มีบทบาทนี้
        </h3>
        @if($role->users->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-white/60 dark:bg-white/10 backdrop-blur-sm">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-user mr-2 text-indigo-600 dark:text-indigo-400"></i>
                                ชื่อ
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-envelope mr-2 text-purple-600 dark:text-purple-400"></i>
                                อีเมล
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-calendar mr-2 text-pink-600 dark:text-pink-400"></i>
                                วันที่สร้าง
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/40 dark:bg-white/5 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($role->users as $user)
                            <tr class="hover:bg-white/60 dark:hover:bg-white/10 transition-all">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $user->created_at->format('d/m/Y') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                                        <i class="fas fa-eye"></i>
                                        ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-users text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีผู้ใช้ที่มีบทบาทนี้</p>
            </div>
        @endif
    </div>
</div>
</div>
@endsection
