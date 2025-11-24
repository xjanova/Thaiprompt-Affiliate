@extends('layouts.app')

@section('title', $service->name)

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places&callback=initMap" async defer></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="bookingForm()">
    {{-- Back Button --}}
    <div class="mb-6">
        <a href="{{ route('user.services.index') }}"
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปหน้าบริการ</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Left Column - Service Details --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Service Image & Info --}}
            <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
                {{-- Image --}}
                <div class="relative h-64 md:h-96 overflow-hidden bg-gradient-to-br from-purple-400/20 to-pink-400/20">
                    @if($service->image_path)
                        <img src="{{ asset('storage/' . $service->image_path) }}"
                             alt="{{ $service->name }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-9xl">
                            {{ $service->category->icon ?? '🔧' }}
                        </div>
                    @endif

                    {{-- Category Badge --}}
                    <div class="absolute top-4 left-4">
                        <span class="inline-flex items-center gap-2 px-4 py-2 backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-full font-semibold"
                              style="color: {{ $service->category->color }};">
                            <span class="text-xl">{{ $service->category->icon }}</span>
                            <span>{{ $service->category->name }}</span>
                        </span>
                    </div>
                </div>

                {{-- Details --}}
                <div class="p-6 md:p-8">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        {{ $service->name }}
                    </h1>

                    @if($service->description)
                        <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                            {{ $service->description }}
                        </p>
                    @endif

                    {{-- Service Meta --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <i class="fas fa-clock text-purple-600 dark:text-purple-400 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ระยะเวลา</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $service->estimated_duration_minutes ?? 60 }} นาที</p>
                        </div>

                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <i class="fas fa-tag text-purple-600 dark:text-purple-400 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ราคาเริ่มต้น</p>
                            <p class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($service->base_price, 2) }}</p>
                        </div>

                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <i class="fas fa-star text-yellow-500 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">คะแนน</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ number_format($service->average_rating ?? 0, 1) }} / 5</p>
                        </div>

                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <i class="fas fa-comment text-purple-600 dark:text-purple-400 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">รีวิว</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $service->reviews_count ?? 0 }} รีวิว</p>
                        </div>
                    </div>

                    {{-- Service Options --}}
                    @if($service->options && count($service->options) > 0)
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                <i class="fas fa-sliders-h mr-2 text-purple-600 dark:text-purple-400"></i>
                                ตัวเลือกเพิ่มเติม
                            </h3>
                            <div class="space-y-3">
                                @foreach($service->options as $option)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox"
                                                   x-model="form.selectedOptions"
                                                   value="{{ $option['key'] ?? $option['name'] }}"
                                                   @change="calculatePrice()"
                                                   class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $option['name'] }}</p>
                                                @if(isset($option['description']))
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $option['description'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="font-semibold text-purple-600 dark:text-purple-400">
                                            +฿{{ number_format($option['price'] ?? 0, 2) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Features --}}
                    @if($service->features && count($service->features) > 0)
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                <i class="fas fa-check-circle mr-2 text-green-600 dark:text-green-400"></i>
                                รายละเอียดบริการ
                            </h3>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($service->features as $feature)
                                    <li class="flex items-start gap-3 text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-check text-green-600 dark:text-green-400 mt-1"></i>
                                        <span>{{ is_array($feature) ? ($feature['name'] ?? $feature[0]) : $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column - Booking Form --}}
        <div class="lg:col-span-1">
            <div class="sticky top-4">
                <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-6 bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <h2 class="text-2xl font-bold mb-2">
                            <i class="fas fa-calendar-check mr-2"></i>
                            จองบริการนี้
                        </h2>
                        <p class="text-sm opacity-90">กรอกข้อมูลเพื่อจองบริการ</p>
                    </div>

                    <form action="{{ route('user.services.store-booking', $service) }}" method="POST" class="p-6 space-y-4">
                        @csrf

                        {{-- Scheduled Date & Time --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-clock mr-1"></i>
                                วันที่และเวลานัดหมาย <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local"
                                   name="scheduled_at"
                                   required
                                   :min="new Date(Date.now() + 3600000).toISOString().slice(0, 16)"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                            @error('scheduled_at')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Location (if required) --}}
                        @if($service->requires_location)
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    ที่อยู่บริการ <span class="text-red-500">*</span>
                                </label>

                                {{-- Get Current Location Button --}}
                                <button type="button"
                                        @click="getCurrentLocation()"
                                        :disabled="gettingLocation"
                                        class="w-full mb-3 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors disabled:opacity-50">
                                    <i :class="gettingLocation ? 'fas fa-spinner fa-spin' : 'fas fa-crosshairs'" class="mr-2"></i>
                                    <span x-text="gettingLocation ? 'กำลังหาตำแหน่ง...' : 'ใช้ตำแหน่งปัจจุบัน'"></span>
                                </button>

                                <input type="hidden" name="customer_latitude" x-model="form.latitude">
                                <input type="hidden" name="customer_longitude" x-model="form.longitude">

                                <textarea name="customer_address"
                                          required
                                          rows="3"
                                          x-model="form.address"
                                          class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white"
                                          placeholder="ที่อยู่เต็ม (บ้านเลขที่, ซอย, ถนน, แขวง/ตำบล, เขต/อำเภอ, จังหวัด, รหัสไปรษณีย์)"></textarea>
                                @error('customer_address')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Additional Location Details --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชั้น/ห้อง</label>
                                    <input type="text" name="floor_number" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลขที่ห้อง</label>
                                    <input type="text" name="room_number" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จุดสังเกต/ตึก</label>
                                <input type="text" name="landmark" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white"
                                       placeholder="เช่น ตึก A, ใกล้ 7-11">
                            </div>
                        @endif

                        {{-- Contact Info --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อผู้ติดต่อ</label>
                                <input type="text" name="contact_name" value="{{ auth()->user()->name }}"
                                       class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เบอร์โทร</label>
                                <input type="tel" name="contact_phone" value="{{ auth()->user()->phone }}"
                                       class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                            </div>
                        </div>

                        {{-- Customer Notes --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <i class="fas fa-comment mr-1"></i>
                                หมายเหตุเพิ่มเติม
                            </label>
                            <textarea name="customer_notes"
                                      rows="2"
                                      class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white"
                                      placeholder="ข้อมูลเพิ่มเติมสำหรับผู้ให้บริการ"></textarea>
                        </div>

                        {{-- Payment Method --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-credit-card mr-1"></i>
                                ช่องทางการชำระเงิน <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <input type="radio" name="payment_method" value="wallet" required
                                           class="w-4 h-4 text-purple-600 focus:ring-purple-500">
                                    <i class="fas fa-wallet text-purple-600 dark:text-purple-400"></i>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">กระเป๋าเงิน (ยอดคงเหลือ: ฿{{ number_format(auth()->user()->wallet_balance ?? 0, 2) }})</span>
                                </label>
                                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <input type="radio" name="payment_method" value="promptpay" required
                                           class="w-4 h-4 text-purple-600 focus:ring-purple-500">
                                    <i class="fas fa-qrcode text-blue-600 dark:text-blue-400"></i>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">PromptPay</span>
                                </label>
                                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <input type="radio" name="payment_method" value="card" required
                                           class="w-4 h-4 text-purple-600 focus:ring-purple-500">
                                    <i class="fas fa-credit-card text-green-600 dark:text-green-400"></i>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">บัตรเครดิต/เดบิต</span>
                                </label>
                            </div>
                        </div>

                        {{-- Price Summary --}}
                        <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                                    <span>ค่าบริการพื้นฐาน</span>
                                    <span>฿<span x-text="({{ $service->base_price }}).toFixed(2)"></span></span>
                                </div>
                                <div class="flex justify-between text-gray-700 dark:text-gray-300" x-show="form.optionsPrice > 0">
                                    <span>ค่าตัวเลือกเพิ่มเติม</span>
                                    <span>฿<span x-text="form.optionsPrice.toFixed(2)"></span></span>
                                </div>
                                <div class="flex justify-between text-gray-700 dark:text-gray-300" x-show="form.distancePrice > 0">
                                    <span>ค่าระยะทาง (<span x-text="form.distanceKm.toFixed(1)"></span> km)</span>
                                    <span>฿<span x-text="form.distancePrice.toFixed(2)"></span></span>
                                </div>
                                <div class="pt-2 border-t border-purple-300 dark:border-purple-700 flex justify-between text-lg font-bold text-purple-600 dark:text-purple-400">
                                    <span>รวมทั้งหมด</span>
                                    <span>฿<span x-text="form.totalPrice.toFixed(2)"></span></span>
                                </div>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit"
                                :disabled="!canSubmit()"
                                class="w-full py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-check-circle mr-2"></i>
                            ยืนยันการจอง
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let map, marker, geocoder;

function initMap() {
    // Initialize Google Maps (will be called by API callback)
}

function bookingForm() {
    return {
        form: {
            selectedOptions: [],
            latitude: null,
            longitude: null,
            address: '',
            distanceKm: 0,
            distancePrice: 0,
            optionsPrice: 0,
            totalPrice: {{ $service->base_price }}
        },
        gettingLocation: false,

        async getCurrentLocation() {
            this.gettingLocation = true;

            if (!navigator.geolocation) {
                alert('เบราว์เซอร์ของคุณไม่รองรับการหาตำแหน่ง');
                this.gettingLocation = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    this.form.latitude = position.coords.latitude;
                    this.form.longitude = position.coords.longitude;

                    // Reverse geocode to get address
                    await this.reverseGeocode();

                    // Calculate distance and price
                    await this.calculatePrice();

                    this.gettingLocation = false;
                },
                (error) => {
                    alert('ไม่สามารถหาตำแหน่งได้: ' + error.message);
                    this.gettingLocation = false;
                }
            );
        },

        async reverseGeocode() {
            if (!geocoder) {
                geocoder = new google.maps.Geocoder();
            }

            const latlng = { lat: this.form.latitude, lng: this.form.longitude };

            try {
                const response = await geocoder.geocode({ location: latlng });
                if (response.results[0]) {
                    this.form.address = response.results[0].formatted_address;
                }
            } catch (error) {
                console.error('Geocoding error:', error);
            }
        },

        async calculatePrice() {
            // Calculate options price
            this.form.optionsPrice = 0;
            const serviceOptions = @json($service->options ?? []);

            this.form.selectedOptions.forEach(optionKey => {
                const option = serviceOptions.find(opt => (opt.key || opt.name) === optionKey);
                if (option && option.price) {
                    this.form.optionsPrice += parseFloat(option.price);
                }
            });

            // Calculate distance-based price (if location is set)
            if (this.form.latitude && this.form.longitude) {
                try {
                    const response = await fetch('{{ route('user.services.calculate-price', $service) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            latitude: this.form.latitude,
                            longitude: this.form.longitude,
                            selected_options: this.form.selectedOptions
                        })
                    });

                    const data = await response.json();
                    if (data.success && data.pricing) {
                        this.form.distanceKm = data.pricing.distance_km || 0;
                        this.form.distancePrice = data.pricing.distance_price || 0;
                        this.form.totalPrice = data.pricing.total_price || {{ $service->base_price }};
                    }
                } catch (error) {
                    console.error('Price calculation error:', error);
                }
            } else {
                // If no location, just calculate base + options
                this.form.totalPrice = {{ $service->base_price }} + this.form.optionsPrice;
            }
        },

        canSubmit() {
            @if($service->requires_location)
                return this.form.latitude && this.form.longitude && this.form.address.length > 10;
            @else
                return true;
            @endif
        }
    }
}
</script>
@endpush
