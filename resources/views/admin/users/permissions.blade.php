@extends('layouts.admin-v3')

@section('title', 'จัดการสิทธิ์ผู้ใช้ - ' . $user->name)

@section('content')
<div class="space-y-6">
    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 dark:bg-white/5 dark:hover:bg-white/10 backdrop-blur-sm rounded-lg transition-all text-white">
            <i class="fas fa-arrow-left"></i>
            กลับไปรายการผู้ใช้
        </a>
    </div>

    {{-- Premium Hero Header (Purple-Pink-Red for Permissions Management) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 dark:from-purple-800 dark:via-pink-800 dark:to-red-800 rounded-2xl shadow-2xl p-8">
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

        {{-- User Profile with Permissions Info --}}
        <div class="relative z-10">
            <div class="flex items-center gap-6">
                @if($user->profile_picture_url)
                    <img src="{{ $user->profile_picture_url }}"
                         alt="{{ $user->name }}"
                         class="h-24 w-24 rounded-2xl object-cover ring-4 ring-white/30 shadow-2xl">
                @else
                    <div class="h-24 w-24 glass-fusion rounded-2xl flex items-center justify-center text-3xl font-bold text-white shadow-2xl ring-4 ring-white/30">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">{{ $user->name }}</h1>
                    <p class="text-purple-100 text-lg mt-1">{{ $user->email }}</p>
                    <div class="mt-2">
                        @if($user->is_super_admin)
                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500/90 backdrop-blur-sm text-yellow-900 rounded-lg font-semibold shadow-lg">
                                <i class="fas fa-crown"></i>
                                Super Admin
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-lg font-semibold">
                                <i class="fas fa-user"></i>
                                {{ ucfirst($user->role) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Permission Summary Stats Cards --}}
    @php
        $totalPermissions = 8;
        $userPermissions = $user->permissions ?? [];
        $grantedPermissions = $user->is_super_admin ? $totalPermissions : count($userPermissions);
        $accessPercentage = round(($grantedPermissions / $totalPermissions) * 100);
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Granted Permissions Card (Blue-Indigo) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-600 dark:to-indigo-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-check-circle text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">✅</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $grantedPermissions }}</p>
                <p class="text-sm text-blue-100">สิทธิ์ที่ได้รับ</p>
            </div>
        </div>

        {{-- Total Permissions Card (Gray) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-gray-500 to-slate-600 dark:from-gray-600 dark:to-slate-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-list text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">📋</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-list text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $totalPermissions }}</p>
                <p class="text-sm text-gray-100">สิทธิ์ทั้งหมด</p>
            </div>
        </div>

        {{-- Access Level Card (Green-Emerald) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-percentage text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">📊</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-percentage text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $accessPercentage }}%</p>
                <p class="text-sm text-green-100">ระดับการเข้าถึง</p>
            </div>
        </div>

        {{-- User Type Card (Purple-Pink) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-{{ $user->is_super_admin ? 'crown' : 'user' }} text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">{{ $user->is_super_admin ? '👑' : '👤' }}</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-{{ $user->is_super_admin ? 'crown' : 'user' }} text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $user->is_super_admin ? 'Admin' : 'User' }}</p>
                <p class="text-sm text-purple-100">{{ $user->is_super_admin ? 'Super Admin' : 'Regular User' }}</p>
            </div>
        </div>
    </div>

    {{-- Super Admin Notice --}}
    @if($user->is_super_admin)
        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 backdrop-blur-sm border-l-4 border-yellow-400 rounded-xl p-6">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-yellow-900 dark:text-yellow-200 text-lg mb-2 flex items-center gap-2">
                        <i class="fas fa-crown"></i>
                        Super Admin Account
                    </h3>
                    <p class="text-yellow-800 dark:text-yellow-300">
                        บัญชีนี้เป็น Super Admin มีสิทธิ์เข้าถึงทุกฟีเจอร์โดยอัตโนมัติและไม่สามารถแก้ไขได้
                    </p>
                    <p class="text-yellow-700 dark:text-yellow-400 text-sm mt-1">
                        สิทธิ์ด้านล่างแสดงเพื่อให้เห็นเท่านั้น
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Permissions Management Form --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-shield-alt text-purple-600 dark:text-purple-400"></i>
                จัดการสิทธิ์การเข้าถึง
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">กำหนดสิทธิ์การเข้าถึงฟีเจอร์ต่างๆ ของระบบ</p>
        </div>

        <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
            @csrf
            @method('PUT')

            @php
                $permissionGroups = [
                    'ระบบหลัก' => [
                        'view_dashboard' => [
                            'label' => 'ดูแดชบอร์ด',
                            'description' => 'อนุญาตให้เข้าถึงและดูข้อมูลในหน้าแดชบอร์ด',
                            'icon' => '📊'
                        ],
                        'view_reports' => [
                            'label' => 'ดูรายงาน',
                            'description' => 'สามารถดูรายงานและสถิติต่างๆ ของระบบ',
                            'icon' => '📈'
                        ],
                    ],
                    'การจัดการผู้ใช้' => [
                        'manage_users' => [
                            'label' => 'จัดการผู้ใช้',
                            'description' => 'สร้าง แก้ไข และลบผู้ใช้ในระบบ',
                            'icon' => '👥'
                        ],
                        'manage_permissions' => [
                            'label' => 'จัดการสิทธิ์',
                            'description' => 'ตั้งค่าสิทธิ์การเข้าถึงของผู้ใช้อื่น',
                            'icon' => '🔐'
                        ],
                    ],
                    'Affiliate & Commission' => [
                        'manage_affiliates' => [
                            'label' => 'จัดการ Affiliates',
                            'description' => 'จัดการข้อมูล Affiliate, ดู Tree และสายงาน',
                            'icon' => '🌐'
                        ],
                        'manage_commissions' => [
                            'label' => 'จัดการคอมมิชชั่น',
                            'description' => 'อนุมัติ ปฏิเสธ และจัดการคอมมิชชั่น',
                            'icon' => '💰'
                        ],
                    ],
                    'การตั้งค่าระบบ' => [
                        'manage_settings' => [
                            'label' => 'จัดการการตั้งค่า',
                            'description' => 'แก้ไขการตั้งค่าทั่วไปของระบบ',
                            'icon' => '⚙️'
                        ],
                        'manage_branding' => [
                            'label' => 'จัดการโลโก้และธีม',
                            'description' => 'อัพโหลดโลโก้, favicon และปรับแต่งสีธีม',
                            'icon' => '🎨'
                        ],
                    ],
                ];

                $isDisabled = $user->is_super_admin;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($permissionGroups as $groupName => $permissions)
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 border-b-2 border-gradient-to-r from-purple-400 to-pink-400 flex items-center gap-2">
                            <i class="fas fa-folder-open text-purple-600 dark:text-purple-400"></i>
                            {{ $groupName }}
                        </h3>

                        @foreach($permissions as $permission => $info)
                            <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm border-2 border-gray-200 dark:border-white/20 rounded-xl p-5 hover:bg-white/80 dark:hover:bg-white/15 transition-all duration-200 {{ $isDisabled ? 'opacity-75' : '' }}">
                                <div class="flex items-start gap-4">
                                    <div class="flex items-center h-6 mt-1">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permission }}"
                                               id="permission_{{ $permission }}"
                                               {{ in_array($permission, $userPermissions) ? 'checked' : '' }}
                                               {{ $isDisabled ? 'disabled' : '' }}
                                               class="rounded border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500 h-6 w-6 {{ $isDisabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                    </div>
                                    <div class="flex-1">
                                        <label for="permission_{{ $permission }}" class="font-semibold text-gray-900 dark:text-white flex items-center gap-2 {{ $isDisabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                            <span class="text-2xl">{{ $info['icon'] }}</span>
                                            {{ $info['label'] }}
                                        </label>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-8">
                                            {{ $info['description'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-between pt-8 mt-8 border-t border-gray-200 dark:border-white/10">
                <a href="{{ route('admin.users.index') }}"
                   class="px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-white/60 dark:bg-white/10 backdrop-blur-sm border border-gray-200 dark:border-white/20 rounded-xl hover:bg-gray-100 dark:hover:bg-white/20 transition-all duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับ
                </a>

                @if(!$user->is_super_admin)
                    <button type="submit"
                            style="background: var(--arrow-x-primary-gradient)"
                            class="px-6 py-2.5 text-white font-semibold rounded-xl focus:outline-none shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกการตั้งค่า
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}
</style>
@endpush
@endsection
