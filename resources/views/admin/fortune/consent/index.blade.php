@extends('layouts.admin-v3')

@section('title', 'กติกาก่อนจองคิว')

@section('content')
@php
    $scopeBadges = [
        'consent' => ['📜 อ่านกติกา', 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200'],
        'cancel' => ['🚫 ตอนยกเลิก', 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200'],
        'expire' => ['⏰ หมดเวลาเอง', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'],
        'both' => ['🔁 กติกา+ยกเลิก', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'],
        'all' => ['🌐 ทุกจังหวะ', 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200'],
    ];
@endphp
<div class="container mx-auto px-4 py-8 max-w-6xl">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📜 กติกาก่อนจองคิว</h1>
        <a href="{{ route('admin.fortune.consent.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow transition">
            ➕ อัปโหลดรูปใหม่
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200">{{ session('error') }}</div>
    @endif

    {{-- ℹ️ คำอธิบาย --}}
    <div class="mb-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-sm text-blue-900 dark:text-blue-200">
        กล่องกติกาจะ <b>เด้งทุกครั้งก่อนสร้างบิลค่าครู</b> ให้ลูกค้าอ่าน + กดยืนยัน "พร้อมบูชาครู" ก่อนจึงออก QR<br>
        ส่วน <b>คำเตือนตอนยกเลิก</b> จะส่งเฉพาะคนที่ <b>สร้างบิลแล้วกดยกเลิกแบบเบี้ยว</b> — ถ้าเป็นเหตุสุดวิสัย (โอนไม่ได้/แอพมีปัญหา) บอทจะไม่เตือนแรง
    </div>

    {{-- ⚙️ Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚙️ ตั้งค่ากติกา</h3>

        <form action="{{ route('admin.fortune.consent.settings') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                    <input type="checkbox" name="fortune_consent_enabled" value="1"
                           @checked($settings->fortune_consent_enabled ?? true)
                           class="w-5 h-5 text-blue-600 rounded">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">เปิดกล่องกติกาก่อนสร้างบิล</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">เด้งให้ยืนยันก่อนออก QR ทุกบิล</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                    <input type="checkbox" name="fortune_consent_cancel_enabled" value="1"
                           @checked($settings->fortune_consent_cancel_enabled ?? true)
                           class="w-5 h-5 text-blue-600 rounded">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">เตือน + ส่งรูปตอนยกเลิก</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">เฉพาะเจตนาเบี้ยว (ไม่ใช่เหตุสุดวิสัย)</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                    <input type="checkbox" name="fortune_consent_expire_enabled" value="1"
                           @checked($settings->fortune_consent_expire_enabled ?? true)
                           class="w-5 h-5 text-blue-600 rounded">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">เตือนตอนบิลหมดเวลาเอง</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">โทนนุ่ม (อาจแค่ลืม) + รูป scope=หมดเวลา</div>
                    </div>
                </label>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">กลยุทธ์การสุ่มรูป</label>
                <select name="fortune_consent_pick_strategy"
                        class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="random" @selected(($settings->fortune_consent_pick_strategy ?? 'random') === 'random')>🎲 สุ่ม (random)</option>
                    <option value="rotation" @selected(($settings->fortune_consent_pick_strategy ?? '') === 'rotation')>🔄 วนรอบ (rotation)</option>
                    <option value="sequential" @selected(($settings->fortune_consent_pick_strategy ?? '') === 'sequential')>📋 ตามลำดับ (sequential)</option>
                </select>
            </div>

            {{-- ข้อความกติกา --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    📜 ข้อความกติกา (เด้งก่อนสร้างบิล) <span class="text-gray-400">— เว้นว่าง = ใช้ข้อความเริ่มต้น</span>
                </label>
                <textarea name="fortune_consent_text" rows="14"
                          class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono text-sm"
                          placeholder="{{ $defaultConsentText }}">{{ old('fortune_consent_text', $settings->fortune_consent_text) }}</textarea>
            </div>

            {{-- ข้อความเตือนตอนยกเลิก --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    🚫 ข้อความเตือนตอนลูกค้ายกเลิกบิล (เจตนาเบี้ยว) <span class="text-gray-400">— เว้นว่าง = ใช้ข้อความเริ่มต้น</span>
                </label>
                <textarea name="fortune_consent_cancel_text" rows="9"
                          class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono text-sm"
                          placeholder="{{ $defaultCancelText }}">{{ old('fortune_consent_cancel_text', $settings->fortune_consent_cancel_text) }}</textarea>
            </div>

            {{-- ข้อความตอนบิลหมดเวลาเอง (โทนนุ่ม) --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    ⏰ ข้อความตอนบิลหมดเวลาเอง (โทนนุ่ม) <span class="text-gray-400">— เว้นว่าง = ใช้ข้อความเริ่มต้น</span>
                </label>
                <textarea name="fortune_consent_expire_text" rows="8"
                          class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono text-sm"
                          placeholder="{{ $defaultExpireText }}">{{ old('fortune_consent_expire_text', $settings->fortune_consent_expire_text) }}</textarea>
            </div>

            <button type="submit"
                    class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow transition">
                💾 บันทึกการตั้งค่า
            </button>
        </form>
    </div>

    {{-- Gallery --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🖼️ คลังรูปกติกา ({{ $images->count() }})</h3>

        @if($images->isEmpty())
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <p class="mb-2">ยังไม่มีรูป — กล่องกติกาจะส่งเฉพาะ "ข้อความ" (ไม่มีรูป)</p>
                <a href="{{ route('admin.fortune.consent.create') }}"
                   class="inline-flex items-center gap-2 mt-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                    ➕ อัปโหลดรูปแรก
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($images as $image)
                    @php [$badgeLabel, $badgeClass] = $scopeBadges[$image->usage_scope] ?? $scopeBadges['both']; @endphp
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden flex flex-col">
                        <div class="relative bg-gray-100 dark:bg-gray-900 aspect-video">
                            <img src="{{ $image->image_url }}" alt="{{ $image->name }}"
                                 class="w-full h-full object-cover" loading="lazy">
                            @if(! $image->is_active)
                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                    <span class="text-white font-bold">ปิดใช้งาน</span>
                                </div>
                            @endif
                            <div class="absolute top-2 left-2 px-2 py-0.5 rounded bg-black/60 text-white text-xs">#{{ $image->sort_order }}</div>
                            <span class="absolute top-2 right-2 px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">{{ $badgeLabel }}</span>
                        </div>

                        <div class="p-4 flex-1 flex flex-col">
                            <div class="font-semibold text-gray-900 dark:text-white mb-1">{{ $image->name }}</div>
                            @if($image->description)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ Str::limit($image->description, 60) }}</div>
                            @endif
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                📐 {{ $image->width }}×{{ $image->height }}
                                • 📤 ส่งไปแล้ว {{ number_format($image->send_count) }} ครั้ง
                            </div>

                            <div class="flex gap-2 mt-auto">
                                <a href="{{ route('admin.fortune.consent.edit', $image) }}"
                                   class="flex-1 text-center px-3 py-1.5 text-xs bg-blue-100 hover:bg-blue-200 text-blue-800 rounded">✏️ แก้ไข</a>
                                <form action="{{ route('admin.fortune.consent.toggle', $image) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                            class="w-full px-3 py-1.5 text-xs {{ $image->is_active ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-800' : 'bg-green-100 hover:bg-green-200 text-green-800' }} rounded">
                                        {{ $image->is_active ? '⏸️ ปิด' : '▶️ เปิด' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.fortune.consent.destroy', $image) }}" method="POST" class="flex-1"
                                      onsubmit="return confirm('ลบรูปนี้?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full px-3 py-1.5 text-xs bg-red-100 hover:bg-red-200 text-red-800 rounded">🗑️</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
