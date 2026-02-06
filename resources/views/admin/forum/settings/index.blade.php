@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าฟอรั่ม')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-white">⚙️ ตั้งค่าฟอรั่ม</h1>
        <p class="text-white/60 mt-1">กำหนดค่าการทำงานของระบบฟอรั่มชุมชน</p>
    </div>

    <form action="{{ route('admin.platform-revenue.forum.settings.save') }}" method="POST">
        @csrf

        <div class="glass-card rounded-xl p-6 space-y-6">
            {{-- General Settings --}}
            <div>
                <h2 class="text-lg font-bold text-white mb-4">📋 การตั้งค่าทั่วไป</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <div class="text-white font-medium">เปิดใช้งานฟอรั่ม</div>
                            <div class="text-white/60 text-sm">อนุญาตให้ผู้ใช้เข้าถึงฟอรั่ม</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="forum_enabled" value="1" {{ $settings['forum_enabled'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <div class="text-white font-medium">ต้องเข้าสู่ระบบ</div>
                            <div class="text-white/60 text-sm">บังคับให้ login ก่อนดูฟอรั่ม</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="require_login" value="1" {{ $settings['require_login'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <div class="text-white font-medium">แขกดูได้</div>
                            <div class="text-white/60 text-sm">อนุญาตให้ผู้ไม่ได้ login ดูฟอรั่ม</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_guest_view" value="1" {{ $settings['allow_guest_view'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <div class="text-white font-medium">อนุญาตไฟล์แนบ</div>
                            <div class="text-white/60 text-sm">อนุญาตให้แนบรูปภาพในกระทู้</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_attachments" value="1" {{ $settings['allow_attachments'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Pagination Settings --}}
            <div>
                <h2 class="text-lg font-bold text-white mb-4">📄 การแบ่งหน้า</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white/80 mb-2">กระทู้ต่อหน้า</label>
                        <input type="number" name="threads_per_page" value="{{ $settings['threads_per_page'] }}" min="5" max="100"
                               class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>
                    <div>
                        <label class="block text-white/80 mb-2">ความคิดเห็นต่อหน้า</label>
                        <input type="number" name="posts_per_page" value="{{ $settings['posts_per_page'] }}" min="5" max="100"
                               class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>
                </div>
            </div>

            {{-- Content Limits --}}
            <div>
                <h2 class="text-lg font-bold text-white mb-4">📏 ข้อจำกัดเนื้อหา</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-white/80 mb-2">ความยาวขั้นต่ำกระทู้ (ตัวอักษร)</label>
                        <input type="number" name="min_thread_length" value="{{ $settings['min_thread_length'] }}" min="1"
                               class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>
                    <div>
                        <label class="block text-white/80 mb-2">ความยาวขั้นต่ำความคิดเห็น (ตัวอักษร)</label>
                        <input type="number" name="min_post_length" value="{{ $settings['min_post_length'] }}" min="1"
                               class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>
                    <div>
                        <label class="block text-white/80 mb-2">ขนาดไฟล์แนบสูงสุด (MB)</label>
                        <input type="number" name="max_attachment_size" value="{{ $settings['max_attachment_size'] }}" min="1" max="50"
                               class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end pt-4 border-t border-white/10">
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-lg hover:from-blue-600 hover:to-purple-600 transition shadow-lg">
                    <i class="fas fa-save mr-2"></i> บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>
@endsection
