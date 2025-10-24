<aside class="bg-indigo-800 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out" :class="{'translate-x-0': sidebarOpen}">
    <!-- Logo -->
    <a href="{{ route('vendor.dashboard') }}" class="text-white flex items-center space-x-2 px-4">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
        </svg>
        <span class="text-2xl font-extrabold">Vendor</span>
    </a>

    <!-- Navigation -->
    <nav>
        <a href="{{ route('vendor.dashboard') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 hover:text-white {{ request()->routeIs('vendor.dashboard') ? 'bg-indigo-700' : '' }}">
            แดชบอร์ด
        </a>

        <a href="{{ route('vendor.products.index') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 hover:text-white {{ request()->routeIs('vendor.products*') ? 'bg-indigo-700' : '' }}">
            สินค้าของฉัน
        </a>

        <a href="{{ route('vendor.sales') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 hover:text-white {{ request()->routeIs('vendor.sales*') ? 'bg-indigo-700' : '' }}">
            ยอดขาย
        </a>

        <a href="{{ route('vendor.earnings') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 hover:text-white {{ request()->routeIs('vendor.earnings*') ? 'bg-indigo-700' : '' }}">
            รายได้
        </a>

        <a href="{{ route('vendor.pos') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 hover:text-white {{ request()->routeIs('vendor.pos*') ? 'bg-indigo-700' : '' }}">
            POS (ขายหน้าร้าน)
        </a>

        <div class="border-t border-indigo-700 my-4"></div>

        <a href="{{ url('/') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 hover:text-white">
            ไปยังหน้าเว็บไซต์
        </a>
    </nav>
</aside>
