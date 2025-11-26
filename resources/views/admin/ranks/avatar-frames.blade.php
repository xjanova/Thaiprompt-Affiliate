@extends('layouts.admin')

@section('title', 'จัดการกรอบอวาต้าร์')

@section('content')
{{--
/**
 * Admin Avatar Frames Management
 *
 * จัดการกรอบอวาต้าร์สำหรับแต่ละ Rank
 * - อัพโหลด custom frame (PNG, SVG, WebP)
 * - เลือก animation type
 * - Preview ตัวอย่างกรอบ
 *
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white/10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white/10 rounded-full"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-portrait text-4xl"></i>
                    <h1 class="text-3xl font-bold">จัดการกรอบอวาต้าร์</h1>
                </div>
                <p class="text-purple-100">อัพโหลดกรอบ Avatar พิเศษสำหรับแต่ละระดับ Rank</p>
            </div>
            <a href="{{ route('admin.ranks.index') }}"
               class="px-6 py-3 bg-white/20 backdrop-blur-lg rounded-xl hover:bg-white/30 transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปหน้า Ranks</span>
            </a>
        </div>
    </div>

    {{-- Info Alert --}}
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-500 text-xl mt-0.5"></i>
            <div>
                <h3 class="font-semibold text-blue-900 dark:text-blue-100">คำแนะนำการอัพโหลดกรอบ</h3>
                <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
                    <li>รองรับไฟล์ PNG, SVG, WebP (แนะนำ PNG หรือ SVG เพื่อรองรับ transparency)</li>
                    <li>ขนาดไฟล์สูงสุด 2MB</li>
                    <li>ขนาดแนะนำ: 200x200 pixels หรือมากกว่า (อัตราส่วน 1:1)</li>
                    <li>กรอบควรมีพื้นหลังโปร่งใส (transparent) เพื่อให้เห็น avatar ได้ชัดเจน</li>
                    <li>ถ้าไม่อัพโหลด custom frame ระบบจะใช้ fallback frame ที่ออกแบบไว้</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Ranks Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($ranks as $rank)
            @php
                $rankBadges = [
                    1 => '🥉', 2 => '🥈', 3 => '🥇', 4 => '💎',
                    5 => '💠', 6 => '👑', 7 => '🏆', 8 => '⭐',
                ];
                $rankBadge = $rankBadges[$rank->level] ?? '🏅';
                $hasCustomFrame = $rank->hasCustomFrame();
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden"
                 x-data="{ showUpload: false }">
                {{-- Rank Header --}}
                <div class="p-4 bg-gradient-to-r {{ $rank->level >= 7 ? 'from-purple-500 to-pink-500' : ($rank->level >= 5 ? 'from-cyan-500 to-blue-500' : ($rank->level >= 3 ? 'from-yellow-400 to-amber-500' : 'from-gray-400 to-slate-500')) }} text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">{{ $rankBadge }}</span>
                            <div>
                                <h3 class="font-bold text-lg">{{ $rank->name_th ?? $rank->name }}</h3>
                                <p class="text-sm opacity-80">Level {{ $rank->level }}</p>
                            </div>
                        </div>
                        @if($hasCustomFrame)
                            <span class="px-2 py-1 bg-green-500/30 text-green-100 text-xs rounded-full">
                                <i class="fas fa-check mr-1"></i> Custom
                            </span>
                        @else
                            <span class="px-2 py-1 bg-white/20 text-white text-xs rounded-full">
                                Fallback
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Preview --}}
                <div class="p-6">
                    {{-- Avatar Preview with Frame --}}
                    <div class="flex justify-center mb-6">
                        <x-rank-avatar
                            :rank-level="$rank->level"
                            :rank="$rank"
                            size="xl"
                            src="https://ui-avatars.com/api/?name={{ urlencode($rank->name) }}&background=random"
                        />
                    </div>

                    {{-- Current Frame Info --}}
                    @if($hasCustomFrame)
                        <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="flex items-center gap-2 text-green-700 dark:text-green-400 text-sm">
                                <i class="fas fa-image"></i>
                                <span>กรอบ Custom ถูกอัพโหลด</span>
                            </div>
                            <p class="text-xs text-green-600 dark:text-green-500 mt-1 truncate">
                                {{ basename($rank->avatar_frame) }}
                            </p>
                        </div>
                    @else
                        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400 text-sm">
                                <i class="fas fa-magic"></i>
                                <span>ใช้กรอบ Fallback (CSS)</span>
                            </div>
                        </div>
                    @endif

                    {{-- Animation Select --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Animation
                        </label>
                        <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                onchange="updateAnimation({{ $rank->id }}, this.value)">
                            <option value="none" {{ $rank->frame_animation === 'none' ? 'selected' : '' }}>ไม่มี Animation</option>
                            <option value="pulse" {{ $rank->frame_animation === 'pulse' ? 'selected' : '' }}>Pulse (เต้น)</option>
                            <option value="glow" {{ $rank->frame_animation === 'glow' ? 'selected' : '' }}>Glow (เรืองแสง)</option>
                            <option value="sparkle" {{ $rank->frame_animation === 'sparkle' ? 'selected' : '' }}>Sparkle (วิบวับ)</option>
                            <option value="rotate" {{ $rank->frame_animation === 'rotate' ? 'selected' : '' }}>Rotate (หมุน)</option>
                        </select>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-2">
                        <button @click="showUpload = !showUpload"
                                class="flex-1 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition flex items-center justify-center gap-2">
                            <i class="fas fa-upload"></i>
                            <span>อัพโหลด</span>
                        </button>

                        @if($hasCustomFrame)
                            <form action="{{ route('admin.ranks.delete-frame', $rank) }}" method="POST"
                                  onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบกรอบนี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- Upload Form (Hidden by default) --}}
                    <div x-show="showUpload" x-collapse class="mt-4">
                        <form action="{{ route('admin.ranks.upload-frame', $rank) }}" method="POST" enctype="multipart/form-data"
                              class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            @csrf
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    เลือกไฟล์กรอบ
                                </label>
                                <input type="file" name="avatar_frame" accept=".png,.svg,.webp" required
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Animation
                                </label>
                                <select name="frame_animation"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                    <option value="none">ไม่มี Animation</option>
                                    <option value="pulse">Pulse</option>
                                    <option value="glow">Glow</option>
                                    <option value="sparkle">Sparkle</option>
                                    <option value="rotate">Rotate</option>
                                </select>
                            </div>
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                                <i class="fas fa-save mr-2"></i>
                                บันทึก
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Preview Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-eye text-purple-500"></i>
            <span>ตัวอย่างกรอบทุกขนาด</span>
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-4 text-gray-600 dark:text-gray-400">Rank</th>
                        <th class="pb-4 text-gray-600 dark:text-gray-400 text-center">XS</th>
                        <th class="pb-4 text-gray-600 dark:text-gray-400 text-center">SM</th>
                        <th class="pb-4 text-gray-600 dark:text-gray-400 text-center">MD</th>
                        <th class="pb-4 text-gray-600 dark:text-gray-400 text-center">LG</th>
                        <th class="pb-4 text-gray-600 dark:text-gray-400 text-center">XL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ranks as $rank)
                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                            <td class="py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">{{ $rankBadges[$rank->level] ?? '🏅' }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $rank->name_th ?? $rank->name }}</span>
                                </div>
                            </td>
                            <td class="py-4 text-center">
                                <div class="flex justify-center">
                                    <x-rank-avatar :rank-level="$rank->level" :rank="$rank" size="xs" :show-badge="false" />
                                </div>
                            </td>
                            <td class="py-4 text-center">
                                <div class="flex justify-center">
                                    <x-rank-avatar :rank-level="$rank->level" :rank="$rank" size="sm" :show-badge="false" />
                                </div>
                            </td>
                            <td class="py-4 text-center">
                                <div class="flex justify-center">
                                    <x-rank-avatar :rank-level="$rank->level" :rank="$rank" size="md" />
                                </div>
                            </td>
                            <td class="py-4 text-center">
                                <div class="flex justify-center">
                                    <x-rank-avatar :rank-level="$rank->level" :rank="$rank" size="lg" />
                                </div>
                            </td>
                            <td class="py-4 text-center">
                                <div class="flex justify-center">
                                    <x-rank-avatar :rank-level="$rank->level" :rank="$rank" size="xl" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    /**
     * อัพเดท animation ของกรอบ
     */
    function updateAnimation(rankId, animation) {
        fetch(`{{ url('admin/ranks') }}/${rankId}/update-animation`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ frame_animation: animation }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // แจ้งเตือนสำเร็จ
                alert(data.message);
                // Reload เพื่อดู preview ใหม่
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการอัพเดท animation');
        });
    }
</script>
@endpush
@endsection
