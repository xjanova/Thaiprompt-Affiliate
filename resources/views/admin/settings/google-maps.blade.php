{{--
/**
 * Google Maps Settings V3 - การตั้งค่า Google Maps API
 *
 * @uses layouts/admin-v3.blade.php
 * @data $settings - การตั้งค่า Google Maps ทั้งหมด
 *
 * @version 3.0 - Tailwind CSS + Alpine.js
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'การตั้งค่า Google Maps')
@section('page-title', 'Google Maps Settings')

@section('content')
<div class="space-y-6" x-data="googleMapsSettings()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                <i class="fas fa-map-marked-alt mr-2"></i>
                การตั้งค่า Google Maps API
            </h1>
            <p class="text-white/70 text-sm mt-1">
                จัดการ API Key และฟีเจอร์ต่างๆ สำหรับระบบ Delivery และ การคำนวณระยะทาง
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.settings.google-maps.guide') }}"
               target="_blank"
               class="btn-secondary">
                <i class="fas fa-book mr-2"></i>
                คู่มือการตั้งค่า
            </a>
            <button @click="testConnection"
                    class="btn-accent"
                    :disabled="testing">
                <i class="fas fa-flask mr-2"></i>
                <span x-text="testing ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ'"></span>
            </button>
            <button @click="saveSettings"
                    class="btn-primary"
                    :disabled="saving">
                <i class="fas fa-save mr-2"></i>
                <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า'"></span>
            </button>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
        {{-- Tabs --}}
        <div class="flex border-b border-white/20 overflow-x-auto">
            <template x-for="tab in tabs" :key="tab.id">
                <button
                    @click="currentTab = tab.id"
                    :class="{
                        'bg-white/20 border-b-2 border-blue-400': currentTab === tab.id,
                        'hover:bg-white/10': currentTab !== tab.id
                    }"
                    class="px-6 py-4 text-white font-medium transition whitespace-nowrap flex items-center gap-2"
                >
                    <i :class="tab.icon"></i>
                    <span x-text="tab.label"></span>
                </button>
            </template>
        </div>

        <div class="p-6">
            {{-- Tab 1: API Configuration --}}
            <div x-show="currentTab === 'api'" x-transition class="space-y-6">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-key mr-2"></i>
                    การตั้งค่า API
                </h3>

                {{-- API Key --}}
                <div>
                    <label class="block text-white/90 font-medium mb-2">
                        <i class="fas fa-key mr-1"></i>
                        Google Maps API Key
                        <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="text"
                        x-model="form.google_maps_api_key"
                        class="input-glass w-full font-mono text-sm"
                        placeholder="AIzaSy..."
                    >
                    <p class="text-white/50 text-xs mt-1">
                        ⚠️ API Key จะถูกเก็บใน database และ .env file
                    </p>
                </div>

                {{-- Enable/Disable Features --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <span class="text-white font-medium">เปิดใช้งาน Google Maps</span>
                            <p class="text-white/50 text-xs">เปิด/ปิดระบบทั้งหมด</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.google_maps_enabled" class="sr-only peer">
                            <div class="toggle-switch"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <span class="text-white font-medium">Geocoding</span>
                            <p class="text-white/50 text-xs">แปลงที่อยู่ ⇔ พิกัด</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.google_maps_geocoding_enabled" class="sr-only peer">
                            <div class="toggle-switch"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <span class="text-white font-medium">Directions</span>
                            <p class="text-white/50 text-xs">คำนวณเส้นทาง</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.google_maps_directions_enabled" class="sr-only peer">
                            <div class="toggle-switch"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <span class="text-white font-medium">Distance Matrix</span>
                            <p class="text-white/50 text-xs">คำนวณระยะทางหลายจุด</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.google_maps_distance_matrix_enabled" class="sr-only peer">
                            <div class="toggle-switch"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <span class="text-white font-medium">Places API</span>
                            <p class="text-white/50 text-xs">ค้นหาสถานที่</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.google_maps_places_enabled" class="sr-only peer">
                            <div class="toggle-switch"></div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Tab 2: Delivery Settings --}}
            <div x-show="currentTab === 'delivery'" x-transition class="space-y-6">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-shipping-fast mr-2"></i>
                    การตั้งค่าระบบ Delivery
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-white/90 font-medium mb-2">
                            <i class="fas fa-ruler mr-1"></i>
                            ระยะทางสูงสุด (กม.)
                        </label>
                        <input
                            type="number"
                            x-model="form.google_maps_delivery_max_distance"
                            class="input-glass w-full"
                            min="1"
                            max="500"
                        >
                    </div>

                    <div>
                        <label class="block text-white/90 font-medium mb-2">
                            <i class="fas fa-money-bill-wave mr-1"></i>
                            ค่าบริการต่อ กม. (บาท)
                        </label>
                        <input
                            type="number"
                            x-model="form.google_maps_delivery_cost_per_km"
                            class="input-glass w-full"
                            min="0"
                            step="0.1"
                        >
                    </div>

                    <div>
                        <label class="block text-white/90 font-medium mb-2">
                            <i class="fas fa-hand-holding-usd mr-1"></i>
                            ค่าบริการพื้นฐาน (บาท)
                        </label>
                        <input
                            type="number"
                            x-model="form.google_maps_delivery_base_fee"
                            class="input-glass w-full"
                            min="0"
                            step="0.1"
                        >
                    </div>
                </div>

                {{-- Distance Calculator Tool --}}
                <div class="bg-gradient-to-br from-blue-500/20 to-purple-500/20 rounded-xl p-6 border border-white/20">
                    <h4 class="text-lg font-bold text-white mb-4">
                        <i class="fas fa-calculator mr-2"></i>
                        เครื่องมือคำนวณระยะทาง
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-white/80 text-sm mb-2">จุดเริ่มต้น (Lat, Lng)</label>
                            <div class="flex gap-2">
                                <input type="number" x-model="calculator.origin_lat" class="input-glass w-full" placeholder="13.7563" step="any">
                                <input type="number" x-model="calculator.origin_lng" class="input-glass w-full" placeholder="100.5018" step="any">
                            </div>
                        </div>
                        <div>
                            <label class="block text-white/80 text-sm mb-2">จุดหมาย (Lat, Lng)</label>
                            <div class="flex gap-2">
                                <input type="number" x-model="calculator.dest_lat" class="input-glass w-full" placeholder="13.7308" step="any">
                                <input type="number" x-model="calculator.dest_lng" class="input-glass w-full" placeholder="100.5418" step="any">
                            </div>
                        </div>
                    </div>

                    <button @click="calculateDistance" class="btn-primary w-full mb-4" :disabled="calculating">
                        <i class="fas fa-calculator mr-2"></i>
                        <span x-text="calculating ? 'กำลังคำนวณ...' : 'คำนวณระยะทาง'"></span>
                    </button>

                    <div x-show="calculatorResult" x-transition class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-white/60 text-xs mb-1">ระยะทาง</div>
                            <div class="text-white font-bold" x-text="calculatorResult?.distance?.text || '-'"></div>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-white/60 text-xs mb-1">เวลา</div>
                            <div class="text-white font-bold" x-text="calculatorResult?.duration?.text || '-'"></div>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-white/60 text-xs mb-1">ค่าบริการ</div>
                            <div class="text-white font-bold" x-text="calculatedCost"></div>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-white/60 text-xs mb-1">ราคารวม</div>
                            <div class="text-white font-bold" x-text="totalCost"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Cache & Performance --}}
            <div x-show="currentTab === 'cache'" x-transition class="space-y-6">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Cache & Performance
                </h3>

                <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                    <div>
                        <span class="text-white font-medium">เปิดใช้งาน Cache</span>
                        <p class="text-white/50 text-xs">เก็บผลลัพธ์ API เพื่อประหยัด Quota</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.google_maps_cache_enabled" class="sr-only peer">
                        <div class="toggle-switch"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-white/90 font-medium mb-2">
                        <i class="fas fa-clock mr-1"></i>
                        Cache TTL (วินาที)
                    </label>
                    <input
                        type="number"
                        x-model="form.google_maps_cache_ttl"
                        class="input-glass w-full"
                        min="0"
                        max="604800"
                    >
                    <p class="text-white/50 text-xs mt-1">
                        แนะนำ: 86400 (24 ชั่วโมง) | สูงสุด: 604800 (7 วัน)
                    </p>
                </div>

                <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                    <div>
                        <span class="text-white font-medium">Rate Limiting</span>
                        <p class="text-white/50 text-xs">จำกัดจำนวนคำขอต่อนาที</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.google_maps_rate_limit_enabled" class="sr-only peer">
                        <div class="toggle-switch"></div>
                    </label>
                </div>

                <div x-show="form.google_maps_rate_limit_enabled">
                    <label class="block text-white/90 font-medium mb-2">
                        <i class="fas fa-stopwatch mr-1"></i>
                        จำนวนคำขอต่อนาที
                    </label>
                    <input
                        type="number"
                        x-model="form.google_maps_rate_limit_per_minute"
                        class="input-glass w-full"
                        min="1"
                        max="1000"
                    >
                </div>
            </div>

            {{-- Tab 4: Advanced Settings --}}
            <div x-show="currentTab === 'advanced'" x-transition class="space-y-6">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-cog mr-2"></i>
                    การตั้งค่าขั้นสูง
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white/90 font-medium mb-2">โหมดการเดินทางเริ่มต้น</label>
                        <select x-model="form.google_maps_default_mode" class="input-glass w-full">
                            <option value="driving">🚗 ขับรถ (Driving)</option>
                            <option value="walking">🚶 เดิน (Walking)</option>
                            <option value="bicycling">🚴 ปั่นจักรยาน (Bicycling)</option>
                            <option value="transit">🚌 ขนส่งสาธารณะ (Transit)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-white/90 font-medium mb-2">ภาษา</label>
                        <select x-model="form.google_maps_geocoding_language" class="input-glass w-full">
                            <option value="th">🇹🇭 ไทย (Thai)</option>
                            <option value="en">🇺🇸 อังกฤษ (English)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-white/90 font-medium mb-2">ภูมิภาค</label>
                        <select x-model="form.google_maps_geocoding_region" class="input-glass w-full">
                            <option value="TH">🇹🇭 ประเทศไทย (TH)</option>
                            <option value="US">🇺🇸 สหรัฐอเมริกา (US)</option>
                            <option value="GB">🇬🇧 สหราชอาณาจักร (GB)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-white/90 font-medium mb-2">หน่วยระยะทาง</label>
                        <select x-model="form.google_maps_distance_unit" class="input-glass w-full">
                            <option value="metric">กิโลเมตร (Metric)</option>
                            <option value="imperial">ไมล์ (Imperial)</option>
                        </select>
                    </div>
                </div>

                <div class="bg-yellow-500/20 border border-yellow-500/50 rounded-lg p-4">
                    <h4 class="text-yellow-300 font-bold mb-2">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        การหลีกเลี่ยงเส้นทาง
                    </h4>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-white/80">
                            <input type="checkbox" x-model="form.google_maps_avoid_tolls" class="rounded">
                            <span>หลีกเลี่ยงทางด่วน (Tolls)</span>
                        </label>
                        <label class="flex items-center gap-2 text-white/80">
                            <input type="checkbox" x-model="form.google_maps_avoid_highways" class="rounded">
                            <span>หลีกเลี่ยงทางหลวง (Highways)</span>
                        </label>
                        <label class="flex items-center gap-2 text-white/80">
                            <input type="checkbox" x-model="form.google_maps_avoid_ferries" class="rounded">
                            <span>หลีกเลี่ยงเรือข้ามฟาก (Ferries)</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function googleMapsSettings() {
    return {
        currentTab: 'api',
        saving: false,
        testing: false,
        calculating: false,
        calculatorResult: null,

        tabs: [
            { id: 'api', label: 'API Configuration', icon: 'fas fa-key' },
            { id: 'delivery', label: 'Delivery Settings', icon: 'fas fa-shipping-fast' },
            { id: 'cache', label: 'Cache & Performance', icon: 'fas fa-tachometer-alt' },
            { id: 'advanced', label: 'Advanced', icon: 'fas fa-cog' },
        ],

        form: {
            google_maps_api_key: '{{ $settings["google_maps_api_key"]->value ?? "" }}',
            google_maps_enabled: {{ ($settings["google_maps_enabled"]->value ?? 1) ? 'true' : 'false' }},
            google_maps_geocoding_enabled: {{ ($settings["google_maps_geocoding_enabled"]->value ?? 1) ? 'true' : 'false' }},
            google_maps_directions_enabled: {{ ($settings["google_maps_directions_enabled"]->value ?? 1) ? 'true' : 'false' }},
            google_maps_distance_matrix_enabled: {{ ($settings["google_maps_distance_matrix_enabled"]->value ?? 1) ? 'true' : 'false' }},
            google_maps_places_enabled: {{ ($settings["google_maps_places_enabled"]->value ?? 1) ? 'true' : 'false' }},
            google_maps_cache_enabled: {{ ($settings["google_maps_cache_enabled"]->value ?? 1) ? 'true' : 'false' }},
            google_maps_cache_ttl: {{ $settings["google_maps_cache_ttl"]->value ?? 86400 }},
            google_maps_geocoding_language: '{{ $settings["google_maps_geocoding_language"]->value ?? "th" }}',
            google_maps_geocoding_region: '{{ $settings["google_maps_geocoding_region"]->value ?? "TH" }}',
            google_maps_default_mode: '{{ $settings["google_maps_default_mode"]->value ?? "driving" }}',
            google_maps_avoid_tolls: {{ ($settings["google_maps_avoid_tolls"]->value ?? 0) ? 'true' : 'false' }},
            google_maps_avoid_highways: {{ ($settings["google_maps_avoid_highways"]->value ?? 0) ? 'true' : 'false' }},
            google_maps_avoid_ferries: {{ ($settings["google_maps_avoid_ferries"]->value ?? 0) ? 'true' : 'false' }},
            google_maps_distance_unit: '{{ $settings["google_maps_distance_unit"]->value ?? "metric" }}',
            google_maps_delivery_max_distance: {{ $settings["google_maps_delivery_max_distance"]->value ?? 50 }},
            google_maps_delivery_cost_per_km: {{ $settings["google_maps_delivery_cost_per_km"]->value ?? 10 }},
            google_maps_delivery_base_fee: {{ $settings["google_maps_delivery_base_fee"]->value ?? 30 }},
            google_maps_rate_limit_enabled: {{ ($settings["google_maps_rate_limit_enabled"]->value ?? 1) ? 'true' : 'false' }},
            google_maps_rate_limit_per_minute: {{ $settings["google_maps_rate_limit_per_minute"]->value ?? 60 }},
        },

        calculator: {
            origin_lat: 13.7563,
            origin_lng: 100.5018,
            dest_lat: 13.7308,
            dest_lng: 100.5418,
        },

        get calculatedCost() {
            if (!this.calculatorResult?.distance?.value) return '-';
            const km = this.calculatorResult.distance.value / 1000;
            const cost = km * parseFloat(this.form.google_maps_delivery_cost_per_km);
            return `${cost.toFixed(2)} ฿`;
        },

        get totalCost() {
            if (!this.calculatorResult?.distance?.value) return '-';
            const km = this.calculatorResult.distance.value / 1000;
            const cost = km * parseFloat(this.form.google_maps_delivery_cost_per_km);
            const total = cost + parseFloat(this.form.google_maps_delivery_base_fee);
            return `${total.toFixed(2)} ฿`;
        },

        async saveSettings() {
            this.saving = true;

            try {
                const response = await fetch('{{ route("admin.settings.google-maps.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message);
                } else {
                    this.showToast('error', data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Save error:', error);
                this.showToast('error', 'เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.saving = false;
            }
        },

        async testConnection() {
            this.testing = true;

            try {
                const response = await fetch('{{ route("admin.settings.google-maps.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message);
                    console.log('Test result:', data.data);
                } else {
                    this.showToast('error', data.message);
                }
            } catch (error) {
                console.error('Test error:', error);
                this.showToast('error', 'เกิดข้อผิดพลาดในการทดสอบ');
            } finally {
                this.testing = false;
            }
        },

        async calculateDistance() {
            this.calculating = true;
            this.calculatorResult = null;

            try {
                const response = await fetch('{{ route("admin.settings.google-maps.calculate-distance") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.calculator),
                });

                const data = await response.json();

                if (data.success) {
                    this.calculatorResult = data.data;
                    this.showToast('success', data.message);
                } else {
                    this.showToast('error', data.message);
                }
            } catch (error) {
                console.error('Calculate error:', error);
                this.showToast('error', 'เกิดข้อผิดพลาดในการคำนวณ');
            } finally {
                this.calculating = false;
            }
        },

        showToast(type, message) {
            // ใช้ระบบ toast ของ admin-v3 layout
            if (window.showNotification) {
                window.showNotification(type, message);
            } else {
                alert(message);
            }
        },
    };
}
</script>
@endpush
@endsection
