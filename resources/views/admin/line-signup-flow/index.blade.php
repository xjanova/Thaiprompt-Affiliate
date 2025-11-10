{{--
    หน้าจัดการ LINE Signup Flow

    หน้านี้ใช้สำหรับ:
    - แสดงรายการ Signup Flow Steps ทั้งหมด
    - จัดเรียงลำดับขั้นตอน (Drag & Drop)
    - สร้าง/แก้ไข/ลบ Flow Steps
    - เปิด/ปิดใช้งาน Steps

    💡 Tips การใช้งาน:
    - ลากขั้นตอนเพื่อเปลี่ยนลำดับ
    - ตั้งค่า Input Type ให้เหมาะสมกับข้อมูลที่ต้องการ
    - ใช้ "Next Step" เพื่อเชื่อมโยงขั้นตอนถัดไป
    - เปิด "Require AI" สำหรับขั้นตอนที่ต้องการ AI ช่วยตอบ

    📚 คู่มือ: LINE_CHATBOT_MLM_SIGNUP.md
--}}

@extends('layouts.admin')
@section('title', 'จัดการ Signup Flow')

@push('styles')
<style>
    /**
     * Custom Styles สำหรับ Signup Flow Management
     * รองรับ Dark/Light Mode, Responsive และ Drag & Drop
     */

    /* Flow Card Styles */
    .flow-card {
        transition: all 0.3s ease;
        border-left: 4px solid #06C755;
        cursor: move;
    }

    .flow-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(6, 199, 85, 0.2);
    }

    .dark .flow-card:hover {
        box-shadow: 0 8px 20px rgba(6, 199, 85, 0.4);
    }

    .flow-card.inactive {
        border-left-color: #9ca3af;
        opacity: 0.6;
    }

    .flow-card.dragging {
        opacity: 0.5;
        transform: rotate(2deg);
    }

    /* Badge Styles */
    .input-type-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        gap: 4px;
    }

    .badge-text { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
    .badge-phone { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .badge-email { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
    .badge-name { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
    .badge-confirm { background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; }
    .badge-choice { background: linear-gradient(135deg, #ec4899, #db2777); color: white; }
    .badge-none { background: #e5e7eb; color: #6b7280; }

    .dark .badge-none {
        background: #374151;
        color: #9ca3af;
    }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        gap: 6px;
    }

    .status-badge.active {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .status-badge.inactive {
        background: #e5e7eb;
        color: #6b7280;
    }

    .dark .status-badge.inactive {
        background: #374151;
        color: #9ca3af;
    }

    /* Action Buttons */
    .action-btn {
        @apply px-4 py-2 rounded-lg font-semibold transition-all duration-200;
        @apply min-w-[44px] min-h-[44px] flex items-center justify-center;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Drag Handle */
    .drag-handle {
        cursor: grab;
        padding: 8px;
        color: #9ca3af;
        transition: color 0.2s;
    }

    .drag-handle:hover {
        color: #06C755;
    }

    .drag-handle:active {
        cursor: grabbing;
    }

    /* Empty State */
    .empty-state {
        @apply text-center py-16;
    }

    /* Stat Card */
    .stat-card {
        @apply bg-gradient-to-br rounded-xl p-6 text-white;
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: scale(1.05);
    }

    /* Step Order Badge */
    .step-order {
        @apply w-10 h-10 rounded-full flex items-center justify-center;
        @apply bg-gradient-to-br from-green-400 to-green-600;
        @apply text-white font-bold text-lg;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .flow-card {
            @apply px-4 py-4;
        }

        .action-btn {
            @apply px-3 py-2 text-sm;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4" x-data="signupFlowManager()">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-3">
                <i class="fas fa-sitemap text-green-500"></i>
                LINE Signup Flow
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                จัดการขั้นตอนการสมัครสมาชิกผ่าน LINE Chatbot
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Back to LINE OA --}}
            <a href="{{ route('admin.line-oa.index') }}"
               class="action-btn bg-gray-500 hover:bg-gray-600 text-white">
                <i class="fas fa-arrow-left mr-2"></i>
                <span class="hidden md:inline">กลับ</span>
            </a>

            {{-- Create New Flow --}}
            <a href="{{ route('admin.signup-flow.create') }}"
               class="action-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white">
                <i class="fas fa-plus mr-2"></i>
                สร้าง Step ใหม่
            </a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card from-blue-500 to-blue-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Total Steps</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $flows->count() }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-list-ol"></i>
                </div>
            </div>
        </div>

        <div class="stat-card from-green-500 to-emerald-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Active Steps</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $flows->where('is_active', true)->count() }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card from-purple-500 to-purple-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">AI Enabled</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $flows->where('require_ai', true)->count() }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-robot"></i>
                </div>
            </div>
        </div>

        <div class="stat-card from-orange-500 to-red-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Skippable</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $flows->where('is_skippable', true)->count() }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-forward"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-lg mb-6">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-500 text-xl mt-1"></i>
            <div class="flex-1">
                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-1">
                    💡 วิธีใช้งาน
                </h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <li>• <strong>ลาก</strong> การ์ดเพื่อเปลี่ยนลำดับขั้นตอน</li>
                    <li>• <strong>Input Type</strong> จะกำหนดประเภทข้อมูลที่รับจากผู้ใช้</li>
                    <li>• <strong>Next Step</strong> จะเชื่อมโยงไปยังขั้นตอนถัดไป</li>
                    <li>• เปิด <strong>Require AI</strong> หากต้องการให้ AI ช่วยตอบคำถาม</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Signup Flow List --}}
    <div id="flowList" class="space-y-4">
        @forelse($flows as $flow)
        <div class="flow-card {{ $flow->is_active ? 'active' : 'inactive' }}
                    bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700"
             data-id="{{ $flow->id }}"
             draggable="true">

            <div class="flex items-start gap-4">
                {{-- Drag Handle --}}
                <div class="drag-handle flex-shrink-0">
                    <i class="fas fa-grip-vertical text-2xl"></i>
                </div>

                {{-- Step Order Badge --}}
                <div class="step-order flex-shrink-0">
                    {{ $flow->step_order }}
                </div>

                {{-- Flow Content --}}
                <div class="flex-1 min-w-0">
                    {{-- Header --}}
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                                    {{ $flow->name }}
                                </h3>
                                <span class="status-badge {{ $flow->is_active ? 'active' : 'inactive' }}">
                                    <i class="fas fa-circle text-xs"></i>
                                    {{ $flow->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">
                                    {{ $flow->step_key }}
                                </code>
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.signup-flow.edit', $flow->id) }}"
                               class="action-btn bg-blue-500 hover:bg-blue-600 text-white text-sm">
                                <i class="fas fa-edit"></i>
                            </a>

                            <button @click="deleteFlow({{ $flow->id }})"
                                    class="action-btn bg-red-500 hover:bg-red-600 text-white text-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Message Preview --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $flow->message_text }}
                        </p>
                    </div>

                    {{-- Flow Metadata --}}
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        {{-- Input Type --}}
                        <div class="flex items-center gap-2">
                            <span class="text-gray-500 dark:text-gray-400">Input:</span>
                            <span class="input-type-badge badge-{{ $flow->input_type }}">
                                @php
                                    $inputIcons = [
                                        'text' => 'fa-keyboard',
                                        'phone' => 'fa-phone',
                                        'email' => 'fa-envelope',
                                        'name' => 'fa-user',
                                        'confirm' => 'fa-check-double',
                                        'choice' => 'fa-list',
                                        'none' => 'fa-ban',
                                    ];
                                @endphp
                                <i class="fas {{ $inputIcons[$flow->input_type] ?? 'fa-question' }}"></i>
                                {{ ucfirst($flow->input_type) }}
                            </span>
                        </div>

                        {{-- Next Step --}}
                        @if($flow->next_step_key)
                        <div class="flex items-center gap-2">
                            <span class="text-gray-500 dark:text-gray-400">Next:</span>
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs font-medium">
                                <i class="fas fa-arrow-right mr-1"></i>
                                {{ $flow->next_step_key }}
                            </span>
                        </div>
                        @endif

                        {{-- Skippable --}}
                        @if($flow->is_skippable)
                        <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded text-xs font-medium">
                            <i class="fas fa-forward mr-1"></i>
                            Skippable
                        </span>
                        @endif

                        {{-- AI Required --}}
                        @if($flow->require_ai)
                        <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs font-medium">
                            <i class="fas fa-robot mr-1"></i>
                            AI Enabled
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state bg-white dark:bg-gray-800 rounded-xl shadow-md p-12 border border-gray-100 dark:border-gray-700">
            <i class="fas fa-sitemap text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-300 mb-2">
                ยังไม่มี Signup Flow
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                เริ่มสร้างขั้นตอนการสมัครสมาชิกแรกของคุณ
            </p>
            <a href="{{ route('admin.signup-flow.create') }}"
               class="action-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white inline-flex">
                <i class="fas fa-plus mr-2"></i>
                สร้าง Step แรก
            </a>
        </div>
        @endforelse
    </div>

</div>
@endsection

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับ Signup Flow Manager
 *
 * Features:
 * - Drag & Drop Reordering
 * - Delete Flow with Confirmation
 * - AJAX Operations
 */
function signupFlowManager() {
    return {
        draggedElement: null,

        /**
         * Initialize Drag & Drop
         */
        init() {
            this.initDragAndDrop();
        },

        /**
         * Initialize Drag and Drop functionality
         *
         * ทำให้สามารถลากการ์ดเพื่อเปลี่ยนลำดับได้
         */
        initDragAndDrop() {
            const flowList = document.getElementById('flowList');
            const cards = flowList.querySelectorAll('.flow-card');

            cards.forEach(card => {
                // เริ่มลาก
                card.addEventListener('dragstart', (e) => {
                    this.draggedElement = card;
                    card.classList.add('dragging');
                });

                // สิ้นสุดการลาก
                card.addEventListener('dragend', (e) => {
                    card.classList.remove('dragging');
                });

                // ลากผ่าน
                card.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const afterElement = this.getDragAfterElement(flowList, e.clientY);
                    if (afterElement == null) {
                        flowList.appendChild(this.draggedElement);
                    } else {
                        flowList.insertBefore(this.draggedElement, afterElement);
                    }
                });
            });

            // เมื่อวางการ์ด ให้บันทึกลำดับใหม่
            flowList.addEventListener('drop', async (e) => {
                e.preventDefault();
                await this.saveOrder();
            });
        },

        /**
         * หา element ที่ควรจะวางการ์ดไว้ข้างหลัง
         */
        getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.flow-card:not(.dragging)')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;

                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        },

        /**
         * บันทึกลำดับใหม่ไปยัง server
         */
        async saveOrder() {
            const cards = document.querySelectorAll('.flow-card');
            const order = Array.from(cards).map(card => card.dataset.id);

            try {
                const response = await fetch('{{ route('admin.signup-flow.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ order })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('บันทึกลำดับสำเร็จ!', 'success');

                    // อัปเดตหมายเลขลำดับ
                    cards.forEach((card, index) => {
                        const badge = card.querySelector('.step-order');
                        if (badge) {
                            badge.textContent = index;
                        }
                    });
                } else {
                    this.showToast('เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                console.error('Error saving order:', error);
                this.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
        },

        /**
         * ลบ Flow (พร้อม Confirmation)
         *
         * @param {number} flowId - ID ของ flow ที่ต้องการลบ
         */
        async deleteFlow(flowId) {
            if (!confirm('ต้องการลบ Signup Flow นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
                return;
            }

            try {
                const response = await fetch(`/admin/signup-flow/${flowId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    this.showToast('ลบ Flow สำเร็จ!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const data = await response.json();
                    this.showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                console.error('Error deleting flow:', error);
                this.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
        },

        /**
         * แสดง Toast Notification
         *
         * @param {string} message - ข้อความที่จะแสดง
         * @param {string} type - ประเภท (success, error, info, warning)
         */
        showToast(message, type = 'info') {
            // สามารถใช้ library toast อื่นๆ ได้ เช่น toastr, sweetalert
            // ตอนนี้ใช้ alert ธรรมดาก่อน
            alert(message);
        }
    }
}
</script>
@endpush
