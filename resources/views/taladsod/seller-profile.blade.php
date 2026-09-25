{{--
    ตั้งค่าร้าน (ฝั่งผู้ขาย) - ตลาดสดไทยพร๊อม

    ตัวแปร: $seller (FreshMarketSeller)
    ฟอร์ม: PUT taladsod.seller.profile.update
      shop_name*, shop_description, phone*, address*, province, district, sub_district, latitude*, longitude*
    หน้านี้เป็นเวอร์ชันใช้งานได้ก่อน — จะถูกสร้างใหม่ในธีม V4
--}}
@extends('layouts.taladsod')

@section('title', 'ตั้งค่าร้าน - ตลาดสดไทยพร๊อม')

@section('content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8"
         x-data="{
            lat: '{{ old('latitude', $seller->latitude) }}',
            lng: '{{ old('longitude', $seller->longitude) }}',
            locating: false,
            locError: '',
            locate() {
                if (! navigator.geolocation) { this.locError = 'อุปกรณ์นี้ไม่รองรับการระบุตำแหน่ง'; return; }
                this.locating = true; this.locError = '';
                navigator.geolocation.getCurrentPosition(
                    (p) => { this.lat = p.coords.latitude.toFixed(7); this.lng = p.coords.longitude.toFixed(7); this.locating = false; },
                    () => { this.locError = 'ระบุตำแหน่งไม่สำเร็จ กรุณาอนุญาตการเข้าถึงตำแหน่ง'; this.locating = false; },
                    { enableHighAccuracy: true, timeout: 15000 }
                );
            }
         }">
        <a href="{{ route('taladsod.seller.dashboard') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium">← แผงควบคุมผู้ขาย</a>
        <h1 class="mt-3 mb-5 text-2xl font-bold text-gray-900 dark:text-white">⚙️ ตั้งค่าร้าน</h1>

        @include('taladsod.partials.flash')

        <form method="POST" action="{{ route('taladsod.seller.profile.update') }}"
              class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow border border-gray-100 dark:border-gray-700 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อร้าน *</label>
                <input type="text" name="shop_name" required maxlength="200" value="{{ old('shop_name', $seller->shop_name) }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รายละเอียดร้าน</label>
                <textarea name="shop_description" rows="3" maxlength="1000"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('shop_description', $seller->shop_description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เบอร์โทรร้าน *</label>
                <input type="tel" name="phone" required maxlength="20" value="{{ old('phone', $seller->phone) }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ที่อยู่ร้าน (จุดรับของของไรเดอร์) *</label>
                <textarea name="address" rows="2" required maxlength="500"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('address', $seller->address) }}</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" name="sub_district" maxlength="100" placeholder="ตำบล/แขวง" value="{{ old('sub_district', $seller->sub_district) }}"
                       class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <input type="text" name="district" maxlength="100" placeholder="อำเภอ/เขต" value="{{ old('district', $seller->district) }}"
                       class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <input type="text" name="province" maxlength="100" placeholder="จังหวัด" value="{{ old('province', $seller->province) }}"
                       class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div class="p-4 rounded-xl bg-green-50 dark:bg-green-900/20">
                <p class="text-sm font-medium text-green-800 dark:text-green-300 mb-2">📍 พิกัดร้าน * (ใช้ให้ผู้ซื้อค้นหาเจอ และให้ไรเดอร์มารับของ)</p>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="latitude" x-model="lat" required placeholder="ละติจูด"
                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <input type="text" name="longitude" x-model="lng" required placeholder="ลองจิจูด"
                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <button type="button" @click="locate()" :disabled="locating"
                        class="mt-3 px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white text-sm font-semibold">
                    <span x-show="! locating">ใช้ตำแหน่งปัจจุบันของฉัน</span>
                    <span x-show="locating">กำลังระบุตำแหน่ง...</span>
                </button>
                <p x-show="locError" x-text="locError" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold">บันทึกข้อมูลร้าน</button>
        </form>
    </div>
@endsection
