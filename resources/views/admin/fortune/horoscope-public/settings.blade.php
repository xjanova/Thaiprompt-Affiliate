{{-- ตั้งค่าระบบดูดวงสาธารณะ --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="horoscopePublicSettings()">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                ⚙️ ตั้งค่าระบบดูดวงสาธารณะ
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                จัดการเปิด/ปิด และจำกัดการใช้งานระบบดูดวง Frontend
            </p>
        </div>

        {{-- Quick Links --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.fortune.horoscope-public.zodiac.index') }}"
               class="px-4 py-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg text-sm font-medium hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition">
                ⭐ จัดการราศี
            </a>
            <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}"
               class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm font-medium hover:bg-purple-200 dark:hover:bg-purple-900/50 transition">
                💭 พจนานุกรมฝัน
            </a>
            <a href="{{ route('admin.fortune.horoscope-public.analytics') }}"
               class="px-4 py-2 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded-lg text-sm font-medium hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition">
                📊 สถิติ
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl">
            <p class="text-red-800 dark:text-red-200 font-semibold mb-2">❌ พบข้อผิดพลาด:</p>
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.fortune.horoscope-public.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- ==================== เปิด/ปิดระบบ ==================== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                🔮 สถานะระบบ
            </h2>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="horoscope_public_enabled" value="0">
                <input type="checkbox" name="horoscope_public_enabled" value="1"
                       class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"
                       {{ old('horoscope_public_enabled', $settings->horoscope_public_enabled ?? true) ? 'checked' : '' }}>
                <div>
                    <span class="font-semibold text-gray-900 dark:text-white">เปิดใช้งานระบบดูดวงสาธารณะ</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        เมื่อเปิด ผู้ใช้สามารถเข้าถึง /horoscope/* ได้
                    </p>
                </div>
            </label>
        </div>

        {{-- ==================== จำกัดการใช้งานฟรี ==================== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                🎁 จำกัดการใช้งานฟรีต่อวัน
            </h2>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800 mb-6">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    💡 <strong>คำแนะนำ:</strong> ตั้งค่า 0 เพื่อปิดการใช้งานฟรี (ต้องมีเครดิตเท่านั้น)
                    หรือตั้งค่าสูงๆ เพื่อเปิดใช้งานฟรีไม่จำกัด
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- ดูดวงรายวัน --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ⭐ ดูดวงรายวัน (ครั้ง/วัน)
                    </label>
                    <input type="number" name="horoscope_free_daily_limit"
                           value="{{ old('horoscope_free_daily_limit', $settings->horoscope_free_daily_limit ?? 5) }}"
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวนครั้งดูดวงรายวันฟรีต่อ IP/วัน</p>
                </div>

                {{-- ทำนายฝัน --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        💭 ทำนายฝัน (ครั้ง/วัน)
                    </label>
                    <input type="number" name="horoscope_dream_free_limit"
                           value="{{ old('horoscope_dream_free_limit', $settings->horoscope_dream_free_limit ?? 3) }}"
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวนครั้งทำนายฝัน AI ฟรีต่อ IP/วัน</p>
                </div>

                {{-- เลขศาสตร์ --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🔢 เลขศาสตร์ (ครั้ง/วัน)
                    </label>
                    <input type="number" name="horoscope_numerology_free_limit"
                           value="{{ old('horoscope_numerology_free_limit', $settings->horoscope_numerology_free_limit ?? 3) }}"
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวนครั้งวิเคราะห์เลขศาสตร์ฟรีต่อ IP/วัน</p>
                </div>
            </div>
        </div>

        {{-- ==================== SEO ==================== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                🌐 SEO Settings
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        SEO Title ภาษาไทย
                    </label>
                    <input type="text" name="horoscope_seo_title_th"
                           value="{{ old('horoscope_seo_title_th', $settings->horoscope_seo_title_th ?? '') }}"
                           placeholder="ดูดวงออนไลน์ฟรี — ดวงรายวัน ทำนายฝัน ไพ่ทาโรต์ เลขศาสตร์"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แนะนำ 50-60 ตัวอักษร</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        SEO Description ภาษาไทย
                    </label>
                    <textarea name="horoscope_seo_description_th" rows="3"
                              placeholder="ดูดวงออนไลน์ฟรี ดวงรายวัน 12 ราศี ทำนายฝัน ไพ่ทาโรต์ เลขศาสตร์ วิเคราะห์ชื่อ เบอร์โทร ทะเบียนรถ — AI ทำนายแม่นยำ"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('horoscope_seo_description_th', $settings->horoscope_seo_description_th ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แนะนำ 150-160 ตัวอักษร</p>
                </div>
            </div>
        </div>

        {{-- ==================== ปุ่มบันทึก ==================== --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.fortune.dashboard') }}"
               class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                ← กลับ
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg transition font-semibold shadow-lg">
                💾 บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

<script>
function horoscopePublicSettings() {
    return {
        // สามารถเพิ่ม interactive logic ได้
    };
}
</script>
@endsection
