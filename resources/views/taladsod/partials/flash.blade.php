{{-- ข้อความ info + error ของฟอร์ม (success/error แสดงที่ layouts.taladsod อยู่แล้ว) --}}
@if (session('info'))
    <div class="mb-4 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 text-sm">{{ session('info') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm">
        @foreach ($errors->all() as $message)
            <p>{{ $message }}</p>
        @endforeach
    </div>
@endif
