@extends('layouts.admin-v3')

@section('title', 'แก้ไขรีวิวประสิทธิภาพ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-950 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.hrm.performance.reviews.show', $review) }}"
               class="p-2 bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition-all duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-3xl font-bold">แก้ไขรีวิวประสิทธิภาพ</h2>
                <p class="text-purple-100 mt-1">อัปเดตข้อมูลการประเมินและคะแนน</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.hrm.performance.reviews.update', $review) }}" method="POST" class="space-y-8 p-8">
            @csrf
            @method('PUT')

            <!-- Basic Information -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            พนักงาน <span class="text-red-500">*</span>
                        </label>
                        <select name="employee_id" id="employee_id" required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('employee_id') border-red-500 @enderror">
                            <option value="">เลือกพนักงาน</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ (old('employee_id', $review->employee_id) == $employee->id) ? 'selected' : '' }}>
                                    {{ $employee->first_name }} {{ $employee->last_name }} - {{ $employee->position ?? 'ไม่ระบุตำแหน่ง' }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="review_period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            รอบการประเมิน <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="review_period" id="review_period" value="{{ old('review_period', $review->review_period) }}" required
                               placeholder="เช่น Q1 2024, ปี 2567"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('review_period') border-red-500 @enderror">
                        @error('review_period')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="review_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ประเภทการประเมิน <span class="text-red-500">*</span>
                        </label>
                        <select name="review_type" id="review_type" required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('review_type') border-red-500 @enderror">
                            <option value="">เลือกประเภท</option>
                            <option value="annual" {{ old('review_type', $review->review_type) == 'annual' ? 'selected' : '' }}>ประจำปี</option>
                            <option value="quarterly" {{ old('review_type', $review->review_type) == 'quarterly' ? 'selected' : '' }}>รายไตรมาส</option>
                            <option value="probation" {{ old('review_type', $review->review_type) == 'probation' ? 'selected' : '' }}>ทดลองงาน</option>
                            <option value="promotion" {{ old('review_type', $review->review_type) == 'promotion' ? 'selected' : '' }}>การเลื่อนตำแหน่ง</option>
                        </select>
                        @error('review_type')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="review_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันที่ประเมิน <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="review_date" id="review_date" value="{{ old('review_date', $review->review_date) }}" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('review_date') border-red-500 @enderror">
                        @error('review_date')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="period_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันเริ่มต้นรอบ <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="period_start_date" id="period_start_date" value="{{ old('period_start_date', $review->period_start_date) }}" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('period_start_date') border-red-500 @enderror">
                        @error('period_start_date')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="period_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันสิ้นสุดรอบ <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="period_end_date" id="period_end_date" value="{{ old('period_end_date', $review->period_end_date) }}" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('period_end_date') border-red-500 @enderror">
                        @error('period_end_date')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Performance Ratings -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">คะแนนประเมิน (1-5 ดาว)</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $ratings = [
                            'technical_skills_rating' => 'ทักษะเทคนิค',
                            'communication_rating' => 'การสื่อสาร',
                            'teamwork_rating' => 'การทำงานเป็นทีม',
                            'leadership_rating' => 'ความเป็นผู้นำ',
                            'problem_solving_rating' => 'การแก้ปัญหา',
                            'productivity_rating' => 'ผลผลิต',
                            'quality_of_work_rating' => 'คุณภาพงาน',
                            'punctuality_rating' => 'ความตรงต่อเวลา',
                            'initiative_rating' => 'ความคิดริเริ่ม',
                            'adaptability_rating' => 'การปรับตัว'
                        ];
                    @endphp

                    @foreach($ratings as $field => $label)
                        <div class="rating-field">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $label }}</label>
                            <div class="flex items-center gap-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="{{ $field }}" value="{{ $i }}" {{ old($field, $review->$field) == $i ? 'checked' : '' }}
                                               class="sr-only peer"
                                               onchange="updateStars(this)">
                                        <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 peer-checked:text-yellow-400 hover:text-yellow-400 transition-colors duration-200"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    </label>
                                @endfor
                            </div>
                            @error($field)
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Evaluation Details -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">รายละเอียดการประเมิน</h3>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="strengths" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จุดแข็ง</label>
                        <textarea name="strengths" id="strengths" rows="4"
                                  placeholder="อธิบายจุดแข็งและความสามารถที่โดดเด่นของพนักงาน..."
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">{{ old('strengths', $review->strengths) }}</textarea>
                    </div>

                    <div>
                        <label for="areas_for_improvement" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จุดที่ควรพัฒนา</label>
                        <textarea name="areas_for_improvement" id="areas_for_improvement" rows="4"
                                  placeholder="ระบุจุดที่ควรพัฒนาและปรับปรุง..."
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">{{ old('areas_for_improvement', $review->areas_for_improvement) }}</textarea>
                    </div>

                    <div>
                        <label for="achievements" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ผลงานที่โดดเด่น</label>
                        <textarea name="achievements" id="achievements" rows="4"
                                  placeholder="ระบุผลงานหรือความสำเร็จที่โดดเด่นในรอบนี้..."
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">{{ old('achievements', $review->achievements) }}</textarea>
                    </div>

                    <div>
                        <label for="goals_for_next_period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เป้าหมายสำหรับรอบถัดไป</label>
                        <textarea name="goals_for_next_period" id="goals_for_next_period" rows="4"
                                  placeholder="กำหนดเป้าหมายและทิศทางพัฒนาสำหรับรอบถัดไป..."
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">{{ old('goals_for_next_period', $review->goals_for_next_period) }}</textarea>
                    </div>

                    <div>
                        <label for="training_needs" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความต้องการอบรม</label>
                        <textarea name="training_needs" id="training_needs" rows="3"
                                  placeholder="ระบุหลักสูตรหรือทักษะที่ควรพัฒนา..."
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">{{ old('training_needs', $review->training_needs) }}</textarea>
                    </div>

                    <div>
                        <label for="reviewer_comments" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความคิดเห็นผู้ประเมิน</label>
                        <textarea name="reviewer_comments" id="reviewer_comments" rows="4"
                                  placeholder="เพิ่มความคิดเห็นและข้อเสนอแนะเพิ่มเติม..."
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">{{ old('reviewer_comments', $review->reviewer_comments) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Recommendations -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">คำแนะนำ</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="recommend_promotion" id="recommend_promotion" value="1" {{ old('recommend_promotion', $review->recommend_promotion) ? 'checked' : '' }}
                               class="w-5 h-5 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500 dark:bg-gray-700">
                        <label for="recommend_promotion" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            แนะนำให้เลื่อนตำแหน่ง
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="recommend_salary_increase" id="recommend_salary_increase" value="1" {{ old('recommend_salary_increase', $review->recommend_salary_increase) ? 'checked' : '' }}
                               class="w-5 h-5 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500 dark:bg-gray-700"
                               onchange="toggleSalaryInput(this)">
                        <label for="recommend_salary_increase" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            แนะนำให้ปรับเงินเดือน
                        </label>
                    </div>

                    <div id="salary_increase_field" class="md:col-span-2" style="display: {{ old('recommend_salary_increase', $review->recommend_salary_increase) ? 'block' : 'none' }}">
                        <label for="recommended_increase_percentage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เปอร์เซ็นต์การปรับเงินเดือนที่แนะนำ (%)
                        </label>
                        <input type="number" name="recommended_increase_percentage" id="recommended_increase_percentage" value="{{ old('recommended_increase_percentage', $review->recommended_increase_percentage) }}"
                               min="0" max="100" step="0.01"
                               placeholder="เช่น 10"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.hrm.performance.reviews.show', $review) }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateStars(element) {
    const parent = element.closest('.rating-field');
    const value = parseInt(element.value);
    const stars = parent.querySelectorAll('svg');

    stars.forEach((star, index) => {
        if (index < value) {
            star.classList.remove('text-gray-300', 'dark:text-gray-600');
            star.classList.add('text-yellow-400');
        } else {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-300', 'dark:text-gray-600');
        }
    });
}

function toggleSalaryInput(checkbox) {
    const field = document.getElementById('salary_increase_field');
    field.style.display = checkbox.checked ? 'block' : 'none';
}

// Initialize stars on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.rating-field input[type="radio"]:checked').forEach(function(radio) {
        updateStars(radio);
    });
});
</script>
@endsection
