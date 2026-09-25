{{--
 | กล่องแสดงข้อผิดพลาดจากการตรวจสอบฟอร์ม (validation) ของแผงร้านค้า V4
 | ใช้: <x-seller-kit.errors />  (อ่านจาก $errors ที่ Laravel แชร์ให้ทุก view)
 --}}
@if($errors->any())
    <div class="tp-card" role="alert" style="padding:14px 18px; border-left:4px solid var(--tp-bad, #d9534f);">
        <div style="font-weight:800; font-size:13.5px; color:var(--tp-bad, #d9534f);">⚠️ กรุณาตรวจสอบข้อมูลอีกครั้ง</div>
        <ul style="margin:6px 0 0; padding-left:18px; font-size:12.5px; color:var(--ink); line-height:1.7;">
            @foreach($errors->all() as $kitError)
                <li>{{ $kitError }}</li>
            @endforeach
        </ul>
    </div>
@endif
