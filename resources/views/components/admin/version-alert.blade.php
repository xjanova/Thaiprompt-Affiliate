@props(['versionInfo'])

@if(isset($versionInfo['update_available']) && $versionInfo['update_available'])
<div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg shadow-md">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-blue-800">
                        มีเวอร์ชันใหม่พร้อมให้อัปเดต!
                    </h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p class="font-semibold">
                            เวอร์ชันปัจจุบัน:
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                v{{ $versionInfo['current_version'] }}
                            </span>
                            →
                            เวอร์ชันใหม่:
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                v{{ $versionInfo['latest_version'] }}
                            </span>
                        </p>

                        @if(isset($versionInfo['last_checked_at']))
                        <p class="mt-1 text-xs">
                            ตรวจสอบล่าสุด: {{ \Carbon\Carbon::parse($versionInfo['last_checked_at'])->diffForHumans() }}
                        </p>
                        @endif

                        @if(isset($versionInfo['changelog']) && count($versionInfo['changelog']) > 0)
                        <details class="mt-2">
                            <summary class="cursor-pointer text-blue-800 hover:text-blue-900 font-medium">
                                ดูสิ่งที่เปลี่ยนแปลง
                            </summary>
                            <ul class="mt-2 list-disc list-inside space-y-1 text-sm">
                                @foreach(array_slice($versionInfo['changelog'], 0, 5) as $change)
                                <li>{{ $change }}</li>
                                @endforeach
                            </ul>
                        </details>
                        @endif
                    </div>
                </div>
                <div class="ml-4 flex-shrink-0 flex space-x-2">
                    <a href="{{ route('admin.version.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        อัปเดตตอนนี้
                    </a>
                    <button onclick="dismissVersionAlert()"
                            class="text-blue-500 hover:text-blue-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function dismissVersionAlert() {
    event.target.closest('.bg-blue-50').style.display = 'none';
    // Optionally save to localStorage to hide for this session
    localStorage.setItem('dismissedVersionAlert_{{ $versionInfo['latest_version'] }}', 'true');
}

// Check if alert was previously dismissed
window.addEventListener('DOMContentLoaded', function() {
    const dismissed = localStorage.getItem('dismissedVersionAlert_{{ $versionInfo['latest_version'] }}');
    if (dismissed === 'true') {
        const alert = document.querySelector('.bg-blue-50');
        if (alert) alert.style.display = 'none';
    }
});
</script>
@endif
