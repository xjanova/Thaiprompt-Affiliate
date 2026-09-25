{{-- ข้อความ info + error ของฟอร์ม (ธีม V4) — success/error ทั่วไปแสดงเป็น toast ที่ layout V4 อยู่แล้ว --}}
@if (session('info'))
    <div class="sf-note sf-note-info" role="status">{{ session('info') }}</div>
@endif
@if ($errors->any())
    <div class="sf-note sf-note-err" role="alert">
        @foreach ($errors->all() as $message)
            <p style="margin:0;">{{ $message }}</p>
        @endforeach
    </div>
@endif
