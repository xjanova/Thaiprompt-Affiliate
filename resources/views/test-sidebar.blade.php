<!DOCTYPE html>
<html>
<head>
    <title>Test Sidebar</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
        }
        .sidebar {
            width: 250px;
            background: red;
            color: white;
            padding: 20px;
        }
        .content {
            flex: 1;
            background: blue;
            color: white;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>SIDEBAR (ควรเห็นนี่)</h2>
        <p>ถ้าเห็นตรงนี้ = sidebar แสดง</p>

        <hr style="margin: 20px 0;">

        <h3>ทดสอบ Alpine.js</h3>
        <div x-data="{ count: 0 }">
            <button @click="count++" style="background: white; color: black; padding: 10px;">
                คลิกที่นี่: <span x-text="count"></span>
            </button>
        </div>

        <hr style="margin: 20px 0;">

        <h3>Component Test</h3>
        <x-arrow-x.sidebar-v3 :iframe-mode="false" />
    </div>

    <div class="content">
        <h1>CONTENT (ด้านขวา)</h1>
        <p>นี่คือ content area</p>
    </div>

    <script>
        console.log('Test page loaded');
        console.log('Alpine:', typeof Alpine);
        console.log('Window:', window.location.href);
    </script>
</body>
</html>
