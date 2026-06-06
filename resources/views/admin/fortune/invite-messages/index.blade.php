@extends('layouts.admin-v3')

@section('title', 'ข้อความชวนดูดวง (สุ่ม)')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl"
     x-data="inviteMessages()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                💬 ข้อความชวนดูดวง (สุ่ม)
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ส่งแทนรูปแบนเนอร์ เมื่อลูกค้าได้รูปไปแล้วในสัปดาห์นี้
            </p>
        </div>
        <button type="button"
                @click="showAdd = !showAdd"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow transition self-start">
            ➕ เพิ่มข้อความใหม่
        </button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ℹ️ How it works --}}
    <div class="mb-6 p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 text-sm text-indigo-900 dark:text-indigo-200">
        <div class="font-semibold mb-1">ℹ️ ระบบนี้ทำงานยังไง</div>
        <ul class="list-disc list-inside space-y-0.5 text-indigo-800 dark:text-indigo-300">
            <li>ลูกค้าคอมเมนต์/กดไลก์ครั้งแรกในสัปดาห์ → บอท DM กลับพร้อม<strong>รูปแบนเนอร์</strong> (เหมือนเดิม)</li>
            <li>ครั้งถัดไป<strong>ในสัปดาห์เดียวกัน</strong> → ไม่ส่งรูปซ้ำ แต่<strong>สุ่ม</strong>ข้อความจากคลังนี้ส่งแทน + ปุ่มดูดวง</li>
            <li>พิมพ์ <code class="px-1 rounded bg-white/60 dark:bg-black/30">{name}</code> เพื่อแทนชื่อลูกค้าอัตโนมัติ (เช่น "คุณ{name}")</li>
            <li>"สัปดาห์" รีเซ็ตทุกวันจันทร์ • ข้อความใช้เสียงแม่หมอ (ผู้หญิง)</li>
        </ul>
    </div>

    {{-- ⚙️ Master setting --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <form action="{{ route('admin.fortune.invite-messages.settings') }}" method="POST">
            @csrf
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="enable_invite_rotation" value="1"
                       @checked($settings->enable_invite_rotation ?? true)
                       class="w-5 h-5 text-blue-600 rounded">
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">เปิดใช้งานระบบสุ่มข้อความแทนรูป</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        ปิด = ส่งรูปแบนเนอร์ทุกครั้งตามเดิม (ไม่สลับเป็นข้อความ)
                    </div>
                </div>
            </label>
            <button type="submit"
                    class="mt-4 px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow transition">
                💾 บันทึกการตั้งค่า
            </button>
        </form>
    </div>

    {{-- 📊 Stats --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $messages->count() }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">ข้อความทั้งหมด</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $activeCount }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">เปิดใช้งาน</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalSent) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">ส่งไปแล้ว (ครั้ง)</div>
        </div>
    </div>

    {{-- ➕ Add form --}}
    <div x-show="showAdd" x-cloak x-transition class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">➕ เพิ่มข้อความใหม่</h3>
        <form action="{{ route('admin.fortune.invite-messages.store') }}" method="POST">
            @csrf
            <textarea name="message" rows="3" maxlength="1000" required
                      placeholder="เช่น 🌙 ช่วงนี้ดาวกำลังเปลี่ยนผ่านนะคะคุณ{name} ถ้าอยากรู้ว่าควรไปทางไหน ทักมาหาแม่หมอได้เลยค่ะ"
                      class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
            <div class="flex flex-col sm:flex-row gap-3 mt-3">
                <input type="text" name="category" list="categoryList" placeholder="หมวด (เช่น timing, love)"
                       class="px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:w-56">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-blue-600 rounded">
                    เปิดใช้งานทันที
                </label>
                <button type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow transition sm:ml-auto">
                    บันทึก
                </button>
            </div>
        </form>
    </div>

    {{-- 🔎 Category filter --}}
    @php $categories = $messages->pluck('category')->unique()->filter()->sort()->values(); @endphp
    <datalist id="categoryList">
        @foreach($categories as $cat)
            <option value="{{ $cat }}"></option>
        @endforeach
    </datalist>

    @if($categories->count() > 1)
        <div class="flex flex-wrap gap-2 mb-4">
            <button type="button" @click="filter = ''"
                    :class="filter === '' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="px-3 py-1 rounded-full text-xs font-medium transition">
                ทั้งหมด
            </button>
            @foreach($categories as $cat)
                <button type="button" @click="filter = '{{ $cat }}'"
                        :class="filter === '{{ $cat }}' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-3 py-1 rounded-full text-xs font-medium transition">
                    {{ $cat }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- 📋 Messages list --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        @if($messages->isEmpty())
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <p class="mb-2">ยังไม่มีข้อความ</p>
                <p class="text-xs">รัน <code>php artisan db:seed --class=FortuneInviteMessageSeeder</code> เพื่อใส่ 100 ข้อความเริ่มต้น</p>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($messages as $msg)
                    <div class="p-4 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition"
                         x-show="filter === '' || filter === '{{ $msg->category }}'">
                        {{-- Number --}}
                        <div class="shrink-0 w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs text-gray-500 dark:text-gray-400 font-mono">
                            {{ $loop->iteration }}
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-words {{ $msg->is_active ? '' : 'opacity-50 line-through' }}">
                                {{ $msg->message }}
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-2 text-xs">
                                <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                                    {{ $msg->category }}
                                </span>
                                <span class="text-gray-400 dark:text-gray-500">
                                    📤 ส่งไปแล้ว {{ number_format($msg->send_count) }} ครั้ง
                                </span>
                                @unless($msg->is_active)
                                    <span class="text-yellow-600 dark:text-yellow-400">⏸️ ปิดอยู่</span>
                                @endunless
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="shrink-0 flex items-center gap-1">
                            <button type="button"
                                    @click="openEdit({{ $msg->id }}, @js($msg->message), @js($msg->category), {{ $msg->is_active ? 'true' : 'false' }})"
                                    class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg" title="แก้ไข">
                                ✏️
                            </button>
                            <form action="{{ route('admin.fortune.invite-messages.toggle', $msg) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                        title="{{ $msg->is_active ? 'ปิด' : 'เปิด' }}">
                                    {{ $msg->is_active ? '⏸️' : '▶️' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.fortune.invite-messages.destroy', $msg) }}" method="POST"
                                  onsubmit="return confirm('ลบข้อความนี้?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg" title="ลบ">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ✏️ Edit modal --}}
    <div x-show="editing.show" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="editing.show = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg p-6"
             x-show="editing.show" x-transition>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">✏️ แก้ไขข้อความ</h3>
            <form :action="updateUrl" method="POST">
                @csrf
                @method('PUT')
                <textarea name="message" rows="4" maxlength="1000" required x-model="editing.message"
                          class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                <div class="flex flex-col sm:flex-row gap-3 mt-3">
                    <input type="text" name="category" list="categoryList" x-model="editing.category" placeholder="หมวด"
                           class="px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:w-48">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_active" value="1" x-model="editing.is_active" class="w-4 h-4 text-blue-600 rounded">
                        เปิดใช้งาน
                    </label>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" @click="editing.show = false"
                            class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                        💾 บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function inviteMessages() {
        return {
            showAdd: false,
            filter: '',
            // base URL ที่มี __ID__ ไว้แทนด้วย id จริงตอนเปิด modal
            updateUrlBase: "{{ route('admin.fortune.invite-messages.update', '__ID__') }}",
            updateUrl: '',
            editing: { show: false, id: null, message: '', category: '', is_active: true },
            openEdit(id, message, category, isActive) {
                this.editing = { show: true, id: id, message: message, category: category, is_active: isActive };
                this.updateUrl = this.updateUrlBase.replace('__ID__', id);
            },
        };
    }
</script>
@endpush
@endsection
