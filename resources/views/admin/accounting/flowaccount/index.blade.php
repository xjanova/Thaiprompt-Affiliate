@extends('layouts.admin')

@section('title', 'FlowAccount Integration')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">FlowAccount Integration</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">เชื่อมต่อและซิงค์ข้อมูลกับ FlowAccount</p>
    </div>

    <!-- Connection Status -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="mr-4">
                    @if($connected ?? false)
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-3xl text-green-600"></i>
                        </div>
                    @else
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <i class="fas fa-unlink text-3xl text-gray-400"></i>
                        </div>
                    @endif
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        @if($connected ?? false)
                            เชื่อมต่อแล้ว
                        @else
                            ยังไม่ได้เชื่อมต่อ
                        @endif
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        @if($connected ?? false)
                            บัญชี: {{ $accountEmail ?? '-' }}
                        @else
                            กรุณาเชื่อมต่อบัญชี FlowAccount ของคุณ
                        @endif
                    </p>
                </div>
            </div>
            <div>
                @if($connected ?? false)
                    <form action="{{ route('admin.accounting.flowaccount.disconnect') }}" method="POST" onsubmit="return confirm('ยืนยันการยกเลิกการเชื่อมต่อ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                            <i class="fas fa-unlink mr-2"></i>ยกเลิกการเชื่อมต่อ
                        </button>
                    </form>
                @else
                    <a href="{{ route('admin.accounting.flowaccount.connect') }}" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all">
                        <i class="fas fa-link mr-2"></i>เชื่อมต่อ FlowAccount
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if($connected ?? false)
    <!-- Sync Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ซิงค์ล่าสุด</div>
            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $lastSync ?? 'ไม่เคย' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ใบแจ้งหนี้</div>
            <div class="text-lg font-bold text-blue-600">{{ number_format($syncStats['invoices'] ?? 0) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">รายจ่าย</div>
            <div class="text-lg font-bold text-orange-600">{{ number_format($syncStats['expenses'] ?? 0) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ผู้ติดต่อ</div>
            <div class="text-lg font-bold text-purple-600">{{ number_format($syncStats['contacts'] ?? 0) }}</div>
        </div>
    </div>

    <!-- Sync Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">การซิงค์ข้อมูล</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <form action="{{ route('admin.accounting.flowaccount.sync', 'invoices') }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-sync mr-2"></i>ซิงค์ใบแจ้งหนี้
                </button>
            </form>
            <form action="{{ route('admin.accounting.flowaccount.sync', 'expenses') }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700">
                    <i class="fas fa-sync mr-2"></i>ซิงค์รายจ่าย
                </button>
            </form>
            <form action="{{ route('admin.accounting.flowaccount.sync', 'contacts') }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700">
                    <i class="fas fa-sync mr-2"></i>ซิงค์ผู้ติดต่อ
                </button>
            </form>
        </div>
        <div class="mt-4">
            <form action="{{ route('admin.accounting.flowaccount.sync', 'all') }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all">
                    <i class="fas fa-sync mr-2"></i>ซิงค์ทั้งหมด
                </button>
            </form>
        </div>
    </div>

    <!-- Sync Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">การตั้งค่า</h3>
        <form action="{{ route('admin.accounting.flowaccount.settings') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="auto_sync" value="1" {{ ($settings['auto_sync'] ?? false) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งานการซิงค์อัตโนมัติ</span>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ช่วงเวลาการซิงค์อัตโนมัติ (นาที)
                </label>
                <select name="sync_interval" class="w-full md:w-64 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="15" {{ ($settings['sync_interval'] ?? 60) == 15 ? 'selected' : '' }}>15 นาที</option>
                    <option value="30" {{ ($settings['sync_interval'] ?? 60) == 30 ? 'selected' : '' }}>30 นาที</option>
                    <option value="60" {{ ($settings['sync_interval'] ?? 60) == 60 ? 'selected' : '' }}>1 ชั่วโมง</option>
                    <option value="180" {{ ($settings['sync_interval'] ?? 60) == 180 ? 'selected' : '' }}>3 ชั่วโมง</option>
                    <option value="360" {{ ($settings['sync_interval'] ?? 60) == 360 ? 'selected' : '' }}>6 ชั่วโมง</option>
                </select>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>
    @endif

    @if(!($connected ?? false))
    <!-- Setup Instructions -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-4">
            <i class="fas fa-info-circle mr-2"></i>วิธีการเชื่อมต่อ FlowAccount
        </h3>
        <ol class="list-decimal list-inside space-y-2 text-blue-800 dark:text-blue-300">
            <li>คลิกปุ่ม "เชื่อมต่อ FlowAccount" ด้านบน</li>
            <li>เข้าสู่ระบบ FlowAccount ของคุณ</li>
            <li>อนุญาตให้แอพพลิเคชันเข้าถึงข้อมูลบัญชีของคุณ</li>
            <li>รอการเชื่อมต่อเสร็จสมบูรณ์</li>
            <li>เริ่มซิงค์ข้อมูลระหว่างระบบ</li>
        </ol>
    </div>
    @endif
</div>
@endsection
