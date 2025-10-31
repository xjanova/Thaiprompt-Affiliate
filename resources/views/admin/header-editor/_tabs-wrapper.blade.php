<div x-data="{ activeTab: 'settings' }" class="mb-6">
    <!-- Tabs Header -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button @click="activeTab = 'settings'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                    :class="activeTab === 'settings' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                <span class="flex items-center gap-2">
                    <span class="text-xl">🎨</span>
                    <span>การตั้งค่า Header</span>
                </span>
            </button>

            <button @click="activeTab = 'template'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                    :class="activeTab === 'template' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                <span class="flex items-center gap-2">
                    <span class="text-xl">📄</span>
                    <span>เทมเพลตหน้าแรก</span>
                </span>
            </button>

            <button @click="activeTab = 'menu'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                    :class="activeTab === 'menu' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                <span class="flex items-center gap-2">
                    <span class="text-xl">🍔</span>
                    <span>จัดการเมนู</span>
                </span>
            </button>
        </nav>
    </div>

    <!-- Tabs Content -->
    <div class="mt-6">
        <!-- Header Settings Tab -->
        <div x-show="activeTab === 'settings'" x-transition>
            @include('admin.header-editor._header-settings')
        </div>

        <!-- Template Selector Tab -->
        <div x-show="activeTab === 'template'" x-transition x-data="templateSelector()">
            @include('admin.header-editor._template-selector')
        </div>

        <!-- Menu Builder Tab -->
        <div x-show="activeTab === 'menu'" x-transition>
            @include('admin.header-editor._menu-builder')
        </div>
    </div>
</div>
