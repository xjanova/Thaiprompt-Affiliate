@extends('layouts.admin')

@section('title', 'จัดการสิทธิ์ผู้ใช้ - ' . $user->name)

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">จัดการสิทธิ์การเข้าถึง</h2>
        <p class="text-gray-600 mt-1">ตั้งค่าสิทธิ์การเข้าถึงฟีเจอร์สำหรับ {{ $user->name }}</p>
    </div>

    <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            @php
                $permissionLabels = [
                    'view_dashboard' => 'ดูแดชบอร์ด',
                    'manage_users' => 'จัดการผู้ใช้',
                    'manage_affiliates' => 'จัดการ Affiliates',
                    'manage_commissions' => 'จัดการคอมมิชชั่น',
                    'view_reports' => 'ดูรายงาน',
                    'manage_settings' => 'จัดการการตั้งค่า',
                    'manage_branding' => 'จัดการโลโก้และธีม',
                    'manage_permissions' => 'จัดการสิทธิ์ผู้ใช้',
                ];
                $userPermissions = $user->permissions ?? [];
            @endphp

            @foreach($availablePermissions as $permission)
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox"
                               name="permissions[]"
                               value="{{ $permission }}"
                               id="permission_{{ $permission }}"
                               {{ in_array($permission, $userPermissions) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                    </div>
                    <div class="ml-3">
                        <label for="permission_{{ $permission }}" class="font-medium text-gray-700 cursor-pointer">
                            {{ $permissionLabels[$permission] ?? $permission }}
                        </label>
                        <p class="text-sm text-gray-500">
                            @if($permission === 'view_dashboard')
                                อนุญาตให้เข้าถึงและดูข้อมูลในหน้าแดชบอร์ด
                            @elseif($permission === 'manage_users')
                                สามารถสร้าง แก้ไข และลบผู้ใช้ได้
                            @elseif($permission === 'manage_affiliates')
                                สามารถจัดการข้อมูล Affiliate และดู Affiliate Tree
                            @elseif($permission === 'manage_commissions')
                                สามารถอนุมัติและจัดการคอมมิชชั่น
                            @elseif($permission === 'view_reports')
                                สามารถดูรายงานและสถิติต่างๆ
                            @elseif($permission === 'manage_settings')
                                สามารถแก้ไขการตั้งค่าระบบทั่วไป
                            @elseif($permission === 'manage_branding')
                                สามารถอัพโหลดโลโก้, favicon และเปลี่ยนสีธีม
                            @elseif($permission === 'manage_permissions')
                                สามารถตั้งค่าสิทธิ์การเข้าถึงของผู้ใช้อื่น
                            @endif
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        @if($user->isSuperAdmin())
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <strong>หมายเหตุ:</strong> ผู้ใช้นี้เป็น Super Admin จึงมีสิทธิ์เข้าถึงทุกฟีเจอร์โดยอัตโนมัติ
                </p>
            </div>
        @endif

        <div class="mt-8 flex gap-3">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                บันทึกการตั้งค่า
            </button>
            <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
