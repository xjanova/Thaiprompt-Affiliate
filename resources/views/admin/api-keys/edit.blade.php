@extends('layouts.admin-v3')

@section('title', 'แก้ไข API Key')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-200">
                    🔑 แก้ไข API Key
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">
                    แก้ไขรายละเอียดและสิทธิ์การเข้าถึง API Key
                </p>
            </div>
            <a href="{{ route('admin.api-management.keys.show', $apiKey) }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 dark:text-gray-200 rounded-xl transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.api-management.keys.update', $apiKey) }}" method="POST" class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <!-- Basic Information Section -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-200 mb-4">📋 ข้อมูลพื้นฐาน</h3>

                <!-- Name -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        ชื่อ API Key <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $apiKey->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                           placeholder="เช่น Production API Key">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- User Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        ผู้ใช้
                    </label>
                    <select name="user_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('user_id') border-red-500 @enderror">
                        <option value="">ไม่ระบุผู้ใช้ (System Key)</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $apiKey->user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">เลือกผู้ใช้ที่จะเป็นเจ้าของ API Key หรือเว้นว่างสำหรับ System Key</p>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('description') border-red-500 @enderror"
                              placeholder="คำอธิบายเกี่ยวกับการใช้งาน API Key นี้">{{ old('description', $apiKey->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expires At -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        วันหมดอายุ
                    </label>
                    <input type="date" name="expires_at" value="{{ old('expires_at', $apiKey->expires_at ? $apiKey->expires_at->format('Y-m-d') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('expires_at') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">เว้นว่างถ้าไม่ต้องการให้หมดอายุ</p>
                    @error('expires_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Permissions Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-200 mb-4">🔐 สิทธิ์การเข้าถึง</h3>

                <!-- Scopes -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        ขอบเขตการใช้งาน (Scopes)
                    </label>
                    <div class="space-y-2">
                        @foreach($scopes as $scope)
                            <label class="flex items-center">
                                <input type="checkbox" name="scopes[]" value="{{ $scope }}"
                                       {{ is_array(old('scopes', $apiKey->scopes)) && in_array($scope, old('scopes', $apiKey->scopes ?? [])) ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 border-gray-300 dark:border-gray-600 rounded focus:ring-green-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                    {{ ucfirst($scope) }}
                                    @if($scope == 'read')
                                        <span class="text-gray-500 dark:text-gray-400 text-xs">(อ่านข้อมูล)</span>
                                    @elseif($scope == 'write')
                                        <span class="text-gray-500 dark:text-gray-400 text-xs">(เขียน/แก้ไขข้อมูล)</span>
                                    @elseif($scope == 'delete')
                                        <span class="text-gray-500 dark:text-gray-400 text-xs">(ลบข้อมูล)</span>
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        Endpoints ที่อนุญาต
                    </label>
                    <div class="border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl p-4 max-h-60 overflow-y-auto dark:bg-gray-700">
                        @php
                            $groupedEndpoints = $endpoints->groupBy('category');
                            $allowedEndpoints = old('allowed_endpoints', $apiKey->allowed_endpoints ?? []);
                        @endphp
                        @foreach($groupedEndpoints as $category => $categoryEndpoints)
                            <div class="mb-4 last:mb-0">
                                <div class="font-semibold text-gray-900 dark:text-white dark:text-gray-200 mb-2">
                                    {{ $category }}
                                </div>
                                <div class="space-y-2 ml-4">
                                    @foreach($categoryEndpoints as $endpoint)
                                        <label class="flex items-start">
                                            <input type="checkbox" name="allowed_endpoints[]" value="{{ $endpoint->id }}"
                                                   {{ is_array($allowedEndpoints) && in_array($endpoint->id, $allowedEndpoints) ? 'checked' : '' }}
                                                   class="w-4 h-4 text-green-600 border-gray-300 dark:border-gray-600 rounded focus:ring-green-500 mt-0.5">
                                            <div class="ml-2 flex-1">
                                                <div class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                                    <code class="bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-600 px-2 py-0.5 rounded text-xs">{{ $endpoint->method }}</code>
                                                    <span class="ml-1">{{ $endpoint->name }}</span>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $endpoint->path }}</div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">เว้นว่างเพื่ออนุญาตทุก endpoints</p>
                    @error('allowed_endpoints')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Security Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-200 mb-4">🔒 ความปลอดภัย</h3>

                <!-- Allowed IPs -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        IP Addresses ที่อนุญาต
                    </label>
                    <textarea name="allowed_ips" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white font-mono text-sm @error('allowed_ips') border-red-500 @enderror"
                              placeholder="192.168.1.1&#10;10.0.0.1&#10;203.154.12.34">{{ old('allowed_ips', $allowedIpsString) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">ใส่ IP แต่ละตัวในบรรทัดใหม่ เว้นว่างเพื่ออนุญาตทุก IP</p>
                    @error('allowed_ips')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Rate Limiting Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-200 mb-4">⏱️ การจำกัดอัตรา (Rate Limiting)</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Rate Limit Per Minute -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            จำกัดต่อนาที
                        </label>
                        <input type="number" name="rate_limit_per_minute" value="{{ old('rate_limit_per_minute', $apiKey->rate_limit_per_minute) }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('rate_limit_per_minute') border-red-500 @enderror"
                               placeholder="เช่น 60">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">จำนวน requests สูงสุดต่อนาที</p>
                        @error('rate_limit_per_minute')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rate Limit Per Hour -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            จำกัดต่อชั่วโมง
                        </label>
                        <input type="number" name="rate_limit_per_hour" value="{{ old('rate_limit_per_hour', $apiKey->rate_limit_per_hour) }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('rate_limit_per_hour') border-red-500 @enderror"
                               placeholder="เช่น 1000">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">จำนวน requests สูงสุดต่อชั่วโมง</p>
                        @error('rate_limit_per_hour')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rate Limit Per Day -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            จำกัดต่อวัน
                        </label>
                        <input type="number" name="rate_limit_per_day" value="{{ old('rate_limit_per_day', $apiKey->rate_limit_per_day) }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('rate_limit_per_day') border-red-500 @enderror"
                               placeholder="เช่น 10000">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">จำนวน requests สูงสุดต่อวัน</p>
                        @error('rate_limit_per_day')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Monthly Quota -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            โควต้ารายเดือน
                        </label>
                        <input type="number" name="monthly_quota" value="{{ old('monthly_quota', $apiKey->monthly_quota) }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white @error('monthly_quota') border-red-500 @enderror"
                               placeholder="เช่น 100000">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">จำนวน requests สูงสุดต่อเดือน</p>
                        @error('monthly_quota')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Current Usage Info -->
                @if($apiKey->monthly_quota)
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900 dark:bg-opacity-20 rounded-xl">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-800 dark:text-blue-200">การใช้งานปัจจุบัน</span>
                        <span class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                            {{ number_format($apiKey->monthly_usage) }} / {{ number_format($apiKey->monthly_quota) }}
                        </span>
                    </div>
                    <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2">
                        @php
                            $percentage = ($apiKey->monthly_usage / $apiKey->monthly_quota) * 100;
                            $colorClass = $percentage >= 90 ? 'bg-red-500' : ($percentage >= 70 ? 'bg-yellow-500' : 'bg-blue-500');
                        @endphp
                        <div class="{{ $colorClass }} h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                    </div>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">{{ number_format($percentage, 1) }}% ใช้ไปแล้ว</p>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <a href="{{ route('admin.api-management.keys.show', $apiKey) }}"
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 dark:text-gray-200 rounded-xl hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                    💾 บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </div>
    </form>

    <!-- Danger Zone -->
    <div class="bg-red-50 dark:bg-red-900 dark:bg-opacity-20 rounded-xl shadow-md p-6 mt-6 border border-red-200 dark:border-red-800">
        <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-4">⚠️ Danger Zone</h3>
        <p class="text-sm text-red-700 dark:text-red-300 mb-4">
            การลบ API Key นี้จะลบข้อมูลทั้งหมด รวมถึงประวัติการใช้งาน การดำเนินการนี้ไม่สามารถย้อนกลับได้
        </p>
        <form action="{{ route('admin.api-management.keys.destroy', $apiKey) }}" method="POST"
              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ API Key นี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้\n\nการลบ API Key จะทำให้แอปพลิเคชันที่ใช้งาน key นี้ไม่สามารถเข้าถึง API ได้อีกต่อไป')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition">
                🗑️ ลบ API Key นี้
            </button>
        </form>
    </div>
</div>
@endsection
