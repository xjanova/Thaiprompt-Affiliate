{{--
    หน้าจัดการ Knowledge Base
    เพิ่มข้อมูลให้ AI จากหลายแหล่ง: ข้อความ, URL, ไฟล์
--}}
@extends('layouts.admin')

@section('title', 'จัดการแหล่งข้อมูล - LINE Recruitment')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="knowledgeBaseManager()">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                        <a href="{{ route('admin.line-recruitment.index') }}" class="hover:text-green-600">LINE Recruitment</a>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span>แหล่งข้อมูล</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการแหล่งข้อมูล (Knowledge Base)</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">AI Setting: {{ $aiSetting->name }}</p>
                </div>
                <button @click="showAddModal = true; editMode = false; resetForm()"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    เพิ่มแหล่งข้อมูลใหม่
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Info Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $knowledgeBases->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $knowledgeBases->where('is_active', true)->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">URL</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $knowledgeBases->where('type', 'url')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ไฟล์</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $knowledgeBases->where('type', 'file')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Knowledge Base List --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ชื่อ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">แหล่งที่มา</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($knowledgeBases as $kb)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg
                                            @if($kb->type === 'url') bg-purple-100 dark:bg-purple-900/30
                                            @elseif($kb->type === 'file') bg-orange-100 dark:bg-orange-900/30
                                            @else bg-blue-100 dark:bg-blue-900/30 @endif">
                                            @if($kb->type === 'url')
                                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                </svg>
                                            @elseif($kb->type === 'file')
                                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $kb->name }}</p>
                                            @if($kb->last_synced_at)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Sync: {{ $kb->last_synced_at->diffForHumans() }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $typeLabels = [
                                            'url' => 'URL',
                                            'file' => 'ไฟล์',
                                            'text' => 'ข้อความ',
                                            'internal' => 'ข้อมูลภายใน',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full
                                        @if($kb->type === 'url') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                        @elseif($kb->type === 'file') bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                        @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @endif">
                                        {{ $typeLabels[$kb->type] ?? $kb->type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($kb->type === 'url' && $kb->source)
                                        <a href="{{ $kb->source }}" target="_blank" class="text-sm text-blue-600 hover:underline truncate block max-w-xs">
                                            {{ Str::limit($kb->source, 40) }}
                                        </a>
                                    @elseif($kb->type === 'file' && $kb->file_path)
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ basename($kb->file_path) }}</span>
                                    @elseif($kb->content)
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($kb->content, 50) }}</span>
                                    @else
                                        <span class="text-sm text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $kb->priority }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($kb->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="editKnowledge({{ $kb->toJson() }})"
                                                class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <form action="{{ route('admin.line-recruitment.knowledge-base.delete', [$aiSetting->id, $kb->id]) }}"
                                              method="POST" class="inline" onsubmit="return confirm('ยืนยันการลบ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มีแหล่งข้อมูล</p>
                                    <button @click="showAddModal = true" class="mt-3 text-green-600 hover:underline">
                                        เพิ่มแหล่งข้อมูลแรก
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add/Edit Modal --}}
    <div x-show="showAddModal" x-transition
         class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-90 transition-opacity" @click="showAddModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" x-text="editMode ? 'แก้ไขแหล่งข้อมูล' : 'เพิ่มแหล่งข้อมูลใหม่'"></h3>
                <form :action="editMode ? '{{ route('admin.line-recruitment.knowledge-base.update', [$aiSetting->id, '']) }}/' + form.id : '{{ route('admin.line-recruitment.knowledge-base.store', $aiSetting->id) }}'"
                      method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <template x-if="editMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อแหล่งข้อมูล *</label>
                        <input type="text" name="name" x-model="form.name" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท *</label>
                        <select name="type" x-model="form.type" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                            <option value="text">ข้อความ</option>
                            <option value="url">URL (ลิ้งค์เอกสาร)</option>
                            <option value="file">ไฟล์ (PDF, TXT, DOC)</option>
                            <option value="internal">ข้อมูลภายใน</option>
                        </select>
                    </div>

                    <div x-show="form.type === 'url'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL แหล่งข้อมูล</label>
                        <input type="url" name="source" x-model="form.source"
                               placeholder="https://example.com/document"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                    </div>

                    <div x-show="form.type === 'file'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อัปโหลดไฟล์</label>
                        <input type="file" name="file" accept=".pdf,.txt,.doc,.docx,.csv,.json"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">รองรับ: PDF, TXT, DOC, DOCX, CSV, JSON (สูงสุด 10MB)</p>
                    </div>

                    <div x-show="form.type === 'text' || form.type === 'internal'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เนื้อหา</label>
                        <textarea name="content" x-model="form.content" rows="5"
                                  placeholder="ใส่ข้อมูลที่ต้องการให้ AI ใช้เป็นความรู้..."
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                            <input type="number" name="priority" x-model="form.priority" min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ยิ่งสูง ยิ่งใช้ก่อน</p>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" x-model="form.is_active" value="1"
                                       class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showAddModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                            <span x-text="editMode ? 'บันทึก' : 'เพิ่ม'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function knowledgeBaseManager() {
        return {
            showAddModal: false,
            editMode: false,
            form: {
                id: null,
                name: '',
                type: 'text',
                source: '',
                content: '',
                priority: 0,
                is_active: true
            },
            resetForm() {
                this.form = {
                    id: null,
                    name: '',
                    type: 'text',
                    source: '',
                    content: '',
                    priority: 0,
                    is_active: true
                };
            },
            editKnowledge(kb) {
                this.editMode = true;
                this.form = {
                    id: kb.id,
                    name: kb.name,
                    type: kb.type,
                    source: kb.source || '',
                    content: kb.content || '',
                    priority: kb.priority,
                    is_active: kb.is_active
                };
                this.showAddModal = true;
            }
        }
    }
</script>
@endpush
@endsection
