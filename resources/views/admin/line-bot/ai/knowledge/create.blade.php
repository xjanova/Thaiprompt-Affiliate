@extends('layouts.admin-v3')

@section('title', 'เพิ่มฐานความรู้ - LINE AI Bot')

@section('content')
<div class="min-h-screen py-6" x-data="{
    knowledgeType: 'text',
    sourceType: 'upload',
    fileSelected: false,
    previewContent: ''
}">
    <!-- Beautiful LINE-Themed Header -->
    <div class="mb-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#00C900] p-10 shadow-2xl shadow-green-500/30">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 glass-fusion rounded-full -translate-x-48 -translate-y-48 animate-pulse border border-white/20 dark:border-white/10"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 glass-fusion rounded-full translate-x-32 translate-y-32 animate-pulse border border-white/20 dark:border-white/10" style="animation-delay: 0.5s"></div>
        </div>

        <!-- Floating Knowledge Icons -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-10 right-20 animate-bounce" style="animation-delay: 0.2s">
                <i class="fas fa-book text-white/20 text-6xl"></i>
            </div>
            <div class="absolute bottom-10 left-20 animate-bounce" style="animation-delay: 0.8s">
                <i class="fas fa-brain text-white/20 text-5xl"></i>
            </div>
        </div>

        <div class="relative">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-md flex items-center justify-center shadow-xl border border-white/30 border border-white/20 dark:border-white/10">
                    <i class="fas fa-book-open text-white text-3xl drop-shadow-lg"></i>
                </div>
                <div class="flex-1">
                    <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg tracking-tight">📚 เพิ่มฐานความรู้ใหม่</h1>
                    <p class="text-white/95 text-lg font-medium">สร้างแหล่งความรู้เพื่อให้ AI ตอบคำถามได้แม่นยำยิ่งขึ้น</p>
                </div>
            </div>

            <!-- AI Setting Info -->
            <div class="mt-6 flex items-center gap-3 glass-fusion backdrop-blur-md rounded-xl px-4 py-3 border border-white/25 border border-white/20 dark:border-white/10">
                <i class="fas fa-robot text-white text-xl"></i>
                <div class="flex-1">
                    <p class="text-white/80 text-xs font-semibold uppercase tracking-wide">AI Setting</p>
                    <p class="text-white text-lg font-bold">{{ $setting->name }}</p>
                </div>
                <span class="px-4 py-2 glass-fusion backdrop-blur-sm rounded-xl text-white font-bold border border-white/30">
                    {{ strtoupper($setting->provider) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Main Form Card -->
    <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden border border-white/20 dark:border-white/10">
        <form action="{{ route('admin.line-bot.ai.knowledge.store', $setting->id) }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-8">
            @csrf

            <!-- Knowledge Type Selection -->
            <div class="space-y-4">
                <label class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white dark:text-white">
                    <i class="fas fa-layer-group text-[#00B900]"></i>
                    ประเภทฐานความรู้
                    <span class="text-red-500">*</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Text Type -->
                    <button type="button" @click="knowledgeType = 'text'"
                            :class="knowledgeType === 'text' ? 'bg-gradient-to-br from-[#00B900] to-[#00E600] text-white shadow-2xl shadow-green-500/40 scale-105 border-transparent' : 'glass-fusion dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 dark:border-slate-600 hover:border-green-400 dark:hover:border-green-600'"
                            class="group relative overflow-hidden border-2 rounded-2xl p-6 transition-all duration-300 hover:scale-105">
                        <div class="relative z-10 flex flex-col items-center gap-3">
                            <div class="w-16 h-16 rounded-xl flex items-center justify-center"
                                 :class="knowledgeType === 'text' ? 'glass-fusion' : 'bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-600 group-hover:bg-green-50 dark:group-hover:bg-green-900/30' border border-white/20 dark:border-white/10">
                                <i class="fas fa-align-left text-3xl" :class="knowledgeType === 'text' ? 'text-white' : 'text-[#00B900]'"></i>
                            </div>
                            <div class="text-center">
                                <p class="font-bold text-lg">ข้อความ</p>
                                <p class="text-xs opacity-80 mt-1">ใส่ข้อความโดยตรง</p>
                            </div>
                        </div>
                        <div x-show="knowledgeType === 'text'"
                             x-transition
                             class="absolute top-2 right-2 w-6 h-6 glass-fusion rounded-full flex items-center justify-center border border-white/20 dark:border-white/10">
                            <i class="fas fa-check text-[#00B900] text-sm"></i>
                        </div>
                    </button>

                    <!-- URL Type -->
                    <button type="button" @click="knowledgeType = 'url'"
                            :class="knowledgeType === 'url' ? 'bg-gradient-to-br from-blue-600 to-cyan-600 text-white shadow-2xl shadow-blue-500/40 scale-105 border-transparent' : 'glass-fusion dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-600'"
                            class="group relative overflow-hidden border-2 rounded-2xl p-6 transition-all duration-300 hover:scale-105">
                        <div class="relative z-10 flex flex-col items-center gap-3">
                            <div class="w-16 h-16 rounded-xl flex items-center justify-center"
                                 :class="knowledgeType === 'url' ? 'glass-fusion' : 'bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-600 group-hover:bg-blue-50 dark:group-hover:bg-blue-900/30' border border-white/20 dark:border-white/10">
                                <i class="fas fa-globe text-3xl" :class="knowledgeType === 'url' ? 'text-white' : 'text-blue-600'"></i>
                            </div>
                            <div class="text-center">
                                <p class="font-bold text-lg">URL ลิงก์</p>
                                <p class="text-xs opacity-80 mt-1">ดึงข้อมูลจากเว็บ</p>
                            </div>
                        </div>
                        <div x-show="knowledgeType === 'url'"
                             x-transition
                             class="absolute top-2 right-2 w-6 h-6 glass-fusion rounded-full flex items-center justify-center border border-white/20 dark:border-white/10">
                            <i class="fas fa-check text-blue-600 text-sm"></i>
                        </div>
                    </button>

                    <!-- File Type -->
                    <button type="button" @click="knowledgeType = 'file'"
                            :class="knowledgeType === 'file' ? 'bg-gradient-to-br from-purple-600 to-pink-600 text-white shadow-2xl shadow-purple-500/40 scale-105 border-transparent' : 'glass-fusion dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 dark:border-slate-600 hover:border-purple-400 dark:hover:border-purple-600'"
                            class="group relative overflow-hidden border-2 rounded-2xl p-6 transition-all duration-300 hover:scale-105">
                        <div class="relative z-10 flex flex-col items-center gap-3">
                            <div class="w-16 h-16 rounded-xl flex items-center justify-center"
                                 :class="knowledgeType === 'file' ? 'glass-fusion' : 'bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-600 group-hover:bg-purple-50 dark:group-hover:bg-purple-900/30' border border-white/20 dark:border-white/10">
                                <i class="fas fa-file-upload text-3xl" :class="knowledgeType === 'file' ? 'text-white' : 'text-purple-600'"></i>
                            </div>
                            <div class="text-center">
                                <p class="font-bold text-lg">ไฟล์</p>
                                <p class="text-xs opacity-80 mt-1">อัพโหลดเอกสาร</p>
                            </div>
                        </div>
                        <div x-show="knowledgeType === 'file'"
                             x-transition
                             class="absolute top-2 right-2 w-6 h-6 glass-fusion rounded-full flex items-center justify-center border border-white/20 dark:border-white/10">
                            <i class="fas fa-check text-purple-600 text-sm"></i>
                        </div>
                    </button>

                    <!-- Internal Type -->
                    <button type="button" @click="knowledgeType = 'internal'"
                            :class="knowledgeType === 'internal' ? 'bg-gradient-to-br from-orange-600 to-red-600 text-white shadow-2xl shadow-orange-500/40 scale-105 border-transparent' : 'glass-fusion dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 dark:border-slate-600 hover:border-orange-400 dark:hover:border-orange-600'"
                            class="group relative overflow-hidden border-2 rounded-2xl p-6 transition-all duration-300 hover:scale-105">
                        <div class="relative z-10 flex flex-col items-center gap-3">
                            <div class="w-16 h-16 rounded-xl flex items-center justify-center"
                                 :class="knowledgeType === 'internal' ? 'glass-fusion' : 'bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-600 group-hover:bg-orange-50 dark:group-hover:bg-orange-900/30' border border-white/20 dark:border-white/10">
                                <i class="fas fa-database text-3xl" :class="knowledgeType === 'internal' ? 'text-white' : 'text-orange-600'"></i>
                            </div>
                            <div class="text-center">
                                <p class="font-bold text-lg">ภายใน</p>
                                <p class="text-xs opacity-80 mt-1">ข้อมูลในระบบ</p>
                            </div>
                        </div>
                        <div x-show="knowledgeType === 'internal'"
                             x-transition
                             class="absolute top-2 right-2 w-6 h-6 glass-fusion rounded-full flex items-center justify-center border border-white/20 dark:border-white/10">
                            <i class="fas fa-check text-orange-600 text-sm"></i>
                        </div>
                    </button>
                </div>
                <input type="hidden" name="type" x-model="knowledgeType">
            </div>

            <!-- Knowledge Name -->
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                    <i class="fas fa-tag text-[#00B900]"></i>
                    ชื่อฐานความรู้
                    <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" required
                       placeholder="เช่น: คู่มือการใช้งานสินค้า, คำถามที่พบบ่อย, นโยบายบริษัท"
                       class="w-full px-6 py-4 text-lg border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300 placeholder-gray-400 dark:placeholder-gray-500">
                @error('name')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Dynamic Content Fields -->
            <div class="space-y-6">
                <!-- Text Content -->
                <div x-show="knowledgeType === 'text'" x-transition class="space-y-3">
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                        <i class="fas fa-edit text-[#00B900]"></i>
                        เนื้อหาความรู้
                        <span class="text-red-500">*</span>
                    </label>
                    <textarea name="content" rows="12"
                              placeholder="ใส่เนื้อหาความรู้ที่ต้องการให้ AI เรียนรู้... เช่น ข้อมูลสินค้า, คำถามที่พบบ่อย, ขั้นตอนการใช้งาน"
                              class="w-full px-6 py-4 text-base border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300 placeholder-gray-400 dark:placeholder-gray-500 font-mono"></textarea>
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span><i class="fas fa-info-circle mr-1"></i>รองรับ Markdown สำหรับจัดรูปแบบข้อความ</span>
                        <span><i class="fas fa-keyboard mr-1"></i>ใส่รายละเอียดมากที่สุดเพื่อความแม่นยำ</span>
                    </div>
                </div>

                <!-- URL Source -->
                <div x-show="knowledgeType === 'url'" x-transition class="space-y-3">
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                        <i class="fas fa-link text-blue-600"></i>
                        URL ที่ต้องการดึงข้อมูล
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                            <i class="fas fa-globe text-gray-400 dark:text-gray-500 dark:text-gray-400"></i>
                        </div>
                        <input type="url" name="source"
                               placeholder="https://example.com/knowledge-base"
                               class="w-full pl-14 pr-6 py-4 text-lg border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-600 transition-all duration-300 placeholder-gray-400 dark:placeholder-gray-500">
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-lightbulb text-blue-600 dark:text-blue-400 text-xl mt-0.5"></i>
                            <div class="flex-1 text-sm text-blue-800 dark:text-blue-200">
                                <p class="font-semibold mb-1">💡 คำแนะนำ:</p>
                                <ul class="list-disc list-inside space-y-1 opacity-90">
                                    <li>ระบบจะดึงข้อมูลจาก URL และแปลงเป็นความรู้</li>
                                    <li>รองรับหน้าเว็บทั่วไป, บทความ, และเอกสารออนไลน์</li>
                                    <li>ควรเป็น URL ที่เข้าถึงได้แบบสาธารณะ</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Upload -->
                <div x-show="knowledgeType === 'file'" x-transition class="space-y-3">
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                        <i class="fas fa-cloud-upload-alt text-purple-600"></i>
                        อัพโหลดไฟล์เอกสาร
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded-2xl p-8 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10 hover:border-purple-400 dark:hover:border-purple-600 transition-all duration-300">
                        <input type="file" name="file" accept=".pdf,.txt,.doc,.docx,.csv"
                               @change="fileSelected = true"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="text-center">
                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-purple-600 to-pink-600 mx-auto mb-4 flex items-center justify-center">
                                <i class="fas fa-cloud-upload-alt text-white text-3xl"></i>
                            </div>
                            <p class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-2">คลิกหรือลากไฟล์มาที่นี่</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">รองรับ: PDF, TXT, DOC, DOCX, CSV (สูงสุด 10MB)</p>
                            <div x-show="fileSelected" class="mt-4 text-green-600 dark:text-green-400 font-semibold flex items-center justify-center gap-2">
                                <i class="fas fa-check-circle"></i>
                                <span>เลือกไฟล์แล้ว</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Internal Source -->
                <div x-show="knowledgeType === 'internal'" x-transition class="space-y-3">
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                        <i class="fas fa-database text-orange-600"></i>
                        แหล่งข้อมูลภายใน
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="source"
                           placeholder="เช่น: products, faqs, policies"
                           class="w-full px-6 py-4 text-lg border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-orange-500/20 focus:border-orange-600 transition-all duration-300 placeholder-gray-400 dark:placeholder-gray-500">
                    <div class="bg-orange-50 dark:bg-orange-900/20 border-2 border-orange-200 dark:border-orange-800 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-orange-600 dark:text-orange-400 text-xl mt-0.5"></i>
                            <div class="flex-1 text-sm text-orange-800 dark:text-orange-200">
                                <p class="font-semibold mb-1">ℹ️ ข้อมูลภายในระบบ:</p>
                                <p class="opacity-90">ระบุแหล่งข้อมูลที่ต้องการดึงจากฐานข้อมูลของระบบ เช่น ข้อมูลสินค้า, บทความ, หรือ FAQ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Priority Setting -->
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                    <i class="fas fa-sort-amount-up text-[#00B900]"></i>
                    ระดับความสำคัญ
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach([0 => 'ปกติ', 1 => 'ค่อนข้างสำคัญ', 2 => 'สำคัญ', 3 => 'สำคัญมาก', 4 => 'สำคัญที่สุด'] as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="priority" value="{{ $value }}" {{ $value === 0 ? 'checked' : '' }}
                                   class="peer sr-only">
                            <div class="border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 peer-checked:border-[#00B900] peer-checked:bg-gradient-to-br peer-checked:from-[#00B900] peer-checked:to-[#00E600] glass-fusion dark:bg-slate-700 rounded-xl p-3 transition-all duration-300 peer-checked:shadow-lg peer-checked:shadow-green-500/40 peer-checked:scale-105 border border-white/20 dark:border-white/10">
                                <div class="text-center">
                                    <div class="font-bold mb-1 peer-checked:text-white text-gray-700 dark:text-gray-300">
                                        @for($i = 0; $i <= $value; $i++)
                                            <i class="fas fa-star text-xs"></i>
                                        @endfor
                                    </div>
                                    <p class="text-xs font-semibold peer-checked:text-white text-gray-600 dark:text-gray-400">{{ $label }}</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Active Status -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-2xl p-6">
                <label class="flex items-center gap-4 cursor-pointer group">
                    <input type="checkbox" name="is_active" value="1" checked
                           class="w-6 h-6 text-[#00B900] glass-fusion dark:bg-slate-700 border-2 border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded-xl focus:ring-4 focus:ring-green-500/20 transition-all duration-300">
                    <div class="flex-1">
                        <p class="text-base font-bold text-gray-900 dark:text-white dark:text-gray-200 group-hover:text-[#00B900] dark:group-hover:text-[#00E600] transition-colors">
                            <i class="fas fa-toggle-on mr-2"></i>เปิดใช้งานฐานความรู้นี้ทันที
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">AI จะสามารถใช้ความรู้นี้ในการตอบคำถามได้ทันที</p>
                    </div>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-4 pt-6 border-t-2 border-gray-200 dark:border-gray-700 dark:border-slate-700">
                <a href="{{ route('admin.line-bot.ai.knowledge.index', $setting->id) }}"
                   class="flex-1 px-8 py-4 bg-gray-200 dark:bg-gray-700 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-300 text-center text-lg font-bold flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>ยกเลิก</span>
                </a>
                <button type="submit"
                        class="flex-1 px-8 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 transform hover:-translate-y-1 text-center text-lg font-bold flex items-center justify-center gap-2">
                    <i class="fas fa-plus-circle"></i>
                    <span>เพิ่มฐานความรู้</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

[x-cloak] { display: none !important; }
</style>
@endsection
