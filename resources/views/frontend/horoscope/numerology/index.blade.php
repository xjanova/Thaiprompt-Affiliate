{{-- หน้าหลักเลขศาสตร์ — 5 ฟอร์มวิเคราะห์ --}}
@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div x-data="numerologyApp()" x-init="init()">

    {{-- ==================== Hero Section ==================== --}}
    <section class="relative py-12 md:py-16 overflow-hidden">
        {{-- ภาพประกอบยันต์เรขาคณิต (เจนเอง) --}}
        <x-art.backdrop image="cos-numerology" tone="dark" :opacity="0.5" mask="vignette" />
        <div class="container relative mx-auto px-4 text-center">
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-600/30 to-teal-600/30 border border-emerald-500/30 backdrop-blur-lg shadow-2xl shadow-emerald-500/20"
                     style="animation: float 4s ease-in-out infinite;">
                    <span class="text-5xl filter drop-shadow-[0_0_20px_rgba(16,185,129,0.6)]">🔢</span>
                </div>
            </div>

            <h1 class="text-3xl md:text-5xl font-black mb-3">
                <span class="shimmer-text">เลขศาสตร์</span>
            </h1>
            <p class="text-purple-200/70 text-lg mb-2">วิเคราะห์ตัวเลขดวงชะตาด้วย AI</p>
            <p class="text-purple-300/50 text-sm max-w-lg mx-auto">
                เลือกประเภทที่ต้องการวิเคราะห์ — ชื่อ เบอร์โทร ทะเบียนรถ วันเกิด
            </p>
        </div>
    </section>

    {{-- ==================== Tab เลือกประเภท ==================== --}}
    <section class="container mx-auto px-4 mb-8">
        <div class="flex justify-center">
            <div class="inline-flex flex-wrap justify-center bg-white/5 backdrop-blur-lg rounded-2xl p-1.5 border border-white/10 gap-1">
                <template x-for="tab in tabs" :key="tab.key">
                    <button @click="activeTab = tab.key; resetResult()"
                            :class="activeTab === tab.key
                                ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/30'
                                : 'text-purple-300/60 hover:text-white hover:bg-white/5'"
                            class="px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all duration-300 flex items-center gap-1.5">
                        <span x-text="tab.icon"></span>
                        <span x-text="tab.label" class="hidden sm:inline"></span>
                    </button>
                </template>
            </div>
        </div>
    </section>

    {{-- ==================== ฟอร์ม + ผลลัพธ์ ==================== --}}
    <section class="container mx-auto px-4 pb-12">
        <div class="max-w-2xl mx-auto">

            {{-- ฟอร์มวิเคราะห์ --}}
            <div class="bg-white/5 backdrop-blur-lg rounded-3xl p-6 md:p-8 border border-white/10 mb-6" x-show="!result">

                {{-- ชื่อ --}}
                <div x-show="activeTab === 'name'" x-transition>
                    <h2 class="text-white text-xl font-bold mb-1 flex items-center gap-2">📝 วิเคราะห์ชื่อ</h2>
                    <p class="text-purple-300/50 text-sm mb-5">ใส่ชื่อภาษาไทย จะแปลงพยัญชนะเป็นตัวเลขตามหลักเลขศาสตร์</p>
                    <input type="text" x-model="formData.name"
                           placeholder="เช่น สมชาย สุขใจ"
                           class="w-full px-5 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-purple-300/30 text-lg focus:outline-none focus:border-emerald-500/50 focus:ring-2 focus:ring-emerald-500/20 transition-all">
                </div>

                {{-- เบอร์โทร --}}
                <div x-show="activeTab === 'phone'" x-transition>
                    <h2 class="text-white text-xl font-bold mb-1 flex items-center gap-2">📱 วิเคราะห์เบอร์โทร</h2>
                    <p class="text-purple-300/50 text-sm mb-5">ใส่เบอร์โทรศัพท์ของคุณ</p>
                    <input type="tel" x-model="formData.phone"
                           placeholder="เช่น 081-234-5678"
                           maxlength="15"
                           class="w-full px-5 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-purple-300/30 text-lg focus:outline-none focus:border-emerald-500/50 focus:ring-2 focus:ring-emerald-500/20 transition-all tracking-wider">
                </div>

                {{-- ทะเบียนรถ --}}
                <div x-show="activeTab === 'license'" x-transition>
                    <h2 class="text-white text-xl font-bold mb-1 flex items-center gap-2">🚗 วิเคราะห์ทะเบียนรถ</h2>
                    <p class="text-purple-300/50 text-sm mb-5">ใส่ทะเบียนรถ (ตัวอักษร + ตัวเลข)</p>
                    <input type="text" x-model="formData.license_plate"
                           placeholder="เช่น กข 1234"
                           class="w-full px-5 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-purple-300/30 text-lg focus:outline-none focus:border-emerald-500/50 focus:ring-2 focus:ring-emerald-500/20 transition-all tracking-wider">
                </div>

                {{-- วันเกิด --}}
                <div x-show="activeTab === 'birthday'" x-transition>
                    <h2 class="text-white text-xl font-bold mb-1 flex items-center gap-2">🎂 วิเคราะห์วันเกิด</h2>
                    <p class="text-purple-300/50 text-sm mb-5">เลือกวันเดือนปีเกิดของคุณ</p>
                    <input type="date" x-model="formData.birthday"
                           max="{{ date('Y-m-d') }}"
                           class="w-full px-5 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-purple-300/30 text-lg focus:outline-none focus:border-emerald-500/50 focus:ring-2 focus:ring-emerald-500/20 transition-all">
                </div>

                {{-- บัตรประชาชน --}}
                <div x-show="activeTab === 'idcard'" x-transition>
                    <h2 class="text-white text-xl font-bold mb-1 flex items-center gap-2">🪪 วิเคราะห์เลขบัตรประชาชน</h2>
                    <p class="text-purple-300/50 text-sm mb-5">ใส่เลข 13 หลัก (ไม่เก็บข้อมูลจริง)</p>
                    <input type="text" x-model="formData.id_card"
                           placeholder="เลข 13 หลัก"
                           maxlength="13"
                           class="w-full px-5 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-purple-300/30 text-lg focus:outline-none focus:border-emerald-500/50 focus:ring-2 focus:ring-emerald-500/20 transition-all tracking-[0.3em]">
                    <p class="text-red-400/60 text-xs mt-2">🔒 ระบบไม่เก็บเลขบัตรจริง — จะเก็บเฉพาะผลวิเคราะห์</p>
                </div>

                {{-- คำถามเพิ่มเติม --}}
                <div class="mt-5">
                    <label class="text-purple-200/60 text-xs mb-2 block">คำถามเพิ่มเติม (ไม่บังคับ)</label>
                    <textarea x-model="formData.question"
                              rows="2"
                              maxlength="500"
                              placeholder="เช่น ต้องการรู้เรื่องการเงิน..."
                              class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-purple-300/30 text-sm focus:outline-none focus:border-emerald-500/50 resize-none transition-all"></textarea>
                </div>

                {{-- ปุ่มวิเคราะห์ --}}
                <div class="mt-6">
                    <button @click="analyze()"
                            :disabled="isLoading || !isFormValid()"
                            class="w-full py-4 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold rounded-2xl shadow-xl shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-[1.02] transition-all duration-300 text-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <template x-if="isLoading">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="isLoading ? 'กำลังวิเคราะห์...' : '🔮 วิเคราะห์เลขศาสตร์'"></span>
                    </button>
                </div>
            </div>

            {{-- ==================== ผลลัพธ์ ==================== --}}
            <div x-show="result" x-transition class="space-y-4">
                {{-- เลขชะตา --}}
                <div class="bg-gradient-to-br from-emerald-600/15 to-teal-600/15 backdrop-blur-lg rounded-3xl p-8 border border-emerald-500/20 text-center">
                    <div class="text-purple-300/50 text-sm mb-2" x-text="result?.type_icon + ' ' + result?.type_label"></div>
                    <div class="text-purple-200/70 text-sm mb-3" x-text="result?.input_value"></div>

                    {{-- ตัวเลขใหญ่ --}}
                    <div class="text-7xl md:text-8xl font-black mb-2"
                         style="background: linear-gradient(135deg, #10b981, #14b8a6, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"
                         x-text="result?.numerology_number">
                    </div>
                    <div class="text-white font-bold text-lg" x-text="'ดาว' + result?.number_meaning?.name"></div>
                    <div class="text-purple-300/60 text-sm mt-1" x-text="result?.number_meaning?.meaning"></div>

                    {{-- ธาตุ + สี --}}
                    <div class="flex justify-center gap-3 mt-4">
                        <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-purple-200/80"
                              x-text="'ธาตุ' + result?.number_meaning?.element"></span>
                        <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-purple-200/80"
                              x-text="'สี' + result?.number_meaning?.color"></span>
                    </div>
                </div>

                {{-- คำวิเคราะห์ AI --}}
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-6 border border-white/10">
                    <h3 class="text-white font-bold text-lg mb-3 flex items-center gap-2">
                        <span>🔮</span> คำวิเคราะห์
                    </h3>
                    <p class="text-purple-200/80 text-sm leading-relaxed whitespace-pre-line" x-text="result?.analysis"></p>
                </div>

                {{-- คำแนะนำ --}}
                <div x-show="result?.advice" class="bg-gradient-to-br from-amber-600/10 to-orange-600/10 backdrop-blur-lg rounded-2xl p-6 border border-amber-500/20">
                    <h3 class="text-white font-bold text-lg mb-3 flex items-center gap-2">
                        <span>💡</span> คำแนะนำ
                    </h3>
                    <p class="text-purple-200/80 text-sm leading-relaxed whitespace-pre-line" x-text="result?.advice"></p>
                </div>

                {{-- เลขมงคล + สีมงคล --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- เลขมงคล --}}
                    <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10">
                        <h4 class="text-white font-bold text-sm mb-3 flex items-center gap-2">
                            <span>🎯</span> เลขมงคล
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="num in (result?.lucky_numbers || [])" :key="num">
                                <span class="px-4 py-2 bg-gradient-to-r from-amber-500/20 to-yellow-500/20 border border-amber-500/30 rounded-xl text-amber-300 font-bold text-lg" x-text="num"></span>
                            </template>
                        </div>
                    </div>

                    {{-- สีมงคล --}}
                    <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10">
                        <h4 class="text-white font-bold text-sm mb-3 flex items-center gap-2">
                            <span>🎨</span> สีมงคล
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="color in (result?.lucky_colors || [])" :key="color">
                                <span class="px-4 py-2 bg-white/10 border border-white/20 rounded-xl text-white font-medium text-sm" x-text="'สี' + color"></span>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- แชร์ + วิเคราะห์ใหม่ --}}
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
                    <a :href="result?.result_url" target="_blank"
                       class="text-purple-300/60 hover:text-white text-sm transition flex items-center gap-1">
                        🔗 ลิงก์ผลวิเคราะห์
                    </a>
                    <button @click="resetResult()"
                            class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold rounded-xl shadow-lg hover:scale-105 transition-all text-sm">
                        🔄 วิเคราะห์ใหม่
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== ตารางความหมายเลข 1-9 ==================== --}}
    <section class="container mx-auto px-4 pb-16" x-show="!result">
        <h2 class="text-xl md:text-2xl font-bold text-white mb-6 text-center">
            📖 ความหมายเลข 1-9
        </h2>
        <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-9 gap-3 max-w-5xl mx-auto">
            @foreach($numberMeanings as $num => $info)
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10 text-center group hover:bg-white/10 transition-all duration-200">
                    <div class="text-3xl font-black text-emerald-400 mb-1">{{ $num }}</div>
                    <div class="text-white text-xs font-bold mb-1">{{ $info['name'] }}</div>
                    <div class="text-purple-300/40 text-[10px] leading-tight">{{ Str::limit($info['meaning'], 20) }}</div>
                    <div class="mt-1.5 px-1.5 py-0.5 bg-white/5 rounded text-[9px] text-purple-300/50">สี{{ $info['color'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ==================== SEO Content ==================== --}}
    <section class="container mx-auto px-4 pb-12" x-show="!result">
        <div class="bg-white/3 rounded-2xl p-6 border border-white/5">
            <h2 class="text-lg font-bold text-white/60 mb-3">เลขศาสตร์ — วิเคราะห์ตัวเลขดวงชะตา</h2>
            <p class="text-purple-300/40 text-xs leading-relaxed">
                เลขศาสตร์ออนไลน์{{ ($freeFortuneEnabled ?? true) ? 'ฟรี ' : ' ' }}วิเคราะห์ชื่อ เบอร์โทรศัพท์ ทะเบียนรถ เลขบัตรประชาชน วันเกิด
                ด้วยหลักเลขศาสตร์ไทย แปลงพยัญชนะเป็นตัวเลข คำนวณเลขชะตา ดาวประจำตัว ธาตุ สีมงคล เลขมงคล
                พร้อมคำวิเคราะห์เชิงลึกจาก AI ครอบคลุมทุกด้านของชีวิต
            </p>
        </div>
    </section>

</div>

<script>
function numerologyApp() {
    return {
        activeTab: 'name',
        tabs: [
            { key: 'name', icon: '📝', label: 'ชื่อ' },
            { key: 'phone', icon: '📱', label: 'เบอร์โทร' },
            { key: 'license', icon: '🚗', label: 'ทะเบียนรถ' },
            { key: 'birthday', icon: '🎂', label: 'วันเกิด' },
            { key: 'idcard', icon: '🪪', label: 'บัตร ปชช.' },
        ],
        formData: {
            name: '',
            phone: '',
            license_plate: '',
            birthday: '',
            id_card: '',
            question: '',
        },
        isLoading: false,
        result: null,
        errorMessage: null,

        init() {},

        // ตรวจสอบฟอร์มว่ากรอกครบหรือยัง
        isFormValid() {
            switch (this.activeTab) {
                case 'name': return this.formData.name.trim().length >= 2;
                case 'phone': return this.formData.phone.replace(/[^0-9]/g, '').length >= 9;
                case 'license': return this.formData.license_plate.trim().length >= 2;
                case 'birthday': return this.formData.birthday !== '';
                case 'idcard': return this.formData.id_card.replace(/[^0-9]/g, '').length === 13;
                default: return false;
            }
        },

        // วิเคราะห์ตามแท็บที่เลือก
        async analyze() {
            if (this.isLoading || !this.isFormValid()) return;
            this.isLoading = true;
            this.result = null;
            this.errorMessage = null;

            const routeMap = {
                name: '{{ route("horoscope.numerology.analyze-name") }}',
                phone: '{{ route("horoscope.numerology.analyze-phone") }}',
                license: '{{ route("horoscope.numerology.analyze-license") }}',
                birthday: '{{ route("horoscope.numerology.analyze-birthday") }}',
                idcard: '{{ route("horoscope.numerology.analyze-idcard") }}',
            };

            const bodyMap = {
                name: { name: this.formData.name, question: this.formData.question },
                phone: { phone: this.formData.phone, question: this.formData.question },
                license: { license_plate: this.formData.license_plate, question: this.formData.question },
                birthday: { birthday: this.formData.birthday, question: this.formData.question },
                idcard: { id_card: this.formData.id_card, question: this.formData.question },
            };

            try {
                const response = await fetch(routeMap[this.activeTab], {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(bodyMap[this.activeTab]),
                });

                const data = await response.json();
                if (data.success) {
                    this.result = data.data;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    this.errorMessage = data.message || 'เกิดข้อผิดพลาด';
                    alert(this.errorMessage);
                }
            } catch (error) {
                console.error('Numerology error:', error);
                alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
            }

            this.isLoading = false;
        },

        resetResult() {
            this.result = null;
            this.errorMessage = null;
        },
    };
}
</script>
@endsection
