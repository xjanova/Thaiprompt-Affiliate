{{--
    หน้าจัดการ Topic Boundaries
    กำหนดขอบเขตหัวข้อที่ AI สามารถคุยได้
--}}
@extends('layouts.admin')

@section('title', 'จัดการขอบเขตหัวข้อ - LINE Recruitment')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="topicBoundariesManager()">
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
                        <span>ขอบเขตหัวข้อ</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการขอบเขตหัวข้อ</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">AI Setting: {{ $aiSetting->name }}</p>
                </div>
                <button @click="showAddModal = true; editMode = false; resetForm()"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    เพิ่มขอบเขตใหม่
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Test Topic Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ทดสอบ Topic Filter</h3>
            <div class="flex gap-4">
                <input type="text" x-model="testMessage"
                       placeholder="พิมพ์ข้อความเพื่อทดสอบ..."
                       class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                <button @click="testFilter()"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    ทดสอบ
                </button>
            </div>
            <div x-show="testResult" x-transition class="mt-4 p-4 rounded-lg" :class="testResult.allowed ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
                <p class="font-medium" :class="testResult.allowed ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                    <span x-text="testResult.allowed ? 'อนุญาต' : 'ถูกกรอง'"></span>
                    <span x-show="testResult.topic_name" class="ml-2">(หัวข้อ: <span x-text="testResult.topic_name"></span>)</span>
                </p>
                <p x-show="testResult.response" class="text-sm mt-2 text-gray-600 dark:text-gray-400" x-text="testResult.response"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Blocked Topics --}}
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">หัวข้อที่ห้ามคุย ({{ $blockedTopics->count() }})</h2>
                </div>
                <div class="space-y-4">
                    @forelse($blockedTopics as $topic)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-900/50 p-4 hover:shadow-lg transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $topic->name }}</h3>
                                        @if($topic->is_active)
                                            <span class="px-2 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Active</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 rounded-full">Inactive</span>
                                        @endif
                                    </div>
                                    @if($topic->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $topic->description }}</p>
                                    @endif
                                    @if($topic->keywords)
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach($topic->keywords as $keyword)
                                                <span class="px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">{{ $keyword }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Priority: {{ $topic->priority }}</span>
                                        <span>Hit: {{ $topic->hit_count }} ครั้ง</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <button @click="editTopic({{ $topic->toJson() }})"
                                            class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form action="{{ route('admin.line-recruitment.topic-boundaries.delete', [$aiSetting->id, $topic->id]) }}"
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
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            <p>ยังไม่มีหัวข้อที่ห้ามคุย</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Allowed Topics --}}
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">หัวข้อที่อนุญาต ({{ $allowedTopics->count() }})</h2>
                </div>
                <div class="space-y-4">
                    @forelse($allowedTopics as $topic)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-green-200 dark:border-green-900/50 p-4 hover:shadow-lg transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $topic->name }}</h3>
                                        @if($topic->is_active)
                                            <span class="px-2 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Active</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 rounded-full">Inactive</span>
                                        @endif
                                    </div>
                                    @if($topic->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $topic->description }}</p>
                                    @endif
                                    @if($topic->keywords)
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach($topic->keywords as $keyword)
                                                <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">{{ $keyword }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Priority: {{ $topic->priority }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <button @click="editTopic({{ $topic->toJson() }})"
                                            class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form action="{{ route('admin.line-recruitment.topic-boundaries.delete', [$aiSetting->id, $topic->id]) }}"
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
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p>ยังไม่มีหัวข้อที่อนุญาต</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Add/Edit Modal --}}
    <div x-show="showAddModal" x-transition
         class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-90 transition-opacity" @click="showAddModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" x-text="editMode ? 'แก้ไขขอบเขตหัวข้อ' : 'เพิ่มขอบเขตหัวข้อใหม่'"></h3>
                <form :action="editMode ? '{{ route('admin.line-recruitment.topic-boundaries.update', [$aiSetting->id, '']) }}/' + form.id : '{{ route('admin.line-recruitment.topic-boundaries.store', $aiSetting->id) }}'" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="editMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อหัวข้อ *</label>
                        <input type="text" name="name" x-model="form.name" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                        <textarea name="description" x-model="form.description" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท *</label>
                        <select name="type" x-model="form.type" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                            <option value="blocked">ห้ามคุย (Blocked)</option>
                            <option value="allowed">อนุญาต (Allowed)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำหลัก (คั่นด้วยเครื่องหมาย ,)</label>
                        <input type="text" name="keywords" x-model="form.keywords"
                               placeholder="คำหลัก1, คำหลัก2, คำหลัก3"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                    </div>

                    <div x-show="form.type === 'blocked'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความตอบกลับเมื่อพบหัวข้อนี้</label>
                        <textarea name="response_message" x-model="form.response_message" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                            <input type="number" name="priority" x-model="form.priority" min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
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
    function topicBoundariesManager() {
        return {
            showAddModal: false,
            editMode: false,
            testMessage: '',
            testResult: null,
            form: {
                id: null,
                name: '',
                description: '',
                type: 'blocked',
                keywords: '',
                response_message: '',
                priority: 0,
                is_active: true
            },
            resetForm() {
                this.form = {
                    id: null,
                    name: '',
                    description: '',
                    type: 'blocked',
                    keywords: '',
                    response_message: '',
                    priority: 0,
                    is_active: true
                };
            },
            editTopic(topic) {
                this.editMode = true;
                this.form = {
                    id: topic.id,
                    name: topic.name,
                    description: topic.description || '',
                    type: topic.type,
                    keywords: topic.keywords ? topic.keywords.join(', ') : '',
                    response_message: topic.response_message || '',
                    priority: topic.priority,
                    is_active: topic.is_active
                };
                this.showAddModal = true;
            },
            async testFilter() {
                if (!this.testMessage) return;

                try {
                    const response = await fetch('{{ route('admin.line-recruitment.test-topic-filter', $aiSetting->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message: this.testMessage })
                    });

                    this.testResult = await response.json();
                } catch (error) {
                    console.error('Error:', error);
                }
            }
        }
    }
</script>
@endpush
@endsection
