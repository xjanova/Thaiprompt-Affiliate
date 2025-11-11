@extends('layouts.admin')

@section('title', 'สร้าง Slider ใหม่')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.smart-sliders.index') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-4 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                กลับไปหน้ารายการ
            </a>
            <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">
                ✨ สร้าง Slider ใหม่
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">เริ่มต้นจากเทมเพลตหรือสร้างใหม่เลยก็ได้</p>
        </div>

        <form action="{{ route('admin.smart-sliders.store') }}" method="POST" x-data="sliderForm()">
            @csrf

            {{-- Step Indicators --}}
            <div class="mb-8">
                <div class="flex items-center justify-between max-w-2xl mx-auto">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold" :class="step === 1 ? 'ring-4 ring-blue-200' : ''">1</div>
                        <span class="font-semibold text-gray-900 dark:text-white">เลือกเทมเพลต</span>
                    </div>
                    <div class="flex-1 h-1 bg-gray-300 dark:bg-slate-600 mx-4" :class="step > 1 ? 'bg-blue-600' : ''"></div>
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-full text-white flex items-center justify-center font-bold" :class="step >= 2 ? 'bg-blue-600 ring-4 ring-blue-200' : 'bg-gray-300 dark:bg-slate-600'">2</div>
                        <span class="font-semibold" :class="step >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-500'">ตั้งค่าพื้นฐาน</span>
                    </div>
                </div>
            </div>

            {{-- Step 1: Template Selection --}}
            <div x-show="step === 1" class="mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">เลือกเทมเพลต</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Blank Template --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="template_id" value="" x-model="selectedTemplate" class="sr-only">
                            <div class="border-2 rounded-xl p-6 transition-all duration-300"
                                 :class="selectedTemplate === '' ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-slate-600 hover:border-blue-400'">
                                <div class="aspect-video bg-gradient-to-br from-gray-200 to-gray-300 dark:from-slate-700 dark:to-slate-600 rounded-lg mb-4 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </div>
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-1">เริ่มต้นใหม่</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">สร้าง Slider จากศูนย์</p>
                                @if($selectedTemplate === '')
                                <div class="absolute top-4 right-4">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                @endif
                            </div>
                        </label>

                        {{-- Templates from Database --}}
                        @foreach($templates as $template)
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="template_id" value="{{ $template->id }}" x-model="selectedTemplate" class="sr-only">
                            <div class="border-2 rounded-xl p-6 transition-all duration-300"
                                 :class="selectedTemplate === '{{ $template->id }}' ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-slate-600 hover:border-blue-400'">
                                <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded-lg mb-4 overflow-hidden">
                                    @if($template->thumbnail)
                                    <img src="{{ $template->getThumbnailUrl() }}" alt="{{ $template->name }}" class="w-full h-full object-cover">
                                    @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    @endif
                                </div>
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-1">{{ $template->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $template->description }}</p>
                                @if($template->is_premium)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded mt-2">
                                    ⭐ Premium
                                </span>
                                @endif
                                <template x-if="selectedTemplate === '{{ $template->id }}'">
                                    <div class="absolute top-4 right-4">
                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </template>
                            </div>
                        </label>
                        @endforeach
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="button" @click="step = 2"
                                class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-300">
                            ถัดไป
                            <svg class="w-5 h-5 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 2: Basic Settings --}}
            <div x-show="step === 2" class="mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">ตั้งค่าพื้นฐาน</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Slider Name --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อ Slider <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" required x-model="formData.name"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white transition-all"
                                   placeholder="เช่น Homepage Hero Slider">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Slider Alias --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Alias (สำหรับเรียกใช้)
                            </label>
                            <input type="text" name="alias" x-model="formData.alias"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white transition-all"
                                   placeholder="homepage-hero (ถ้าไม่ระบุจะสร้างอัตโนมัติ)">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                ใช้เรียก Slider: <code class="px-2 py-1 bg-gray-100 dark:bg-slate-700 rounded">&lt;x-smart-slider slider="homepage-hero" /&gt;</code>
                            </p>
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบาย
                            </label>
                            <textarea name="description" x-model="formData.description" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white transition-all"
                                      placeholder="อธิบายว่า Slider นี้ใช้สำหรับอะไร..."></textarea>
                        </div>

                        {{-- Slider Type --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ประเภท Slider <span class="text-red-500">*</span>
                            </label>
                            <select name="type" required x-model="formData.type"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white transition-all">
                                <option value="simple">Simple - ธรรมดา</option>
                                <option value="block">Block - แบบกล่อง</option>
                                <option value="carousel">Carousel - หมุนเวียน</option>
                                <option value="showcase">Showcase - โชว์เคส</option>
                            </select>
                        </div>

                        {{-- Responsive Mode --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                โหมด Responsive <span class="text-red-500">*</span>
                            </label>
                            <select name="responsive_mode" required x-model="formData.responsive_mode"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white transition-all">
                                <option value="auto">Auto - ปรับอัตโนมัติ</option>
                                <option value="fullwidth">Full Width - เต็มความกว้าง</option>
                                <option value="boxed">Boxed - จำกัดความกว้าง</option>
                                <option value="fullpage">Full Page - เต็มหน้าจอ</option>
                            </select>
                        </div>

                        {{-- Width --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ความกว้าง (px) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="width" required x-model="formData.width" min="100" max="3840"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white transition-all"
                                   placeholder="1920">
                        </div>

                        {{-- Height --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ความสูง (px) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="height" required x-model="formData.height" min="100" max="2160"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white transition-all"
                                   placeholder="800">
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-8 flex items-center justify-between">
                        <button type="button" @click="step = 1"
                                class="px-6 py-3 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            ย้อนกลับ
                        </button>
                        <button type="submit"
                                class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-300">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            สร้าง Slider
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function sliderForm() {
    return {
        step: 1,
        selectedTemplate: '',
        formData: {
            name: '',
            alias: '',
            description: '',
            type: 'simple',
            responsive_mode: 'auto',
            width: 1920,
            height: 800
        }
    }
}
</script>
@endpush
@endsection
