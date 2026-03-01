{{--
    สมัครเป็นผู้ขาย - ตลาดสดไทยพร้อม

    ตัวแปรที่ใช้: ไม่มี (หน้า public)
--}}
@extends('layouts.taladsod')

@section('title', 'สมัครเป็นผู้ขาย - ตลาดสดไทยพร้อม')

@section('content')

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

        {{-- ===== หัวข้อ ===== --}}
        <div class="text-center mb-10">
            <div class="text-5xl mb-4">&#x1F3EA;</div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white mb-3">
                สมัครเป็นผู้ขาย
            </h1>
            <p class="text-base sm:text-lg text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
                เปิดร้านฟรี เข้าถึงลูกค้าในละแวกใกล้เคียง เริ่มขายของสดได้ทันที
            </p>
        </div>

        <div class="grid lg:grid-cols-5 gap-8">

            {{-- ===== ส่วนข้อดี (Sidebar) ===== --}}
            <div class="lg:col-span-2 order-2 lg:order-1">
                <div class="bg-gradient-to-br from-green-500 to-teal-600 dark:from-green-700 dark:to-teal-800 rounded-2xl p-6 sm:p-8 text-white sticky top-24">
                    <h2 class="text-xl font-bold mb-6">
                        &#x1F31F; ทำไมต้องขายกับเรา
                    </h2>
                    <div class="space-y-5">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-gift text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-0.5">เปิดร้านฟรี</h3>
                                <p class="text-sm text-green-50 leading-relaxed">ไม่มีค่าธรรมเนียมแรกเข้า ไม่มีค่ารายเดือน เริ่มขายได้เลย</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-location-dot text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-0.5">ลูกค้าค้นเจอง่าย</h3>
                                <p class="text-sm text-green-50 leading-relaxed">ระบบ GPS ช่วยให้ลูกค้าในละแวกใกล้เคียงค้นหาร้านคุณได้ทันที</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-chart-line text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-0.5">เพิ่มยอดขาย</h3>
                                <p class="text-sm text-green-50 leading-relaxed">เข้าถึงลูกค้ากลุ่มใหม่ที่ต้องการซื้อของสดจากแหล่งใกล้บ้าน</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-mobile-screen text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-0.5">จัดการง่าย</h3>
                                <p class="text-sm text-green-50 leading-relaxed">ระบบจัดการสินค้า ออเดอร์ และรายได้ ใช้ง่ายบนมือถือ</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-coins text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-0.5">รับเงินคืน</h3>
                                <p class="text-sm text-green-50 leading-relaxed">แนะนำเพื่อนมาขาย รับค่าคอมมิชชั่นจากระบบ Affiliate</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                <i class="fab fa-line text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-0.5">เชื่อมต่อ LINE</h3>
                                <p class="text-sm text-green-50 leading-relaxed">รับแจ้งเตือนและสนทนากับลูกค้าผ่าน LINE ได้ทันที</p>
                            </div>
                        </div>
                    </div>

                    {{-- สถิติ --}}
                    <div class="mt-8 pt-6 border-t border-white/20">
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold">500+</div>
                                <div class="text-xs text-green-100">ผู้ขายเข้าร่วม</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold">10K+</div>
                                <div class="text-xs text-green-100">ลูกค้าใช้งาน</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== ฟอร์มสมัคร ===== --}}
            <div class="lg:col-span-3 order-1 lg:order-2">
                <form method="POST"
                      action="{{ route('taladsod.register-seller.store') }}"
                      x-data="{ step: 1 }"
                      class="space-y-6">
                    @csrf

                    {{-- การ์ดข้อมูลร้านค้า --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-5 sm:p-6 space-y-5">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-7 h-7 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                            ข้อมูลร้านค้า
                        </h2>

                        {{-- ชื่อร้านค้า --}}
                        <div>
                            <label for="shop_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                ชื่อร้านค้า <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="shop_name"
                                   name="shop_name"
                                   value="{{ old('shop_name') }}"
                                   required
                                   placeholder="เช่น สวนผักลุงสม ตลาดสดป้าแดง"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors placeholder-gray-400 dark:placeholder-gray-500">
                            @error('shop_name')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- คำอธิบายร้าน --}}
                        <div>
                            <label for="shop_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                คำอธิบายร้านค้า
                            </label>
                            <textarea id="shop_description"
                                      name="shop_description"
                                      rows="3"
                                      placeholder="บอกเล่าเกี่ยวกับร้านค้าของคุณ สินค้าเด่น แหล่งที่มา..."
                                      class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors resize-none placeholder-gray-400 dark:placeholder-gray-500">{{ old('shop_description') }}</textarea>
                            @error('shop_description')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- การ์ดข้อมูลติดต่อ --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-5 sm:p-6 space-y-5">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-7 h-7 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                            ข้อมูลติดต่อ
                        </h2>

                        {{-- เบอร์โทร --}}
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                เบอร์โทรศัพท์ <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="tel"
                                       id="phone"
                                       name="phone"
                                       value="{{ old('phone') }}"
                                       required
                                       placeholder="08X-XXX-XXXX"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                            </div>
                            @error('phone')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- LINE ID (ถ้ามี) --}}
                        <div>
                            <label for="line_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                LINE ID (ถ้ามี)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-green-500">
                                    <i class="fab fa-line"></i>
                                </span>
                                <input type="text"
                                       id="line_id"
                                       name="line_id"
                                       value="{{ old('line_id') }}"
                                       placeholder="@yourlineid"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                            </div>
                        </div>
                    </div>

                    {{-- การ์ดที่อยู่ --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-5 sm:p-6 space-y-5"
                         x-data="geolocation()">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-7 h-7 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-bold">3</span>
                            ที่อยู่ร้านค้า
                        </h2>

                        {{-- ที่อยู่ --}}
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                ที่อยู่ <span class="text-red-500">*</span>
                            </label>
                            <textarea id="address"
                                      name="address"
                                      rows="2"
                                      required
                                      placeholder="บ้านเลขที่ ถนน ซอย ตำบล อำเภอ"
                                      class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors resize-none placeholder-gray-400 dark:placeholder-gray-500">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- จังหวัด --}}
                        <div>
                            <label for="province" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                จังหวัด <span class="text-red-500">*</span>
                            </label>
                            <select id="province"
                                    name="province"
                                    required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                                <option value="">-- เลือกจังหวัด --</option>
                                @php
                                    $provinces = [
                                        'กรุงเทพมหานคร', 'กระบี่', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร',
                                        'ขอนแก่น', 'จันทบุรี', 'ฉะเชิงเทรา', 'ชลบุรี', 'ชัยนาท',
                                        'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่', 'ตรัง',
                                        'ตราด', 'ตาก', 'นครนายก', 'นครปฐม', 'นครพนม',
                                        'นครราชสีมา', 'นครศรีธรรมราช', 'นครสวรรค์', 'นนทบุรี', 'นราธิวาส',
                                        'น่าน', 'บึงกาฬ', 'บุรีรัมย์', 'ปทุมธานี', 'ประจวบคีรีขันธ์',
                                        'ปราจีนบุรี', 'ปัตตานี', 'พระนครศรีอยุธยา', 'พะเยา', 'พังงา',
                                        'พัทลุง', 'พิจิตร', 'พิษณุโลก', 'เพชรบุรี', 'เพชรบูรณ์',
                                        'แพร่', 'ภูเก็ต', 'มหาสารคาม', 'มุกดาหาร', 'แม่ฮ่องสอน',
                                        'ยโสธร', 'ยะลา', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง',
                                        'ราชบุรี', 'ลพบุรี', 'ลำปาง', 'ลำพูน', 'เลย',
                                        'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล', 'สมุทรปราการ',
                                        'สมุทรสงคราม', 'สมุทรสาคร', 'สระแก้ว', 'สระบุรี', 'สิงห์บุรี',
                                        'สุโขทัย', 'สุพรรณบุรี', 'สุราษฎร์ธานี', 'สุรินทร์', 'หนองคาย',
                                        'หนองบัวลำภู', 'อ่างทอง', 'อำนาจเจริญ', 'อุดรธานี', 'อุตรดิตถ์',
                                        'อุทัยธานี', 'อุบลราชธานี',
                                    ];
                                @endphp
                                @foreach($provinces as $province)
                                    <option value="{{ $province }}" {{ old('province') === $province ? 'selected' : '' }}>
                                        {{ $province }}
                                    </option>
                                @endforeach
                            </select>
                            @error('province')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ตำแหน่ง GPS --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                ตำแหน่ง GPS (แนะนำ)
                            </label>
                            <div class="flex gap-3 mb-3">
                                <div class="flex-1">
                                    <input type="text"
                                           name="latitude"
                                           :value="lat"
                                           readonly
                                           placeholder="ละติจูด"
                                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                                </div>
                                <div class="flex-1">
                                    <input type="text"
                                           name="longitude"
                                           :value="lng"
                                           readonly
                                           placeholder="ลองจิจูด"
                                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                                </div>
                            </div>
                            <button type="button"
                                    @click="getLocation()"
                                    :disabled="loading"
                                    class="w-full py-3 bg-green-50 dark:bg-green-900/30 hover:bg-green-100 dark:hover:bg-green-900/50 text-green-700 dark:text-green-400 rounded-xl font-medium transition-colors flex items-center justify-center gap-2 text-sm border border-green-200 dark:border-green-800"
                                    :class="{ 'animate-pulse': loading }">
                                <i class="fas fa-location-crosshairs"></i>
                                <span x-text="loading ? 'กำลังค้นหาตำแหน่ง...' : (lat ? 'พบตำแหน่งแล้ว - กดเพื่ออัพเดท' : 'ระบุตำแหน่งปัจจุบัน')"></span>
                            </button>
                            <template x-if="error">
                                <p class="text-xs text-red-500 mt-2 flex items-center gap-1">
                                    <i class="fas fa-exclamation-triangle"></i> <span x-text="error"></span>
                                </p>
                            </template>
                        </div>
                    </div>

                    {{-- ข้อตกลง --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-5 sm:p-6">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox"
                                   name="agree_terms"
                                   required
                                   class="w-5 h-5 mt-0.5 rounded border-gray-300 dark:border-gray-600 text-green-500 focus:ring-green-500 focus:ring-offset-0">
                            <span class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                ข้าพเจ้ายอมรับ <a href="#" class="text-green-600 dark:text-green-400 hover:underline font-medium">ข้อกำหนดการให้บริการ</a>
                                และ <a href="#" class="text-green-600 dark:text-green-400 hover:underline font-medium">นโยบายความเป็นส่วนตัว</a>
                                ของตลาดสดไทยพร้อม
                            </span>
                        </label>
                    </div>

                    {{-- ปุ่มสมัคร --}}
                    <button type="submit"
                            class="w-full py-4 bg-green-500 hover:bg-green-600 text-white text-lg font-bold rounded-2xl transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] flex items-center justify-center gap-2">
                        <i class="fas fa-store"></i> สมัครเป็นผู้ขาย
                    </button>

                    {{-- ลิงก์กลับ --}}
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                        มีร้านค้าอยู่แล้ว?
                        <a href="{{ route('login') }}" class="text-green-600 dark:text-green-400 hover:underline font-medium">เข้าสู่ระบบ</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

@endsection
