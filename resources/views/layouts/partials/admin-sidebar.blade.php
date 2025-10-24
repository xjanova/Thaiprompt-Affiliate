<aside class="bg-gray-800 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out" :class="{'translate-x-0': sidebarOpen}">
    <!-- Logo -->
    <a href="{{ route('admin.dashboard') }}" class="text-white flex items-center space-x-2 px-4">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
        </svg>
        <span class="text-2xl font-extrabold">Admin</span>
    </a>

    <!-- Navigation -->
    <nav>
        <a href="{{ route('admin.dashboard') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700' : '' }}">
            แดชบอร์ด
        </a>

        <a href="{{ route('admin.users') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.users*') ? 'bg-gray-700' : '' }}">
            จัดการผู้ใช้
        </a>

        <a href="{{ route('admin.vendors.index') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.vendors*') ? 'bg-gray-700' : '' }}">
            จัดการร้านค้า
        </a>

        <a href="{{ route('admin.products') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.products*') ? 'bg-gray-700' : '' }}">
            จัดการสินค้า
        </a>

        <a href="{{ route('admin.orders') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.orders*') ? 'bg-gray-700' : '' }}">
            จัดการคำสั่งซื้อ
        </a>

        <a href="{{ route('admin.commissions') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.commissions*') ? 'bg-gray-700' : '' }}">
            จัดการค่าคอมมิชชั่น
        </a>

        <a href="{{ route('admin.withdrawals') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.withdrawals*') ? 'bg-gray-700' : '' }}">
            อนุมัติการถอนเงิน
        </a>

        <a href="{{ route('admin.settings') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white {{ request()->routeIs('admin.settings*') ? 'bg-gray-700' : '' }}">
            ตั้งค่าระบบ
        </a>

        <div class="border-t border-gray-700 my-4"></div>

        <a href="{{ url('/') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 hover:text-white">
            ไปยังหน้าเว็บไซต์
        </a>
    </nav>
</aside>
