<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Menu</title>

    {{-- Vite Assets สำหรับ Tailwind --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Arrow X Theme Styles --}}
    <x-arrow-x.theme-styles />

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    {{-- Sidebar V3 Component (แบบเต็ม) --}}
    <x-arrow-x.sidebar-v3 :iframe-mode="false" />

    <script>
        /**
         * Menu Click Handler
         * เมื่อคลิกเมนู → ส่ง message ไปยัง parent → parent เปลี่ยน content iframe
         */

        document.addEventListener('DOMContentLoaded', function() {
            // จับทุก link ใน sidebar
            const sidebar = document.querySelector('aside');

            if (sidebar) {
                sidebar.addEventListener('click', function(e) {
                    const link = e.target.closest('a[href]');

                    if (link) {
                        const href = link.getAttribute('href');

                        // ถ้าเป็น internal link (ไม่ใช่ # หรือ external)
                        if (href && !href.startsWith('#') && !href.startsWith('http') && href !== 'javascript:void(0)') {
                            e.preventDefault();

                            // ดึง title จาก link text
                            const title = link.textContent.trim();

                            console.log('📨 Sending navigate message:', href);

                            // ส่ง message ไปยัง parent window
                            window.parent.postMessage({
                                type: 'navigate',
                                url: href,
                                title: title
                            }, window.location.origin);
                        }
                    }
                });

                console.log('✅ Sidebar menu click handler initialized');
            }
        });
    </script>
</body>
</html>
