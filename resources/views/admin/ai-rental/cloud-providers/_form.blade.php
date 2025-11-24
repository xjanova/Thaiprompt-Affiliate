{{--
    Form Partial สำหรับ Cloud Provider (ใช้ร่วมกันทั้ง Create และ Edit)

    @param $provider - AiRentalCloudProvider model (null สำหรับ create)
--}}

<div class="space-y-6">
    {{-- Basic Information --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-info-circle text-cyan-400"></i>
            ข้อมูลพื้นฐาน
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Name --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    ชื่อ Provider <span class="text-red-400">*</span>
                </label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $provider->name ?? '') }}"
                       required
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('name') border-red-500 @enderror"
                       placeholder="เช่น Google Colab, RunPod">
                @error('name')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Slug --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Slug <span class="text-red-400">*</span>
                </label>
                <input type="text"
                       name="slug"
                       value="{{ old('slug', $provider->slug ?? '') }}"
                       required
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('slug') border-red-500 @enderror"
                       placeholder="เช่น google-colab, runpod">
                @error('slug')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div class="md:col-span-2">
                <label class="block text-white font-semibold mb-2">
                    คำอธิบาย
                </label>
                <textarea name="description"
                          rows="3"
                          class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('description') border-red-500 @enderror"
                          placeholder="คำอธิบายเกี่ยวกับ provider นี้...">{{ old('description', $provider->description ?? '') }}</textarea>
                @error('description')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tier --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Tier <span class="text-red-400">*</span>
                </label>
                <select name="tier"
                        required
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('tier') border-red-500 @enderror">
                    <option value="">-- เลือก Tier --</option>
                    <option value="free" {{ old('tier', $provider->tier ?? '') === 'free' ? 'selected' : '' }}>Free (ฟรี)</option>
                    <option value="paid" {{ old('tier', $provider->tier ?? '') === 'paid' ? 'selected' : '' }}>Paid (ต้องจ่ายเงิน)</option>
                    <option value="both" {{ old('tier', $provider->tier ?? '') === 'both' ? 'selected' : '' }}>Both (มีทั้งฟรีและเสียเงิน)</option>
                </select>
                @error('tier')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Display Order --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    ลำดับการแสดงผล
                </label>
                <input type="number"
                       name="display_order"
                       value="{{ old('display_order', $provider->display_order ?? 0) }}"
                       min="0"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="0">
            </div>
        </div>
    </div>

    {{-- URLs --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-link text-purple-400"></i>
            ลิงก์ต่างๆ
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Website URL --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Website URL <span class="text-red-400">*</span>
                </label>
                <input type="url"
                       name="website_url"
                       value="{{ old('website_url', $provider->website_url ?? '') }}"
                       required
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('website_url') border-red-500 @enderror"
                       placeholder="https://example.com">
                @error('website_url')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Signup URL --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Signup URL
                </label>
                <input type="url"
                       name="signup_url"
                       value="{{ old('signup_url', $provider->signup_url ?? '') }}"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="https://example.com/signup">
            </div>

            {{-- Documentation URL --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Documentation URL
                </label>
                <input type="url"
                       name="documentation_url"
                       value="{{ old('documentation_url', $provider->documentation_url ?? '') }}"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="https://docs.example.com">
            </div>

            {{-- Pricing URL --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Pricing URL
                </label>
                <input type="url"
                       name="pricing_url"
                       value="{{ old('pricing_url', $provider->pricing_url ?? '') }}"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="https://example.com/pricing">
            </div>
        </div>
    </div>

    {{-- Pricing --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-dollar-sign text-green-400"></i>
            ราคา
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Min Price --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    ราคาต่ำสุดต่อชั่วโมง (USD)
                </label>
                <input type="number"
                       name="min_price_per_hour"
                       value="{{ old('min_price_per_hour', $provider->min_price_per_hour ?? '') }}"
                       step="0.01"
                       min="0"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="0.20">
            </div>

            {{-- Max Price --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    ราคาสูงสุดต่อชั่วโมง (USD)
                </label>
                <input type="number"
                       name="max_price_per_hour"
                       value="{{ old('max_price_per_hour', $provider->max_price_per_hour ?? '') }}"
                       step="0.01"
                       min="0"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="2.50">
            </div>
        </div>
    </div>

    {{-- Features --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-check-square text-blue-400"></i>
            คุณสมบัติที่รองรับ
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            {{-- Hugging Face --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="supports_huggingface"
                       value="1"
                       {{ old('supports_huggingface', $provider->supports_huggingface ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-cyan-500 focus:ring-2 focus:ring-cyan-400">
                <span class="text-white">🤗 Hugging Face</span>
            </label>

            {{-- Docker --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="supports_docker"
                       value="1"
                       {{ old('supports_docker', $provider->supports_docker ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-cyan-500 focus:ring-2 focus:ring-cyan-400">
                <span class="text-white">🐳 Docker</span>
            </label>

            {{-- Jupyter --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="supports_jupyter"
                       value="1"
                       {{ old('supports_jupyter', $provider->supports_jupyter ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-cyan-500 focus:ring-2 focus:ring-cyan-400">
                <span class="text-white">📓 Jupyter</span>
            </label>

            {{-- SSH --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="supports_ssh"
                       value="1"
                       {{ old('supports_ssh', $provider->supports_ssh ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-cyan-500 focus:ring-2 focus:ring-cyan-400">
                <span class="text-white">🔑 SSH</span>
            </label>

            {{-- API --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="supports_api"
                       value="1"
                       {{ old('supports_api', $provider->supports_api ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-cyan-500 focus:ring-2 focus:ring-cyan-400">
                <span class="text-white">🔌 API</span>
            </label>

            {{-- Spot Instances --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="supports_spot_instances"
                       value="1"
                       {{ old('supports_spot_instances', $provider->supports_spot_instances ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-cyan-500 focus:ring-2 focus:ring-cyan-400">
                <span class="text-white">⚡ Spot Instances</span>
            </label>
        </div>
    </div>

    {{-- Free Tier --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-gift text-purple-400"></i>
            Free Tier (ถ้ามี)
        </h2>

        <div class="space-y-4">
            {{-- Free Tier Available --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="free_tier_available"
                       value="1"
                       {{ old('free_tier_available', $provider->free_tier_available ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-purple-500 focus:ring-2 focus:ring-purple-400">
                <span class="text-white font-semibold">มี Free Tier</span>
            </label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Free Hours --}}
                <div>
                    <label class="block text-white font-semibold mb-2">
                        ชั่วโมงฟรีต่อเดือน
                    </label>
                    <input type="number"
                           name="free_tier_hours_per_month"
                           value="{{ old('free_tier_hours_per_month', $provider->free_tier_hours_per_month ?? '') }}"
                           min="0"
                           class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                           placeholder="12">
                </div>

                {{-- Limitations --}}
                <div>
                    <label class="block text-white font-semibold mb-2">
                        ข้อจำกัด
                    </label>
                    <input type="text"
                           name="free_tier_limitations"
                           value="{{ old('free_tier_limitations', $provider->free_tier_limitations ?? '') }}"
                           class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                           placeholder="เช่น No GPU persistence, Session timeout">
                </div>
            </div>
        </div>
    </div>

    {{-- Status & Settings --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-cog text-yellow-400"></i>
            การตั้งค่า
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Active --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="is_active"
                       value="1"
                       {{ old('is_active', $provider->is_active ?? true) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-green-500 focus:ring-2 focus:ring-green-400">
                <span class="text-white font-semibold">เปิดใช้งาน</span>
            </label>

            {{-- Recommended --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="is_recommended"
                       value="1"
                       {{ old('is_recommended', $provider->is_recommended ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-yellow-500 focus:ring-2 focus:ring-yellow-400">
                <span class="text-white font-semibold">แนะนำ</span>
            </label>

            {{-- Rating --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    คะแนน (0-5)
                </label>
                <input type="number"
                       name="average_rating"
                       value="{{ old('average_rating', $provider->average_rating ?? '') }}"
                       step="0.1"
                       min="0"
                       max="5"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="4.5">
            </div>
        </div>
    </div>
</div>
