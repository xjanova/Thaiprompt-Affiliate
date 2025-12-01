@extends('layouts.admin-v3')

@section('title', 'สร้างภารกิจดูคลิปใหม่')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                ➕ สร้างภารกิจดูคลิปใหม่
            </h1>
            <p class="text-gray-600 dark:text-gray-400">กำหนดรายละเอียดภารกิจและรางวัล</p>
        </div>
        <a href="{{ route('admin.video-missions.missions') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
            ← กลับ
        </a>
    </div>

    <form action="{{ route('admin.video-missions.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- ข้อมูลพื้นฐาน --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📝</span> ข้อมูลพื้นฐาน
            </h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อภารกิจ (EN) <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อภารกิจ (TH)</label>
                    <input type="text" name="title_th" value="{{ old('title_th') }}"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย (EN)</label>
                    <textarea name="description" rows="2"
                              class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">{{ old('description') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย (TH)</label>
                    <textarea name="description_th" rows="2"
                              class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">{{ old('description_th') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ไอคอน (Emoji)</label>
                    <input type="text" name="icon" value="{{ old('icon', '🎬') }}" maxlength="10"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่</label>
                    <input type="text" name="category" value="{{ old('category') }}" placeholder="เช่น แนะนำ, สอนใช้งาน"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รูปภาพปก</label>
                    <input type="file" name="thumbnail" accept="image/*"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
        </div>

        {{-- ข้อมูลวิดีโอ --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>🎥</span> ข้อมูลวิดีโอ
            </h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL วิดีโอ <span class="text-red-500">*</span></label>
                    <input type="url" name="video_url" value="{{ old('video_url') }}" required
                           placeholder="https://www.youtube.com/watch?v=..."
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    @error('video_url') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทวิดีโอ <span class="text-red-500">*</span></label>
                    <select name="video_type" required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                        <option value="youtube" {{ old('video_type') == 'youtube' ? 'selected' : '' }}>YouTube</option>
                        <option value="vimeo" {{ old('video_type') == 'vimeo' ? 'selected' : '' }}>Vimeo</option>
                        <option value="tiktok" {{ old('video_type') == 'tiktok' ? 'selected' : '' }}>TikTok</option>
                        <option value="facebook" {{ old('video_type') == 'facebook' ? 'selected' : '' }}>Facebook</option>
                        <option value="direct" {{ old('video_type') == 'direct' ? 'selected' : '' }}>Direct URL</option>
                        <option value="embed" {{ old('video_type') == 'embed' ? 'selected' : '' }}>Embed Code</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Video ID</label>
                    <input type="text" name="video_id" value="{{ old('video_id') }}"
                           placeholder="เช่น dQw4w9WgXcQ"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความยาววิดีโอ (วินาที)</label>
                    <input type="number" name="video_duration_seconds" value="{{ old('video_duration_seconds') }}" min="1"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
            </div>
        </div>

        {{-- เงื่อนไขการดู --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>⏱️</span> เงื่อนไขการดู
            </h2>
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ต้องดูกี่วินาที <span class="text-red-500">*</span></label>
                    <input type="number" name="required_watch_seconds" value="{{ old('required_watch_seconds', 30) }}" required min="5"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ต้องดูกี่ % ของวิดีโอ</label>
                    <input type="number" name="required_watch_percentage" value="{{ old('required_watch_percentage', 80) }}" min="1" max="100"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div class="flex items-end gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="must_watch_complete" value="1" {{ old('must_watch_complete') ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">ต้องดูจนจบ</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- รางวัล --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>🎁</span> รางวัล
            </h2>
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทรางวัล <span class="text-red-500">*</span></label>
                    <select name="reward_type" required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                        <option value="money" {{ old('reward_type') == 'money' ? 'selected' : '' }}>เงิน</option>
                        <option value="coins" {{ old('reward_type') == 'coins' ? 'selected' : '' }}>Coins</option>
                        <option value="points" {{ old('reward_type') == 'points' ? 'selected' : '' }}>Points</option>
                        <option value="tpix" {{ old('reward_type') == 'tpix' ? 'selected' : '' }}>TPIX</option>
                        <option value="exp" {{ old('reward_type') == 'exp' ? 'selected' : '' }}>EXP เท่านั้น</option>
                        <option value="mixed" {{ old('reward_type', 'mixed') == 'mixed' ? 'selected' : '' }}>รวม (หลายอย่าง)</option>
                    </select>
                </div>
            </div>
            <div class="grid md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">💵 เงิน (บาท)</label>
                    <input type="number" name="reward_money" value="{{ old('reward_money', 0) }}" step="0.01" min="0"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🪙 Coins</label>
                    <input type="number" name="reward_coins" value="{{ old('reward_coins', 0) }}" min="0"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">⭐ Points</label>
                    <input type="number" name="reward_points" value="{{ old('reward_points', 0) }}" min="0"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">💎 TPIX</label>
                    <input type="number" name="reward_tpix" value="{{ old('reward_tpix', 0) }}" step="0.0001" min="0"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">📈 EXP</label>
                    <input type="number" name="reward_exp" value="{{ old('reward_exp', 0) }}" min="0"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
            </div>
        </div>

        {{-- ความถี่และลิมิต --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>🔄</span> ความถี่และลิมิต
            </h2>
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความถี่ <span class="text-red-500">*</span></label>
                    <select name="frequency" required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                        <option value="once" {{ old('frequency') == 'once' ? 'selected' : '' }}>ครั้งเดียวต่อผู้ใช้</option>
                        <option value="daily" {{ old('frequency', 'daily') == 'daily' ? 'selected' : '' }}>วันละครั้ง</option>
                        <option value="weekly" {{ old('frequency') == 'weekly' ? 'selected' : '' }}>สัปดาห์ละครั้ง</option>
                        <option value="monthly" {{ old('frequency') == 'monthly' ? 'selected' : '' }}>เดือนละครั้ง</option>
                        <option value="unlimited" {{ old('frequency') == 'unlimited' ? 'selected' : '' }}>ไม่จำกัด (มี cooldown)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cooldown (นาที)</label>
                    <input type="number" name="cooldown_minutes" value="{{ old('cooldown_minutes', 0) }}" min="0"
                           placeholder="สำหรับความถี่ไม่จำกัด"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำกัดต่อผู้ใช้ (ครั้ง)</label>
                    <input type="number" name="max_completions_per_user" value="{{ old('max_completions_per_user') }}" min="1"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
            </div>
        </div>

        {{-- คุณสมบัติผู้เข้าร่วม --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>👤</span> คุณสมบัติผู้เข้าร่วม
            </h2>
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rank ขั้นต่ำ</label>
                    <select name="min_rank_id"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                        <option value="">ไม่จำกัด</option>
                        @foreach($ranks as $rank)
                        <option value="{{ $rank->id }}" {{ old('min_rank_id') == $rank->id ? 'selected' : '' }}>{{ $rank->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rank สูงสุด</label>
                    <select name="max_rank_id"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                        <option value="">ไม่จำกัด</option>
                        @foreach($ranks as $rank)
                        <option value="{{ $rank->id }}" {{ old('max_rank_id') == $rank->id ? 'selected' : '' }}>{{ $rank->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เป็นสมาชิกอย่างน้อย (วัน)</label>
                    <input type="number" name="min_days_registered" value="{{ old('min_days_registered', 0) }}" min="0"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
            </div>
            <div class="flex flex-wrap gap-6 mt-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="require_kyc" value="1" {{ old('require_kyc') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">ต้องยืนยัน KYC</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="require_email_verified" value="1" {{ old('require_email_verified') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">ต้องยืนยันอีเมล</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="require_phone_verified" value="1" {{ old('require_phone_verified') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">ต้องยืนยันเบอร์โทร</span>
                </label>
            </div>
        </div>

        {{-- Anti-Cheat --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>🛡️</span> Anti-Cheat
            </h2>
            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="anti_cheat_enabled" value="1" {{ old('anti_cheat_enabled', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน Anti-Cheat</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="require_focus" value="1" {{ old('require_focus', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">ต้อง Focus หน้าต่าง</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="detect_tab_switch" value="1" {{ old('detect_tab_switch', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">ตรวจจับการสลับ Tab</span>
                </label>
            </div>
            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อนุญาตสลับ Tab ได้กี่ครั้ง</label>
                    <input type="number" name="max_tab_switches" value="{{ old('max_tab_switches', 3) }}" min="0"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
            </div>
        </div>

        {{-- ตัวเลือกเพิ่มเติม --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>⚙️</span> ตัวเลือกเพิ่มเติม
            </h2>
            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">แนะนำ (Featured)</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_premium" value="1" {{ old('is_premium') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Premium Only</span>
                </label>
            </div>
            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority (ยิ่งสูงยิ่งแสดงก่อน)</label>
                    <input type="number" name="priority" value="{{ old('priority', 50) }}" min="0"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.video-missions.missions') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all shadow-lg hover:shadow-pink-500/25">
                💾 สร้างภารกิจ
            </button>
        </div>
    </form>
</div>
@endsection
