@extends('layouts.admin')

@section('title', 'รายละเอียดบทบาท')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.roles.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← กลับ
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        🔐 {{ $role->display_name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        รายละเอียดบทบาทและสิทธิ์การเข้าถึง
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.roles.permissions', $role) }}"
                   class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    จัดการสิทธิ์
                </a>
                <a href="{{ route('admin.roles.edit', $role) }}"
                   class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    แก้ไข
                </a>
            </div>
        </div>
    </div>

    <!-- Role Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สิทธิ์ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $role->permissions->count() }}</p>
                </div>
                <div class="text-4xl">🔐</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ผู้ใช้ที่มีบทบาทนี้</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $role->users->count() }}</p>
                </div>
                <div class="text-4xl">👥</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ประเภท</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200 mt-2">
                        @if($role->is_system_role)
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-sm">ระบบ</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 rounded-full text-sm">กำหนดเอง</span>
                        @endif
                    </p>
                </div>
                <div class="text-4xl">⚙️</div>
            </div>
        </div>
    </div>

    <!-- Basic Info -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">ข้อมูลพื้นฐาน</h3>
        <div class="space-y-3">
            <div>
                <span class="text-sm text-gray-600 dark:text-gray-400">ชื่อบทบาท (ระบบ):</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-gray-100">{{ $role->name }}</span>
            </div>
            <div>
                <span class="text-sm text-gray-600 dark:text-gray-400">ชื่อที่แสดง:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-gray-100">{{ $role->display_name }}</span>
            </div>
            @if($role->description)
            <div>
                <span class="text-sm text-gray-600 dark:text-gray-400">คำอธิบาย:</span>
                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $role->description }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Permissions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">สิทธิ์การเข้าถึง</h3>
        @if($role->permissions->count() > 0)
            @php
                $groupedPermissions = $role->permissions->groupBy('category');
            @endphp
            <div class="space-y-4">
                @foreach($groupedPermissions as $category => $categoryPermissions)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                            {{ \App\Models\Permission::getCategoryDisplayName($category) }}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($categoryPermissions as $permission)
                                <div class="flex items-start p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                    <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $permission->display_name }}
                                        </div>
                                        @if($permission->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
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
            <p class="text-gray-500 dark:text-gray-400">บทบาทนี้ยังไม่มีสิทธิ์ใดๆ</p>
        @endif
    </div>

    <!-- Users with this role -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">ผู้ใช้ที่มีบทบาทนี้</h3>
        @if($role->users->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ชื่อ</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">อีเมล</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่สร้าง</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($role->users as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        ดูรายละเอียด →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีผู้ใช้ที่มีบทบาทนี้</p>
        @endif
    </div>
</div>
@endsection
