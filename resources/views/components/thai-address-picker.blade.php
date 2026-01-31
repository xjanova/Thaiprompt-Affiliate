{{--
    Thai Address Picker Component
    ระบบเลือกที่อยู่ไทยอัจฉริยะแบบ Cascading

    การใช้งาน:
    <x-thai-address-picker
        province-field="province"
        district-field="district"
        sub-district-field="sub_district"
        postal-code-field="postal_code"
        :province-value="old('province', $address->province ?? '')"
        :district-value="old('district', $address->district ?? '')"
        :sub-district-value="old('sub_district', $address->sub_district ?? '')"
        :postal-code-value="old('postal_code', $address->postal_code ?? '')"
    />

    Props:
    - province-field: ชื่อ field สำหรับจังหวัด (default: province)
    - district-field: ชื่อ field สำหรับอำเภอ (default: district)
    - sub-district-field: ชื่อ field สำหรับตำบล (default: sub_district)
    - postal-code-field: ชื่อ field สำหรับรหัสไปรษณีย์ (default: postal_code)
    - *-value: ค่าเริ่มต้นของแต่ละ field (สำหรับ edit form)
    - show-sub-district: แสดง/ซ่อน ตำบล (default: true) - ถ้า false จะเติมรหัสไปรษณีย์จากอำเภอ
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
     })"
     x-init="init()"
     class="grid grid-cols-1 md:grid-cols-2 gap-4">

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
                    :class="{'border-red-500': errors[provinceField]}"
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

    {{-- ตำบล/แขวง (แสดงเฉพาะเมื่อ showSubDistrict = true) --}}
    <div x-show="showSubDistrict">
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
                    :required="showSubDistrict">
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

    {{-- รหัสไปรษณีย์ (อัตโนมัติ) --}}
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
                <template x-if="!postalCode">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </template>
            </div>
        </div>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            รหัสไปรษณีย์จะเติมอัตโนมัติเมื่อเลือกตำบล
        </p>
    </div>
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
         * เริ่มต้น component - โหลดจังหวัดและ restore ค่าเดิมถ้ามี
         */
        async init() {
            await this.loadProvinces();

            // ถ้ามีค่าเริ่มต้น (edit mode) ให้โหลด cascading ตามลำดับ
            if (this.selectedProvince) {
                await this.loadDistrictsForProvince(this.selectedProvince);

                if (this.selectedDistrict && this.showSubDistrict) {
                    await this.loadSubDistrictsForDistrict(this.selectedDistrict);
                }
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
            // รีเซ็ตค่าที่ขึ้นอยู่กับจังหวัด
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
         *
         * @param {string} provinceName ชื่อจังหวัดภาษาไทย
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
            // รีเซ็ตค่าที่ขึ้นอยู่กับอำเภอ
            this.selectedSubDistrict = '';
            this.postalCode = '';
            this.subDistricts = [];

            if (this.selectedDistrict) {
                if (this.showSubDistrict) {
                    // โหมดปกติ: โหลดตำบลเพื่อให้เลือก
                    await this.loadSubDistrictsForDistrict(this.selectedDistrict);
                } else {
                    // โหมดไม่มีตำบล: เติมรหัสไปรษณีย์จากอำเภอโดยตรง
                    const district = this.districts.find(d => d.districtNameTh === this.selectedDistrict);
                    if (district && district.postalCode) {
                        this.postalCode = String(district.postalCode);
                    }
                }
            }
        },

        /**
         * โหลดตำบล/แขวง จากชื่ออำเภอ
         *
         * @param {string} districtName ชื่ออำเภอภาษาไทย
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
