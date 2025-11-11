@extends('layouts.admin')

@section('title', 'สร้าง API Key ใหม่')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    🔑 สร้าง API Key ใหม่
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    กรอกรายละเอียดเพื่อสร้าง API key สำหรับการเข้าถึง API
                </p>
            </div>
            <a href="{{ route('admin.api-management.keys.index') }}"
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.api-management.keys.store') }}" method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        @csrf

        <div class="space-y-6">
            <!-- Basic Information Section -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">📋 ข้อมูลพื้นฐาน</h3>

                <!-- Name -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อ API Key <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                           placeholder="เช่น Production API Key">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- User Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ผู้ใช้
                    </label>
                    <select name="user_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('user_id') border-red-500 @enderror">
                        <option value="">ไม่ระบุผู้ใช้ (System Key)</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เลือกผู้ใช้ที่จะเป็นเจ้าของ API Key หรือเว้นว่างสำหรับ System Key</p>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('description') border-red-500 @enderror"
                              placeholder="คำอธิบายเกี่ยวกับการใช้งาน API Key นี้">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expires At -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันหมดอายุ
                    </label>
                    <input type="date" name="expires_at" value="{{ old('expires_at') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('expires_at') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างถ้าไม่ต้องการให้หมดอายุ</p>
                    @error('expires_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Permissions Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">🔐 สิทธิ์การเข้าถึง</h3>

                <!-- Scopes -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ขอบเขตการใช้งาน (Scopes)
                    </label>
                    <div class="space-y-2">
                        @foreach($scopes as $scope)
                            <label class="flex items-center">
                                <input type="checkbox" name="scopes[]" value="{{ $scope }}"
                                       {{ is_array(old('scopes')) && in_array($scope, old('scopes')) ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ ucfirst($scope) }}
                                    @if($scope == 'read')
                                        <span class="text-gray-500 text-xs">(อ่านข้อมูล)</span>
                                    @elseif($scope == 'write')
                                        <span class="text-gray-500 text-xs">(เขียน/แก้ไขข้อมูล)</span>
                                    @elseif($scope == 'delete')
                                        <span class="text-gray-500 text-xs">(ลบข้อมูล)</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('scopes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Allowed Endpoints -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Endpoints ที่อนุญาต
                    </label>
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 max-h-60 overflow-y-auto dark:bg-gray-700">
                        @php
                            $groupedEndpoints = $endpoints->groupBy('category');
                        @endphp
                        @foreach($groupedEndpoints as $category => $categoryEndpoints)
                            <div class="mb-4 last:mb-0">
                                <div class="font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    {{ $category }}
                                </div>
                                <div class="space-y-2 ml-4">
                                    @foreach($categoryEndpoints as $endpoint)
                                        <label class="flex items-start">
                                            <input type="checkbox" name="allowed_endpoints[]" value="{{ $endpoint->id }}"
                                                   {{ is_array(old('allowed_endpoints')) && in_array($endpoint->id, old('allowed_endpoints')) ? 'checked' : '' }}
                                                   class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500 mt-0.5">
                                            <div class="ml-2 flex-1">
                                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                                    <code class="bg-gray-100 dark:bg-gray-600 px-2 py-0.5 rounded text-xs">{{ $endpoint->method }}</code>
                                                    <span class="ml-1">{{ $endpoint->name }}</span>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $endpoint->path }}</div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างเพื่ออนุญาตทุก endpoints</p>
                    @error('allowed_endpoints')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Security Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">🔒 ความปลอดภัย</h3>

                <!-- Allowed IPs -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        IP Addresses ที่อนุญาต
                    </label>
                    <textarea name="allowed_ips" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white font-mono text-sm @error('allowed_ips') border-red-500 @enderror"
                              placeholder="192.168.1.1&#10;10.0.0.1&#10;203.154.12.34">{{ old('allowed_ips') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ใส่ IP แต่ละตัวในบรรทัดใหม่ เว้นว่างเพื่ออนุญาตทุก IP</p>
                    @error('allowed_ips')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Rate Limiting Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">⏱️ การจำกัดอัตรา (Rate Limiting)</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Rate Limit Per Minute -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำกัดต่อนาที
                        </label>
                        <input type="number" name="rate_limit_per_minute" value="{{ old('rate_limit_per_minute') }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('rate_limit_per_minute') border-red-500 @enderror"
                               placeholder="เช่น 60">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวน requests สูงสุดต่อนาที</p>
                        @error('rate_limit_per_minute')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rate Limit Per Hour -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำกัดต่อชั่วโมง
                        </label>
                        <input type="number" name="rate_limit_per_hour" value="{{ old('rate_limit_per_hour') }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('rate_limit_per_hour') border-red-500 @enderror"
                               placeholder="เช่น 1000">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวน requests สูงสุดต่อชั่วโมง</p>
                        @error('rate_limit_per_hour')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rate Limit Per Day -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำกัดต่อวัน
                        </label>
                        <input type="number" name="rate_limit_per_day" value="{{ old('rate_limit_per_day') }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('rate_limit_per_day') border-red-500 @enderror"
                               placeholder="เช่น 10000">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวน requests สูงสุดต่อวัน</p>
                        @error('rate_limit_per_day')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Monthly Quota -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            โควต้ารายเดือน
                        </label>
                        <input type="number" name="monthly_quota" value="{{ old('monthly_quota') }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('monthly_quota') border-red-500 @enderror"
                               placeholder="เช่น 100000">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวน requests สูงสุดต่อเดือน</p>
                        @error('monthly_quota')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.api-management.keys.index') }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl">
                    🔑 สร้าง API Key
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
