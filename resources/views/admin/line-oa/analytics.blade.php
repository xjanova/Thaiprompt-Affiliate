@extends('layouts.admin')

@section('title', 'LINE OA Analytics Dashboard')

@section('content')
<div class="container-fluid px-4 py-6" x-data="analyticsApp()">
    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-600 to-purple-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i class="fas fa-chart-line text-3xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">LINE OA Analytics</h1>
                        <p class="text-blue-100">รายงานและวิเคราะห์การสมัครสมาชิกแบบ Real-time</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <!-- Period Selector -->
                <select x-model="period" @change="loadDashboard()"
                        class="px-4 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg focus:ring-2 focus:ring-white/50">
                    <option value="day">วันนี้</option>
                    <option value="week">สัปดาห์นี้</option>
                    <option value="month" selected>เดือนนี้</option>
                    <option value="all">ทั้งหมด</option>
                </select>
                <button @click="exportData('csv')"
                        class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-download me-2"></i>Export CSV
                </button>
                <button @click="clearCache()"
                        class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-sync me-2"></i>รีเฟรช
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600">กำลังโหลดข้อมูล...</p>
    </div>

    <!-- Dashboard Content -->
    <div x-show="!loading" class="space-y-6">
        <!-- Overview Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Invitations -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-envelope text-2xl text-blue-600"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">
                        การเชิญ
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-800 mb-1" x-text="stats.total_invitations || 0"></h3>
                <p class="text-sm text-gray-600">คำเชิญทั้งหมด</p>
            </div>

            <!-- Clicked Rate -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                        <i class="fas fa-mouse-pointer text-2xl text-green-600"></i>
                    </div>
                    <span class="text-xs font-semibold text-green-600 bg-green-100 px-3 py-1 rounded-full">
                        คลิก
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-800 mb-1" x-text="stats.click_rate ? stats.click_rate.toFixed(1) + '%' : '0%'"></h3>
                <p class="text-sm text-gray-600">อัตราการคลิก</p>
                <p class="text-xs text-gray-500 mt-1" x-text="(stats.total_clicked || 0) + ' คลิก'"></p>
            </div>

            <!-- Completion Rate -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl text-purple-600"></i>
                    </div>
                    <span class="text-xs font-semibold text-purple-600 bg-purple-100 px-3 py-1 rounded-full">
                        สำเร็จ
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-800 mb-1" x-text="stats.completion_rate ? stats.completion_rate.toFixed(1) + '%' : '0%'"></h3>
                <p class="text-sm text-gray-600">อัตราการสมัครสำเร็จ</p>
                <p class="text-xs text-gray-500 mt-1" x-text="(stats.total_completed || 0) + ' คน'"></p>
            </div>

            <!-- Active Conversations -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center">
                        <i class="fas fa-comments text-2xl text-orange-600"></i>
                    </div>
                    <span class="text-xs font-semibold text-orange-600 bg-orange-100 px-3 py-1 rounded-full">
                        Active
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-800 mb-1" x-text="activeConversations.length || 0"></h3>
                <p class="text-sm text-gray-600">การสนทนาที่กำลังดำเนินการ</p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Conversion Funnel -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-filter text-blue-600 mr-2"></i>
                    Conversion Funnel
                </h3>
                <div class="space-y-3">
                    <template x-for="step in funnel" :key="step.step">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700" x-text="step.step"></span>
                                <span class="text-gray-600" x-text="step.count + ' คน (' + step.rate.toFixed(1) + '%)'"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2.5 rounded-full transition-all duration-500"
                                     :style="`width: ${step.rate}%`"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Dropout Analysis -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-slash text-red-600 mr-2"></i>
                    จุดที่ผู้ใช้หลุดออกมากที่สุด
                </h3>
                <div class="space-y-3">
                    <template x-for="dropout in dropouts.slice(0, 5)" :key="dropout.step">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all">
                            <div class="flex-1">
                                <p class="font-medium text-gray-800" x-text="dropout.step"></p>
                                <p class="text-xs text-gray-500" x-text="dropout.reason || 'ไม่ระบุสาเหตุ'"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-red-600" x-text="dropout.count"></p>
                                <p class="text-xs text-gray-500">คน</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Leaderboard & Active Conversations -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Sponsor Leaderboard -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-trophy text-yellow-600 mr-2"></i>
                    อันดับ Sponsor ยอดนิยม
                </h3>
                <div class="space-y-3">
                    <template x-for="(sponsor, index) in leaderboard.slice(0, 10)" :key="sponsor.sponsor_id">
                        <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
                                 :class="{
                                     'bg-yellow-400 text-yellow-900': index === 0,
                                     'bg-gray-300 text-gray-700': index === 1,
                                     'bg-orange-400 text-orange-900': index === 2,
                                     'bg-gray-200 text-gray-600': index > 2
                                 }">
                                <span x-text="index + 1"></span>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800" x-text="sponsor.sponsor_name"></p>
                                <p class="text-xs text-gray-500">
                                    สำเร็จ <span x-text="sponsor.completed_count"></span> / ทั้งหมด <span x-text="sponsor.total_signups"></span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-green-600" x-text="sponsor.success_rate.toFixed(1) + '%'"></p>
                                <p class="text-xs text-gray-500">Success Rate</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Active Conversations -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-comment-dots text-green-600 mr-2"></i>
                    การสนทนาที่กำลังดำเนินการ
                </h3>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <template x-for="conv in activeConversations" :key="conv.id">
                        <div class="p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all">
                            <div class="flex justify-between items-start mb-2">
                                <p class="font-medium text-gray-800" x-text="conv.sponsor_name"></p>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full"
                                      x-text="conv.progress + '%'"></span>
                            </div>
                            <p class="text-xs text-gray-600 mb-1" x-text="'ขั้นตอน: ' + conv.current_step"></p>
                            <p class="text-xs text-gray-500" x-text="'เริ่ม: ' + formatTime(conv.started_at)"></p>
                        </div>
                    </template>
                    <div x-show="activeConversations.length === 0" class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 opacity-50"></i>
                        <p>ไม่มีการสนทนาที่กำลังดำเนินการ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trends Chart -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-chart-area text-blue-600 mr-2"></i>
                แนวโน้มการสมัคร
            </h3>
            <div class="h-64 flex items-center justify-center text-gray-500">
                <div class="text-center">
                    <i class="fas fa-chart-line text-6xl mb-4 opacity-50"></i>
                    <p>กราฟแสดงแนวโน้ม (ต้องการ Chart.js หรือ ApexCharts)</p>
                    <p class="text-sm mt-2">จำนวนการสมัครต่อวัน: <span x-text="trends.length"></span> วัน</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function analyticsApp() {
    return {
        loading: true,
        period: 'month',
        stats: {},
        funnel: [],
        dropouts: [],
        leaderboard: [],
        activeConversations: [],
        trends: [],

        init() {
            this.loadDashboard();
        },

        async loadDashboard() {
            this.loading = true;
            try {
                const response = await fetch(`/api/v1/line-analytics/dashboard?period=${this.period}`);
                const data = await response.json();

                if (data.success) {
                    this.stats = data.data.stats;
                    this.funnel = data.data.funnel;
                    this.dropouts = data.data.dropouts;
                    this.leaderboard = data.data.leaderboard;
                    this.trends = data.data.trends;
                }

                // Load active conversations
                const activeResponse = await fetch('/api/v1/line-analytics/active');
                const activeData = await activeResponse.json();
                if (activeData.success) {
                    this.activeConversations = activeData.data;
                }
            } catch (error) {
                console.error('Error loading analytics:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            } finally {
                this.loading = false;
            }
        },

        async exportData(format) {
            try {
                const response = await fetch(`/api/v1/line-analytics/export?format=${format}&period=${this.period}`);
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `line-analytics-${this.period}.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } catch (error) {
                console.error('Error exporting data:', error);
                alert('เกิดข้อผิดพลาดในการ export ข้อมูล');
            }
        },

        async clearCache() {
            if (!confirm('คุณต้องการล้าง cache และโหลดข้อมูลใหม่หรือไม่?')) {
                return;
            }

            this.loading = true;
            try {
                await fetch('/api/v1/line-analytics/clear-cache', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                await this.loadDashboard();
                alert('ล้าง cache สำเร็จแล้ว!');
            } catch (error) {
                console.error('Error clearing cache:', error);
                alert('เกิดข้อผิดพลาดในการล้าง cache');
            }
        },

        formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            const now = new Date();
            const diffMinutes = Math.floor((now - date) / 60000);

            if (diffMinutes < 1) return 'เมื่อสักครู่';
            if (diffMinutes < 60) return `${diffMinutes} นาทีที่แล้ว`;
            const diffHours = Math.floor(diffMinutes / 60);
            if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`;
            return date.toLocaleDateString('th-TH');
        }
    }
}
</script>
@endpush
@endsection
