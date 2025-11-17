@extends('layouts.admin-v3')

@section('title', 'รายละเอียด Ticket #' . $ticket->ticket_number)

@section('content')
<div class="space-y-6">
    <!-- Header with Priority Color -->
    <div class="relative overflow-hidden rounded-2xl shadow-2xl p-8 text-white
        {{ $ticket->priority === 'critical' ? 'bg-gradient-to-r from-red-600 via-rose-600 to-pink-600 dark:from-red-800 dark:via-rose-800 dark:to-pink-800' : '' }}
        {{ $ticket->priority === 'high' ? 'bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 dark:from-orange-800 dark:via-amber-800 dark:to-yellow-800' : '' }}
        {{ $ticket->priority === 'medium' ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-800 dark:via-purple-800 dark:to-pink-800' : '' }}
        {{ $ticket->priority === 'low' ? 'bg-gradient-to-r from-gray-600 via-slate-600 to-zinc-600 dark:from-gray-800 dark:via-slate-800 dark:to-zinc-800' : '' }}">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 glass-fusion dark:bg-gray-800 rounded-full opacity-10 blur-3xl" border border-white/20 dark:border-white/10></div>
        <div class="relative flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-3">
                    <span class="px-4 py-2 glass-fusion backdrop-blur-sm rounded-xl text-sm font-bold">
                        #{{ $ticket->ticket_number }}
                    </span>
                    <span class="px-4 py-2 glass-fusion backdrop-blur-sm rounded-xl text-sm font-bold">
                        {{ $ticket->priority === 'critical' ? '🔴 วิกฤต' : '' }}
                        {{ $ticket->priority === 'high' ? '🟠 สูง' : '' }}
                        {{ $ticket->priority === 'medium' ? '🟡 ปานกลาง' : '' }}
                        {{ $ticket->priority === 'low' ? '⚪ ต่ำ' : '' }}
                    </span>
                    <span class="px-4 py-2 glass-fusion backdrop-blur-sm rounded-xl text-sm font-bold">
                        {{ $ticket->status === 'open' ? '🟢 เปิด' : '' }}
                        {{ $ticket->status === 'in_progress' ? '🔵 กำลังดำเนินการ' : '' }}
                        {{ $ticket->status === 'waiting_customer' ? '🟣 รอลูกค้า' : '' }}
                        {{ $ticket->status === 'resolved' ? '✅ แก้ไขแล้ว' : '' }}
                        {{ $ticket->status === 'closed' ? '⚫ ปิด' : '' }}
                    </span>
                </div>
                <h1 class="text-3xl font-bold mb-2">{{ $ticket->subject }}</h1>
                <p class="text-white/80 text-sm">
                    สร้างโดย {{ $ticket->user->name }} • {{ $ticket->created_at->format('d/m/Y H:i') }} • {{ $ticket->created_at->diffForHumans() }}
                </p>
            </div>
            <a href="{{ route('admin.tickets.index') }}" class="px-6 py-3 glass-fusion hover:glass-fusion backdrop-blur-sm rounded-xl transition-all flex items-center gap-2 font-semibold">
                <i class="fa-solid fa-arrow-left"></i>
                กลับ
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content (Conversation Thread) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Original Message -->
            <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl font-bold shadow-lg">
                            {{ substr($ticket->user->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $ticket->user->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $ticket->user->email }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $ticket->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400">{{ $ticket->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                        <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 rounded-xl p-4 mt-3">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $ticket->description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Replies Timeline -->
            @foreach($ticket->replies as $index => $reply)
                <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6
                    {{ $reply->is_internal_note ? 'border-l-4 border-yellow-500 bg-yellow-50/50 dark:bg-yellow-900/10' : '' }}
                    {{ $reply->isFromStaff() && !$reply->is_internal_note ? 'border-l-4 border-purple-500' : '' }}
                    {{ !$reply->isFromStaff() ? 'border-l-4 border-green-500' : '' }}" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>

                    @if($reply->is_internal_note)
                        <div class="mb-3 inline-flex items-center gap-2 px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-xs font-bold">
                            <i class="fa-solid fa-lock"></i>
                            บันทึกภายใน (เฉพาะพนักงาน)
                        </div>
                    @endif

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold shadow-lg
                                {{ $reply->isFromStaff() ? 'bg-gradient-to-br from-purple-500 to-pink-600' : 'style="background: var(--arrow-x-success-gradient)"' }}">
                                {{ substr($reply->user->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ $reply->user->name }}
                                        </h3>
                                        @if($reply->isFromStaff())
                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs font-bold">
                                                <i class="fa-solid fa-shield-halved"></i>
                                                พนักงาน
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded text-xs font-bold">
                                                <i class="fa-solid fa-user"></i>
                                                ลูกค้า
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">{{ $reply->user->email }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $reply->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400">{{ $reply->created_at->format('H:i') }}</div>
                                </div>
                            </div>
                            <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 rounded-xl p-4 mt-3">
                                <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $reply->message }}</p>
                            </div>
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                {{ $reply->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Reply Form -->
            @if(!$ticket->isClosed())
                <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white">
                            <i class="fa-solid fa-reply text-lg"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            ตอบกลับ Ticket
                        </h3>
                    </div>

                    <form method="POST" action="{{ route('admin.tickets.reply', $ticket->id) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ข้อความ</label>
                            <textarea name="message" rows="6" required class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none" placeholder="พิมพ์ข้อความตอบกลับ..."></textarea>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 px-4 py-2 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-xl cursor-pointer hover:bg-yellow-200 dark:hover:bg-yellow-800 transition-colors">
                                <input type="checkbox" name="is_internal_note" value="1" class="rounded border-gray-300 dark:border-gray-600 text-yellow-600 focus:ring-yellow-500">
                                <span class="text-sm font-semibold flex items-center gap-1">
                                    <i class="fa-solid fa-lock"></i>
                                    บันทึกภายใน (เฉพาะพนักงานเห็น)
                                </span>
                            </label>
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg transition-all transform hover:scale-105">
                                <i class="fa-solid fa-paper-plane mr-2"></i>
                                ส่งข้อความ
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-700 rounded-xl p-8 text-center">
                    <i class="fa-solid fa-lock text-6xl text-gray-400 dark:text-gray-500 dark:text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Ticket นี้ถูกปิดแล้ว</h3>
                    <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400">ไม่สามารถตอบกลับได้ กรุณาเปิด Ticket ใหม่หากต้องการติดต่อ</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Ticket Info Card -->
            <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center text-white">
                        <i class="fa-solid fa-info-circle"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        ข้อมูล Ticket
                    </h3>
                </div>

                <div class="space-y-4">
                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">สถานะ</label>
                        <form method="POST" action="{{ route('admin.tickets.update-status', $ticket->id) }}" x-data="{ status: '{{ $ticket->status }}' }">
                            @csrf
                            @method('PUT')
                            <select name="status" x-model="status" @change="$el.form.submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 font-semibold">
                                <option value="open">🟢 เปิด</option>
                                <option value="in_progress">🔵 กำลังดำเนินการ</option>
                                <option value="waiting_customer">🟣 รอลูกค้า</option>
                                <option value="resolved">✅ แก้ไขแล้ว</option>
                                <option value="closed">⚫ ปิด</option>
                            </select>
                        </form>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ความสำคัญ</label>
                        <form method="POST" action="{{ route('admin.tickets.update-priority', $ticket->id) }}" x-data="{ priority: '{{ $ticket->priority }}' }">
                            @csrf
                            @method('PUT')
                            <select name="priority" x-model="priority" @change="$el.form.submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 font-semibold">
                                <option value="low">⚪ ต่ำ</option>
                                <option value="medium">🟡 ปานกลาง</option>
                                <option value="high">🟠 สูง</option>
                                <option value="critical">🔴 วิกฤต</option>
                            </select>
                        </form>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">หมวดหมู่</label>
                        <form method="POST" action="{{ route('admin.tickets.update-category', $ticket->id) }}" x-data="{ category: '{{ $ticket->category_id }}' }">
                            @csrf
                            @method('PUT')
                            <select name="category_id" x-model="category" @change="$el.form.submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 font-semibold">
                                <option value="">เลือกหมวดหมู่</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <!-- Assigned To -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">มอบหมายให้</label>
                        <form method="POST" action="{{ route('admin.tickets.assign', $ticket->id) }}" x-data="{ assigned: '{{ $ticket->assigned_to }}' }">
                            @csrf
                            <select name="assigned_to" x-model="assigned" @change="$el.form.submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 font-semibold">
                                <option value="">ไม่มอบหมาย</option>
                                @foreach($staffUsers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <!-- Divider -->
                    <hr class="border-gray-200 dark:border-gray-700 dark:border-gray-700">

                    <!-- Timeline Info -->
                    <div class="space-y-3">
                        <!-- Created At -->
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-clock text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400">สร้างเมื่อ</div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 dark:text-gray-400">{{ $ticket->created_at->diffForHumans() }}</div>
                            </div>
                        </div>

                        <!-- Last Reply -->
                        @if($ticket->last_reply_at)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-comment text-green-600 dark:text-green-400"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400">ตอบกลับล่าสุด</div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $ticket->last_reply_at->format('d/m/Y H:i') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 dark:text-gray-400">{{ $ticket->last_reply_at->diffForHumans() }}</div>
                            </div>
                        </div>
                        @endif

                        <!-- Resolved At -->
                        @if($ticket->resolved_at)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-check-circle text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400">แก้ไขเมื่อ</div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $ticket->resolved_at->format('d/m/Y H:i') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 dark:text-gray-400">{{ $ticket->resolved_at->diffForHumans() }}</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- User Info Card -->
            <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 style="background: var(--arrow-x-success-gradient)" rounded-xl flex items-center justify-center text-white">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        ข้อมูลผู้ใช้
                    </h3>
                </div>
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                        {{ substr($ticket->user->name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ $ticket->user->name }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">{{ $ticket->user->email }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.users.show', $ticket->user->id) }}" class="block w-full text-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-bold transition-all transform hover:scale-105">
                    <i class="fa-solid fa-external-link-alt mr-2"></i>
                    ดูโปรไฟล์ผู้ใช้
                </a>
            </div>

            <!-- Actions Card -->
            <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 style="background: var(--arrow-x-warning-gradient)" rounded-xl flex items-center justify-center text-white">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        การดำเนินการ
                    </h3>
                </div>
                <div class="space-y-3">
                    <form method="POST" action="{{ route('admin.tickets.destroy', $ticket->id) }}" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ Ticket นี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl font-bold transition-all transform hover:scale-105">
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
