@extends('layouts.admin')
@section('title', 'จัดการ AI Bots')

@push('styles')
<style>
    .bot-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .bot-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .bot-card.active {
        border-left-color: #10b981;
    }
    .bot-card.inactive {
        border-left-color: #ef4444;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        transition: transform 0.3s ease;
    }
    .stat-card:hover {
        transform: scale(1.05);
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-badge.active {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    .status-badge.inactive {
        background: #e5e7eb;
        color: #6b7280;
    }
    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
        animation: pulse 2s ease-in-out infinite;
    }
    .status-indicator.online {
        background-color: #10b981;
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
    }
    .status-indicator.offline {
        background-color: #ef4444;
    }
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 300px;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    }
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    .action-btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .bot-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .bot-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
        flex-wrap: wrap;
    }
    .bot-meta-item {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 13px;
        color: #6b7280;
    }
    .bot-meta-item i {
        font-size: 12px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4" x-data="aiBotsManager()">

    <!-- Toast Notification Container -->
    <div id="toast-container"></div>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-robot mr-3"></i>AI Bots
            </h2>
            <p class="text-gray-600 mt-1">จัดการ AI Bot ทั้งหมดของคุณ</p>
        </div>
        <a href="{{ route('admin.ai-bots.create') }}" class="action-btn bg-gradient-to-r from-purple-500 to-indigo-600 text-white">
            <i class="fas fa-plus mr-2"></i>สร้าง Bot ใหม่
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Total Bots</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $stats['total_bots'] }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-robot"></i>
                </div>
            </div>
        </div>

        <div class="stat-card p-6" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Active Bots</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $stats['active_bots'] }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card p-6" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Conversations</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['total_conversations']) }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-comments"></i>
                </div>
            </div>
        </div>

        <div class="stat-card p-6" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Total Messages</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['total_messages']) }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-paper-plane"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Bots Grid -->
    <div class="grid grid-cols-1 gap-6">
        @forelse($bots as $bot)
        <div class="bot-card {{ $bot->is_active ? 'active' : 'inactive' }} bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <!-- Bot Header -->
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-4 flex-1">
                        <div class="bot-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-2xl font-bold text-gray-800">{{ $bot->display_name }}</h3>
                                <span class="status-badge {{ $bot->is_active ? 'active' : 'inactive' }}">
                                    <span class="status-indicator {{ $bot->is_active ? 'online' : 'offline' }}"></span>
                                    {{ $bot->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                </span>
                                @if($bot->is_public)
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                                    <i class="fas fa-globe mr-1"></i>Public
                                </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-mono bg-gray-100 px-2 py-1 rounded">{{ $bot->name }}</span>
                            </p>
                            @if($bot->description)
                            <p class="text-sm text-gray-600 mt-2">{{ $bot->description }}</p>
                            @endif

                            <!-- Bot Meta Info -->
                            <div class="bot-meta">
                                <div class="bot-meta-item">
                                    <i class="fas fa-server"></i>
                                    <span>{{ $bot->provider->display_name }}</span>
                                </div>
                                <div class="bot-meta-item">
                                    <i class="fas fa-brain"></i>
                                    <span>{{ $bot->model->display_name }}</span>
                                </div>
                                <div class="bot-meta-item">
                                    <i class="fas fa-user"></i>
                                    <span>{{ $bot->owner->name }}</span>
                                </div>
                                <div class="bot-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span>{{ $bot->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 ml-4">
                        <!-- Toggle Status -->
                        @if($bot->owner_id === Auth::id())
                        <button @click="toggleBot({{ $bot->id }})"
                                class="action-btn transition-all"
                                :class="bots[{{ $bot->id }}]?.is_active ? 'bg-green-500 text-white hover:bg-green-600' : 'bg-gray-300 text-gray-700 hover:bg-gray-400'">
                            <i class="fas" :class="bots[{{ $bot->id }}]?.is_active ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                        </button>
                        @endif

                        <!-- View Details -->
                        <a href="{{ route('admin.ai-bots.show', $bot->id) }}"
                           class="action-btn bg-blue-500 text-white hover:bg-blue-600">
                            <i class="fas fa-eye"></i>
                        </a>

                        @if($bot->owner_id === Auth::id())
                        <!-- Edit -->
                        <a href="{{ route('admin.ai-bots.edit', $bot->id) }}"
                           class="action-btn bg-yellow-500 text-white hover:bg-yellow-600">
                            <i class="fas fa-edit"></i>
                        </a>

                        <!-- Duplicate -->
                        <button @click="duplicateBot({{ $bot->id }})"
                                class="action-btn bg-purple-500 text-white hover:bg-purple-600">
                            <i class="fas fa-copy"></i>
                        </button>

                        <!-- Delete -->
                        <button @click="deleteBot({{ $bot->id }})"
                                class="action-btn bg-red-500 text-white hover:bg-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Bot Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
                        <p class="text-xs text-blue-600 font-semibold mb-1">Conversations</p>
                        <p class="text-2xl font-bold text-blue-800">{{ number_format($bot->conversations()->count()) }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
                        <p class="text-xs text-green-600 font-semibold mb-1">Messages</p>
                        <p class="text-2xl font-bold text-green-800">{{ number_format($bot->conversations()->sum('total_messages')) }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
                        <p class="text-xs text-purple-600 font-semibold mb-1">Tokens</p>
                        <p class="text-2xl font-bold text-purple-800">{{ number_format($bot->usageLogs()->sum('total_tokens')) }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-4">
                        <p class="text-xs text-pink-600 font-semibold mb-1">Cost</p>
                        <p class="text-2xl font-bold text-pink-800">${{ number_format($bot->usageLogs()->sum('cost'), 4) }}</p>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <i class="fas fa-robot text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">ยังไม่มี Bot</h3>
            <p class="text-gray-500 mb-4">เริ่มสร้าง AI Bot แรกของคุณ</p>
            <a href="{{ route('admin.ai-bots.create') }}" class="action-btn bg-gradient-to-r from-purple-500 to-indigo-600 text-white inline-block">
                <i class="fas fa-plus mr-2"></i>สร้าง Bot ใหม่
            </a>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($bots->hasPages())
    <div class="mt-6">
        {{ $bots->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function aiBotsManager() {
    return {
        bots: @json($bots->pluck('is_active', 'id')),

        async toggleBot(botId) {
            try {
                const response = await fetch(`/admin/ai-bots/${botId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.bots[botId] = data.is_active;
                    this.showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        },

        async duplicateBot(botId) {
            if (!confirm('ต้องการคัดลอก Bot นี้?')) return;

            try {
                const response = await fetch(`/admin/ai-bots/${botId}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    this.showToast('คัดลอก Bot สำเร็จ', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showToast('เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async deleteBot(botId) {
            if (!confirm('ต้องการลบ Bot นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) return;

            try {
                const response = await fetch(`/admin/ai-bots/${botId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    this.showToast('ลบ Bot สำเร็จ', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const data = await response.json();
                    this.showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        showToast(message, type = 'info') {
            const colors = {
                success: { bg: '#10b981', icon: 'check-circle' },
                error: { bg: '#ef4444', icon: 'times-circle' },
                warning: { bg: '#f59e0b', icon: 'exclamation-triangle' },
                info: { bg: '#3b82f6', icon: 'info-circle' }
            };

            const color = colors[type] || colors.info;
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `
                <div class="rounded-lg shadow-xl p-4 flex items-center gap-3" style="background: ${color.bg}; color: white;">
                    <i class="fas fa-${color.icon} text-xl"></i>
                    <span class="font-semibold">${message}</span>
                </div>
            `;

            document.getElementById('toast-container').appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }
}
</script>
@endpush
