@extends('layouts.admin')

@section('title', 'Template Builder')

@section('content')
<div x-data="templateManager()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🎭 Template Builder</h1>
            <p class="mt-1 text-sm text-gray-600">สร้างและจัดการเทมเพลตสำหรับหน้าเว็บไซต์</p>
        </div>
        <div class="flex gap-3">
            <button @click="showImportModal = true"
                    class="flex items-center gap-2 px-4 py-2 bg-white border-2 border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span>นำเข้าเทมเพลต</span>
            </button>
            <button @click="showCreateModal = true"
                    class="flex items-center gap-2 px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>สร้างเทมเพลตใหม่</span>
            </button>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="mb-6 flex gap-4">
        <div class="flex-1 relative">
            <input type="text"
                   x-model="searchQuery"
                   placeholder="ค้นหาเทมเพลต..."
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <select x-model="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="all">ทั้งหมด</option>
            <option value="active">ใช้งาน</option>
            <option value="inactive">ไม่ใช้งาน</option>
            <option value="default">ดีฟอลต์</option>
        </select>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="template in filteredTemplates" :key="template.id">
            <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border-2"
                 :class="template.is_default ? 'border-green-500' : 'border-transparent hover:border-indigo-500'">
                <!-- Thumbnail -->
                <div class="relative h-48 bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 flex items-center justify-center overflow-hidden">
                    <template x-if="template.thumbnail">
                        <img :src="template.thumbnail" :alt="template.name" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!template.thumbnail">
                        <div class="text-6xl opacity-30">🎨</div>
                    </template>

                    <!-- Badges -->
                    <div class="absolute top-3 left-3 flex gap-2">
                        <template x-if="template.is_default">
                            <span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                ดีฟอลต์
                            </span>
                        </template>
                        <template x-if="template.is_in_use">
                            <span class="px-3 py-1 bg-blue-500 text-white text-xs font-bold rounded-full shadow-lg">
                                กำลังใช้งาน
                            </span>
                        </template>
                    </div>

                    <!-- Status Badge -->
                    <div class="absolute top-3 right-3">
                        <span x-show="template.is_active"
                              class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">
                            เปิดใช้งาน
                        </span>
                        <span x-show="!template.is_active"
                              class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-bold rounded-full">
                            ปิดใช้งาน
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-5">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-1" x-text="template.name"></h3>
                    <p class="text-sm text-gray-600 mb-4 line-clamp-2" x-text="template.description || 'ไม่มีคำอธิบาย'"></p>

                    <!-- Stats -->
                    <div class="flex items-center gap-4 mb-4 text-sm text-gray-500">
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <span x-text="template.sections_count + ' ส่วน'"></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="formatDate(template.updated_at)"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a :href="`/admin/templates/${template.id}`"
                           class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            แก้ไข
                        </a>

                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                                    class="p-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open"
                                 @click.away="open = false"
                                 x-transition
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-50"
                                 style="display: none;">

                                <template x-if="!template.is_default">
                                    <button @click="setDefault(template.id); open = false"
                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        ตั้งเป็นดีฟอลต์
                                    </button>
                                </template>

                                <button @click="duplicateTemplate(template.id); open = false"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    คัดลอก
                                </button>

                                <button @click="exportTemplate(template.id); open = false"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    ส่งออก JSON
                                </button>

                                <button @click="toggleActive(template.id); open = false"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    <span x-text="template.is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน'"></span>
                                </button>

                                <hr class="my-1">

                                <template x-if="!template.is_default && !template.is_in_use">
                                    <button @click="deleteTemplate(template.id); open = false"
                                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        ลบ
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="filteredTemplates.length === 0" class="text-center py-16">
        <div class="text-6xl mb-4">📄</div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">ไม่พบเทมเพลต</h3>
        <p class="text-gray-600 mb-6">เริ่มต้นสร้างเทมเพลตแรกของคุณเลย!</p>
        <button @click="showCreateModal = true"
                class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700">
            สร้างเทมเพลตใหม่
        </button>
    </div>

    <!-- Create Template Modal -->
    <div x-show="showCreateModal"
         x-transition
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         style="display: none;">
        <div @click.away="showCreateModal = false"
             class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">🎨 สร้างเทมเพลตใหม่</h2>

            <form @submit.prevent="createTemplate">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อเทมเพลต</label>
                    <input type="text"
                           x-model="newTemplate.name"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="เช่น Homepage Modern">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">คำอธิบาย (ไม่บังคับ)</label>
                    <textarea x-model="newTemplate.description"
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="อธิบายเกี่ยวกับเทมเพลตนี้..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            @click="showCreateModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            :disabled="creating"
                            class="flex-1 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50">
                        <span x-show="!creating">สร้าง</span>
                        <span x-show="creating">กำลังสร้าง...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Template Modal -->
    <div x-show="showImportModal"
         x-transition
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         style="display: none;">
        <div @click.away="showImportModal = false"
             class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">📥 นำเข้าเทมเพลต</h2>

            <form @submit.prevent="importTemplate">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อเทมเพลต</label>
                    <input type="text"
                           x-model="importData.name"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="ชื่อสำหรับเทมเพลตที่นำเข้า">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">JSON Data</label>
                    <textarea x-model="importData.template_data"
                              required
                              rows="8"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-xs"
                              placeholder='{"name":"Template Name","sections":[...]}'></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            @click="showImportModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            :disabled="importing"
                            class="flex-1 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50">
                        <span x-show="!importing">นำเข้า</span>
                        <span x-show="importing">กำลังนำเข้า...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function templateManager() {
    return {
        templates: @json($templates),
        searchQuery: '',
        statusFilter: 'all',
        showCreateModal: false,
        showImportModal: false,
        creating: false,
        importing: false,
        newTemplate: {
            name: '',
            description: '',
        },
        importData: {
            name: '',
            template_data: '',
        },

        get filteredTemplates() {
            return this.templates.filter(template => {
                // Search filter
                const matchesSearch = template.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                    (template.description && template.description.toLowerCase().includes(this.searchQuery.toLowerCase()));

                // Status filter
                let matchesStatus = true;
                if (this.statusFilter === 'active') matchesStatus = template.is_active;
                else if (this.statusFilter === 'inactive') matchesStatus = !template.is_active;
                else if (this.statusFilter === 'default') matchesStatus = template.is_default;

                return matchesSearch && matchesStatus;
            });
        },

        async createTemplate() {
            this.creating = true;
            try {
                const response = await fetch('{{ route('admin.templates.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.newTemplate)
                });

                const data = await response.json();
                if (data.success) {
                    window.location.href = `/admin/templates/${data.template.id}`;
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
            this.creating = false;
        },

        async importTemplate() {
            this.importing = true;
            try {
                const response = await fetch('{{ route('admin.templates.import') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.importData)
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
            this.importing = false;
        },

        async setDefault(id) {
            if (!confirm('ต้องการตั้งเป็นเทมเพลตดีฟอลต์?')) return;

            try {
                const response = await fetch(`/admin/templates/${id}/set-default`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        },

        async duplicateTemplate(id) {
            try {
                const response = await fetch(`/admin/templates/${id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        },

        async exportTemplate(id) {
            try {
                const response = await fetch(`/admin/templates/${id}/export`);
                const data = await response.json();

                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `template-${data.name.toLowerCase().replace(/\s+/g, '-')}.json`;
                a.click();
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        },

        async toggleActive(id) {
            const template = this.templates.find(t => t.id === id);
            try {
                const response = await fetch(`/admin/templates/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ is_active: !template.is_active })
                });

                const data = await response.json();
                if (data.success) {
                    template.is_active = !template.is_active;
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        },

        async deleteTemplate(id) {
            if (!confirm('ต้องการลบเทมเพลตนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) return;

            try {
                const response = await fetch(`/admin/templates/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.templates = this.templates.filter(t => t.id !== id);
                } else {
                    alert(data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));

            if (days === 0) return 'วันนี้';
            if (days === 1) return 'เมื่อวาน';
            if (days < 7) return days + ' วันที่แล้ว';
            if (days < 30) return Math.floor(days / 7) + ' สัปดาห์ที่แล้ว';
            return Math.floor(days / 30) + ' เดือนที่แล้ว';
        }
    };
}
</script>
@endsection
