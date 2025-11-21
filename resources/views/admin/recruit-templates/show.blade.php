@extends('layouts.admin')

@section('title', 'รายละเอียดเทมเพลต: ' . $template->title)

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                รายละเอียดเทมเพลต
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
            <a href="{{ route('admin.recruit-templates.edit', $template) }}"
               class="inline-flex items-center px-6 py-3 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                แก้ไข
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

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ข้อมูลพื้นฐาน</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">ชื่อเทมเพลต</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $template->title }}</p>
                    </div>

                    @if($template->description)
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">คำอธิบาย</label>
                        <p class="text-gray-700 dark:text-gray-300">{{ $template->description }}</p>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">วันที่สร้าง</label>
                        <p class="text-gray-700 dark:text-gray-300">{{ $template->created_at->format('d/m/Y H:i น.') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">อัพเดทล่าสุด</label>
                        <p class="text-gray-700 dark:text-gray-300">{{ $template->updated_at->format('d/m/Y H:i น.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Messages --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ข้อความ</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">ข้อความต้อนรับ</label>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $template->welcome_message }}</p>
                        </div>
                    </div>

                    @if($template->pitch_message)
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">ข้อความชักชวน</label>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $template->pitch_message }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Benefits --}}
            @if($template->benefits && count($template->benefits) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ประโยชน์ที่ได้รับ</h3>

                <ul class="space-y-3">
                    @foreach($template->benefits as $benefit)
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">{{ $benefit }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Features --}}
            @if($template->features && count($template->features) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ฟีเจอร์ที่ได้รับ</h3>

                <ul class="space-y-3">
                    @foreach($template->features as $feature)
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Instructions --}}
            @if($template->instructions && count($template->instructions) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ขั้นตอนการสมัคร</h3>

                <div class="space-y-4">
                    @foreach($template->instructions as $instruction)
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xl font-bold shadow-lg">
                            {{ $instruction['icon'] ?? $instruction['step'] }}
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $instruction['title'] }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $instruction['description'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">สถานะ</h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">การใช้งาน</span>
                        @if($template->is_active)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-green-400 to-green-600 text-white shadow-lg">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                ใช้งาน
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-gray-400 to-gray-600 text-white shadow-lg">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                ปิด
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Default</span>
                        @if($template->is_default)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-yellow-400 to-orange-500 text-white shadow-lg">
                                ⭐ Default
                            </span>
                        @else
                            <span class="text-xs text-gray-500">-</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">จำนวนผู้ใช้</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($template->customizations_count) }}</span>
                    </div>
                </div>
            </div>

            {{-- Settings Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">การตั้งค่า</h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">บังคับเพิ่มเพื่อน LINE</span>
                        @if($template->require_line_friend)
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">แสดงข้อมูลแม่ทีม</span>
                        @if($template->show_team_leader_info)
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">แสดงสถิติ</span>
                        @if($template->show_statistics)
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">การจัดการ</h3>

                <div class="space-y-3">
                    @if(!$template->is_default)
                    <form action="{{ route('admin.recruit-templates.set-default', $template) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                            ตั้งเป็น Default
                        </button>
                    </form>
                    @endif

                    <form action="{{ route('admin.recruit-templates.toggle-active', $template) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-3 {{ $template->is_active ? 'bg-gray-500 hover:bg-gray-600' : 'bg-green-500 hover:bg-green-600' }} text-white rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl">
                            @if($template->is_active)
                                ปิดใช้งาน
                            @else
                                เปิดใช้งาน
                            @endif
                        </button>
                    </form>

                    @if($template->customizations_count == 0 && !$template->is_default)
                    <form action="{{ route('admin.recruit-templates.destroy', $template) }}" method="POST" onsubmit="return confirm('ยืนยันการลบเทมเพลตนี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            ลบเทมเพลต
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
