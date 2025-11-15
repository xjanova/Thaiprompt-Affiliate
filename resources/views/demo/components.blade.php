<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arrow X Components Showcase</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+Thai:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Arrow X Theme Styles --}}
    <x-arrow-x.theme-styles />

    <style>
        body {
            font-family: 'Inter', 'Noto Sans Thai', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <div class="min-h-screen">
        {{-- Header --}}
        <header class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-40">
            <div class="container mx-auto px-4 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 bg-clip-text text-transparent">
                            Arrow X Components
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Component Library Showcase - V3
                        </p>
                    </div>

                    {{-- Dark mode toggle --}}
                    <button
                        @click="document.documentElement.classList.toggle('dark')"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                    >
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                </div>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="container mx-auto px-4 py-12 space-y-12">

            {{-- 1. Card Components --}}
            <section>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">1. Card Components</h2>

                {{-- Stat Cards --}}
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Stat Cards</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <x-arrow-x.card.stat
                        title="ผู้ใช้ทั้งหมด"
                        value="12,543"
                        icon="fa-users"
                        color="purple"
                        trend="up"
                        trendValue="+12%"
                    />

                    <x-arrow-x.card.stat
                        title="รายได้"
                        value="฿1.2M"
                        icon="fa-dollar-sign"
                        color="green"
                        trend="up"
                        trendValue="+18.5%"
                    />

                    <x-arrow-x.card.stat
                        title="คำสั่งซื้อ"
                        value="854"
                        icon="fa-shopping-cart"
                        color="blue"
                        trend="down"
                        trendValue="-3.2%"
                    />

                    <x-arrow-x.card.stat
                        title="คอมมิชชั่น"
                        value="฿234K"
                        icon="fa-chart-line"
                        color="orange"
                        trend="up"
                        trendValue="+24%"
                    />
                </div>

                {{-- Info Cards --}}
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Info Cards</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <x-arrow-x.card.info title="การ์ดข้อมูล" subtitle="Card พื้นฐานพร้อม glassmorphism">
                        <p class="text-gray-600 dark:text-gray-400">
                            นี่คือตัวอย่าง Info Card ที่มี glassmorphism effect
                            สามารถใส่เนื้อหาอะไรก็ได้ภายใน
                        </p>
                    </x-arrow-x.card.info>

                    <x-arrow-x.card.info :glassmorph="false" :hover="false">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">ไม่มี Glassmorphism</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Card แบบธรรมดา ไม่มี hover effect
                        </p>
                    </x-arrow-x.card.info>
                </div>

                {{-- Gradient Cards --}}
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Gradient Cards</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-arrow-x.card.gradient title="Purple Gradient" gradient="purple" :pattern="true">
                        <p class="text-white/90">
                            Card พร้อม gradient background และ pattern overlay
                        </p>
                    </x-arrow-x.card.gradient>

                    <x-arrow-x.card.gradient title="Blue Gradient" gradient="blue">
                        <p class="text-white/90">
                            Gradient สีน้ำเงินสดใส
                        </p>
                    </x-arrow-x.card.gradient>

                    <x-arrow-x.card.gradient title="Green Gradient" gradient="green">
                        <p class="text-white/90">
                            Gradient สีเขียวสดชื่น
                        </p>
                    </x-arrow-x.card.gradient>
                </div>
            </section>

            {{-- 2. Button Components --}}
            <section>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">2. Button Components</h2>

                <div class="space-y-6">
                    {{-- Variants --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Button Variants</h3>
                        <div class="flex flex-wrap gap-4">
                            <x-arrow-x.button variant="primary" icon="fa-check">Primary</x-arrow-x.button>
                            <x-arrow-x.button variant="secondary" icon="fa-cog">Secondary</x-arrow-x.button>
                            <x-arrow-x.button variant="gradient" icon="fa-rocket">Gradient</x-arrow-x.button>
                            <x-arrow-x.button variant="outline" icon="fa-heart">Outline</x-arrow-x.button>
                            <x-arrow-x.button variant="ghost" icon="fa-star">Ghost</x-arrow-x.button>
                        </div>
                    </div>

                    {{-- Colors --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Colors</h3>
                        <div class="flex flex-wrap gap-4">
                            <x-arrow-x.button variant="gradient" color="purple">Purple</x-arrow-x.button>
                            <x-arrow-x.button variant="gradient" color="blue">Blue</x-arrow-x.button>
                            <x-arrow-x.button variant="gradient" color="green">Green</x-arrow-x.button>
                            <x-arrow-x.button variant="gradient" color="orange">Orange</x-arrow-x.button>
                            <x-arrow-x.button variant="gradient" color="pink">Pink</x-arrow-x.button>
                            <x-arrow-x.button variant="gradient" color="red">Red</x-arrow-x.button>
                        </div>
                    </div>

                    {{-- Sizes --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Sizes</h3>
                        <div class="flex flex-wrap items-center gap-4">
                            <x-arrow-x.button size="xs">Extra Small</x-arrow-x.button>
                            <x-arrow-x.button size="sm">Small</x-arrow-x.button>
                            <x-arrow-x.button size="md">Medium</x-arrow-x.button>
                            <x-arrow-x.button size="lg">Large</x-arrow-x.button>
                            <x-arrow-x.button size="xl">Extra Large</x-arrow-x.button>
                        </div>
                    </div>

                    {{-- States --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">States</h3>
                        <div class="flex flex-wrap gap-4">
                            <x-arrow-x.button variant="gradient" :loading="true">Loading</x-arrow-x.button>
                            <x-arrow-x.button variant="primary" :disabled="true">Disabled</x-arrow-x.button>
                            <x-arrow-x.button variant="gradient" :fullWidth="true">Full Width</x-arrow-x.button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 3. Badge Components --}}
            <section>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">3. Badge Components</h2>

                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Variants</h3>
                        <div class="flex flex-wrap gap-3">
                            <x-arrow-x.badge variant="solid">Solid</x-arrow-x.badge>
                            <x-arrow-x.badge variant="outline">Outline</x-arrow-x.badge>
                            <x-arrow-x.badge variant="soft">Soft</x-arrow-x.badge>
                            <x-arrow-x.badge variant="gradient">Gradient</x-arrow-x.badge>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Status Colors</h3>
                        <div class="flex flex-wrap gap-3">
                            <x-arrow-x.badge color="success" icon="fa-check">Success</x-arrow-x.badge>
                            <x-arrow-x.badge color="warning" icon="fa-exclamation-triangle">Warning</x-arrow-x.badge>
                            <x-arrow-x.badge color="error" icon="fa-times">Error</x-arrow-x.badge>
                            <x-arrow-x.badge color="info" icon="fa-info">Info</x-arrow-x.badge>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">With Dot Indicator</h3>
                        <div class="flex flex-wrap gap-3">
                            <x-arrow-x.badge :dot="true" color="success">Online</x-arrow-x.badge>
                            <x-arrow-x.badge :dot="true" color="warning">Away</x-arrow-x.badge>
                            <x-arrow-x.badge :dot="true" color="error">Offline</x-arrow-x.badge>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 4. Alert Components --}}
            <section>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">4. Alert Components</h2>

                <div class="space-y-4">
                    <x-arrow-x.alert type="success" title="สำเร็จ!" :dismissible="true">
                        การดำเนินการเสร็จสมบูรณ์แล้ว
                    </x-arrow-x.alert>

                    <x-arrow-x.alert type="warning" title="คำเตือน">
                        กรุณาตรวจสอบข้อมูลก่อนดำเนินการต่อ
                    </x-arrow-x.alert>

                    <x-arrow-x.alert type="error" title="เกิดข้อผิดพลาด" :dismissible="true">
                        ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง
                    </x-arrow-x.alert>

                    <x-arrow-x.alert type="info" variant="gradient">
                        ระบบจะอัพเดทในวันที่ 20 พฤศจิกายน 2025
                    </x-arrow-x.alert>
                </div>
            </section>

            {{-- 5. Form Components --}}
            <section>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">5. Form Components</h2>

                <x-arrow-x.card.info title="ตัวอย่าง Form" padding="lg">
                    <form class="space-y-6">
                        {{-- Input --}}
                        <x-arrow-x.form.input
                            label="ชื่อผู้ใช้"
                            name="username"
                            icon="fa-user"
                            placeholder="กรอกชื่อผู้ใช้"
                            :required="true"
                            help="ใช้ตัวอักษร a-z, 0-9 และ _ เท่านั้น"
                        />

                        <x-arrow-x.form.input
                            label="อีเมล"
                            type="email"
                            name="email"
                            icon="fa-envelope"
                            placeholder="example@domain.com"
                            :required="true"
                        />

                        <x-arrow-x.form.input
                            label="รหัสผ่าน"
                            type="password"
                            name="password"
                            icon="fa-lock"
                            placeholder="••••••••"
                            error="รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร"
                        />

                        {{-- Select --}}
                        <x-arrow-x.form.select
                            label="ประเภทผู้ใช้"
                            name="role"
                            :options="['admin' => 'ผู้ดูแลระบบ', 'user' => 'ผู้ใช้ทั่วไป', 'seller' => 'ผู้ขาย']"
                            :required="true"
                        />

                        {{-- Checkbox --}}
                        <x-arrow-x.form.checkbox
                            label="ฉันยอมรับข้อกำหนดและเงื่อนไข"
                            name="terms"
                            description="กรุณาอ่านข้อกำหนดและเงื่อนไขก่อนดำเนินการต่อ"
                        />

                        {{-- Submit Button --}}
                        <div class="flex gap-3">
                            <x-arrow-x.button variant="gradient" type="submit" icon="fa-save">
                                บันทึก
                            </x-arrow-x.button>
                            <x-arrow-x.button variant="outline" type="button">
                                ยกเลิก
                            </x-arrow-x.button>
                        </div>
                    </form>
                </x-arrow-x.card.info>
            </section>

            {{-- 6. Table Component --}}
            <section>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">6. Table Component</h2>

                <x-arrow-x.table
                    :headers="['#', 'ชื่อ', 'อีเมล', 'สถานะ', 'บทบาท', 'จัดการ']"
                    :striped="true"
                    :hover="true"
                    class="arrow-x-table"
                >
                    <x-slot:caption>รายชื่อผู้ใช้งาน</x-slot:caption>

                    <tr>
                        <td>1</td>
                        <td>สมชาย ใจดี</td>
                        <td>somchai@example.com</td>
                        <td><x-arrow-x.badge color="success" :dot="true">Active</x-arrow-x.badge></td>
                        <td><x-arrow-x.badge variant="soft" color="purple">Admin</x-arrow-x.badge></td>
                        <td>
                            <x-arrow-x.button size="xs" variant="outline" icon="fa-edit">แก้ไข</x-arrow-x.button>
                        </td>
                    </tr>

                    <tr>
                        <td>2</td>
                        <td>สมหญิง รักสงบ</td>
                        <td>somying@example.com</td>
                        <td><x-arrow-x.badge color="success" :dot="true">Active</x-arrow-x.badge></td>
                        <td><x-arrow-x.badge variant="soft" color="blue">User</x-arrow-x.badge></td>
                        <td>
                            <x-arrow-x.button size="xs" variant="outline" icon="fa-edit">แก้ไข</x-arrow-x.button>
                        </td>
                    </tr>

                    <tr>
                        <td>3</td>
                        <td>วิชัย เก่งกล้า</td>
                        <td>wichai@example.com</td>
                        <td><x-arrow-x.badge color="warning" :dot="true">Away</x-arrow-x.badge></td>
                        <td><x-arrow-x.badge variant="soft" color="green">Seller</x-arrow-x.badge></td>
                        <td>
                            <x-arrow-x.button size="xs" variant="outline" icon="fa-edit">แก้ไข</x-arrow-x.button>
                        </td>
                    </tr>
                </x-arrow-x.table>
            </section>

            {{-- 7. Modal Component --}}
            <section>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">7. Modal Component</h2>

                <div class="space-y-4">
                    <x-arrow-x.button
                        variant="gradient"
                        icon="fa-window-restore"
                        @click="$dispatch('open-modal', 'exampleModal')"
                    >
                        เปิด Modal
                    </x-arrow-x.button>

                    {{-- Modal Definition --}}
                    <x-arrow-x.modal id="exampleModal" title="ตัวอย่าง Modal" size="md">
                        <div class="space-y-4">
                            <p class="text-gray-600 dark:text-gray-400">
                                นี่คือตัวอย่าง Modal Component ของ Arrow X Theme System
                            </p>
                            <x-arrow-x.alert type="info">
                                Modal รองรับ Alpine.js และมี transition animations
                            </x-arrow-x.alert>
                        </div>

                        <x-slot:footer>
                            <x-arrow-x.button variant="outline" @click="$dispatch('close-modal', 'exampleModal')">
                                ปิด
                            </x-arrow-x.button>
                            <x-arrow-x.button variant="gradient" icon="fa-check">
                                ตกลง
                            </x-arrow-x.button>
                        </x-slot:footer>
                    </x-arrow-x.modal>
                </div>
            </section>

        </main>

        {{-- Footer --}}
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
            <div class="container mx-auto px-4 py-8 text-center text-gray-600 dark:text-gray-400">
                <p>&copy; 2025 Arrow X Theme System - All Components Showcase</p>
            </div>
        </footer>
    </div>

    {{-- Alpine.js (CDN for demo) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
