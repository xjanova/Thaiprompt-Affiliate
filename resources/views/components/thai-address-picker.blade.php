{{--
    Thai Address Picker Component
    ระบบเลือกที่อยู่ไทยอัจฉริยะแบบ Cascading

    การใช้งาน (โหมดปกติ - ไทยเท่านั้น):
    <x-thai-address-picker
        province-field="province"
        district-field="district"
        sub-district-field="sub_district"
        postal-code-field="postal_code"
        :province-value="old('province', '')"
        :district-value="old('district', '')"
        :sub-district-value="old('sub_district', '')"
        :postal-code-value="old('postal_code', '')"
    />

    การใช้งาน (โหมดมีประเทศ - สลับอัตโนมัติ/กรอกเอง):
    <x-thai-address-picker
        province-field="state"
        district-field="city"
        postal-code-field="postal_code"
        country-field="country"
        :country-value="old('country', 'TH')"
        :province-value="old('state', '')"
        :district-value="old('city', '')"
        :postal-code-value="old('postal_code', '')"
        :show-sub-district="false"
    />

    Props:
    - province-field: ชื่อ field สำหรับจังหวัด (default: province)
    - district-field: ชื่อ field สำหรับอำเภอ (default: district)
    - sub-district-field: ชื่อ field สำหรับตำบล (default: sub_district)
    - postal-code-field: ชื่อ field สำหรับรหัสไปรษณีย์ (default: postal_code)
    - *-value: ค่าเริ่มต้นของแต่ละ field (สำหรับ edit form)
    - show-sub-district: แสดง/ซ่อน ตำบล (default: true)
    - country-field: ชื่อ field สำหรับประเทศ (ถ้าไม่ระบุ = ไทยเท่านั้น)
    - country-value: ค่าเริ่มต้นประเทศ (default: TH)
--}}

@props([
    'provinceField' => 'province',
    'districtField' => 'district',
    'subDistrictField' => 'sub_district',
    'postalCodeField' => 'postal_code',
    'provinceValue' => '',
    'districtValue' => '',
    'subDistrictValue' => '',
    'postalCodeValue' => '',
    'showSubDistrict' => true,
    'countryField' => '',
    'countryValue' => 'TH',
])

<div x-data="thaiAddressPicker({
        provinceField: '{{ $provinceField }}',
        districtField: '{{ $districtField }}',
        subDistrictField: '{{ $subDistrictField }}',
        postalCodeField: '{{ $postalCodeField }}',
        initialProvince: '{{ $provinceValue }}',
        initialDistrict: '{{ $districtValue }}',
        initialSubDistrict: '{{ $subDistrictValue }}',
        initialPostalCode: '{{ $postalCodeValue }}',
        showSubDistrict: {{ $showSubDistrict ? 'true' : 'false' }},
        countryField: '{{ $countryField }}',
        initialCountry: '{{ $countryValue }}',
     })"
     x-init="init()"
     class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- ประเทศ (แสดงเฉพาะเมื่อตั้ง countryField) --}}
    @if($countryField)
    <div class="md:col-span-2">
        <label :for="'tap-country-' + _uid"
               class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            <i class="fas fa-globe mr-1 text-blue-500"></i>
            ประเทศ <span class="text-red-500">*</span>
        </label>
        <select name="{{ $countryField }}"
                :id="'tap-country-' + _uid"
                x-model="selectedCountry"
                @change="onCountryChange()"
                class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                       bg-white dark:bg-gray-700
                       text-gray-900 dark:text-gray-100
                       rounded-xl appearance-none
                       focus:border-indigo-500 dark:focus:border-indigo-400
                       focus:ring-4 focus:ring-indigo-500/20
                       transition-all duration-200"
                required>
            <option value="TH">ประเทศไทย</option>
            <option value="US">United States</option>
            <option value="GB">United Kingdom</option>
            <option value="JP">Japan</option>
            <option value="CN">China</option>
            <option value="KR">South Korea</option>
            <option value="SG">Singapore</option>
            <option value="MY">Malaysia</option>
            <option value="LA">Laos</option>
            <option value="KH">Cambodia</option>
            <option value="MM">Myanmar</option>
            <option value="VN">Vietnam</option>
            <option value="OTHER">อื่นๆ</option>
        </select>
    </div>
    @endif

    {{-- === โหมดไทย: Cascading Dropdowns === --}}
    <template x-if="isThailand">
        {{-- จังหวัด --}}
        <div>
            <label :for="'tap-province-' + _uid"
                   class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                จังหวัด <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <select :name="provinceField"
                        :id="'tap-province-' + _uid"
                        x-model="selectedProvince"
                        @change="onProvinceChange()"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                               bg-white dark:bg-gray-700
                               text-gray-900 dark:text-gray-100
                               rounded-xl appearance-none
                               focus:border-indigo-500 dark:focus:border-indigo-400
                               focus:ring-4 focus:ring-indigo-500/20
                               transition-all duration-200"
                        required>
                    <option value="">-- เลือกจังหวัด --</option>
                    <template x-for="province in provinces" :key="province.provinceCode">
                        <option :value="province.provinceNameTh"
                                :data-code="province.provinceCode"
                                x-text="province.provinceNameTh"
                                :selected="province.provinceNameTh === selectedProvince">
                        </option>
                    </template>
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                    <template x-if="loadingProvinces">
                        <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <template x-if="!loadingProvinces">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <template x-if="isThailand">
        {{-- อำเภอ/เขต --}}
        <div>
            <label :for="'tap-district-' + _uid"
                   class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                อำเภอ/เขต <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <select :name="districtField"
                        :id="'tap-district-' + _uid"
                        x-model="selectedDistrict"
                        @change="onDistrictChange()"
                        :disabled="!selectedProvince || districts.length === 0"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                               bg-white dark:bg-gray-700
                               text-gray-900 dark:text-gray-100
                               rounded-xl appearance-none
                               focus:border-indigo-500 dark:focus:border-indigo-400
                               focus:ring-4 focus:ring-indigo-500/20
                               disabled:opacity-50 disabled:cursor-not-allowed
                               transition-all duration-200"
                        required>
                    <option value="">-- เลือกอำเภอ/เขต --</option>
                    <template x-for="district in districts" :key="district.districtCode">
                        <option :value="district.districtNameTh"
                                :data-code="district.districtCode"
                                x-text="district.districtNameTh"
                                :selected="district.districtNameTh === selectedDistrict">
                        </option>
                    </template>
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                    <template x-if="loadingDistricts">
                        <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <template x-if="!loadingDistricts">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </template>
                </div>
            </div>
        </div>
    </template>

    {{-- ตำบล/แขวง (แสดงเฉพาะเมื่อ showSubDistrict = true และเป็นไทย) --}}
    <template x-if="isThailand && showSubDistrict">
        <div>
            <label :for="'tap-subdistrict-' + _uid"
                   class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                ตำบล/แขวง <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <select :name="subDistrictField"
                        :id="'tap-subdistrict-' + _uid"
                        x-model="selectedSubDistrict"
                        @change="onSubDistrictChange()"
                        :disabled="!selectedDistrict || subDistricts.length === 0"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                               bg-white dark:bg-gray-700
                               text-gray-900 dark:text-gray-100
                               rounded-xl appearance-none
                               focus:border-indigo-500 dark:focus:border-indigo-400
                               focus:ring-4 focus:ring-indigo-500/20
                               disabled:opacity-50 disabled:cursor-not-allowed
                               transition-all duration-200"
                        required>
                    <option value="">-- เลือกตำบล/แขวง --</option>
                    <template x-for="sub in subDistricts" :key="sub.subdistrictCode">
                        <option :value="sub.subdistrictNameTh"
                                :data-postal="sub.postalCode"
                                x-text="sub.subdistrictNameTh"
                                :selected="sub.subdistrictNameTh === selectedSubDistrict">
                        </option>
                    </template>
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                    <template x-if="loadingSubDistricts">
                        <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <template x-if="!loadingSubDistricts">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </template>
                </div>
            </div>
        </div>
    </template>

    {{-- รหัสไปรษณีย์ (อัตโนมัติเมื่อเป็นไทย) --}}
    <template x-if="isThailand">
        <div>
            <label :for="'tap-postal-' + _uid"
                   class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                รหัสไปรษณีย์ <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input type="text"
                       :name="postalCodeField"
                       :id="'tap-postal-' + _uid"
                       x-model="postalCode"
                       readonly
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                              bg-gray-50 dark:bg-gray-600
                              text-gray-900 dark:text-gray-100
                              rounded-xl
                              focus:border-indigo-500 dark:focus:border-indigo-400
                              focus:ring-4 focus:ring-indigo-500/20
                              cursor-default
                              transition-all duration-200"
                       placeholder="เลือกตำบลเพื่อเติมอัตโนมัติ"
                       required>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                    <template x-if="postalCode">
                        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                </div>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                รหัสไปรษณีย์จะเติมอัตโนมัติเมื่อเลือก<span x-text="showSubDistrict ? 'ตำบล' : 'อำเภอ'"></span>
            </p>
        </div>
    </template>

    {{-- === โหมดต่างประเทศ: กรอกเอง === --}}
    <template x-if="!isThailand">
        <div>
            <label :for="'tap-province-manual-' + _uid"
                   class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                จังหวัด/รัฐ <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   :name="provinceField"
                   :id="'tap-province-manual-' + _uid"
                   x-model="selectedProvince"
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                          bg-white dark:bg-gray-700
                          text-gray-900 dark:text-gray-100
                          rounded-xl
                          focus:border-indigo-500 dark:focus:border-indigo-400
                          focus:ring-4 focus:ring-indigo-500/20
                          transition-all duration-200"
                   placeholder="State / Province"
                   required>
        </div>
    </template>

    <template x-if="!isThailand">
        <div>
            <label :for="'tap-district-manual-' + _uid"
                   class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                เมือง/อำเภอ <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   :name="districtField"
                   :id="'tap-district-manual-' + _uid"
                   x-model="selectedDistrict"
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                          bg-white dark:bg-gray-700
                          text-gray-900 dark:text-gray-100
                          rounded-xl
                          focus:border-indigo-500 dark:focus:border-indigo-400
                          focus:ring-4 focus:ring-indigo-500/20
                          transition-all duration-200"
                   placeholder="City / District"
                   required>
        </div>
    </template>

    <template x-if="!isThailand && showSubDistrict">
        <div>
            <label :for="'tap-subdistrict-manual-' + _uid"
                   class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                ตำบล/แขวง
            </label>
            <input type="text"
                   :name="subDistrictField"
                   :id="'tap-subdistrict-manual-' + _uid"
                   x-model="selectedSubDistrict"
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                          bg-white dark:bg-gray-700
                          text-gray-900 dark:text-gray-100
                          rounded-xl
                          focus:border-indigo-500 dark:focus:border-indigo-400
                          focus:ring-4 focus:ring-indigo-500/20
                          transition-all duration-200"
                   placeholder="Sub-district">
        </div>
    </template>

    <template x-if="!isThailand">
        <div>
            <label :for="'tap-postal-manual-' + _uid"
                   class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                รหัสไปรษณีย์ <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   :name="postalCodeField"
                   :id="'tap-postal-manual-' + _uid"
                   x-model="postalCode"
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600
                          bg-white dark:bg-gray-700
                          text-gray-900 dark:text-gray-100
                          rounded-xl
                          focus:border-indigo-500 dark:focus:border-indigo-400
                          focus:ring-4 focus:ring-indigo-500/20
                          transition-all duration-200"
                   placeholder="Postal / Zip Code"
                   required>
        </div>
    </template>
</div>

{{-- Alpine.js Component Logic (โหลดครั้งเดียว) --}}
@once
@push('scripts')
<script>
/**
 * Thai Address Picker - Alpine.js Component
 *
 * ระบบเลือกที่อยู่ไทยอัจฉริยะแบบ Cascading
 * จังหวัด → อำเภอ → ตำบล → รหัสไปรษณีย์ (อัตโนมัติ)
 * รองรับการสลับเป็นโหมดกรอกเองเมื่อเลือกประเทศอื่น
 */
function thaiAddressPicker(config = {}) {
    return {
        // ชื่อ field ที่ใช้ใน form
        provinceField: config.provinceField || 'province',
        districtField: config.districtField || 'district',
        subDistrictField: config.subDistrictField || 'sub_district',
        postalCodeField: config.postalCodeField || 'postal_code',

        // แสดง/ซ่อน ตำบล (ถ้า false จะเติมรหัสไปรษณีย์จากอำเภอแทน)
        showSubDistrict: config.showSubDistrict !== undefined ? config.showSubDistrict : true,

        // ประเทศ
        countryField: config.countryField || '',
        selectedCountry: config.initialCountry || 'TH',
        isThailand: true,

        // ค่าที่เลือก
        selectedProvince: config.initialProvince || '',
        selectedDistrict: config.initialDistrict || '',
        selectedSubDistrict: config.initialSubDistrict || '',
        postalCode: config.initialPostalCode || '',

        // ข้อมูลตัวเลือก
        provinces: [],
        districts: [],
        subDistricts: [],

        // สถานะโหลด
        loadingProvinces: false,
        loadingDistricts: false,
        loadingSubDistricts: false,

        // ข้อผิดพลาด
        errors: {},

        // Unique ID สำหรับ labels
        _uid: Math.random().toString(36).substring(2, 8),

        /**
         * เริ่มต้น component - ตรวจสอบประเทศและโหลดข้อมูลที่เหมาะสม
         */
        async init() {
            // ตรวจสอบว่าเป็นประเทศไทยหรือไม่
            this.isThailand = this.checkIsThailand(this.selectedCountry);

            if (this.isThailand) {
                await this.loadProvinces();

                // ถ้ามีค่าเริ่มต้น (edit mode) ให้โหลด cascading ตามลำดับ
                if (this.selectedProvince) {
                    await this.loadDistrictsForProvince(this.selectedProvince);

                    if (this.selectedDistrict && this.showSubDistrict) {
                        await this.loadSubDistrictsForDistrict(this.selectedDistrict);
                    }
                }
            }
        },

        /**
         * ตรวจสอบว่าเป็นประเทศไทยหรือไม่
         */
        checkIsThailand(country) {
            if (!country) return true;
            const thaiValues = ['TH', 'th', 'Thailand', 'thailand', 'ไทย', 'ประเทศไทย'];
            return thaiValues.includes(country);
        },

        /**
         * เมื่อเปลี่ยนประเทศ - สลับโหมด
         */
        async onCountryChange() {
            const wasThai = this.isThailand;
            this.isThailand = this.checkIsThailand(this.selectedCountry);

            if (this.isThailand && !wasThai) {
                // เปลี่ยนจากต่างประเทศเป็นไทย → รีเซ็ตและโหลดข้อมูลใหม่
                this.selectedProvince = '';
                this.selectedDistrict = '';
                this.selectedSubDistrict = '';
                this.postalCode = '';
                this.districts = [];
                this.subDistricts = [];

                if (this.provinces.length === 0) {
                    await this.loadProvinces();
                }
            } else if (!this.isThailand && wasThai) {
                // เปลี่ยนจากไทยเป็นต่างประเทศ → รีเซ็ตค่า
                this.selectedProvince = '';
                this.selectedDistrict = '';
                this.selectedSubDistrict = '';
                this.postalCode = '';
            }
        },

        /**
         * โหลดรายชื่อจังหวัดทั้งหมด
         */
        async loadProvinces() {
            this.loadingProvinces = true;
            try {
                const response = await fetch('/api/thai-addresses/provinces');
                const result = await response.json();
                if (result.success) {
                    this.provinces = result.data;
                }
            } catch (error) {
                console.error('โหลดข้อมูลจังหวัดล้มเหลว:', error);
            } finally {
                this.loadingProvinces = false;
            }
        },

        /**
         * เมื่อเลือกจังหวัด - โหลดอำเภอ/เขต
         */
        async onProvinceChange() {
            this.selectedDistrict = '';
            this.selectedSubDistrict = '';
            this.postalCode = '';
            this.districts = [];
            this.subDistricts = [];

            if (this.selectedProvince) {
                await this.loadDistrictsForProvince(this.selectedProvince);
            }
        },

        /**
         * โหลดอำเภอ/เขต จากชื่อจังหวัด
         */
        async loadDistrictsForProvince(provinceName) {
            const province = this.provinces.find(p => p.provinceNameTh === provinceName);
            if (!province) return;

            this.loadingDistricts = true;
            try {
                const response = await fetch(`/api/thai-addresses/districts/${province.provinceCode}`);
                const result = await response.json();
                if (result.success) {
                    this.districts = result.data;
                }
            } catch (error) {
                console.error('โหลดข้อมูลอำเภอล้มเหลว:', error);
            } finally {
                this.loadingDistricts = false;
            }
        },

        /**
         * เมื่อเลือกอำเภอ - โหลดตำบล/แขวง หรือเติมรหัสไปรษณีย์อัตโนมัติ
         */
        async onDistrictChange() {
            this.selectedSubDistrict = '';
            this.postalCode = '';
            this.subDistricts = [];

            if (this.selectedDistrict) {
                if (this.showSubDistrict) {
                    await this.loadSubDistrictsForDistrict(this.selectedDistrict);
                } else {
                    const district = this.districts.find(d => d.districtNameTh === this.selectedDistrict);
                    if (district && district.postalCode) {
                        this.postalCode = String(district.postalCode);
                    }
                }
            }
        },

        /**
         * โหลดตำบล/แขวง จากชื่ออำเภอ
         */
        async loadSubDistrictsForDistrict(districtName) {
            const district = this.districts.find(d => d.districtNameTh === districtName);
            if (!district) return;

            this.loadingSubDistricts = true;
            try {
                const response = await fetch(`/api/thai-addresses/sub-districts/${district.districtCode}`);
                const result = await response.json();
                if (result.success) {
                    this.subDistricts = result.data;
                }
            } catch (error) {
                console.error('โหลดข้อมูลตำบลล้มเหลว:', error);
            } finally {
                this.loadingSubDistricts = false;
            }
        },

        /**
         * เมื่อเลือกตำบล - เติมรหัสไปรษณีย์อัตโนมัติ
         */
        onSubDistrictChange() {
            if (this.selectedSubDistrict) {
                const sub = this.subDistricts.find(s => s.subdistrictNameTh === this.selectedSubDistrict);
                if (sub && sub.postalCode) {
                    this.postalCode = String(sub.postalCode);
                }
            } else {
                this.postalCode = '';
            }
        },
    };
}

// ลงทะเบียน component กับ window สำหรับ Alpine.js
window.thaiAddressPicker = thaiAddressPicker;
</script>
@endpush
@endonce
