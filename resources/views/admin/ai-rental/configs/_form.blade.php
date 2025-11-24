{{--
    Form Partial สำหรับ Configuration (ใช้ร่วมกันทั้ง Create และ Edit)

    @param $config - AiRentalCloudConfig model (null สำหรับ create)
    @param $providers - Collection of CloudProviders
--}}

<div class="space-y-6">
    {{-- Basic Information --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-info-circle text-cyan-400"></i>
            ข้อมูลพื้นฐาน
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Config Name --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    ชื่อ Configuration <span class="text-red-400">*</span>
                </label>
                <input type="text"
                       name="config_name"
                       value="{{ old('config_name', $config->config_name ?? '') }}"
                       required
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('config_name') border-red-500 @enderror"
                       placeholder="เช่น My RunPod Config, Vast.ai Production">
                @error('config_name')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-white/50 text-xs mt-1">ชื่อที่ใช้อ้างอิงถึง configuration นี้</p>
            </div>

            {{-- Cloud Provider --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Cloud Provider <span class="text-red-400">*</span>
                </label>
                <select name="cloud_provider_id"
                        required
                        {{ $config ? 'disabled' : '' }}
                        class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('cloud_provider_id') border-red-500 @enderror">
                    <option value="">-- เลือก Provider --</option>
                    @foreach($providers as $provider)
                    <option value="{{ $provider->id }}"
                            {{ old('cloud_provider_id', $config->cloud_provider_id ?? '') == $provider->id ? 'selected' : '' }}>
                        {{ $provider->name }}
                        @if($provider->tier === 'free')
                            (FREE)
                        @endif
                    </option>
                    @endforeach
                </select>
                @if($config)
                <input type="hidden" name="cloud_provider_id" value="{{ $config->cloud_provider_id }}">
                <p class="text-white/50 text-xs mt-1">ไม่สามารถเปลี่ยน provider ได้หลังจากสร้างแล้ว</p>
                @endif
                @error('cloud_provider_id')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- API Credentials --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-key text-yellow-400"></i>
            API Credentials
        </h2>

        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4 mb-4">
            <div class="flex gap-3">
                <i class="fas fa-shield-alt text-blue-400 text-xl"></i>
                <div class="flex-1">
                    <div class="text-white font-semibold mb-1">🔐 ข้อมูลจะถูกเข้ารหัส</div>
                    <div class="text-white/70 text-sm">
                        API Key และ Secret จะถูกเข้ารหัสด้วย Laravel Encryption ก่อนบันทึกลงฐานข้อมูล
                        เพื่อความปลอดภัยสูงสุด
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
            {{-- API Key --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    API Key
                </label>
                <input type="password"
                       name="api_key"
                       value="{{ old('api_key') }}"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('api_key') border-red-500 @enderror"
                       placeholder="กรอก API Key จาก Cloud Provider">
                @error('api_key')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
                @if($config && $config->api_key)
                <p class="text-green-400 text-sm mt-1">
                    <i class="fas fa-check-circle"></i> API Key ถูกตั้งค่าไว้แล้ว (เว้นว่างถ้าไม่ต้องการเปลี่ยน)
                </p>
                @endif
            </div>

            {{-- API Secret --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    API Secret / Token (ถ้ามี)
                </label>
                <input type="password"
                       name="api_secret"
                       value="{{ old('api_secret') }}"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="กรอก API Secret (ถ้า Provider ต้องการ)">
                @if($config && $config->api_secret)
                <p class="text-green-400 text-sm mt-1">
                    <i class="fas fa-check-circle"></i> API Secret ถูกตั้งค่าไว้แล้ว
                </p>
                @endif
            </div>
        </div>
    </div>

    {{-- GPU Configuration --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-microchip text-purple-400"></i>
            GPU Configuration
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Region --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Region / Data Center
                </label>
                <input type="text"
                       name="region"
                       value="{{ old('region', $config->gpu_config['region'] ?? '') }}"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="เช่น us-east-1, eu-west-1">
                <p class="text-white/50 text-xs mt-1">ระบุ region ที่ต้องการใช้งาน</p>
            </div>

            {{-- GPU Type --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    GPU Type
                </label>
                <input type="text"
                       name="gpu_type"
                       value="{{ old('gpu_type', $config->gpu_config['gpu_type'] ?? '') }}"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="เช่น RTX 4090, A100, T4">
                <p class="text-white/50 text-xs mt-1">ระบุประเภท GPU ที่ต้องการ</p>
            </div>

            {{-- Max Instances --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Max GPU Instances
                </label>
                <input type="number"
                       name="max_instances"
                       value="{{ old('max_instances', $config->gpu_config['max_instances'] ?? 1) }}"
                       min="1"
                       max="10"
                       class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                       placeholder="1">
                <p class="text-white/50 text-xs mt-1">จำนวน GPU สูงสุดที่สามารถรันพร้อมกัน</p>
            </div>

            {{-- Auto Shutdown --}}
            <div>
                <label class="block text-white font-semibold mb-2">
                    Auto Shutdown
                </label>
                <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                    <input type="checkbox"
                           name="auto_shutdown"
                           value="1"
                           {{ old('auto_shutdown', $config->gpu_config['auto_shutdown'] ?? false) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-white/30 bg-white/10 text-cyan-500 focus:ring-2 focus:ring-cyan-400">
                    <span class="text-white">เปิดใช้งาน Auto Shutdown</span>
                </label>
                <p class="text-white/50 text-xs mt-1">ปิด instance อัตโนมัติเมื่อไม่ใช้งาน</p>
            </div>
        </div>
    </div>

    {{-- Settings --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-cog text-green-400"></i>
            การตั้งค่า
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Active --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="is_active"
                       value="1"
                       {{ old('is_active', $config->is_active ?? true) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-green-500 focus:ring-2 focus:ring-green-400">
                <div>
                    <div class="text-white font-semibold">เปิดใช้งาน</div>
                    <div class="text-white/60 text-sm">Configuration นี้พร้อมใช้งาน</div>
                </div>
            </label>

            {{-- Default --}}
            <label class="flex items-center gap-3 p-4 glass-neu rounded-xl cursor-pointer hover:bg-white/10 transition">
                <input type="checkbox"
                       name="is_default"
                       value="1"
                       {{ old('is_default', $config->is_default ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-white/30 bg-white/10 text-yellow-500 focus:ring-2 focus:ring-yellow-400">
                <div>
                    <div class="text-white font-semibold">ตั้งเป็น Default</div>
                    <div class="text-white/60 text-sm">ใช้เป็น configuration หลัก</div>
                </div>
            </label>
        </div>
    </div>

    {{-- Help Text --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30 bg-blue-500/5">
        <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
            <i class="fas fa-lightbulb text-yellow-400"></i>
            💡 คำแนะนำ
        </h3>
        <div class="text-white/70 space-y-2 text-sm">
            <p>
                <strong class="text-white">API Key:</strong> หา API Key ได้จากหน้า Account Settings ของแต่ละ Cloud Provider
            </p>
            <p>
                <strong class="text-white">Region:</strong> เลือก region ที่ใกล้กับคุณหรือ users เพื่อความเร็วในการเชื่อมต่อ
            </p>
            <p>
                <strong class="text-white">GPU Type:</strong> เลือกตามความต้องการของ model ที่จะรัน (ดูจาก Trending Models)
            </p>
            <p>
                <strong class="text-white">Auto Shutdown:</strong> ช่วยประหยัดค่าใช้จ่ายโดยปิด GPU เมื่อไม่ใช้งาน
            </p>
        </div>
    </div>
</div>
