@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า AI Core')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.ai-core.dashboard') }}"
           class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white shadow-lg hover:shadow-xl transition-all hover:scale-105">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 dark:from-purple-400 dark:to-indigo-400 bg-clip-text text-transparent">
                ตั้งค่า AI Core
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                กำหนดค่าระบบ AI Core แบบ Global
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.ai-core.settings.update') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Settings --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Global Quota Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 text-white">
                            <i class="fas fa-gauge-high text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                Quota Settings
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                การจัดการ Quota แบบ Global
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        {{-- Enable Global Quota --}}
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                            <div class="flex-1">
                                <label class="font-semibold text-gray-900 dark:text-white">
                                    เปิดใช้งาน Global Quota
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    จำกัดการใช้งานรวมทุก Tenant
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_global_quota" value="1" class="sr-only peer" checked>
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        {{-- Auto Reset Quota --}}
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                            <div class="flex-1">
                                <label class="font-semibold text-gray-900 dark:text-white">
                                    รีเซ็ต Quota อัตโนมัติ
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    รีเซ็ต Quota ทุกวันที่ 1 ของเดือน
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_auto_reset" value="1" class="sr-only peer" checked>
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Alert Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-pink-600 text-white">
                            <i class="fas fa-bell text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                Alert Settings
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                การแจ้งเตือนเมื่อมีเหตุการณ์สำคัญ
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        {{-- Enable Alerts --}}
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                            <div class="flex-1">
                                <label class="font-semibold text-gray-900 dark:text-white">
                                    เปิดใช้งาน Alerts
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    แจ้งเตือนเมื่อมีปัญหาในระบบ
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_alerts" value="1" class="sr-only peer" checked>
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        {{-- Alert Channels --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                            <label class="font-semibold text-gray-900 dark:text-white mb-3 block">
                                ช่องทางแจ้งเตือน
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" name="alert_channels[]" value="email" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" checked>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-envelope text-blue-500"></i>
                                        <span class="text-gray-900 dark:text-white">Email</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" name="alert_channels[]" value="line" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fab fa-line text-green-500"></i>
                                        <span class="text-gray-900 dark:text-white">LINE Notify</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" name="alert_channels[]" value="slack" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fab fa-slack text-purple-500"></i>
                                        <span class="text-gray-900 dark:text-white">Slack</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-green-600 text-white">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                Schedule Settings
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                การตั้งเวลาทำงานอัตโนมัติ
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        {{-- Enable Scheduler --}}
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                            <div class="flex-1">
                                <label class="font-semibold text-gray-900 dark:text-white">
                                    เปิดใช้งาน Scheduler
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    รัน Schedules ตามเวลาที่กำหนด
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_scheduler" value="1" class="sr-only peer" checked>
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Info Card --}}
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center gap-3 mb-4">
                        <i class="fas fa-info-circle text-3xl"></i>
                        <h3 class="text-lg font-bold">
                            ข้อมูล
                        </h3>
                    </div>
                    <ul class="space-y-2 text-sm text-blue-100">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle mt-0.5"></i>
                            <span>การตั้งค่าจะมีผลกับทุก Features</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle mt-0.5"></i>
                            <span>Global Quota ใช้ร่วมกันทุก Tenant</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle mt-0.5"></i>
                            <span>Alerts แจ้งเตือนผ่านช่องทางที่เลือก</span>
                        </li>
                    </ul>
                </div>

                {{-- Quick Stats --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        สถิติระบบ
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Features</span>
                            <span class="font-bold text-gray-900 dark:text-white">8</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active Tenants</span>
                            <span class="font-bold text-gray-900 dark:text-white">-</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active Schedules</span>
                            <span class="font-bold text-gray-900 dark:text-white">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="mt-8 flex items-center justify-between">
            <a href="{{ route('admin.ai-core.dashboard') }}"
               class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-xl transition">
                <i class="fas fa-times mr-2"></i>
                ยกเลิก
            </a>

            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                <i class="fas fa-save mr-2"></i>
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection
