{{-- 🔐 (2026-11-03) T13 — ส่วนหัวร่วมของหน้า gateway เว็บจันทรา (interstitial / expired)
     - ไม่ให้ index / ไม่ส่ง referrer (มี token ใน URL)
     - dark mode: tailwind ใช้ darkMode 'class' → ติด class dark ตาม prefers-color-scheme ของเครื่อง --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<meta name="color-scheme" content="light dark">
<title>{{ $title ?? 'แม่หมอจันทรา' }}</title>
<script>
    // ตามโหมดมืด/สว่างของเครื่องลูกค้า (in-app browser ของ FB ไม่มีปุ่มสลับธีม)
    try {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    } catch (e) {}
</script>
@vite(['resources/css/app.css'])
