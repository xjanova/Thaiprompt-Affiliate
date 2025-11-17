@extends('layouts.admin-v3')

@section('title', 'จัดการ Hybrid Bot Keywords')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header Section --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-red-500 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">🤖 Hybrid Bot Keywords</h1>
                <p class="text-purple-100">จัดการ keywords สำหรับระบบตอบอัตโนมัติ</p>
            </div>
            <a href="{{ route('admin.line-bot.keywords.create') }}"
               class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/20 transition">
                <i class="fas fa-plus mr-2"></i>สร้าง Keyword ใหม่
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
            <p class="text-green-800 dark:text-green-300"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Total Keywords --}}
        <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Keywords ทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_keywords'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-key text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Active Keywords --}}
        <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Keywords ใช้งาน</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['active_keywords'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Inactive Keywords --}}
        <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Keywords ปิดใช้งาน</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['inactive_keywords'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-red-100 dark:bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-ban text-red-600 dark:text-red-400 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Categories --}}
        <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">หมวดหมู่</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['by_category']->count() }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-th text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Filter --}}
    <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10 mb-8">
        <form method="GET" class="flex gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" placeholder="ค้นหา Keyword..."
                    value="{{ request('search') }}"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <select name="category" class="px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">ทั้งหมด</option>
                <option value="faq" @selected(request('category') === 'faq')>FAQ</option>
                <option value="support" @selected(request('category') === 'support')>Support</option>
                <option value="product" @selected(request('category') === 'product')>Product</option>
                <option value="custom" @selected(request('category') === 'custom')>Custom</option>
            </select>
            <button type="submit" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
        </form>
    </div>

    {{-- Keywords Table --}}
    <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Keyword</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Triggers</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">หมวดหมู่</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-900 dark:text-white">Priority</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-900 dark:text-white">สถานะ</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($keywords as $keyword)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            {{-- Keyword Name --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center">
                                        <i class="fas fa-key text-purple-600 dark:text-purple-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $keyword->keyword }}</p>
                                        @if($keyword->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($keyword->description, 50) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Trigger Words --}}
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @foreach(array_slice($keyword->trigger_words ?? [], 0, 3) as $trigger)
                                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                                            {{ $trigger }}
                                        </span>
                                    @endforeach
                                    @if(count($keyword->trigger_words ?? []) > 3)
                                        <span class="px-3 py-1 bg-gray-100 dark:bg-gray-500/20 text-gray-800 dark:text-gray-300 rounded-full text-xs font-medium">
                                            +{{ count($keyword->trigger_words) - 3 }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Category --}}
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($keyword->category === 'faq') bg-cyan-100 dark:bg-cyan-500/20 text-cyan-800 dark:text-cyan-300
                                    @elseif($keyword->category === 'support') bg-yellow-100 dark:bg-yellow-500/20 text-yellow-800 dark:text-yellow-300
                                    @elseif($keyword->category === 'product') bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300
                                    @else bg-gray-100 dark:bg-gray-500/20 text-gray-800 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst($keyword->category) }}
                                </span>
                            </td>

                            {{-- Priority --}}
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <i class="fas fa-arrow-up text-gray-400"></i>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ $keyword->priority }}</span>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 text-center">
                                @if($keyword->is_active)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300">
                                        <i class="fas fa-check-circle mr-1"></i>ใช้งาน
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300">
                                        <i class="fas fa-ban mr-1"></i>ปิด
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.line-bot.keywords.edit', $keyword) }}"
                                       class="px-4 py-2 bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-500/30 transition text-sm font-semibold">
                                        <i class="fas fa-edit mr-1"></i>แก้ไข
                                    </a>
                                    <form method="POST" action="{{ route('admin.line-bot.keywords.destroy', $keyword) }}" class="inline"
                                          onsubmit="return confirm('ยืนยันการลบ Keyword นี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-4 py-2 bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-500/30 transition text-sm font-semibold">
                                            <i class="fas fa-trash mr-1"></i>ลบ
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-24 h-24 rounded-full bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-key text-purple-600 dark:text-purple-400 text-4xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ไม่มี Keywords</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มสร้าง Keyword แรกของคุณตอนนี้</p>
                                <a href="{{ route('admin.line-bot.keywords.create') }}"
                                   class="inline-block px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                                    <i class="fas fa-plus mr-2"></i>สร้าง Keyword ใหม่
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $keywords->links() }}
    </div>

    {{-- Test Keyword Section --}}
    <div class="mt-8 glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            <i class="fas fa-flask mr-2 text-purple-600"></i>ทดสอบ Keyword
        </h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">พิมพ์ข้อความเพื่อทดสอบว่า Keyword ไหนจะตรงกับข้อความนี้</p>

        <div class="space-y-4">
            <textarea id="testMessage" placeholder="พิมพ์ข้อความทดสอบ... เช่น 'คืนเงินได้ไหม?' หรือ 'shipping ขนาดไหน'"
                      class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                      rows="3"></textarea>

            <button onclick="testKeyword()" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition font-semibold">
                <i class="fas fa-play mr-2"></i>ทดสอบ
            </button>

            <div id="testResult" class="hidden mt-6 p-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div id="testContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function testKeyword() {
    const message = document.getElementById('testMessage').value;

    if (!message.trim()) {
        alert('กรุณาพิมพ์ข้อความทดสอบ');
        return;
    }

    const resultDiv = document.getElementById('testResult');
    const contentDiv = document.getElementById('testContent');

    // Show loading
    resultDiv.classList.remove('hidden');
    contentDiv.innerHTML = '<p class="text-gray-600 dark:text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังทดสอบ...</p>';

    fetch('{{ route("admin.line-bot.keywords.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.matched) {
                contentDiv.innerHTML = `
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-500/20 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-lg text-green-800 dark:text-green-300 mb-3">พบ Keyword ✓</h3>
                            <div class="space-y-2 text-sm">
                                <div><strong class="text-gray-900 dark:text-white">Keyword:</strong> <span class="text-gray-600 dark:text-gray-400">${data.keyword}</span></div>
                                <div><strong class="text-gray-900 dark:text-white">หมวดหมู่:</strong> <span class="text-gray-600 dark:text-gray-400">${data.category}</span></div>
                                <div><strong class="text-gray-900 dark:text-white">Priority:</strong> <span class="text-gray-600 dark:text-gray-400">${data.priority}</span></div>
                                <div><strong class="text-gray-900 dark:text-white">ประเภท:</strong> <span class="text-gray-600 dark:text-gray-400">${data.response_type}</span></div>
                                <div><strong class="text-gray-900 dark:text-white">Trigger Words:</strong> <span class="text-gray-600 dark:text-gray-400">${data.trigger_words.join(', ')}</span></div>
                                <div><strong class="text-gray-900 dark:text-white">ข้อความตอบ:</strong> <span class="text-gray-600 dark:text-gray-400">${data.response_text || '(ไม่มี)'}</span></div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-lg text-blue-800 dark:text-blue-300 mb-2">ไม่พบ Keyword</h3>
                            <p class="text-gray-600 dark:text-gray-400">ข้อความนี้จะถูกส่งให้ AI provider เพื่อประมวลผล</p>
                        </div>
                    </div>
                `;
            }
        } else {
            contentDiv.innerHTML = `<div class="text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>${data.error}</div>`;
        }
    })
    .catch(error => {
        contentDiv.innerHTML = `<div class="text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>เกิดข้อผิดพลาด: ${error.message}</div>`;
    });
}
</script>
@endsection
