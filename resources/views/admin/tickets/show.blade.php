@extends('layouts.admin')

@section('title', 'รายละเอียด Ticket #' . $ticket->ticket_number)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">{{ $ticket->subject }}</h2>
                <p class="text-indigo-100 text-sm font-mono">Ticket #{{ $ticket->ticket_number }}</p>
            </div>
            <a href="{{ route('admin.tickets.index') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg transition-all">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                กลับ
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Original Message -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <div class="flex items-start space-x-4 mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-lg font-bold">
                            {{ substr($ticket->user->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $ticket->user->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $ticket->user->email }}</p>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $ticket->description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Replies -->
            @foreach($ticket->replies as $reply)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 {{ $reply->is_internal_note ? 'border-l-4 border-yellow-500' : '' }}">
                    @if($reply->is_internal_note)
                        <div class="mb-3 inline-flex items-center px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-xs font-medium">
                            <i class="fa-solid fa-lock mr-1"></i>
                            บันทึกภายใน (เฉพาะพนักงาน)
                        </div>
                    @endif

                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full {{ $reply->isFromStaff() ? 'bg-gradient-to-br from-purple-500 to-pink-600' : 'bg-gradient-to-br from-blue-500 to-indigo-600' }} flex items-center justify-center text-white text-lg font-bold">
                                {{ substr($reply->user->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ $reply->user->name }}
                                        @if($reply->isFromStaff())
                                            <span class="ml-2 inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs font-medium">
                                                <i class="fa-solid fa-shield-halved mr-1"></i>
                                                พนักงาน
                                            </span>
                                        @endif
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $reply->user->email }}</p>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $reply->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="prose dark:prose-invert max-w-none">
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reply->message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Reply Form -->
            @if(!$ticket->isClosed())
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        <i class="fa-solid fa-reply mr-2"></i>
                        ตอบกลับ Ticket
                    </h3>
                    <form method="POST" action="{{ route('admin.tickets.reply', $ticket->id) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความ</label>
                            <textarea name="message" rows="6" required class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 resize-none" placeholder="พิมพ์ข้อความตอบกลับ..."></textarea>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="is_internal_note" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    <i class="fa-solid fa-lock mr-1"></i>
                                    บันทึกภายใน (เฉพาะพนักงานเห็น)
                                </span>
                            </label>
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg transition-all">
                                <i class="fa-solid fa-paper-plane mr-2"></i>
                                ส่งข้อความ
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-6 text-center">
                    <i class="fa-solid fa-lock text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-600 dark:text-gray-400">Ticket นี้ถูกปิดแล้ว ไม่สามารถตอบกลับได้</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Ticket Info -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fa-solid fa-info-circle mr-2"></i>
                    ข้อมูล Ticket
                </h3>

                <div class="space-y-4">
                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                        <form method="POST" action="{{ route('admin.tickets.update-status', $ticket->id) }}" x-data="{ status: '{{ $ticket->status }}' }">
                            @csrf
                            @method('PUT')
                            <select name="status" x-model="status" @change="$el.form.submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="open">เปิด</option>
                                <option value="in_progress">กำลังดำเนินการ</option>
                                <option value="waiting_customer">รอลูกค้า</option>
                                <option value="resolved">แก้ไขแล้ว</option>
                                <option value="closed">ปิด</option>
                            </select>
                        </form>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความสำคัญ</label>
                        <form method="POST" action="{{ route('admin.tickets.update-priority', $ticket->id) }}" x-data="{ priority: '{{ $ticket->priority }}' }">
                            @csrf
                            @method('PUT')
                            <select name="priority" x-model="priority" @change="$el.form.submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="low">ต่ำ</option>
                                <option value="medium">ปานกลาง</option>
                                <option value="high">สูง</option>
                                <option value="critical">วิกฤต</option>
                            </select>
                        </form>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมวดหมู่</label>
                        <form method="POST" action="{{ route('admin.tickets.update-category', $ticket->id) }}" x-data="{ category: '{{ $ticket->category_id }}' }">
                            @csrf
                            @method('PUT')
                            <select name="category_id" x-model="category" @change="$el.form.submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">เลือกหมวดหมู่</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <!-- Assigned To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">มอบหมายให้</label>
                        <form method="POST" action="{{ route('admin.tickets.assign', $ticket->id) }}" x-data="{ assigned: '{{ $ticket->assigned_to }}' }">
                            @csrf
                            <select name="assigned_to" x-model="assigned" @change="$el.form.submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">ไม่มอบหมาย</option>
                                @foreach($staffUsers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <!-- Divider -->
                    <hr class="border-gray-200 dark:border-slate-700">

                    <!-- Created At -->
                    <div>
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สร้างเมื่อ</span>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">{{ $ticket->created_at->diffForHumans() }}</p>
                    </div>

                    <!-- Last Reply -->
                    @if($ticket->last_reply_at)
                        <div>
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตอบกลับล่าสุด</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $ticket->last_reply_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">{{ $ticket->last_reply_at->diffForHumans() }}</p>
                        </div>
                    @endif

                    <!-- Resolved At -->
                    @if($ticket->resolved_at)
                        <div>
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แก้ไขเมื่อ</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $ticket->resolved_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">{{ $ticket->resolved_at->diffForHumans() }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- User Info -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fa-solid fa-user mr-2"></i>
                    ผู้ใช้
                </h3>
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-2xl font-bold">
                        {{ substr($ticket->user->name, 0, 1) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white">{{ $ticket->user->name }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $ticket->user->email }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.users.show', $ticket->user->id) }}" class="block w-full text-center px-4 py-2 bg-indigo-100 dark:bg-indigo-900 hover:bg-indigo-200 dark:hover:bg-indigo-800 text-indigo-700 dark:text-indigo-200 rounded-lg transition-colors">
                    <i class="fa-solid fa-external-link-alt mr-2"></i>
                    ดูโปรไฟล์
                </a>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fa-solid fa-bolt mr-2"></i>
                    การดำเนินการ
                </h3>
                <div class="space-y-3">
                    <form method="POST" action="{{ route('admin.tickets.destroy', $ticket->id) }}" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ Ticket นี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            <i class="fa-solid fa-trash mr-2"></i>
                            ลบ Ticket
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
