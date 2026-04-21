{{-- ปุ่มแชร์โซเชียล --}}
{{-- @param string $url -- URL สำหรับแชร์ --}}
{{-- @param string $title -- ข้อความสำหรับแชร์ --}}
@php
    $shareUrl = $url ?? request()->url();
    $shareTitle = $title ?? ('ดูดวงออนไลน์'.(($freeFortuneEnabled ?? true) ? 'ฟรี' : ''));
    $encodedUrl = urlencode($shareUrl);
    $encodedTitle = urlencode($shareTitle);
@endphp

<div class="flex items-center gap-2">
    <span class="text-purple-300/50 text-sm">แชร์:</span>

    {{-- Facebook --}}
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}"
       target="_blank" rel="noopener noreferrer"
       class="w-8 h-8 rounded-lg bg-blue-600/20 hover:bg-blue-600/40 border border-blue-500/20 flex items-center justify-center text-blue-400 transition-all duration-200 hover:scale-110">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/>
        </svg>
    </a>

    {{-- LINE --}}
    <a href="https://social-plugins.line.me/lineit/share?url={{ $encodedUrl }}"
       target="_blank" rel="noopener noreferrer"
       class="w-8 h-8 rounded-lg bg-green-600/20 hover:bg-green-600/40 border border-green-500/20 flex items-center justify-center text-green-400 transition-all duration-200 hover:scale-110">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
        </svg>
    </a>

    {{-- Twitter/X --}}
    <a href="https://twitter.com/intent/tweet?text={{ $encodedTitle }}&url={{ $encodedUrl }}"
       target="_blank" rel="noopener noreferrer"
       class="w-8 h-8 rounded-lg bg-slate-600/20 hover:bg-slate-600/40 border border-slate-500/20 flex items-center justify-center text-slate-400 transition-all duration-200 hover:scale-110">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
        </svg>
    </a>

    {{-- Copy Link --}}
    <button @click="navigator.clipboard.writeText('{{ $shareUrl }}'); $dispatch('notify', { message: 'คัดลอกลิงก์แล้ว!', type: 'success' })"
            class="w-8 h-8 rounded-lg bg-purple-600/20 hover:bg-purple-600/40 border border-purple-500/20 flex items-center justify-center text-purple-400 transition-all duration-200 hover:scale-110">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
        </svg>
    </button>
</div>
