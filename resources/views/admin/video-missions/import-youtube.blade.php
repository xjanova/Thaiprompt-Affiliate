@extends('layouts.admin-v3')

@section('title', 'นำเข้าวิดีโอจาก YouTube')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="youtubeImporter()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.video-missions.missions') }}"
               class="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition">
                <i class="fas fa-arrow-left text-white"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    <i class="fab fa-youtube text-red-500"></i>
                    นำเข้าวิดีโอจาก YouTube
                </h1>
                <p class="text-white/70 mt-1">ดึงวิดีโอจากช่อง YouTube และสร้างเป็นภารกิจอัตโนมัติ</p>
            </div>
        </div>
    </div>

    {{-- API Key Warning --}}
    @if(!$hasApiKey)
    <div class="bg-yellow-500/20 border border-yellow-500/50 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-400 text-xl mt-1"></i>
            <div>
                <h3 class="font-bold text-yellow-300">ยังไม่ได้ตั้งค่า YouTube API Key</h3>
                <p class="text-yellow-200/80 text-sm mt-1">
                    กรุณาเพิ่ม <code class="bg-black/30 px-2 py-0.5 rounded">YOUTUBE_API_KEY</code> ใน .env
                    หรือใส่ API Key ในฟอร์มด้านล่าง
                </p>
                <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
                   class="inline-flex items-center gap-2 mt-2 text-yellow-300 hover:text-yellow-100 text-sm">
                    <i class="fas fa-external-link-alt"></i>
                    รับ API Key จาก Google Cloud Console
                </a>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form --}}
        <div class="lg:col-span-2">
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-download text-blue-400"></i>
                    กรอกข้อมูลนำเข้า
                </h2>

                <form @submit.prevent="submitImport" class="space-y-6">
                    {{-- Channel Input --}}
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-tv mr-2 text-red-400"></i>
                            ช่อง YouTube <span class="text-red-400">*</span>
                        </label>
                        <input type="text"
                               x-model="form.channel"
                               placeholder="ใส่ชื่อช่อง เช่น @Metal-XProject หรือ URL หรือ Channel ID"
                               class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 transition"
                               required>
                        <p class="text-white/60 text-sm mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            รองรับ: @ชื่อช่อง, URL ช่อง, Channel ID (เริ่มด้วย UC)
                        </p>
                    </div>

                    {{-- API Key (ถ้าไม่มีใน .env) --}}
                    @if(!$hasApiKey)
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-key mr-2 text-yellow-400"></i>
                            YouTube API Key <span class="text-red-400">*</span>
                        </label>
                        <input type="password"
                               x-model="form.api_key"
                               placeholder="AIzaSy..."
                               class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/30 transition"
                               required>
                    </div>
                    @endif

                    {{-- Category --}}
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-folder mr-2 text-purple-400"></i>
                            หมวดหมู่ <span class="text-red-400">*</span>
                        </label>
                        <div class="flex gap-2">
                            <select x-model="form.category"
                                    class="flex-1 px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30 transition">
                                @foreach($categories as $cat)
                                <option value="{{ $cat }}" class="bg-gray-800">{{ ucfirst($cat) }}</option>
                                @endforeach
                            </select>
                            <input type="text"
                                   x-model="newCategory"
                                   placeholder="หรือพิมพ์หมวดใหม่"
                                   @keyup.enter="addCategory"
                                   class="w-48 px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:border-purple-500 transition">
                        </div>
                    </div>

                    {{-- Rewards --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-white font-medium mb-2">
                                <i class="fas fa-coins mr-2 text-yellow-400"></i>
                                รางวัลเงิน (บาท)
                            </label>
                            <input type="number"
                                   x-model="form.reward_money"
                                   min="0" max="1000" step="0.5"
                                   class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white focus:border-yellow-500 transition">
                        </div>
                        <div>
                            <label class="block text-white font-medium mb-2">
                                <i class="fas fa-gem mr-2 text-cyan-400"></i>
                                Coins
                            </label>
                            <input type="number"
                                   x-model="form.reward_coins"
                                   min="0" max="10000"
                                   class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white focus:border-cyan-500 transition">
                        </div>
                        <div>
                            <label class="block text-white font-medium mb-2">
                                <i class="fas fa-star mr-2 text-green-400"></i>
                                EXP
                            </label>
                            <input type="number"
                                   x-model="form.reward_exp"
                                   min="0" max="10000"
                                   class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white focus:border-green-500 transition">
                        </div>
                    </div>

                    {{-- Watch Percent & Limit --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-white font-medium mb-2">
                                <i class="fas fa-percentage mr-2 text-blue-400"></i>
                                ต้องดู % ของวิดีโอ
                            </label>
                            <input type="range"
                                   x-model="form.watch_percent"
                                   min="50" max="100" step="5"
                                   class="w-full">
                            <p class="text-white/70 text-center mt-1" x-text="form.watch_percent + '%'"></p>
                        </div>
                        <div>
                            <label class="block text-white font-medium mb-2">
                                <i class="fas fa-list-ol mr-2 text-orange-400"></i>
                                จำนวนวิดีโอ (สูงสุด)
                            </label>
                            <input type="number"
                                   x-model="form.limit"
                                   min="1" max="500"
                                   class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white focus:border-orange-500 transition">
                        </div>
                    </div>

                    {{-- Options --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-3 p-4 rounded-xl bg-white/5 border border-white/10 cursor-pointer hover:bg-white/10 transition">
                            <input type="checkbox"
                                   x-model="form.is_active"
                                   class="w-5 h-5 rounded bg-white/10 border-white/30 text-green-500 focus:ring-green-500/30">
                            <div>
                                <span class="text-white font-medium">เปิดใช้งานทันที</span>
                                <p class="text-white/60 text-sm">เปิดให้ user ทำภารกิจได้เลย</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 rounded-xl bg-white/5 border border-white/10 cursor-pointer hover:bg-white/10 transition">
                            <input type="checkbox"
                                   x-model="form.is_featured"
                                   class="w-5 h-5 rounded bg-white/10 border-white/30 text-yellow-500 focus:ring-yellow-500/30">
                            <div>
                                <span class="text-white font-medium">ภารกิจแนะนำ</span>
                                <p class="text-white/60 text-sm">แสดงในส่วนภารกิจแนะนำ</p>
                            </div>
                        </label>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit"
                            :disabled="isLoading"
                            class="w-full py-4 rounded-xl bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white font-bold text-lg transition transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        <span x-show="!isLoading" class="flex items-center justify-center gap-2">
                            <i class="fab fa-youtube"></i>
                            นำเข้าวิดีโอ
                        </span>
                        <span x-show="isLoading" class="flex items-center justify-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>
                            กำลังนำเข้า...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            {{-- Result --}}
            <div x-show="result" x-cloak
                 class="glass-card rounded-2xl p-6"
                 :class="result?.success ? 'border-2 border-green-500/50' : 'border-2 border-red-500/50'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2"
                    :class="result?.success ? 'text-green-400' : 'text-red-400'">
                    <i :class="result?.success ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                    <span x-text="result?.success ? 'นำเข้าสำเร็จ!' : 'เกิดข้อผิดพลาด'"></span>
                </h3>

                <template x-if="result?.success">
                    <div class="space-y-3 text-white/80">
                        <p><i class="fas fa-tv mr-2 text-red-400"></i> ช่อง: <span x-text="result?.data?.channel" class="text-white font-medium"></span></p>
                        <p><i class="fas fa-video mr-2 text-blue-400"></i> วิดีโอทั้งหมด: <span x-text="result?.data?.total_videos" class="text-white font-medium"></span></p>
                        <p><i class="fas fa-plus-circle mr-2 text-green-400"></i> สร้างใหม่: <span x-text="result?.data?.created" class="text-green-400 font-bold"></span></p>
                        <p><i class="fas fa-forward mr-2 text-yellow-400"></i> ข้าม (มีอยู่แล้ว): <span x-text="result?.data?.skipped" class="text-yellow-400 font-medium"></span></p>
                        <template x-if="result?.data?.errors > 0">
                            <p><i class="fas fa-exclamation-triangle mr-2 text-red-400"></i> ข้อผิดพลาด: <span x-text="result?.data?.errors" class="text-red-400 font-medium"></span></p>
                        </template>

                        <a href="{{ route('admin.video-missions.missions') }}"
                           class="block mt-4 text-center py-2 px-4 rounded-lg bg-white/10 hover:bg-white/20 text-white transition">
                            <i class="fas fa-list mr-2"></i>
                            ดูภารกิจทั้งหมด
                        </a>
                    </div>
                </template>

                <template x-if="!result?.success">
                    <p class="text-red-300" x-text="result?.message"></p>
                </template>
            </div>

            {{-- Quick Tips --}}
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-yellow-400"></i>
                    เคล็ดลับ
                </h3>
                <ul class="space-y-3 text-white/70 text-sm">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-1"></i>
                        <span>ใส่ <code class="bg-white/10 px-1 rounded">@ชื่อช่อง</code> เช่น @Metal-XProject</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-1"></i>
                        <span>วาง URL ช่องได้โดยตรง</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-1"></i>
                        <span>วิดีโอที่มีอยู่แล้วจะถูกข้ามโดยอัตโนมัติ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-1"></i>
                        <span>ระบบจะดึงความยาววิดีโอและคำนวณเวลาที่ต้องดูให้</span>
                    </li>
                </ul>
            </div>

            {{-- Example Channels --}}
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-star text-purple-400"></i>
                    ตัวอย่างการใส่
                </h3>
                <div class="space-y-2">
                    <button @click="form.channel = '@Metal-XProject'"
                            class="w-full text-left px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/80 text-sm transition">
                        @Metal-XProject
                    </button>
                    <button @click="form.channel = 'https://www.youtube.com/channel/UCEfNuxMsSvg8OTUzo53yIYg'"
                            class="w-full text-left px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/80 text-sm transition truncate">
                        youtube.com/channel/UCEfN...
                    </button>
                    <button @click="form.channel = 'UCEfNuxMsSvg8OTUzo53yIYg'"
                            class="w-full text-left px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/80 text-sm transition">
                        UCEfNuxMsSvg8OTUzo53yIYg
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function youtubeImporter() {
    return {
        form: {
            channel: '',
            api_key: '',
            category: 'youtube',
            reward_money: 1,
            reward_coins: 10,
            reward_exp: 5,
            watch_percent: 80,
            limit: 50,
            is_active: true,
            is_featured: false,
        },
        newCategory: '',
        isLoading: false,
        result: null,

        addCategory() {
            if (this.newCategory.trim()) {
                this.form.category = this.newCategory.trim().toLowerCase();
                this.newCategory = '';
            }
        },

        async submitImport() {
            if (!this.form.channel) {
                alert('กรุณาระบุช่อง YouTube');
                return;
            }

            this.isLoading = true;
            this.result = null;

            try {
                const response = await fetch('{{ route("admin.video-missions.import-youtube.process") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();
                this.result = data;

                if (!response.ok) {
                    this.result.success = false;
                }
            } catch (error) {
                console.error('Import error:', error);
                this.result = {
                    success: false,
                    message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message,
                };
            } finally {
                this.isLoading = false;
            }
        },
    };
}
</script>
@endpush

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
</style>
@endsection
