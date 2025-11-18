@extends('layouts.admin-v3')

@section('title', 'สร้างเทมเพลตการประเมินผล')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">สร้างเทมเพลตการประเมินผล</h1>
                <p class="text-purple-100">กรอกข้อมูลเทมเพลตการประเมินผลการปฏิบัติงาน</p>
            </div>
            <a href="{{ route('admin.hrm.performance.templates.index') }}"
               class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.hrm.performance.templates.store') }}" class="space-y-6">
        @csrf

        <!-- Basic Information -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อเทมเพลต <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 @error('name') border-red-500 @enderror"
                           placeholder="เช่น เทมเพลตประเมินผลประจำปี 2024">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                              placeholder="อธิบายรายละเอียดของเทมเพลตนี้">{{ old('description') }}</textarea>
                    @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทการประเมิน</label>
                    <select name="review_type"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">เลือกประเภท</option>
                        <option value="annual" {{ old('review_type') == 'annual' ? 'selected' : '' }}>ประจำปี</option>
                        <option value="quarterly" {{ old('review_type') == 'quarterly' ? 'selected' : '' }}>รายไตรมาส</option>
                        <option value="monthly" {{ old('review_type') == 'monthly' ? 'selected' : '' }}>รายเดือน</option>
                        <option value="probation" {{ old('review_type') == 'probation' ? 'selected' : '' }}>ทดลองงาน</option>
                        <option value="project" {{ old('review_type') == 'project' ? 'selected' : '' }}>ตามโครงการ</option>
                    </select>
                    @error('review_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คะแนนเต็ม</label>
                    <input type="number" name="max_score" value="{{ old('max_score', 100) }}" min="1" step="1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 @error('max_score') border-red-500 @enderror">
                    @error('max_score')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- Evaluation Criteria -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">หัวข้อการประเมิน</h3>
            <div id="criteria-container" class="space-y-4">
                <div class="criteria-item border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-5">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หัวข้อ</label>
                            <input type="text" name="criteria[0][name]"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                                   placeholder="เช่น คุณภาพงาน">
                        </div>
                        <div class="md:col-span-5">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                            <input type="text" name="criteria[0][description]"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                                   placeholder="อธิบายหัวข้อการประเมิน">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">น้ำหนัก (%)</label>
                            <input type="number" name="criteria[0][weight]" min="0" max="100" step="1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                                   placeholder="10">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" id="add-criteria" class="mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                เพิ่มหัวข้อการประเมิน
            </button>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">หมายเหตุ: ผลรวมน้ำหนักควรเท่ากับ 100%</p>
        </div>

        <!-- Rating Scale -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">มาตรวัดการให้คะแนน</h3>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระบบการให้คะแนน</label>
                    <select name="rating_scale"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="5" {{ old('rating_scale', 5) == 5 ? 'selected' : '' }}>มาตรวัด 5 ระดับ (1-5)</option>
                        <option value="10" {{ old('rating_scale') == 10 ? 'selected' : '' }}>มาตรวัด 10 ระดับ (1-10)</option>
                        <option value="100" {{ old('rating_scale') == 100 ? 'selected' : '' }}>มาตรวัด 100 คะแนน (0-100)</option>
                    </select>
                    @error('rating_scale')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำแนะนำการประเมิน</label>
                    <textarea name="rating_guidelines" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                              placeholder="ระบุคำแนะนำหรือเกณฑ์การให้คะแนนในแต่ละระดับ">{{ old('rating_guidelines') }}</textarea>
                    @error('rating_guidelines')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถานะ</h3>
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', true) ? 'checked' : '' }}
                       class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    เปิดใช้งานเทมเพลตนี้
                </label>
            </div>
            @error('is_active')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.hrm.performance.templates.index') }}"
               class="px-6 py-2 bg-gray-200 text-gray-700 dark:bg-slate-600 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-slate-500 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                บันทึก
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let criteriaCount = 1;
    const addButton = document.getElementById('add-criteria');
    const container = document.getElementById('criteria-container');

    addButton.addEventListener('click', function() {
        const newCriteria = document.createElement('div');
        newCriteria.className = 'criteria-item border border-gray-300 dark:border-gray-600 rounded-lg p-4';
        newCriteria.innerHTML = `
            <div class="flex justify-between items-start mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">หัวข้อที่ ${criteriaCount + 1}</span>
                <button type="button" class="remove-criteria text-red-600 hover:text-red-800 text-sm">ลบ</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หัวข้อ</label>
                    <input type="text" name="criteria[${criteriaCount}][name]"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                           placeholder="เช่น คุณภาพงาน">
                </div>
                <div class="md:col-span-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <input type="text" name="criteria[${criteriaCount}][description]"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                           placeholder="อธิบายหัวข้อการประเมิน">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">น้ำหนัก (%)</label>
                    <input type="number" name="criteria[${criteriaCount}][weight]" min="0" max="100" step="1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                           placeholder="10">
                </div>
            </div>
        `;
        container.appendChild(newCriteria);
        criteriaCount++;
    });

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-criteria')) {
            e.target.closest('.criteria-item').remove();
        }
    });
});
</script>
@endpush
@endsection
