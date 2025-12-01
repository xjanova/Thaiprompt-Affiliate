{{--
    Thread Card Component
    แสดงข้อมูลกระทู้ในรูปแบบ card
    ใช้ใน: หน้าหลัก, หมวดหมู่, ค้นหา
--}}

@php
    $authorTrophies = app(\App\Services\ForumTrophyService::class)->getDisplayTrophies($thread->user);
    $authorNameColor = app(\App\Services\ForumTrophyService::class)->getNameColor($thread->user);
@endphp

<a href="{{ route('forum.thread.show', $thread->slug) }}"
   class="block p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all group">
    <div class="flex items-start gap-4">
        {{-- Avatar --}}
        <div class="flex-shrink-0">
            @if($thread->user->profile_photo ?? false)
            <img src="{{ $thread->user->profile_photo }}"
                 alt="{{ $thread->user->name }}"
                 class="w-12 h-12 rounded-full object-cover border-2 border-white dark:border-gray-600 shadow">
            @else
            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow">
                {{ strtoupper(substr($thread->user->name ?? 'U', 0, 1)) }}
            </div>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                {{-- Pinned Badge --}}
                @if($thread->is_pinned)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-semibold rounded-full">
                    <i class="fas fa-thumbtack"></i>
                    ปักหมุด
                </span>
                @endif

                {{-- Locked Badge --}}
                @if($thread->is_locked)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 text-xs font-semibold rounded-full">
                    <i class="fas fa-lock"></i>
                    ล็อค
                </span>
                @endif
            </div>

            <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 mt-1 line-clamp-2">
                {{ $thread->title }}
            </h3>

            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-1">
                {{ $thread->excerpt }}
            </p>

            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500 dark:text-gray-400">
                {{-- Author --}}
                <div class="flex items-center gap-1">
                    <span class="{{ $authorNameColor ?? '' }}">{{ $thread->user->name ?? 'ผู้ใช้' }}</span>
                    {{-- โทรฟี่ --}}
                    @foreach($authorTrophies as $trophy)
                    <span title="{{ $trophy->name }}: {{ $trophy->description }}" class="cursor-help">
                        {{ $trophy->icon }}
                    </span>
                    @endforeach
                </div>

                {{-- Category --}}
                <span class="flex items-center gap-1">
                    <i class="fas fa-folder text-xs"></i>
                    {{ $thread->category->name }}
                </span>

                {{-- Time --}}
                <span class="flex items-center gap-1">
                    <i class="fas fa-clock text-xs"></i>
                    {{ $thread->created_at->diffForHumans() }}
                </span>
            </div>
        </div>

        {{-- Stats --}}
        <div class="flex-shrink-0 flex flex-col items-end gap-2">
            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1" title="ไลค์">
                    <i class="fas fa-heart text-red-400"></i>
                    {{ number_format($thread->likes_count) }}
                </span>
                <span class="flex items-center gap-1" title="คอมเมนต์">
                    <i class="fas fa-comment text-blue-400"></i>
                    {{ number_format($thread->posts_count) }}
                </span>
                <span class="flex items-center gap-1" title="เข้าชม">
                    <i class="fas fa-eye text-gray-400"></i>
                    {{ number_format($thread->views_count) }}
                </span>
            </div>

            {{-- Last Reply --}}
            @if($thread->last_post_at)
            <div class="text-xs text-gray-400 dark:text-gray-500">
                ตอบล่าสุด: {{ $thread->last_post_at->diffForHumans() }}
            </div>
            @endif
        </div>
    </div>
</a>
