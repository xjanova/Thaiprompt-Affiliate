@extends('layouts.admin-v3')

@section('title', 'จัดการ Hybrid Bot Keywords')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header Section พร้อม animated background pattern --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-red-500 p-8 shadow-2xl">
        {{-- Animated Background Pattern --}}
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-40"></div>

        {{-- Floating Particles Effect --}}
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-2 h-2 bg-white/30 rounded-full animate-ping"></div>
            <div class="absolute top-20 right-20 w-3 h-3 bg-purple-300/40 rounded-full animate-pulse"></div>
            <div class="absolute bottom-10 left-1/3 w-2 h-2 bg-pink-300/30 rounded-full animate-bounce"></div>
        </div>

        <div class="relative flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-white/25 to-white/10 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/20">
                        <i class="fas fa-key text-white text-3xl drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg tracking-tight">🔑 Hybrid Bot Keywords</h1>
                        <p class="text-purple-100 text-lg font-medium">จัดการ keywords สำหรับระบบตอบอัตโนมัติ</p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-xs glass-fusion backdrop-blur-sm px-3 py-1 rounded-full text-white font-semibold border border-white/30">
                                Auto-Response • Smart Matching
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.line-bot.keywords.create') }}"
               class="px-8 py-3 bg-gradient-to-r from-white to-purple-50 text-purple-700 rounded-xl hover:from-purple-50 hover:to-white transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 font-bold flex items-center gap-2">
                <i class="fas fa-plus-circle"></i>
                <span>สร้าง Keyword ใหม่</span>
            </a>
        </div>
    </div>

    {{-- Alert Messages พร้อม animation --}}
    @if(session('success'))
        <div class="mb-6 rounded-2xl glass-fusion border-2 border-green-200 dark:border-green-800 p-6 shadow-xl animate-fade-in">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-green-900 dark:text-green-100 mb-1">สำเร็จ!</h4>
                    <p class="text-green-800 dark:text-green-300 text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Statistics Cards พร้อม 3D effects และ hover animations --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Keywords --}}
        <div class="group glass-fusion rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Keywords ทั้งหมด</p>
                    <h3 class="text-4xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">{{ $stats['total_keywords'] }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">รายการทั้งหมด</p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-key text-white text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Active Keywords --}}
        <div class="group glass-fusion rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Keywords ใช้งาน</p>
                    <h3 class="text-4xl font-black bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">{{ $stats['active_keywords'] }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">กำลังทำงาน</p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Inactive Keywords --}}
        <div class="group glass-fusion rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Keywords ปิดใช้งาน</p>
                    <h3 class="text-4xl font-black bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent">{{ $stats['inactive_keywords'] }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">ไม่ใช้งาน</p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-red-500 to-orange-600 flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-ban text-white text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Categories --}}
        <div class="group glass-fusion rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">หมวดหมู่</p>
                    <h3 class="text-4xl font-black bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">{{ $stats['by_category']->count() }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">จัดหมวดหมู่</p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-th text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Filter พร้อม glassmorphism --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/20 dark:border-white/10 mb-8 shadow-lg">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-search text-white"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">ค้นหาและกรองข้อมูล</h3>
        </div>
        <form method="GET" class="flex gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" placeholder="ค้นหา Keyword..."
                        value="{{ request('search') }}"
                        class="w-full pl-12 pr-4 py-3 glass-fusion border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all duration-300">
                </div>
            </div>
            <select name="category" class="px-4 py-3 glass-fusion border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all duration-300">
                <option value="">📂 ทั้งหมด</option>
                <option value="faq" @selected(request('category') === 'faq')>❓ FAQ</option>
                <option value="support" @selected(request('category') === 'support')>🎯 Support</option>
                <option value="product" @selected(request('category') === 'product')>🛍️ Product</option>
                <option value="custom" @selected(request('category') === 'custom')>⚙️ Custom</option>
            </select>
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
            @if(request('search') || request('category'))
                <a href="{{ route('admin.line-bot.keywords.index') }}" class="px-6 py-3 glass-fusion border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:shadow-lg transition-all duration-300 font-semibold">
                    <i class="fas fa-times mr-2"></i>ล้างตัวกรอง
                </a>
            @endif
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

    {{-- Test Keyword Section พร้อม Interactive UI --}}
    <div class="mt-8 glass-fusion rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-flask text-white text-2xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white">🧪 ทดสอบ Keyword</h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">พิมพ์ข้อความเพื่อทดสอบว่า Keyword ไหนจะตรงกับข้อความนี้</p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="relative">
                <div class="absolute top-3 left-3 text-gray-400">
                    <i class="fas fa-comment-alt"></i>
                </div>
                <textarea id="testMessage" placeholder="พิมพ์ข้อความทดสอบ... เช่น 'คืนเงินได้ไหม?' หรือ 'shipping ขนาดไหน' 💬"
                          class="w-full pl-12 pr-4 py-4 glass-fusion border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all duration-300 font-medium"
                          rows="4"></textarea>
            </div>

            <button onclick="testKeyword()" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 font-bold text-lg">
                <i class="fas fa-play mr-2"></i>เริ่มทดสอบ
            </button>

            <div id="testResult" class="hidden mt-6 p-6 glass-fusion rounded-2xl border-2 border-gray-200 dark:border-gray-700 shadow-lg">
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
