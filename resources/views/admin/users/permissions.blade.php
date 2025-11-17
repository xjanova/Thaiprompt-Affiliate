@extends('layouts.admin-v3')

@section('title', 'จัดการสิทธิ์ผู้ใช้ - ' . $user->name)

@section('content')
<div class="space-y-6">
    <!-- User Info Card -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center">
            <div class="h-16 w-16 glass-fusion rounded-full flex items-center justify-center text-indigo-600 text-2xl font-bold" border border-white/20 dark:border-white/10>
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="ml-4">
                <h2 class="text-2xl font-bold">{{ $user->name }}</h2>
                <p class="text-indigo-100">{{ $user->email }}</p>
                <div class="mt-1">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                        {{ $user->is_super_admin ? 'bg-yellow-500 text-yellow-900' : 'bg-indigo-300 text-indigo-900' }}">
                        {{ $user->is_super_admin ? 'Super Admin' : ucfirst($user->role) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions Form -->
    <div class="glass-fusion rounded-xl shadow-md p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <span class="text-2xl mr-2">🔐</span>
                จัดการสิทธิ์การเข้าถึง
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mt-1">กำหนดสิทธิ์การเข้าถึงฟีเจอร์ต่างๆ ของระบบ</p>
        </div>

        @if($user->is_super_admin)
            <!-- Super Admin Notice -->
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">⚠️</span>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Super Admin Account</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>บัญชีนี้เป็น Super Admin มีสิทธิ์เข้าถึงทุกฟีเจอร์โดยอัตโนมัติและไม่สามารถแก้ไขได้</p>
                            <p class="mt-1">สิทธิ์ด้านล่างแสดงเพื่อให้เห็นเท่านั้น</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

                    $userPermissions = $user->permissions ?? [];
                    $isDisabled = $user->is_super_admin;
                @endphp

                @foreach($permissionGroups as $groupName => $permissions)
                    <div class="col-span-1">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b-2 border-indigo-200">
                            {{ $groupName }}
                        </h4>
                        <div class="space-y-4">
                            @foreach($permissions as $permission => $info)
                                <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 rounded-xl p-4 hover:bg-gray-100/50 dark:bg-gray-800/50 transition {{ $isDisabled ? 'opacity-75' : '' }}">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission }}"
                                                   id="permission_{{ $permission }}"
                                                   {{ in_array($permission, $userPermissions) ? 'checked' : '' }}
                                                   {{ $isDisabled ? 'disabled' : '' }}
                                                   class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 h-5 w-5 {{ $isDisabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <label for="permission_{{ $permission }}" class="font-medium text-gray-900 flex items-center {{ $isDisabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                                <span class="text-xl mr-2">{{ $info['icon'] }}</span>
                                                {{ $info['label'] }}
                                            </label>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $info['description'] }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!$user->is_super_admin)
                <div class="mt-8 flex gap-3">
                    <button type="submit" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition shadow-md hover:shadow-lg">
                        💾 บันทึกการตั้งค่า
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 transition shadow-md hover:shadow-lg">
                        ❌ ยกเลิก
                    </a>
                </div>
            @else
                <div class="mt-8">
                    <a href="{{ route('admin.users.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 transition shadow-md hover:shadow-lg inline-block">
                        ← กลับไปหน้ารายชื่อผู้ใช้
                    </a>
                </div>
            @endif
        </form>
    </div>

    <!-- Permission Summary -->
    <div class="glass-fusion rounded-xl shadow-md p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <span class="text-xl mr-2">📋</span>
            สรุปสิทธิ์ปัจจุบัน
        </h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $totalPermissions = 8;
                $grantedPermissions = $user->is_super_admin ? $totalPermissions : count($userPermissions);
            @endphp
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $grantedPermissions }}</div>
                <div class="text-sm text-blue-800 mt-1">สิทธิ์ที่ได้รับ</div>
            </div>
            <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-gray-600 dark:text-gray-400">{{ $totalPermissions }}</div>
                <div class="text-sm text-gray-900 dark:text-white mt-1">สิทธิ์ทั้งหมด</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-green-600">{{ round(($grantedPermissions / $totalPermissions) * 100) }}%</div>
                <div class="text-sm text-green-800 mt-1">ระดับการเข้าถึง</div>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-purple-600">{{ $user->is_super_admin ? '👑' : '👤' }}</div>
                <div class="text-sm text-purple-800 mt-1">{{ $user->is_super_admin ? 'Super Admin' : 'User' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
