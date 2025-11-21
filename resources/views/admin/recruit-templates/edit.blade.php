@extends('layouts.admin')

@section('title', 'แก้ไขเทมเพลต: ' . $template->title)

@section('content')
<div class="container-fluid px-4 py-6" x-data="templateForm()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                แก้ไขเทมเพลต
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {{ $template->title }}
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('admin.recruit-templates.preview', $template) }}" target="_blank"
               class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                ดูตัวอย่าง
            </a>
            <a href="{{ route('admin.recruit-templates.index') }}"
               class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <h3 class="font-semibold mb-2">กรุณาแก้ไขข้อผิดพลาดต่อไปนี้:</h3>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('admin.recruit-templates.update', $template) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            {{-- Basic Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ข้อมูลพื้นฐาน</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อเทมเพลต <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title', $template->title) }}" required
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบาย
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">{{ old('description', $template->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Messages --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ข้อความต้อนรับและแนะนำ</h3>

                <div class="space-y-6">
                    <div>
                        <label for="welcome_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ข้อความต้อนรับ <span class="text-red-500">*</span>
                        </label>
                        <textarea name="welcome_message" id="welcome_message" rows="5" required
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">{{ old('welcome_message', $template->welcome_message) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ข้อความแรกที่ผู้มุ่งหวังจะเห็น</p>
                    </div>

                    <div>
                        <label for="pitch_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ข้อความชักชวน
                        </label>
                        <textarea name="pitch_message" id="pitch_message" rows="5"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">{{ old('pitch_message', $template->pitch_message) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ข้อความโน้มน้าวใจให้สมัคร</p>
                    </div>
                </div>
            </div>

            {{-- Benefits --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ประโยชน์ที่ได้รับ</h3>
                    <button type="button" @click="addBenefit()"
                            class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        เพิ่มประโยชน์
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(benefit, index) in benefits" :key="index">
                        <div class="flex gap-3">
                            <input type="text" :name="'benefits[' + index + ']'" x-model="benefits[index]"
                                   placeholder="ระบุประโยชน์..."
                                   class="flex-1 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <button type="button" @click="removeBenefit(index)"
                                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Features --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ฟีเจอร์ที่ได้รับ</h3>
                    <button type="button" @click="addFeature()"
                            class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        เพิ่มฟีเจอร์
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(feature, index) in features" :key="index">
                        <div class="flex gap-3">
                            <input type="text" :name="'features[' + index + ']'" x-model="features[index]"
                                   placeholder="ระบุฟีเจอร์..."
                                   class="flex-1 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <button type="button" @click="removeFeature(index)"
                                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Settings --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">การตั้งค่า</h3>

                <div class="space-y-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="require_line_friend" value="1" {{ old('require_line_friend', $template->require_line_friend) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                            บังคับเพิ่มเพื่อน LINE OA ก่อนสมัคร
                        </span>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="show_team_leader_info" value="1" {{ old('show_team_leader_info', $template->show_team_leader_info) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                            แสดงข้อมูลแม่ทีม
                        </span>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="show_statistics" value="1" {{ old('show_statistics', $template->show_statistics) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                            แสดงสถิติ
                        </span>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                            เปิดใช้งาน
                        </span>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $template->is_default) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                            ตั้งเป็นเทมเพลต Default
                        </span>
                    </label>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-end gap-4">
                <a href="{{ route('admin.recruit-templates.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                    บันทึกการแก้ไข
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function templateForm() {
    return {
        benefits: @json(old('benefits', $template->benefits ?? [''])),
        features: @json(old('features', $template->features ?? [''])),

        addBenefit() {
            this.benefits.push('');
        },

        removeBenefit(index) {
            this.benefits.splice(index, 1);
        },

        addFeature() {
            this.features.push('');
        },

        removeFeature(index) {
            this.features.splice(index, 1);
        }
    }
}
</script>
@endpush
@endsection
