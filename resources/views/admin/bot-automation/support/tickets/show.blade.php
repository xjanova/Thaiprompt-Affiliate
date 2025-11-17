@extends('layouts.admin-v3')

@section('title', 'รายละเอียดทิกเก็ต')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Language Switcher Component -->
    <div class="absolute top-0 right-0 z-10 mt-4 mr-4">
        <div class="relative inline-block" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="flex items-center gap-2 px-4 py-2 glass-fusion dark:bg-gray-800 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl shadow-sm hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span x-text="language === 'th' ? 'ไทย' : language === 'en' ? 'English' : language === 'zh' ? '中文' : '日本語'" class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300"></span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="open"
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 dark:border-gray-700 py-2 z-50" border border-white/20 dark:border-white/10
                style="display: none;"
            >
                <button @click="language = 'th'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇹🇭</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">ไทย (Thai)</span>
                </button>
                <button @click="language = 'en'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇬🇧</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">English</span>
                </button>
                <button @click="language = 'zh'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇨🇳</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">中文 (Chinese)</span>
                </button>
                <button @click="language = 'ja'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇯🇵</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">日本語 (Japanese)</span>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <span data-translate>ทิกเก็ต</span> #{{ $ticket->id ?? 'N/A' }}
            </h1>
            <a href="{{ route('admin.bot-automation.support.tickets.index') }}"
               class="flex items-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-xl transition shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span data-translate>กลับไปทิกเก็ต</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content (Left Side - 2 columns) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Ticket Details Card -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-blue-600 dark:text-cyan-400">
                            {{ $ticket->subject ?? 'N/A' }}
                        </h2>
                        <div class="flex gap-2">
                            @if(isset($ticket->priority))
                                @if($ticket->priority == 'high')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" data-translate>
                                        ความสำคัญสูง
                                    </span>
                                @elseif($ticket->priority == 'medium')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200" data-translate>
                                        ความสำคัญปานกลาง
                                    </span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200" data-translate>
                                        ความสำคัญต่ำ
                                    </span>
                                @endif
                            @endif

                            @if(isset($ticket->status))
                                @if($ticket->status == 'open')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200" data-translate>
                                        เปิด
                                    </span>
                                @elseif($ticket->status == 'in_progress')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" data-translate>
                                        กำลังดำเนินการ
                                    </span>
                                @elseif($ticket->status == 'resolved')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200" data-translate>
                                        แก้ไขแล้ว
                                    </span>
                                @elseif($ticket->status == 'closed')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-300" data-translate>
                                        ปิด
                                    </span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Customer & Bot Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>
                                    ส่งโดย
                                </h3>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ $ticket->user_name ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                    {{ $ticket->user_email ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>
                                    บอท
                                </h3>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ $ticket->bot_name ?? 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <!-- Category & Created Date -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>
                                    หมวดหมู่
                                </h3>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ ucfirst($ticket->category ?? 'N/A') }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>
                                    สร้างเมื่อ
                                </h3>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ isset($ticket->created_at) ? $ticket->created_at->format('M d, Y h:i A') : 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <hr class="border-gray-200 dark:border-gray-700 dark:border-gray-700">

                        <!-- Description -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-3" data-translate>
                                คำอธิบาย
                            </h3>
                            <p class="text-gray-900 dark:text-gray-100 leading-relaxed">
                                {{ $ticket->description ?? 'ไม่มีคำอธิบาย' }}
                            </p>
                        </div>

                        <!-- Attachments -->
                        @if(isset($ticket->attachments) && count($ticket->attachments) > 0)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-3" data-translate>
                                ไฟล์แนบ
                            </h3>
                            <div class="space-y-2">
                                @foreach($ticket->attachments as $attachment)
                                <a href="{{ asset('storage/' . $attachment->path) }}"
                                   target="_blank"
                                   class="flex items-center gap-3 p-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-600 rounded-xl transition">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                    <span class="flex-1 text-gray-900 dark:text-gray-100">{{ $attachment->name }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">({{ $attachment->size }})</span>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <hr class="border-gray-200 dark:border-gray-700 dark:border-gray-700">

                        <!-- Conversation -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" data-translate>
                                การสนทนา
                            </h3>
                            <div class="space-y-4">
                                @forelse($ticket->replies ?? [] as $reply)
                                <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl p-4 {{ $reply->is_staff ? 'border-l-4 border-blue-500' : '' }}">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white font-semibold">
                                                {{ strtoupper(substr($reply->user_name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">
                                                    {{ $reply->user_name ?? 'N/A' }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                                    {{ isset($reply->created_at) ? $reply->created_at->diffForHumans() : 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                        @if($reply->is_staff)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs font-semibold rounded" data-translate>
                                            พนักงาน
                                        </span>
                                        @endif
                                    </div>
                                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 leading-relaxed">
                                        {{ $reply->message ?? '' }}
                                    </p>
                                </div>
                                @empty
                                <div class="text-center py-8">
                                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400" data-translate>ยังไม่มีการตอบกลับ</p>
                                </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Reply Form -->
                        @if(isset($ticket->status) && $ticket->status != 'closed')
                        <hr class="border-gray-200 dark:border-gray-700 dark:border-gray-700">

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" data-translate>
                                เพิ่มการตอบกลับ
                            </h3>
                            <form action="{{ route('admin.bot-automation.support.tickets.reply', $ticket->id ?? 0) }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <textarea
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-cyan-500 focus:border-transparent transition"
                                        name="message"
                                        rows="4"
                                        placeholder="พิมพ์ข้อความตอบกลับ..."
                                        data-translate-placeholder="พิมพ์ข้อความตอบกลับ..."
                                        required></textarea>
                                </div>
                                <div class="flex justify-between items-center">
                                    <button type="submit"
                                            class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl transition shadow-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        <span data-translate>ส่งการตอบกลับ</span>
                                    </button>
                                    <div class="flex gap-2">
                                        <button type="button"
                                                onclick="updateStatus('resolved')"
                                                class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span data-translate>ทำเครื่องหมายว่าแก้ไขแล้ว</span>
                                        </button>
                                        <button type="button"
                                                onclick="updateStatus('closed')"
                                                class="flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            <span data-translate>ปิดทิกเก็ต</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar (Right Side - 1 column) -->
            <div class="space-y-6">
                <!-- Ticket Actions Card -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-blue-600 dark:text-cyan-400" data-translate>
                            การดำเนินการทิกเก็ต
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Update Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2" data-translate>
                                อัพเดทสถานะ
                            </label>
                            <select class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-cyan-500 focus:border-transparent transition"
                                    id="status"
                                    onchange="updateTicketStatus(this.value)">
                                <option value="open" {{ isset($ticket->status) && $ticket->status == 'open' ? 'selected' : '' }} data-translate>เปิด</option>
                                <option value="in_progress" {{ isset($ticket->status) && $ticket->status == 'in_progress' ? 'selected' : '' }} data-translate>กำลังดำเนินการ</option>
                                <option value="pending" {{ isset($ticket->status) && $ticket->status == 'pending' ? 'selected' : '' }} data-translate>รอดำเนินการ</option>
                                <option value="resolved" {{ isset($ticket->status) && $ticket->status == 'resolved' ? 'selected' : '' }} data-translate>แก้ไขแล้ว</option>
                                <option value="closed" {{ isset($ticket->status) && $ticket->status == 'closed' ? 'selected' : '' }} data-translate>ปิด</option>
                            </select>
                        </div>

                        <!-- Update Priority -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2" data-translate>
                                อัพเดทลำดับความสำคัญ
                            </label>
                            <select class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-cyan-500 focus:border-transparent transition"
                                    id="priority"
                                    onchange="updateTicketPriority(this.value)">
                                <option value="low" {{ isset($ticket->priority) && $ticket->priority == 'low' ? 'selected' : '' }} data-translate>ต่ำ</option>
                                <option value="medium" {{ isset($ticket->priority) && $ticket->priority == 'medium' ? 'selected' : '' }} data-translate>ปานกลาง</option>
                                <option value="high" {{ isset($ticket->priority) && $ticket->priority == 'high' ? 'selected' : '' }} data-translate>สูง</option>
                            </select>
                        </div>

                        <!-- Assign To -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2" data-translate>
                                มอบหมายให้
                            </label>
                            <select class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-cyan-500 focus:border-transparent transition"
                                    id="assignee"
                                    onchange="assignTicket(this.value)">
                                <option value="" data-translate>ยังไม่มอบหมาย</option>
                                @foreach($staff ?? [] as $member)
                                <option value="{{ $member->id }}" {{ isset($ticket->assignee_id) && $ticket->assignee_id == $member->id ? 'selected' : '' }}>
                                    {{ $member->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="border-gray-200 dark:border-gray-700 dark:border-gray-700">

                        <!-- Action Buttons -->
                        <div class="space-y-2">
                            <button onclick="exportTicket()"
                                    class="w-full flex items-center gap-3 px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 rounded-xl transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                <span data-translate>ส่งออกทิกเก็ต</span>
                            </button>
                            <button onclick="printTicket()"
                                    class="w-full flex items-center gap-3 px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 rounded-xl transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                </svg>
                                <span data-translate>พิมพ์ทิกเก็ต</span>
                            </button>
                            <button onclick="deleteTicket()"
                                    class="w-full flex items-center gap-3 px-4 py-3 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-xl transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span data-translate>ลบทิกเก็ต</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Ticket Statistics Card -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-blue-600 dark:text-cyan-400" data-translate>
                            สถิติทิกเก็ต
                        </h2>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>การตอบกลับทั้งหมด</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $ticket->replies_count ?? '0' }}</span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>การตอบกลับครั้งแรก</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ isset($ticket->first_response) ? date('M d, Y', strtotime($ticket->first_response)) : 'รอดำเนินการ' }}
                                </span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>อัพเดทล่าสุด</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ isset($ticket->updated_at) ? $ticket->updated_at->diffForHumans() : 'N/A' }}
                                </span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>เวลาแก้ไข</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $ticket->resolution_time ?? 'N/A' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Google Translate Script -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    // ฟังก์ชันสำหรับแปลภาษาด้วย Google Translate API
    async function translateText(text, targetLang) {
        if (targetLang === 'th') return text; // ไม่ต้องแปลถ้าเป็นภาษาไทยอยู่แล้ว

        try {
            const response = await axios.post('/api/translate', {
                text: text,
                target: targetLang,
                source: 'th'
            });
            return response.data.translatedText;
        } catch (error) {
            console.error('Translation error:', error);
            return text; // คืนค่าข้อความเดิมถ้าแปลไม่สำเร็จ
        }
    }

    // แปลข้อความทั้งหมดที่มี data-translate attribute
    async function translatePage(targetLang) {
        const elements = document.querySelectorAll('[data-translate]');

        for (const element of elements) {
            const originalText = element.getAttribute('data-original') || element.textContent.trim();

            // เก็บข้อความต้นฉบับไว้ในครั้งแรก
            if (!element.getAttribute('data-original')) {
                element.setAttribute('data-original', originalText);
            }

            if (targetLang === 'th') {
                element.textContent = originalText;
            } else {
                const translatedText = await translateText(originalText, targetLang);
                element.textContent = translatedText;
            }
        }

        // แปล placeholders
        const placeholders = document.querySelectorAll('[data-translate-placeholder]');
        for (const element of placeholders) {
            const originalPlaceholder = element.getAttribute('data-original-placeholder') || element.getAttribute('placeholder');

            if (!element.getAttribute('data-original-placeholder')) {
                element.setAttribute('data-original-placeholder', originalPlaceholder);
            }

            if (targetLang === 'th') {
                element.setAttribute('placeholder', originalPlaceholder);
            } else {
                const translatedPlaceholder = await translateText(originalPlaceholder, targetLang);
                element.setAttribute('placeholder', translatedPlaceholder);
            }
        }
    }

    // ฟังการเปลี่ยนแปลงภาษา
    document.addEventListener('alpine:initialized', () => {
        Alpine.effect(() => {
            const lang = Alpine.store('language');
            if (lang) {
                translatePage(lang);
            }
        });
    });
});

// Alpine.js global store สำหรับ language state
if (typeof Alpine !== 'undefined') {
    Alpine.store('language', 'th');
}

// ฟังก์ชันต่างๆ สำหรับจัดการทิกเก็ต
async function updateStatus(status) {
    if (confirm('อัพเดทสถานะทิกเก็ตเป็น ' + status + '?')) {
        try {
            const response = await fetch(window.location.pathname + '/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ status: status })
            });

            if (response.ok) {
                window.location.reload();
            } else {
                alert('เกิดข้อผิดพลาดในการอัพเดทสถานะ');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        }
    }
}

async function updateTicketStatus(status) {
    await updateStatus(status);
}

async function updateTicketPriority(priority) {
    try {
        const response = await fetch(window.location.pathname + '/priority', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ priority: priority })
        });

        if (response.ok) {
            window.location.reload();
        } else {
            alert('เกิดข้อผิดพลาดในการอัพเดทความสำคัญ');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}

async function assignTicket(userId) {
    try {
        const response = await fetch(window.location.pathname + '/assign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ assigned_to: userId })
        });

        if (response.ok) {
            window.location.reload();
        } else {
            alert('เกิดข้อผิดพลาดในการมอบหมายทิกเก็ต');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}

function exportTicket() {
    // ดาวน์โหลดไฟล์ JSON
    window.location.href = window.location.pathname + '/export';
}

function printTicket() {
    window.print();
}

function deleteTicket() {
    if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบทิกเก็ตนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
        // สร้าง form และ submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.pathname;

        // เพิ่ม CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfInput);

        // เพิ่ม method spoofing สำหรับ DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
