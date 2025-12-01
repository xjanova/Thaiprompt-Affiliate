{{--
    หน้าหลักฟอรั่ม Community
    แสดงหมวดหมู่ กระทู้ล่าสุด และกระทู้ยอดนิยม
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-arrow-x')

@section('title', 'ฟอรั่ม Community')

@section('content')
<div class="container-fluid px-4 py-6" x-data="forumIndex()">

    {{-- Header Section --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 rounded-3xl p-8 mb-8 shadow-2xl">
        {{-- Pattern Background --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-center md:text-left">
                <h1 class="text-4xl md:text-5xl font-black text-white mb-3">
                    💬 ฟอรั่ม Community
                </h1>
                <p class="text-white/90 text-lg max-w-2xl">
                    แลกเปลี่ยนความรู้ แบ่งปันประสบการณ์ และพูดคุยกับสมาชิกในชุมชน
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4">
                {{-- ค้นหา --}}
                <form action="{{ route('forum.search') }}" method="GET" class="relative">
                    <input type="text"
                           name="q"
                           placeholder="ค้นหากระทู้..."
                           class="w-full sm:w-64 pl-12 pr-4 py-3 bg-white/20 backdrop-blur-sm border border-white/30 rounded-xl text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-white/70"></i>
                </form>

                @auth
                <a href="{{ route('forum.thread.create') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white text-purple-600 font-bold rounded-xl hover:bg-white/90 hover:scale-105 transition-all shadow-lg">
                    <i class="fas fa-plus"></i>
                    <span>สร้างกระทู้ใหม่</span>
                </a>
                @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-sm border border-white/30 text-white font-bold rounded-xl hover:bg-white/30 transition-all">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>เข้าสู่ระบบเพื่อโพสต์</span>
                </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-comments text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">กระทู้ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_threads']) }}</p>
                </div>
            </div>
        </div>

        <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-message text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">คอมเมนต์ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_posts']) }}</p>
                </div>
            </div>
        </div>

        <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-folder text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">หมวดหมู่</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_categories']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- กระทู้แนะนำ --}}
            @if($featuredThreads->count() > 0)
            <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-500"></i>
                    กระทู้แนะนำ
                </h2>

                <div class="space-y-4">
                    @foreach($featuredThreads as $thread)
                    <a href="{{ route('forum.thread.show', $thread->slug) }}"
                       class="block p-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl hover:shadow-lg transition-all group">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-star text-white"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 truncate">
                                    {{ $thread->title }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    โดย {{ $thread->user->name ?? 'ผู้ใช้' }} • {{ $thread->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-heart text-red-500"></i>
                                    {{ $thread->likes_count }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-comment"></i>
                                    {{ $thread->posts_count }}
                                </span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- หมวดหมู่ --}}
            <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-folder-tree text-purple-500"></i>
                    หมวดหมู่
                </h2>

                <div class="space-y-4">
                    @foreach($categories as $category)
                    <div class="group">
                        {{-- หมวดหมู่หลัก --}}
                        <a href="{{ route('forum.category', $category->slug) }}"
                           class="flex items-center gap-4 p-4 rounded-xl bg-gradient-to-r {{ $category->gradient_class }} bg-opacity-10 hover:bg-opacity-20 transition-all">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br {{ $category->gradient_class }} rounded-xl flex items-center justify-center shadow-lg">
                                <i class="{{ $category->icon ?? 'fas fa-folder' }} text-white text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ $category->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $category->description }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($category->threads_count) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">กระทู้</p>
                            </div>
                        </a>

                        {{-- หมวดหมู่ย่อย --}}
                        @if($category->children->count() > 0)
                        <div class="ml-8 mt-2 space-y-2">
                            @foreach($category->children as $child)
                            <a href="{{ route('forum.category', $child->slug) }}"
                               class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                                <i class="{{ $child->icon ?? 'fas fa-folder' }} text-gray-400 dark:text-gray-500"></i>
                                <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $child->name }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $child->threads_count }} กระทู้</span>
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- กระทู้ล่าสุด --}}
            <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-clock text-blue-500"></i>
                    กระทู้ล่าสุด
                </h2>

                <div class="space-y-4">
                    @forelse($latestThreads as $thread)
                    @include('forum.components.thread-card', ['thread' => $thread])
                    @empty
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-inbox text-5xl mb-4"></i>
                        <p>ยังไม่มีกระทู้</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- กระทู้ยอดนิยม --}}
            <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-fire text-red-500"></i>
                    กระทู้ยอดนิยม
                </h2>

                <div class="space-y-3">
                    @forelse($popularThreads as $index => $thread)
                    <a href="{{ route('forum.thread.show', $thread->slug) }}"
                       class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all group">
                        <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-red-500 to-orange-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                            {{ $index + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 line-clamp-2">
                                {{ $thread->title }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-heart text-red-400"></i>
                                    {{ $thread->likes_count }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-eye"></i>
                                    {{ $thread->views_count }}
                                </span>
                            </p>
                        </div>
                    </a>
                    @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                        ยังไม่มีกระทู้ยอดนิยม
                    </p>
                    @endforelse
                </div>
            </div>

            {{-- กฎของฟอรั่ม --}}
            <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-gavel text-purple-500"></i>
                    กฎของฟอรั่ม
                </h2>

                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-0.5"></i>
                        <span>เคารพผู้อื่น ใช้ภาษาสุภาพ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-0.5"></i>
                        <span>ห้ามโพสต์สแปมหรือโฆษณา</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-0.5"></i>
                        <span>ห้ามเนื้อหาหลอกลวงหรือเท็จ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-0.5"></i>
                        <span>ห้ามละเมิดลิขสิทธิ์</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
                        <span>ผู้ฝ่าฝืนจะได้รับโทรฟี่ไม่ดีติดตัวถาวร!</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Forum Index Component - หน้าหลักฟอรั่ม
 *
 * จัดการ state และ interactions ของหน้าหลัก
 */
function forumIndex() {
    return {
        // State
        searchQuery: '',

        // Methods
        init() {
            console.log('Forum Index initialized');
        }
    };
}
</script>
@endpush
@endsection
