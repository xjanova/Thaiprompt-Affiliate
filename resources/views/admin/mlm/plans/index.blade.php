@extends('layouts.admin-v3')

@section('title', 'จัดการแผนคอมมิชชัน MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fas fa-layer-group text-purple-600 dark:text-purple-400"></i>
            จัดการแผนคอมมิชชัน MLM
        </h1>
        <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">ระบบใช้แผนคอมมิชชัน Global บังคับทั้งระบบ</p>
    </div>

    <!-- Info Card -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl shadow-lg p-8 border-2 border-blue-200 dark:border-blue-700">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-blue-500 dark:bg-blue-600 rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-info-circle text-white text-3xl"></i>
                </div>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">ระบบใช้แผนคอมมิชชัน Global</h2>
                <div class="space-y-2 text-gray-700 dark:text-gray-300 dark:text-gray-300">
                    <p class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                        <span>แผนคอมมิชชันถูกกำหนดโดยตรงในโค้ดและ MlmGlobalSettings</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                        <span>ไม่ต้องจัดการแผนคอมมิชชันผ่าน UI</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                        <span>สมาชิกทุกคนใช้แผนคอมมิชชันเดียวกัน</span>
                    </p>
                </div>

                <div class="mt-6 pt-6 border-t border-blue-200 dark:border-blue-700">
                    <p class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                        <strong>หมายเหตุ:</strong> หากต้องการจัดการแพคเกจสมาชิก (Bronze, Silver, Gold, etc.)
                        กรุณาไปที่เมนู <strong>MLM Packages</strong>
                    </p>
                    <a href="{{ route('admin.mlm.settings.index') }}"
                       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-6 py-3 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-cog"></i>
                        ตั้งค่า MLM Global Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('admin.mlm.settings.index') }}" class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-200 border-l-4 border-blue-500 dark:border-blue-400">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-cog text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Global Settings</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ตั้งค่าระบบ MLM</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.mlm.members.index') }}" class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-200 border-l-4 border-emerald-500 dark:border-emerald-400">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-emerald-600 dark:text-emerald-400 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">สมาชิก MLM</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">จัดการสมาชิก</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.mlm.reports.dashboard') }}" class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-200 border-l-4 border-purple-500 dark:border-purple-400">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-bar text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">รายงาน</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ดูสถิติและรายงาน</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
