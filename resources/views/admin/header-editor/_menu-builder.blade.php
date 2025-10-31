<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">

<div class="bg-white rounded-lg shadow-lg p-6" x-data="menuBuilder()" x-init="loadMenus()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">🍔 Menu Builder</h2>
            <p class="text-gray-600">จัดการเมนูหลักของเว็บไซต์ (Drag & Drop)</p>
        </div>
        <button @click="showAddModal = true"
                class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            เพิ่มเมนู
        </button>
    </div>

    <!-- Menu List -->
    <div id="menu-container" class="space-y-2">
        <template x-for="(menu, index) in menus" :key="menu.id">
            <div :data-id="menu.id"
                 class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 flex-1 cursor-move">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                        </svg>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span x-show="menu.icon" x-text="menu.icon" class="text-lg"></span>
                                <h3 class="font-semibold text-gray-900" x-text="menu.title"></h3>
                            </div>
                            <p class="text-sm text-gray-500" x-text="menu.url || menu.route || '-'"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="toggleMenuActive(menu)"
                                class="p-2 rounded hover:bg-gray-200"
                                :class="menu.is_active ? 'text-green-600' : 'text-gray-400'">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                            </svg>
                        </button>

                        <button @click="editMenu(menu)"
                                class="p-2 text-indigo-600 hover:bg-indigo-50 rounded">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>

                        <button @click="deleteMenu(menu.id)"
                                class="p-2 text-red-600 hover:bg-red-50 rounded">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Children -->
                <div x-show="menu.children && menu.children.length > 0" class="ml-8 mt-3 space-y-2">
                    <template x-for="child in menu.children" :key="child.id">
                        <div class="bg-white border border-gray-100 rounded p-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span x-text="child.title"></span>
                                <div class="flex gap-1">
                                    <button @click="editMenu(child)" class="p-1 text-indigo-600">✏️</button>
                                    <button @click="deleteMenu(child.id)" class="p-1 text-red-600">🗑️</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="menus.length === 0" class="text-center py-12 text-gray-400">
        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
        </svg>
        <p>ยังไม่มีเมนู</p>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showAddModal || editingMenu"
         x-transition
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         style="display: none;">
        <div @click.away="closeModal()"
             class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4" x-text="editingMenu ? 'แก้ไขเมนู' : 'เพิ่มเมนูใหม่'"></h2>

            <form @submit.prevent="saveMenu">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อเมนู *</label>
                        <input type="text"
                               x-model="formData.title"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                        <input type="text"
                               x-model="formData.url"
                               placeholder="/about หรือ https://example.com"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Laravel Route (ไม่บังคับ)</label>
                        <input type="text"
                               x-model="formData.route"
                               placeholder="home, about.index"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Icon (Emoji)</label>
                        <input type="text"
                               x-model="formData.icon"
                               placeholder="🏠"
                               maxlength="2"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">เปิดใน</label>
                        <select x-model="formData.target"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="_self">หน้าเดียวกัน (_self)</option>
                            <option value="_blank">แท็บใหม่ (_blank)</option>
                        </select>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox"
                               x-model="formData.is_active"
                               id="menu_active"
                               class="mr-2">
                        <label for="menu_active" class="text-sm text-gray-700">เปิดใช้งาน</label>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="button"
                            @click="closeModal()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            :disabled="saving"
                            class="flex-1 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50">
                        <span x-show="!saving">บันทึก</span>
                        <span x-show="saving">กำลังบันทึก...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function menuBuilder() {
    return {
        menus: @json($menuItems),
        showAddModal: false,
        editingMenu: null,
        saving: false,
        formData: {
            title: '',
            url: '',
            route: '',
            target: '_self',
            icon: '',
            is_active: true
        },
        sortable: null,

        async loadMenus() {
            try {
                const response = await fetch('{{ route('admin.header-editor.menu-items.index') }}');
                const data = await response.json();
                this.menus = data.items;
                this.initSortable();
            } catch (error) {
                console.error('Error loading menus:', error);
            }
        },

        initSortable() {
            const el = document.getElementById('menu-container');
            if (!el) return;

            this.sortable = Sortable.create(el, {
                animation: 150,
                handle: '.cursor-move',
                onEnd: () => {
                    this.reorderMenus();
                }
            });
        },

        async reorderMenus() {
            const items = Array.from(document.querySelectorAll('#menu-container > div')).map((el, index) => ({
                id: el.dataset.id,
                order: index
            }));

            try {
                await fetch('{{ route('admin.header-editor.menu-items.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ items })
                });
            } catch (error) {
                console.error('Error reordering:', error);
            }
        },

        editMenu(menu) {
            this.editingMenu = menu;
            this.formData = { ...menu };
            this.showAddModal = false;
        },

        closeModal() {
            this.showAddModal = false;
            this.editingMenu = null;
            this.formData = {
                title: '',
                url: '',
                route: '',
                target: '_self',
                icon: '',
                is_active: true
            };
        },

        async saveMenu() {
            this.saving = true;
            try {
                const url = this.editingMenu
                    ? `/admin/header-editor/menu-items/${this.editingMenu.id}`
                    : '{{ route('admin.header-editor.menu-items.store') }}';

                const method = this.editingMenu ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.formData)
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadMenus();
                    this.closeModal();
                    alert(data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
            this.saving = false;
        },

        async toggleMenuActive(menu) {
            try {
                const response = await fetch(`/admin/header-editor/menu-items/${menu.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ is_active: !menu.is_active })
                });

                const data = await response.json();
                if (data.success) {
                    menu.is_active = !menu.is_active;
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        },

        async deleteMenu(id) {
            if (!confirm('ต้องการลบเมนูนี้?')) return;

            try {
                const response = await fetch(`/admin/header-editor/menu-items/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadMenus();
                    alert(data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        }
    };
}
</script>
