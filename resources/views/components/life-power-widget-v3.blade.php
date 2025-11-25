{{-- Life Power Widget V3 - แสดงสถานะพลังชีวิตการรักษายอด --}}
{{-- V3: Tailwind CSS + Alpine.js + Dark Mode --}}
<div x-data="lifePowerWidget()"
     x-init="init()"
     class="relative overflow-hidden rounded-2xl
            bg-gradient-to-br from-purple-600 via-pink-500 to-orange-500
            dark:from-purple-900 dark:via-pink-800 dark:to-orange-800
            shadow-2xl">

    {{-- Animated Background --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full blur-3xl animate-pulse"
             style="animation-delay: 1s;"></div>
    </div>

    {{-- Header --}}
    <div class="relative z-10 flex items-center justify-between p-4 md:p-6
                bg-white/10 backdrop-blur-xl border-b border-white/20">
        <div class="flex items-center gap-3">
            {{-- Heart Icon with Animation --}}
            <div class="w-12 h-12 bg-white/20 backdrop-blur-xl rounded-xl
                        flex items-center justify-center border border-white/20">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-7 h-7 text-white animate-[heartbeat_1.5s_ease-in-out_infinite]"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-white font-bold text-lg">การรักษายอด</h3>
                <p class="text-white/70 text-sm" x-text="statusText">กำลังโหลด...</p>
            </div>
        </div>

        {{-- Status Badge --}}
        <span class="px-4 py-2 bg-white/20 backdrop-blur-xl rounded-xl
                     text-white font-semibold text-sm border border-white/20"
              x-text="statusBadge">
            กำลังโหลด...
        </span>
    </div>

    {{-- Body --}}
    <div class="relative z-10 p-4 md:p-6 space-y-4">
        {{-- Days Remaining Card --}}
        <div class="flex items-center gap-4 p-4 bg-white/10 backdrop-blur-xl rounded-xl border border-white/20">
            {{-- Calendar Icon --}}
            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>

            {{-- Days Info --}}
            <div class="flex-1">
                <div class="text-white/70 text-xs uppercase tracking-wide mb-1">วันที่เหลือ</div>
                <div class="text-white text-3xl font-bold" x-text="daysRemaining + ' วัน'">-- วัน</div>
            </div>

            {{-- Health Bar --}}
            <div class="w-24 flex-shrink-0">
                <div class="h-3 bg-black/20 rounded-full overflow-hidden border border-white/20">
                    <div class="h-full rounded-full transition-all duration-500 relative"
                         :class="healthBarColor"
                         :style="'width: ' + healthPercentage + '%'">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-pulse"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Renewal Date --}}
        <div class="flex items-center gap-3 px-4 py-3 bg-white/5 rounded-xl border border-white/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-white/80 text-sm" x-text="'วันต่ออายุ: ' + nextRenewalDate">วันต่ออายุ: --</span>
        </div>

        {{-- Points Grid --}}
        <div class="grid grid-cols-2 gap-3">
            {{-- Current Points --}}
            <div class="p-4 bg-white/10 backdrop-blur-xl rounded-xl border border-white/20
                        hover:bg-white/15 transition-colors">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-lg
                                flex items-center justify-center shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                </div>
                <div class="text-white/60 text-xs uppercase tracking-wide mb-1">แต้มปัจจุบัน</div>
                <div class="text-white text-2xl font-bold" x-text="formatNumber(currentPoints)">0</div>
            </div>

            {{-- Required Points --}}
            <div class="p-4 bg-white/10 backdrop-blur-xl rounded-xl border border-white/20
                        hover:bg-white/15 transition-colors">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg
                                flex items-center justify-center shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="text-white/60 text-xs uppercase tracking-wide mb-1">เป้าหมาย</div>
                <div class="text-white text-2xl font-bold" x-text="formatNumber(requiredPoints)">0</div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-white/80 text-sm" x-text="'เหลืออีก ' + formatNumber(remainingPoints) + ' แต้ม'">
                    เหลืออีก 0 แต้ม
                </span>
                <span class="text-white font-bold text-sm" x-text="pointsPercentage + '%'">0%</span>
            </div>
            <div class="h-3 bg-black/20 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-green-400 to-emerald-500 rounded-full
                            transition-all duration-500 relative"
                     :style="'width: ' + pointsPercentage + '%'">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-pulse"></div>
                </div>
            </div>
        </div>

        {{-- Action Links --}}
        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-white/20">
            <a href="{{ route('user.retention.index') }}"
               class="flex flex-col items-center gap-2 p-3 bg-white/10 rounded-xl
                      border border-white/20 hover:bg-white/20 transition-all
                      text-white text-xs font-semibold group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>รายละเอียด</span>
            </a>

            <a href="{{ route('user.retention.repair') }}"
               class="flex flex-col items-center gap-2 p-3 bg-white/10 rounded-xl
                      border border-white/20 hover:bg-white/20 transition-all
                      text-white text-xs font-semibold group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>ซ่อมสิทธิ์</span>
            </a>

            <a href="{{ route('user.retention.advance-renewal') }}"
               class="flex flex-col items-center gap-2 p-3 bg-white/10 rounded-xl
                      border border-white/20 hover:bg-white/20 transition-all
                      text-white text-xs font-semibold group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>เติมล่วงหน้า</span>
            </a>
        </div>
    </div>
</div>

{{-- Custom Animation Keyframes --}}
<style>
@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    10% { transform: scale(1.08); }
    20% { transform: scale(1); }
    30% { transform: scale(1.12); }
    40% { transform: scale(1); }
}
</style>

<script>
/**
 * Alpine.js component สำหรับ Life Power Widget
 * ดึงข้อมูลและแสดงสถานะการรักษายอด
 */
function lifePowerWidget() {
    return {
        // Data
        status: 'loading',
        statusText: 'กำลังโหลด...',
        statusBadge: 'กำลังโหลด...',
        currentPoints: 0,
        requiredPoints: 0,
        remainingPoints: 0,
        pointsPercentage: 0,
        daysRemaining: 0,
        healthPercentage: 0,
        healthColor: 'green',
        nextRenewalDate: '--',

        /**
         * เริ่มต้น widget และโหลดข้อมูล
         */
        init() {
            this.loadData();
            // รีเฟรชทุก 60 วินาที
            setInterval(() => this.loadData(), 60000);
        },

        /**
         * โหลดข้อมูลจาก API
         */
        async loadData() {
            try {
                const response = await fetch('{{ route("user.retention.widget-data") }}');
                const result = await response.json();

                if (result.success) {
                    this.updateWidget(result.data);
                }
            } catch (error) {
                console.error('Error loading widget data:', error);
            }
        },

        /**
         * อัพเดทข้อมูลใน widget
         */
        updateWidget(data) {
            this.status = data.status;
            this.currentPoints = data.current_points || 0;
            this.requiredPoints = data.required_points || 0;
            this.remainingPoints = data.remaining_points || 0;
            this.pointsPercentage = Math.round(data.points_percentage || 0);
            this.daysRemaining = data.days_remaining || 0;
            this.healthPercentage = Math.max(0, 100 - (data.health_percentage || 0));
            this.healthColor = data.health_color || 'green';
            this.nextRenewalDate = data.next_renewal_date || '--';

            // อัพเดทสถานะ
            const statusMap = {
                'active': { text: 'ใช้งานอยู่', badge: 'ใช้งานอยู่' },
                'inactive': { text: 'ไม่ใช้งาน', badge: 'ไม่ใช้งาน' },
                'expired': { text: 'หมดอายุ', badge: 'หมดอายุ' }
            };
            const statusInfo = statusMap[data.status] || { text: 'ไม่ทราบสถานะ', badge: 'ไม่ทราบ' };
            this.statusText = statusInfo.text;
            this.statusBadge = statusInfo.badge;
        },

        /**
         * คืนค่า class สำหรับ health bar ตามสี
         */
        get healthBarColor() {
            const colorMap = {
                'green': 'bg-gradient-to-r from-green-400 to-emerald-500',
                'yellow': 'bg-gradient-to-r from-yellow-400 to-amber-500',
                'orange': 'bg-gradient-to-r from-orange-400 to-red-500',
                'red': 'bg-gradient-to-r from-red-500 to-pink-600 animate-pulse'
            };
            return colorMap[this.healthColor] || colorMap['green'];
        },

        /**
         * ฟอร์แมตตัวเลขให้อ่านง่าย
         */
        formatNumber(num) {
            return new Intl.NumberFormat('th-TH').format(num);
        }
    };
}
</script>
