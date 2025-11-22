@extends('layouts.admin-v3')

@section('title', isset($broadcast) ? 'แก้ไข Broadcast' : 'สร้าง Broadcast ใหม่')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    scheduleType: '{{ old('schedule_type', isset($broadcast) && $broadcast->scheduled_at ? 'scheduled' : 'now') }}',
    messageType: '{{ old('message_type', $broadcast->message_type ?? 'text') }}',
    targetType: '{{ old('target_type', $broadcast->target_type ?? 'all') }}'
}">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.line-bot.broadcast.index') }}"
               class="flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                    @isset($broadcast)
                        📝 แก้ไข Broadcast
                    @else
                        ✨ สร้าง Broadcast ใหม่
                    @endisset
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ส่งข้อความไปยังผู้ใช้หลายคนพร้อมกัน</p>
            </div>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 shadow-lg">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mt-0.5"></i>
                <div class="flex-1">
                    <h4 class="font-bold text-red-800 dark:text-red-300 mb-2">พบข้อผิดพลาด:</h4>
                    <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ isset($broadcast) ? route('admin.line-bot.broadcast.update', $broadcast->id) : route('admin.line-bot.broadcast.store') }}">
        @csrf
        @isset($broadcast) @method('PUT') @endisset

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Information --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-red-500 p-8 shadow-2xl">
                    <div class="absolute inset-0 bg-[url('/images/patterns/topography.svg')] opacity-10"></div>
                    <div class="relative bg-white/10 backdrop-blur-xl rounded-xl p-6 border border-white/20">
                        <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            ข้อมูลพื้นฐาน
                        </h3>

                        <div class="space-y-5">
                            {{-- Broadcast Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-tag mr-1"></i> ชื่อแคมเปญ *
                                </label>
                                <input type="text"
                                       name="name"
                                       value="{{ old('name', $broadcast->name ?? '') }}"
                                       required
                                       placeholder="เช่น: โปรโมชั่นพิเศษ, ข่าวสารใหม่"
                                       class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50 placeholder-gray-500 dark:placeholder-gray-400">
                            </div>

                            {{-- Target Audience --}}
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-users mr-1"></i> กลุ่มเป้าหมาย *
                                </label>
                                <select name="target_type"
                                        x-model="targetType"
                                        required
                                        class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50">
                                    <option value="all">👥 ผู้ใช้ทั้งหมด</option>
                                    <option value="users">🧑 สมาชิกทั่วไป</option>
                                    <option value="sellers">🏪 ผู้ขายเท่านั้น</option>
                                    <option value="custom">🎯 กำหนดเอง</option>
                                </select>
                            </div>

                            {{-- Message Type --}}
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-comment-alt mr-1"></i> ประเภทข้อความ *
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <button type="button"
                                            @click="messageType = 'text'"
                                            :class="messageType === 'text' ? 'bg-white text-purple-600 shadow-xl scale-105' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-3 rounded-xl font-semibold transition-all transform">
                                        💬 Text
                                    </button>
                                    <button type="button"
                                            @click="messageType = 'flex'"
                                            :class="messageType === 'flex' ? 'bg-white text-purple-600 shadow-xl scale-105' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-3 rounded-xl font-semibold transition-all transform">
                                        🎨 Flex
                                    </button>
                                    <button type="button"
                                            @click="messageType = 'image'"
                                            :class="messageType === 'image' ? 'bg-white text-purple-600 shadow-xl scale-105' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-3 rounded-xl font-semibold transition-all transform">
                                        🖼️ Image
                                    </button>
                                    <button type="button"
                                            @click="messageType = 'video'"
                                            :class="messageType === 'video' ? 'bg-white text-purple-600 shadow-xl scale-105' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-3 rounded-xl font-semibold transition-all transform">
                                        🎥 Video
                                    </button>
                                </div>
                                <input type="hidden" name="message_type" x-model="messageType">
                            </div>

                            {{-- Text Message Content --}}
                            <div x-show="messageType === 'text'" x-transition>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-edit mr-1"></i> เนื้อหาข้อความ *
                                </label>
                                <textarea name="content"
                                          rows="6"
                                          x-bind:required="messageType === 'text'"
                                          placeholder="พิมพ์ข้อความที่ต้องการส่ง... (รองรับ Emoji 😊)"
                                          class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50 placeholder-gray-500 dark:placeholder-gray-400 font-thai">{{ old('content', $broadcast->content ?? '') }}</textarea>
                                <p class="text-xs text-white/70 mt-1">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    คำแนะนำ: ใช้ข้อความสั้นๆ กระชับ และเพิ่ม emoji เพื่อดึงดูดความสนใจ
                                </p>
                            </div>

                            {{-- Flex Template Selection --}}
                            <div x-show="messageType === 'flex'" x-transition>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-palette mr-1"></i> เลือก Flex Template *
                                </label>
                                <select name="flex_template_id"
                                        x-bind:required="messageType === 'flex'"
                                        class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50">
                                    <option value="">-- เลือก Template --</option>
                                    @foreach($flexTemplates ?? [] as $template)
                                        <option value="{{ $template->id }}" {{ old('flex_template_id', $broadcast->flex_template_id ?? '') == $template->id ? 'selected' : '' }}>
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Scheduling --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 p-8 shadow-2xl">
                    <div class="absolute inset-0 bg-[url('/images/patterns/circuit-board.svg')] opacity-10"></div>
                    <div class="relative bg-white/10 backdrop-blur-xl rounded-xl p-6 border border-white/20">
                        <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-clock"></i>
                            📅 ตั้งเวลาส่ง
                        </h3>

                        {{-- Schedule Type Selection --}}
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <button type="button"
                                    @click="scheduleType = 'now'"
                                    :class="scheduleType === 'now' ? 'bg-white text-blue-600 shadow-2xl scale-105' : 'bg-white/20 text-white hover:bg-white/30'"
                                    class="px-6 py-4 rounded-xl font-semibold transition-all transform">
                                <div class="text-3xl mb-2">🚀</div>
                                <div>ส่งทันที</div>
                                <div class="text-xs opacity-75 mt-1">ส่งเมื่อกด Submit</div>
                            </button>
                            <button type="button"
                                    @click="scheduleType = 'scheduled'"
                                    :class="scheduleType === 'scheduled' ? 'bg-white text-blue-600 shadow-2xl scale-105' : 'bg-white/20 text-white hover:bg-white/30'"
                                    class="px-6 py-4 rounded-xl font-semibold transition-all transform">
                                <div class="text-3xl mb-2">⏰</div>
                                <div>ตั้งเวลา</div>
                                <div class="text-xs opacity-75 mt-1">กำหนดวัน-เวลาส่ง</div>
                            </button>
                        </div>

                        {{-- Datetime Picker --}}
                        <div x-show="scheduleType === 'scheduled'"
                             x-transition
                             class="bg-white/20 rounded-xl p-5 border border-white/30">
                            <label class="block text-sm font-semibold text-white mb-3">
                                <i class="fas fa-calendar-alt mr-1"></i> เลือกวันและเวลา
                            </label>
                            <input type="datetime-local"
                                   name="scheduled_at"
                                   value="{{ old('scheduled_at', isset($broadcast) && $broadcast->scheduled_at ? $broadcast->scheduled_at->format('Y-m-d\TH:i') : '') }}"
                                   min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                                   x-bind:required="scheduleType === 'scheduled'"
                                   class="w-full px-4 py-3 rounded-lg bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-blue-400 text-lg">
                            <p class="text-xs text-white/80 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                ระบบจะส่งข้อความอัตโนมัติในเวลาที่กำหนด
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar: Tips & Stats --}}
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-6">
                    {{-- Tips --}}
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 to-teal-600 p-6 shadow-2xl">
                        <div class="absolute inset-0 bg-[url('/images/patterns/wiggle.svg')] opacity-10"></div>
                        <div class="relative">
                            <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-lightbulb text-yellow-300 text-xl"></i>
                                เคล็ดลับ Broadcast
                            </h3>

                            <div class="space-y-3 text-sm">
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                                    <p class="font-semibold text-white mb-2">✨ Best Practices:</p>
                                    <ul class="text-xs text-white/90 space-y-1">
                                        <li class="flex items-start gap-2">
                                            <span class="text-green-300">•</span>
                                            <span>ใช้ข้อความสั้นๆ ได้ใจความ</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="text-green-300">•</span>
                                            <span>เพิ่ม emoji เพื่อดึงดูดสายตา</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="text-green-300">•</span>
                                            <span>ใส่ Call-to-Action ที่ชัดเจน</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="text-green-300">•</span>
                                            <span>ทดสอบส่งก่อนส่งจริง</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                                    <p class="font-semibold text-white mb-2">⏰ เวลาที่เหมาะสม:</p>
                                    <p class="text-xs text-white/90">ส่งในช่วง 9:00 - 21:00 น. เพื่อ engagement ที่ดีกว่า</p>
                                </div>

                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                                    <p class="font-semibold text-white mb-2">🎯 เลือกกลุ่มเป้าหมาย:</p>
                                    <p class="text-xs text-white/90">แบ่งกลุ่มผู้รับเพื่อส่งข้อความที่ตรงใจมากขึ้น</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stats (if editing) --}}
                    @isset($broadcast)
                        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 p-6 shadow-2xl">
                            <h3 class="font-bold text-white mb-4">📊 สถิติ</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center text-white">
                                    <span class="text-sm">ผู้รับทั้งหมด:</span>
                                    <span class="font-bold text-lg">{{ number_format($broadcast->total_recipients) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-white">
                                    <span class="text-sm">ส่งสำเร็จ:</span>
                                    <span class="font-bold text-lg text-green-300">{{ number_format($broadcast->sent_count) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-white">
                                    <span class="text-sm">ล้มเหลว:</span>
                                    <span class="font-bold text-lg text-red-300">{{ number_format($broadcast->failed_count) }}</span>
                                </div>
                            </div>
                        </div>
                    @endisset
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-8 sticky bottom-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-2xl shadow-2xl p-6 border-2 border-purple-200 dark:border-purple-800">
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.line-bot.broadcast.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                    <i class="fas fa-arrow-left"></i>
                    <span class="font-semibold">กลับ</span>
                </a>
                <div class="flex gap-3">
                    <button type="submit" name="action" value="draft"
                            class="px-8 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl hover:from-gray-600 hover:to-gray-700 transition-all shadow-lg font-bold transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกแบบร่าง
                    </button>
                    <button type="submit" name="action" value="send"
                            class="px-8 py-3 bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 text-white rounded-xl hover:from-purple-700 hover:via-pink-700 hover:to-red-700 transition-all shadow-2xl font-bold transform hover:scale-105 animate-pulse-slow">
                        <i class="fas fa-paper-plane mr-2"></i>
                        <span x-text="scheduleType === 'now' ? 'ส่งทันที' : 'ตั้งเวลาส่ง'"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// เพิ่ม character counter (optional)
document.addEventListener('alpine:init', () => {
    // Future enhancements can go here
});
</script>
@endpush
@endsection
