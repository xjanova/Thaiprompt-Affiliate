{{-- คอนเทนต์สายมูอัตโนมัติ --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="mysticContentPage()">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                🌙 คอนเทนต์สายมูอัตโนมัติ
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                โพส Facebook ของแม่หมอจันทรา — สายมู / แก้เคล็ด / สิ่งลี้ลับ ฯลฯ ตาม schedule
            </p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl">
            <p class="text-red-800 dark:text-red-200 font-semibold mb-2">❌ พบข้อผิดพลาด:</p>
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">โพสรวม</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_posts']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">โพสสำเร็จวันนี้</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['posted_today'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">ล้มเหลววันนี้</p>
            <p class="text-2xl font-bold {{ $stats['failed_today'] > 0 ? 'text-red-600' : 'text-gray-400' }} mt-1">{{ $stats['failed_today'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">หมวดที่เปิด</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['enabled_topics'] }}/{{ $topics->count() }}</p>
        </div>
    </div>

    {{-- ──────────── Settings Form ──────────── --}}
    <form action="{{ route('admin.fortune.mystic.settings.update') }}" method="POST" class="mb-8">
        @csrf
        @method('PUT')

        {{-- ── Toggles ── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                ⚙️ สถานะระบบ
            </h2>

            {{-- Daily Horoscope Per Day toggle --}}
            <label class="flex items-start gap-3 cursor-pointer p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg mb-3">
                <input type="hidden" name="daily_horoscope_per_day_enabled" value="0">
                <input type="checkbox" name="daily_horoscope_per_day_enabled" value="1"
                       class="w-5 h-5 mt-0.5 text-purple-600 rounded focus:ring-purple-500"
                       {{ $settings->daily_horoscope_per_day_enabled ? 'checked' : '' }}>
                <div class="flex-1">
                    <span class="font-semibold text-gray-900 dark:text-white">📅 ระบบดวงรายวันเกิด (เดิม) — ตี 1-7 โมงเช้า</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        โพสดวงประจำวันสำหรับคนเกิดวันจันทร์-อาทิตย์ (รายชั่วโมง 01:00-07:00)
                        <strong class="text-orange-600 dark:text-orange-400">— ปิดเป็น default หลัง deploy v3</strong>
                    </p>
                </div>
            </label>

            {{-- Mystic Content toggle --}}
            <label class="flex items-start gap-3 cursor-pointer p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border-2 border-purple-300 dark:border-purple-700">
                <input type="hidden" name="mystic_content_enabled" value="0">
                <input type="checkbox" name="mystic_content_enabled" value="1"
                       class="w-5 h-5 mt-0.5 text-purple-600 rounded focus:ring-purple-500"
                       {{ $settings->mystic_content_enabled ? 'checked' : '' }}>
                <div class="flex-1">
                    <span class="font-semibold text-gray-900 dark:text-white">🌙 ระบบคอนเทนต์สายมู (ใหม่)</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        โพสคอนเทนต์ สายมู / แก้เคล็ด / ปัญหาชีวิต / สิ่งลี้ลับ / รู้หรือไม่ทั่วโลก
                        — สุ่มหัวข้อหมุนเวียน + AI เขียนใหม่จากแหล่งเว็บที่น่าเชื่อถือ
                    </p>
                </div>
            </label>
        </div>

        {{-- ── Schedule ── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                ⏰ ตารางเวลาโพส
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                กำหนดชั่วโมงที่ต้องการโพส (Asia/Bangkok) — โพสได้สูงสุด 6 ครั้ง/วัน เพื่อไม่ให้โดน FB ลด reach
            </p>

            <div x-data="{ slots: @json($settings->mystic_content_schedule ?? ['08:00', '20:00']) }">
                <template x-for="(slot, idx) in slots" :key="idx">
                    <div class="flex items-center gap-2 mb-2">
                        <input type="time"
                               :name="'mystic_content_schedule[' + idx + ']'"
                               x-model="slots[idx]"
                               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <button type="button"
                                @click="slots.splice(idx, 1)"
                                class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 text-sm">
                            ลบ
                        </button>
                    </div>
                </template>

                <button type="button"
                        @click="if (slots.length < 6) slots.push('12:00')"
                        :disabled="slots.length >= 6"
                        class="mt-2 px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium">
                    + เพิ่ม slot (สูงสุด 6 ตัว)
                </button>
            </div>
        </div>

        {{-- ── Caption + Hashtag ── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                📝 คุณภาพคอนเทนต์
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความยาวขั้นต่ำ (ตัวอักษร)
                    </label>
                    <input type="number" name="mystic_content_caption_min"
                           value="{{ $settings->mystic_content_caption_min ?? 400 }}"
                           min="100" max="2000"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความยาวสูงสุด (ตัวอักษร)
                    </label>
                    <input type="number" name="mystic_content_caption_max"
                           value="{{ $settings->mystic_content_caption_max ?? 700 }}"
                           min="200" max="3000"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        จำนวน Hashtag/โพส
                    </label>
                    <input type="number" name="mystic_content_hashtag_count"
                           value="{{ $settings->mystic_content_hashtag_count ?? 6 }}"
                           min="0" max="10"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">แนะนำ ≤7 (เกินจะโดน FB ลด reach)</p>
                </div>
            </div>
        </div>

        {{-- ── API Keys ── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                🔍 Web Search API
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                AI จะค้นข้อมูลจากเว็บที่น่าเชื่อถือก่อนเขียนบทความ — เลือกอย่างน้อย 1 service หรือใช้ DuckDuckGo ฟรี (ไม่ต้องคีย์)
            </p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🥇 Tavily API Key <span class="text-xs text-gray-500">(แนะนำ — 1000 free/เดือน)</span>
                    </label>
                    <input type="password" name="tavily_api_key"
                           placeholder="{{ $settings->tavily_api_key ? '••••••••••••• (มี key อยู่ — เว้นไว้ถ้าไม่ต้องการเปลี่ยน)' : 'tvly-... (สมัครฟรีที่ tavily.com)' }}"
                           autocomplete="off"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🥈 Brave Search API Key <span class="text-xs text-gray-500">(fallback — 2000 free/เดือน)</span>
                    </label>
                    <input type="password" name="brave_search_api_key"
                           placeholder="{{ $settings->brave_search_api_key ? '••••••••••••• (มี key อยู่)' : 'BSA... (สมัครที่ brave.com/search/api)' }}"
                           autocomplete="off"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                </div>

                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-800 dark:text-blue-200">
                    💡 <strong>ฟรีเกือบทั้งระบบ:</strong> 2 โพส/วัน × 30 วัน = 60 search/เดือน → Tavily ฟรี 1000 หรือ Brave ฟรี 2000 ใช้ไม่หมดแน่นอน หากไม่กรอกคีย์ใดๆ ระบบจะใช้ DuckDuckGo HTML scraping (ฟรีไม่จำกัด)
                </div>
            </div>
        </div>

        {{-- Save button --}}
        <div class="flex justify-end gap-2 mb-8">
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl shadow-lg font-semibold transition">
                💾 บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    {{-- ──────────── Manual Publish ──────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border-2 border-amber-300 dark:border-amber-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            ⚡ โพสทันที (ทดสอบ)
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            โพสตอนนี้เลย ไม่ต้องรอ schedule — ใช้สำหรับทดสอบหรือรันโพสที่พลาด
        </p>

        <form action="{{ route('admin.fortune.mystic.publish-now') }}" method="POST" class="flex flex-wrap gap-3 items-end">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Slot Hour (0-23)
                </label>
                <input type="number" name="slot" value="{{ now('Asia/Bangkok')->hour }}" min="0" max="23"
                       class="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
            </div>

            <label class="flex items-center gap-2 cursor-pointer pb-2">
                <input type="hidden" name="force" value="0">
                <input type="checkbox" name="force" value="1"
                       class="w-4 h-4 text-amber-600 rounded focus:ring-amber-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">--force (ลบของเก่าก่อน)</span>
            </label>

            <button type="submit"
                    class="px-6 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg shadow font-medium transition">
                ⚡ โพสทันที
            </button>
        </form>
    </div>

    {{-- ──────────── Topics Management ──────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            📚 หมวดและหัวข้อ
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            สุ่มหมวด → สุ่ม sub-topic → search web → AI rewrite → โพส
            (1 บรรทัด = 1 รายการ ใน textarea)
        </p>

        <div class="space-y-4">
            @foreach($topics as $topic)
                <details class="bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <summary class="cursor-pointer p-4 flex justify-between items-center hover:bg-gray-100 dark:hover:bg-gray-700/50">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ $topic->emoji }}</span>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $topic->name_th }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ count($topic->sub_topic_pool ?? []) }} sub-topics •
                                    ใช้ไป {{ $topic->use_count }} ครั้ง
                                    @if($topic->last_used_at) • ล่าสุด {{ $topic->last_used_at->diffForHumans() }} @endif
                                </p>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded {{ $topic->is_enabled ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}">
                            {{ $topic->is_enabled ? 'เปิด' : 'ปิด' }}
                        </span>
                    </summary>

                    <form action="{{ route('admin.fortune.mystic.topics.update', $topic) }}" method="POST" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อหมวด</label>
                                <input type="text" name="name_th" value="{{ $topic->name_th }}"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Emoji</label>
                                <input type="text" name="emoji" value="{{ $topic->emoji }}" maxlength="10"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                            <input type="text" name="description_th" value="{{ $topic->description_th }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sub-topics (1 บรรทัด/รายการ)</label>
                                <textarea name="sub_topic_pool_text" rows="6"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono">{{ implode("\n", $topic->sub_topic_pool ?? []) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hashtags (1 บรรทัด/รายการ — ขึ้นต้นด้วย #)</label>
                                <textarea name="hashtag_pool_text" rows="6"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono">{{ implode("\n", $topic->hashtag_pool ?? []) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Image Keywords (eng — สำหรับ AI image)</label>
                                <textarea name="image_keywords_text" rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono">{{ implode("\n", $topic->image_keywords ?? []) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Queries (สำหรับ web search)</label>
                                <textarea name="search_queries_text" rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono">{{ implode("\n", $topic->search_queries ?? []) }}</textarea>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_enabled" value="0">
                                <input type="checkbox" name="is_enabled" value="1"
                                       class="w-4 h-4 text-purple-600 rounded"
                                       {{ $topic->is_enabled ? 'checked' : '' }}>
                                <span class="text-sm">เปิดใช้งานหมวดนี้</span>
                            </label>

                            <div class="flex items-center gap-2">
                                <label class="text-sm">ลำดับ:</label>
                                <input type="number" name="sort_order" value="{{ $topic->sort_order }}" min="0"
                                       class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            </div>

                            <button type="submit"
                                    class="ml-auto px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium">
                                บันทึกหมวดนี้
                            </button>
                        </div>
                    </form>
                </details>
            @endforeach
        </div>
    </div>

    {{-- ──────────── Recent Posts ──────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            📜 โพสล่าสุด
        </h2>

        @if($recentPosts->isEmpty())
            <p class="text-center text-gray-500 dark:text-gray-400 py-8">ยังไม่มีโพส — กดปุ่ม "โพสทันที" ด้านบนเพื่อทดสอบ</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่ • slot</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หมวด</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หัวข้อ</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Search</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentPosts as $post)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $post->post_date->format('d/m') }} • {{ sprintf('%02d:00', $post->slot_hour) }}
                                </td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                    @if($post->topic)
                                        {{ $post->topic->emoji }} {{ $post->topic->name_th }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ $post->sub_topic ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400 text-xs">{{ $post->search_provider ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    @if($post->status === 'posted')
                                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded text-xs">✅ posted</span>
                                    @elseif($post->status === 'failed')
                                        <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded text-xs" title="{{ $post->error_message }}">❌ failed</span>
                                    @else
                                        <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded text-xs">{{ $post->status }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right text-xs">
                                    @if($post->fb_post_url)
                                        <a href="{{ $post->fb_post_url }}" target="_blank" class="text-blue-600 hover:underline">FB ↗</a>
                                    @endif
                                    <button type="button" @click="viewPost({{ $post->id }})" class="text-purple-600 hover:underline ml-2">ดู</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ──────────── Modal: Post Detail ──────────── --}}
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="modalOpen = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">รายละเอียดโพส</h3>
                    <button @click="modalOpen = false" class="text-gray-500 hover:text-gray-900 dark:hover:text-white text-2xl">&times;</button>
                </div>

                <template x-if="currentPost">
                    <div class="space-y-3 text-sm">
                        <div><strong class="text-gray-700 dark:text-gray-300">วันที่:</strong> <span x-text="currentPost.post_date + ' slot ' + currentPost.slot_hour + ':00'" class="text-gray-900 dark:text-white"></span></div>
                        <div><strong class="text-gray-700 dark:text-gray-300">หมวด:</strong> <span x-text="currentPost.topic" class="text-gray-900 dark:text-white"></span></div>
                        <div><strong class="text-gray-700 dark:text-gray-300">หัวข้อ:</strong> <span x-text="currentPost.sub_topic" class="text-gray-900 dark:text-white"></span></div>
                        <div x-show="currentPost.image_url">
                            <img :src="currentPost.image_url" class="max-w-full rounded-lg shadow my-3">
                        </div>
                        <div>
                            <strong class="text-gray-700 dark:text-gray-300">Caption:</strong>
                            <pre x-text="currentPost.caption" class="mt-1 p-3 bg-gray-50 dark:bg-gray-900/50 rounded whitespace-pre-wrap text-gray-900 dark:text-white text-xs"></pre>
                        </div>
                        <div x-show="currentPost.sources && currentPost.sources.length > 0">
                            <strong class="text-gray-700 dark:text-gray-300">แหล่งอ้างอิง (<span x-text="currentPost.search_provider"></span>):</strong>
                            <ul class="mt-1 space-y-1">
                                <template x-for="src in currentPost.sources" :key="src.url">
                                    <li class="text-xs">
                                        <a :href="src.url" target="_blank" class="text-blue-600 hover:underline" x-text="src.title || src.url"></a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <div x-show="currentPost.error_message" class="p-2 bg-red-50 dark:bg-red-900/20 rounded text-red-700 dark:text-red-300 text-xs">
                            <strong>Error:</strong> <span x-text="currentPost.error_message"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function mysticContentPage() {
    return {
        modalOpen: false,
        currentPost: null,

        async viewPost(id) {
            try {
                const res = await fetch(`{{ url('/admin/fortune/mystic/posts') }}/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                this.currentPost = await res.json();
                this.modalOpen = true;
            } catch (e) {
                alert('โหลดข้อมูลล้มเหลว: ' + e.message);
            }
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
