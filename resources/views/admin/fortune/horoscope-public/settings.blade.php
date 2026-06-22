{{-- ตั้งค่าระบบดูดวงสาธารณะ — ธีม V4 นวลทองคำ --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- คอนเทนเนอร์หลัก ระยะห่างแนวตั้ง --}}
<div style="display:flex;flex-direction:column;gap:18px;" x-data="horoscopePublicSettings()">

    {{-- ส่วนหัว: eyebrow + ชื่อหน้า + ปุ่มลัด --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · ตั้งค่าสาธารณะ
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-sliders"></i> ตั้งค่าระบบดูดวงสาธารณะ
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">
                จัดการเปิด/ปิด และจำกัดการใช้งานระบบดูดวง Frontend
            </p>
        </div>

        {{-- ปุ่มลัดไปหน้าอื่น --}}
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @if(Route::has('admin.fortune.horoscope-public.zodiac.index'))
                <a href="{{ route('admin.fortune.horoscope-public.zodiac.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-star"></i> จัดการราศี
                </a>
            @endif
            @if(Route::has('admin.fortune.horoscope-public.dream.index'))
                <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-cloud-moon"></i> พจนานุกรมฝัน
                </a>
            @endif
            @if(Route::has('admin.fortune.horoscope-public.analytics'))
                <a href="{{ route('admin.fortune.horoscope-public.analytics') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-chart-column"></i> สถิติ
                </a>
            @endif
        </div>
    </div>

    {{-- กล่องข้อผิดพลาด (flash success ถูก toast โดย layout อัตโนมัติ) --}}
    @if($errors->any())
        <div class="tp-card" style="border-left:4px solid #d9534f;">
            <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#d9534f;margin-bottom:8px;">
                <i class="fas fa-circle-exclamation"></i> พบข้อผิดพลาด
            </div>
            <ul style="margin:0;padding-left:20px;font-size:13px;color:var(--ink2);">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ฟอร์มตั้งค่า — คง action/method/@csrf/@method เดิม --}}
    <form action="{{ route('admin.fortune.horoscope-public.settings.update') }}" method="POST"
          style="display:flex;flex-direction:column;gap:18px;">
        @csrf
        @method('PUT')

        {{-- ==================== สถานะระบบ (เปิด/ปิด) ==================== --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="fas fa-wand-magic-sparkles" style="color:var(--accent1);"></i> สถานะระบบ
            </div>

            {{-- toggle เปิดใช้งาน — hidden 0 + checkbox 1 คงเดิม --}}
            <label style="display:flex;align-items:center;gap:14px;cursor:pointer;padding:14px;border-radius:14px;box-shadow:var(--inset);">
                <input type="hidden" name="horoscope_public_enabled" value="0">
                <input type="checkbox" name="horoscope_public_enabled" value="1"
                       style="width:20px;height:20px;accent-color:var(--accent1);cursor:pointer;"
                       {{ old('horoscope_public_enabled', $settings->horoscope_public_enabled ?? true) ? 'checked' : '' }}>
                <div>
                    <span style="font-weight:700;color:var(--ink);">เปิดใช้งานระบบดูดวงสาธารณะ</span>
                    <p class="tp-muted" style="margin:4px 0 0;font-size:12px;">
                        เมื่อเปิด ผู้ใช้สามารถเข้าถึง <code style="color:var(--accent1);">/horoscope/*</code> ได้
                    </p>
                </div>
            </label>
        </div>

        {{-- ==================== จำกัดการใช้งานฟรีต่อวัน ==================== --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="fas fa-gift" style="color:var(--accent1);"></i> จำกัดการใช้งานฟรีต่อวัน
            </div>

            {{-- คำแนะนำ --}}
            <div style="padding:12px 14px;border-radius:12px;box-shadow:var(--inset-sm);margin-bottom:18px;border-left:3px solid #5689b8;">
                <p style="margin:0;font-size:13px;color:var(--ink2);">
                    <i class="fas fa-lightbulb" style="color:#e0a52e;"></i>
                    <strong style="color:var(--ink);">คำแนะนำ:</strong>
                    ตั้งค่า <strong>0</strong> เพื่อปิดการใช้งานฟรี (ต้องมีเครดิตเท่านั้น)
                    หรือตั้งค่าสูง ๆ เพื่อเปิดใช้งานฟรีไม่จำกัด
                </p>
            </div>

            {{-- กริด 3 ช่อง — field/name คงเดิม --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                {{-- ดูดวงรายวัน --}}
                <div>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:8px;">
                        <i class="fas fa-star" style="color:var(--accent2);"></i> ดูดวงรายวัน (ครั้ง/วัน)
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="horoscope_free_daily_limit"
                               value="{{ old('horoscope_free_daily_limit', $settings->horoscope_free_daily_limit ?? 5) }}"
                               min="0" max="100"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 16px;color:var(--ink);font-size:13.5px;font-family:inherit;">
                    </div>
                    <p class="tp-muted" style="margin:6px 0 0;font-size:11px;">จำนวนครั้งดูดวงรายวันฟรีต่อ IP/วัน</p>
                </div>

                {{-- ทำนายฝัน --}}
                <div>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:8px;">
                        <i class="fas fa-cloud-moon" style="color:var(--accent2);"></i> ทำนายฝัน (ครั้ง/วัน)
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="horoscope_dream_free_limit"
                               value="{{ old('horoscope_dream_free_limit', $settings->horoscope_dream_free_limit ?? 3) }}"
                               min="0" max="100"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 16px;color:var(--ink);font-size:13.5px;font-family:inherit;">
                    </div>
                    <p class="tp-muted" style="margin:6px 0 0;font-size:11px;">จำนวนครั้งทำนายฝัน AI ฟรีต่อ IP/วัน</p>
                </div>

                {{-- เลขศาสตร์ --}}
                <div>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:8px;">
                        <i class="fas fa-hashtag" style="color:var(--accent2);"></i> เลขศาสตร์ (ครั้ง/วัน)
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="horoscope_numerology_free_limit"
                               value="{{ old('horoscope_numerology_free_limit', $settings->horoscope_numerology_free_limit ?? 3) }}"
                               min="0" max="100"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 16px;color:var(--ink);font-size:13.5px;font-family:inherit;">
                    </div>
                    <p class="tp-muted" style="margin:6px 0 0;font-size:11px;">จำนวนครั้งวิเคราะห์เลขศาสตร์ฟรีต่อ IP/วัน</p>
                </div>
            </div>
        </div>

        {{-- ==================== SEO ==================== --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="fas fa-globe" style="color:var(--accent1);"></i> ตั้งค่า SEO
            </div>

            <div style="display:flex;flex-direction:column;gap:16px;">
                {{-- SEO Title --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:8px;">
                        SEO Title ภาษาไทย
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="horoscope_seo_title_th"
                               value="{{ old('horoscope_seo_title_th', $settings->horoscope_seo_title_th ?? '') }}"
                               placeholder="ดูดวงออนไลน์ฟรี — ดวงรายวัน ทำนายฝัน ไพ่ทาโรต์ เลขศาสตร์"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 16px;color:var(--ink);font-size:13.5px;font-family:inherit;">
                    </div>
                    <p class="tp-muted" style="margin:6px 0 0;font-size:11px;">แนะนำ 50-60 ตัวอักษร</p>
                </div>

                {{-- SEO Description --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:8px;">
                        SEO Description ภาษาไทย
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <textarea name="horoscope_seo_description_th" rows="3"
                                  placeholder="ดูดวงออนไลน์ฟรี ดวงรายวัน 12 ราศี ทำนายฝัน ไพ่ทาโรต์ เลขศาสตร์ วิเคราะห์ชื่อ เบอร์โทร ทะเบียนรถ — AI ทำนายแม่นยำ"
                                  style="width:100%;background:transparent;border:0;outline:0;padding:11px 16px;color:var(--ink);font-size:13.5px;line-height:1.6;resize:vertical;font-family:inherit;">{{ old('horoscope_seo_description_th', $settings->horoscope_seo_description_th ?? '') }}</textarea>
                    </div>
                    <p class="tp-muted" style="margin:6px 0 0;font-size:11px;">แนะนำ 150-160 ตัวอักษร</p>
                </div>
            </div>
        </div>

        {{-- ==================== ปุ่มบันทึก ==================== --}}
        <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
            @if(Route::has('admin.fortune.dashboard'))
                <a href="{{ route('admin.fortune.dashboard') }}" class="tp-btn">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
            @endif
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // ฟังก์ชัน Alpine สำหรับหน้าตั้งค่าดูดวงสาธารณะ (คง logic เดิม)
    function horoscopePublicSettings() {
        return {
            // สามารถเพิ่ม interactive logic ได้
        };
    }
</script>
@endpush
