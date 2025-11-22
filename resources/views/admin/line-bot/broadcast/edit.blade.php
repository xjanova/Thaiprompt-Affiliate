@extends('layouts.admin-v3')

@section('title', 'แก้ไข Broadcast')

@section('content')
<div class="container-fluid px-4 py-6" x-data="broadcast Editor()">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.line-bot.broadcast.index') }}"
                   class="flex items-center justify-center w-12 h-12 rounded-xl glass-fusion border-2 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-black bg-gradient-to-r from-[#06C755] via-emerald-600 to-teal-600 bg-clip-text text-transparent">
                        📝 แก้ไข Broadcast
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $broadcast->name }}</p>
                </div>
            </div>

            {{-- Status Badge --}}
            <div class="px-6 py-3 rounded-xl text-sm font-bold shadow-lg
                @if($broadcast->status === 'completed') bg-gradient-to-r from-green-500 to-emerald-600 text-white
                @elseif($broadcast->status === 'sending') bg-gradient-to-r from-blue-500 to-cyan-600 text-white animate-pulse
                @elseif($broadcast->status === 'scheduled') bg-gradient-to-r from-orange-500 to-yellow-600 text-white
                @elseif($broadcast->status === 'failed') bg-gradient-to-r from-red-500 to-pink-600 text-white
                @else bg-gradient-to-r from-gray-500 to-gray-600 text-white
                @endif">
                <i class="fas fa-circle mr-2 animate-pulse"></i>
                {{ strtoupper($broadcast->status) }}
            </div>
        </div>
    </div>

    {{-- Warning Messages --}}
    @if($broadcast->status === 'completed' || $broadcast->status === 'sending')
        <div class="mb-6 rounded-2xl glass-fusion border-2 border-yellow-200 dark:border-yellow-800 p-6 shadow-xl">
            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-yellow-900 dark:text-yellow-100 mb-2">⚠️ คำเตือน!</h4>
                    <p class="text-yellow-800 dark:text-yellow-300 text-sm">
                        @if($broadcast->status === 'completed')
                            Broadcast นี้ส่งเรียบร้อยแล้ว การแก้ไขจะไม่ส่งผลต่อข้อความที่ส่งไปแล้ว
                        @else
                            Broadcast นี้กำลังอยู่ในระหว่างการส่ง ควรระมัดระวังในการแก้ไข
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 rounded-2xl glass-fusion border-2 border-red-200 dark:border-red-800 p-6 shadow-xl">
            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-exclamation-circle text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-red-900 dark:text-red-100 mb-2">พบข้อผิดพลาด:</h4>
                    <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content: Edit Form --}}
        <div class="lg:col-span-2 space-y-6">
            <form method="POST" action="{{ route('admin.line-bot.broadcast.update', $broadcast->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Basic Information --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-red-500 p-8 shadow-2xl mb-6">
                    <div class="absolute inset-0 bg-[url('/images/patterns/topography.svg')] opacity-10"></div>
                    <div class="relative glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                        <h3 class="text-2xl font-black text-white mb-6 flex items-center gap-2">
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
                                       value="{{ old('name', $broadcast->name) }}"
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
                                        required
                                        class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50">
                                    <option value="all" {{ old('target_type', $broadcast->target_type) === 'all' ? 'selected' : '' }}>👥 ผู้ใช้ทั้งหมด</option>
                                    <option value="users" {{ old('target_type', $broadcast->target_type) === 'users' ? 'selected' : '' }}>🧑 สมาชิกทั่วไป</option>
                                    <option value="sellers" {{ old('target_type', $broadcast->target_type) === 'sellers' ? 'selected' : '' }}>🏪 ผู้ขายเท่านั้น</option>
                                    <option value="custom" {{ old('target_type', $broadcast->target_type) === 'custom' ? 'selected' : '' }}>🎯 กำหนดเอง</option>
                                </select>
                            </div>

                            {{-- Message Type --}}
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-comment-alt mr-1"></i> ประเภทข้อความ *
                                </label>
                                <select name="message_type"
                                        class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50">
                                    <option value="text" {{ old('message_type', $broadcast->message_type) === 'text' ? 'selected' : '' }}>💬 Text</option>
                                    <option value="flex" {{ old('message_type', $broadcast->message_type) === 'flex' ? 'selected' : '' }}>🎨 Flex</option>
                                    <option value="image" {{ old('message_type', $broadcast->message_type) === 'image' ? 'selected' : '' }}>🖼️ Image</option>
                                    <option value="video" {{ old('message_type', $broadcast->message_type) === 'video' ? 'selected' : '' }}>🎥 Video</option>
                                </select>
                            </div>

                            {{-- Text Message Content --}}
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-edit mr-1"></i> เนื้อหาข้อความ *
                                </label>
                                <textarea name="content"
                                          rows="6"
                                          required
                                          placeholder="พิมพ์ข้อความที่ต้องการส่ง... (รองรับ Emoji 😊)"
                                          class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50 placeholder-gray-500 dark:placeholder-gray-400 font-thai">{{ old('content', $broadcast->content) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Scheduling --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 p-8 shadow-2xl mb-6">
                    <div class="absolute inset-0 bg-[url('/images/patterns/circuit-board.svg')] opacity-10"></div>
                    <div class="relative glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                        <h3 class="text-2xl font-black text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-clock"></i>
                            📅 ตั้งเวลาส่ง
                        </h3>

                        <div>
                            <label class="block text-sm font-semibold text-white mb-3">
                                <i class="fas fa-calendar-alt mr-1"></i> เลือกวันและเวลา (ถ้าต้องการตั้งเวลา)
                            </label>
                            <input type="datetime-local"
                                   name="scheduled_at"
                                   value="{{ old('scheduled_at', $broadcast->scheduled_at ? $broadcast->scheduled_at->format('Y-m-d\TH:i') : '') }}"
                                   min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                                   class="w-full px-4 py-3 rounded-lg bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-blue-400 text-lg">
                            <p class="text-xs text-white/80 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                เว้นว่างไว้ถ้าต้องการส่งทันทีเมื่อกด Submit
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-2xl p-6 border-2 border-[#06C755]/30 dark:border-emerald-800">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('admin.line-bot.broadcast.index') }}"
                           class="px-6 py-3 glass-fusion border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-all shadow-lg font-bold">
                            <i class="fas fa-arrow-left mr-2"></i>
                            กลับ
                        </a>
                        <div class="flex gap-3">
                            <button type="submit" name="action" value="draft"
                                    class="px-8 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl hover:from-gray-600 hover:to-gray-700 transition-all shadow-lg font-bold transform hover:scale-105">
                                <i class="fas fa-save mr-2"></i>
                                บันทึก
                            </button>
                            @if($broadcast->status === 'draft' || $broadcast->status === 'scheduled' || $broadcast->status === 'failed')
                                <button type="submit" name="action" value="send"
                                        class="px-8 py-3 bg-gradient-to-r from-[#06C755] via-emerald-600 to-teal-600 text-white rounded-xl hover:from-emerald-600 hover:to-[#06C755] transition-all shadow-2xl font-bold transform hover:scale-105">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    {{ $broadcast->status === 'failed' ? 'ส่งอีกครั้ง' : 'อัพเดทและส่ง' }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Sidebar: History & Stats --}}
        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-6">
                {{-- Statistics --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 p-6 shadow-2xl">
                    <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-bar"></i>
                        📊 สถิติ
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-white">
                            <span class="text-sm">ผู้รับทั้งหมด:</span>
                            <span class="font-bold text-2xl">{{ number_format($broadcast->total_recipients ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-white">
                            <span class="text-sm">ส่งสำเร็จ:</span>
                            <span class="font-bold text-2xl text-green-300">{{ number_format($broadcast->sent_count ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-white">
                            <span class="text-sm">ล้มเหลว:</span>
                            <span class="font-bold text-2xl text-red-300">{{ number_format($broadcast->failed_count ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Broadcast History --}}
                @if($broadcast->sent_at)
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-700 p-6 shadow-2xl">
                        <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-history"></i>
                            📜 ประวัติการส่ง
                        </h3>
                        <div class="space-y-3">
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                                <p class="text-xs text-white/80 mb-1">สร้างเมื่อ:</p>
                                <p class="text-white font-bold">{{ $broadcast->created_at->format('d M Y, H:i น.') }}</p>
                            </div>
                            @if($broadcast->scheduled_at)
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                                    <p class="text-xs text-white/80 mb-1">ตั้งเวลาส่ง:</p>
                                    <p class="text-white font-bold">{{ $broadcast->scheduled_at->format('d M Y, H:i น.') }}</p>
                                </div>
                            @endif
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                                <p class="text-xs text-white/80 mb-1">ส่งเมื่อ:</p>
                                <p class="text-white font-bold">{{ $broadcast->sent_at->format('d M Y, H:i น.') }}</p>
                                <p class="text-xs text-white/70 mt-1">{{ $broadcast->sent_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Resend Option (สำหรับ failed) --}}
                @if($broadcast->status === 'failed')
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-red-500 to-pink-700 p-6 shadow-2xl">
                        <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            ส่งล้มเหลว
                        </h3>
                        <p class="text-white/90 text-sm mb-4">
                            การส่ง broadcast นี้ล้มเหลว คุณสามารถแก้ไขและส่งอีกครั้งได้
                        </p>
                        <form method="POST" action="{{ route('admin.line-bot.broadcast.send', $broadcast->id) }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('ส่ง broadcast นี้อีกครั้งหรือไม่?')"
                                    class="w-full px-4 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white rounded-xl transition-all shadow-lg font-bold">
                                <i class="fas fa-redo mr-2"></i>
                                ส่งอีกครั้ง
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Broadcast Editor Alpine.js Component
 *
 * จัดการ state สำหรับการแก้ไข broadcast
 */
function broadcastEditor() {
    return {
        // Initialize component
        init() {
            console.log('Broadcast Editor initialized');
        }
    }
}
</script>
@endpush
@endsection
