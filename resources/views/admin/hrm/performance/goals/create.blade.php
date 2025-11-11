@extends('layouts.admin')

@section('title', 'สร้างเป้าหมายประสิทธิภาพ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-700 dark:from-purple-900 dark:via-indigo-900 dark:to-purple-950 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.hrm.performance.goals.index') }}"
               class="p-2 bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition-all duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-3xl font-bold">สร้างเป้าหมายประสิทธิภาพใหม่</h2>
                <p class="text-purple-100 mt-1">กำหนดเป้าหมายและตัวชี้วัดผลงานให้กับพนักงาน</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.hrm.performance.goals.store') }}" method="POST" class="space-y-8 p-8">
            @csrf

            <!-- Employee Information Section -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลพนักงาน</h3>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            พนักงาน <span class="text-red-500">*</span>
                        </label>
                        <select name="employee_id" id="employee_id" required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('employee_id') border-red-500 @enderror">
                            <option value="">เลือกพนักงาน</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->first_name }} {{ $employee->last_name }} - {{ $employee->position ?? 'ไม่ระบุตำแหน่ง' }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Goal Details Section -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">รายละเอียดเป้าหมาย</h3>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <!-- Goal Title -->
                    <div>
                        <label for="goal_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อเป้าหมาย <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="goal_title" id="goal_title" value="{{ old('goal_title') }}" required
                               placeholder="เช่น เพิ่มยอดขาย 20% ในไตรมาสนี้"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('goal_title') border-red-500 @enderror">
                        @error('goal_title')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Goal Description -->
                    <div>
                        <label for="goal_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบายเป้าหมาย <span class="text-red-500">*</span>
                        </label>
                        <textarea name="goal_description" id="goal_description" rows="4" required
                                  placeholder="อธิบายรายละเอียดเป้าหมาย วัตถุประสงค์ และความคาดหวัง..."
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('goal_description') border-red-500 @enderror">{{ old('goal_description') }}</textarea>
                        @error('goal_description')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Goal Type & Category -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="goal_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ประเภทเป้าหมาย <span class="text-red-500">*</span>
                            </label>
                            <select name="goal_type" id="goal_type" required
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('goal_type') border-red-500 @enderror">
                                <option value="">เลือกประเภท</option>
                                <option value="individual" {{ old('goal_type') == 'individual' ? 'selected' : '' }}>บุคคล</option>
                                <option value="team" {{ old('goal_type') == 'team' ? 'selected' : '' }}>ทีม</option>
                                <option value="departmental" {{ old('goal_type') == 'departmental' ? 'selected' : '' }}>แผนก</option>
                                <option value="organizational" {{ old('goal_type') == 'organizational' ? 'selected' : '' }}>องค์กร</option>
                            </select>
                            @error('goal_type')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                หมวดหมู่ <span class="text-red-500">*</span>
                            </label>
                            <select name="category" id="category" required
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('category') border-red-500 @enderror">
                                <option value="">เลือกหมวดหมู่</option>
                                <option value="productivity" {{ old('category') == 'productivity' ? 'selected' : '' }}>ผลผลิต</option>
                                <option value="quality" {{ old('category') == 'quality' ? 'selected' : '' }}>คุณภาพ</option>
                                <option value="learning" {{ old('category') == 'learning' ? 'selected' : '' }}>การเรียนรู้</option>
                                <option value="behavior" {{ old('category') == 'behavior' ? 'selected' : '' }}>พฤติกรรม</option>
                                <option value="project" {{ old('category') == 'project' ? 'selected' : '' }}>โครงการ</option>
                                <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>อื่นๆ</option>
                            </select>
                            @error('category')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ระดับความสำคัญ <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-purple-500 dark:hover:border-purple-500 transition-all duration-200 has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50 dark:has-[:checked]:bg-purple-900/20">
                                <input type="radio" name="priority" value="low" {{ old('priority') == 'low' ? 'checked' : '' }} class="sr-only">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">ต่ำ</span>
                                </div>
                            </label>

                            <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-purple-500 dark:hover:border-purple-500 transition-all duration-200 has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50 dark:has-[:checked]:bg-purple-900/20">
                                <input type="radio" name="priority" value="medium" {{ old('priority') == 'medium' ? 'checked' : '' }} class="sr-only">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">ปานกลาง</span>
                                </div>
                            </label>

                            <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-purple-500 dark:hover:border-purple-500 transition-all duration-200 has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50 dark:has-[:checked]:bg-purple-900/20">
                                <input type="radio" name="priority" value="high" {{ old('priority') == 'high' ? 'checked' : '' }} class="sr-only">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">สูง</span>
                                </div>
                            </label>

                            <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-purple-500 dark:hover:border-purple-500 transition-all duration-200 has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50 dark:has-[:checked]:bg-purple-900/20">
                                <input type="radio" name="priority" value="critical" {{ old('priority') == 'critical' ? 'checked' : '' }} class="sr-only">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">วิกฤต</span>
                                </div>
                            </label>
                        </div>
                        @error('priority')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Timeline Section -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ระยะเวลา</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันที่เริ่มต้น <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('start_date') border-red-500 @enderror">
                        @error('start_date')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="target_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันที่เป้าหมาย <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="target_date" id="target_date" value="{{ old('target_date') }}" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('target_date') border-red-500 @enderror">
                        @error('target_date')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Measurement Section -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">การวัดผล</h3>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="measurement_criteria" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เกณฑ์การวัด
                        </label>
                        <textarea name="measurement_criteria" id="measurement_criteria" rows="3"
                                  placeholder="อธิบายวิธีการวัดความสำเร็จของเป้าหมายนี้..."
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('measurement_criteria') border-red-500 @enderror">{{ old('measurement_criteria') }}</textarea>
                        @error('measurement_criteria')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="target_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ค่าเป้าหมาย
                            </label>
                            <input type="text" name="target_value" id="target_value" value="{{ old('target_value') }}"
                                   placeholder="เช่น 100 หน่วย, 20%, 1,000,000 บาท"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('target_value') border-red-500 @enderror">
                            @error('target_value')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="weight_percentage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                น้ำหนักเป้าหมาย (%)
                            </label>
                            <input type="number" name="weight_percentage" id="weight_percentage" value="{{ old('weight_percentage') }}"
                                   min="0" max="100" step="0.01"
                                   placeholder="0-100"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('weight_percentage') border-red-500 @enderror">
                            @error('weight_percentage')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.hrm.performance.goals.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    สร้างเป้าหมาย
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
