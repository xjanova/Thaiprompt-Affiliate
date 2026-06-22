@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: จัดการ AI API Keys (ธีม V4 "นวลทองคำ")
     ── KPI สรุป + การ์ด provider + โหมดการวนใช้ key
     ── คงฟังก์ชัน/AJAX/Alpine/ปุ่ม action เดิม 100%
     ════════════════════════════════════════════════════════════ --}}
<div x-data="aiApiKeysDashboard()" x-init="init()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header: eyebrow + h1 + ปุ่ม action ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                หลังบ้าน · AI · API Keys
            </div>
            <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;">{{ $pageTitle }}</h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">จัดการ API Keys สำหรับ AI Providers ทั้งหมด</p>
        </div>

        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            {{-- รีเฟรชสถิติ (AJAX → stats) --}}
            <button @click="refreshStats()" class="tp-btn tp-btn-sm" type="button">
                <i class="fas fa-rotate" :class="{ 'fa-spin': loading }"></i>
                <span>รีเฟรช</span>
            </button>

            {{-- Usage Dashboard --}}
            <a href="{{ route('admin.ai-api-keys.usage-dashboard') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-chart-line" style="color:var(--accent2);"></i>
                <span>Usage Dashboard</span>
            </a>

            {{-- เพิ่ม API Key --}}
            <a href="{{ route('admin.ai-api-keys.create') }}" class="tp-btn tp-btn-primary tp-btn-sm">
                <i class="fas fa-plus"></i>
                <span>เพิ่ม API Key</span>
            </a>
        </div>
    </div>

    {{-- ── KPI Grid: สรุปรวมทุก provider ── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">

        {{-- Keys ทั้งหมด --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:46px;height:46px;border-radius:13px;font-size:18px;background:var(--accent2);">
                    <i class="fas fa-key"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.7rem;font-weight:800;color:var(--ink);line-height:1;"
                         x-text="formatNumber(summary.total_keys)">{{ number_format($summary['total_keys']) }}</div>
                    <div class="tp-muted" style="font-size:.78rem;margin-top:3px;">Keys ทั้งหมด</div>
                </div>
            </div>
        </div>

        {{-- Active --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:46px;height:46px;border-radius:13px;font-size:18px;background:#5aa07e;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.7rem;font-weight:800;color:#5aa07e;line-height:1;"
                         x-text="formatNumber(summary.active_keys)">{{ number_format($summary['active_keys']) }}</div>
                    <div class="tp-muted" style="font-size:.78rem;margin-top:3px;">Active</div>
                </div>
            </div>
        </div>

        {{-- พร้อมใช้ --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:46px;height:46px;border-radius:13px;font-size:18px;background:#b79ae8;">
                    <i class="fas fa-bolt"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.7rem;font-weight:800;color:#b79ae8;line-height:1;"
                         x-text="formatNumber(summary.available_keys)">{{ number_format($summary['available_keys']) }}</div>
                    <div class="tp-muted" style="font-size:.78rem;margin-top:3px;">พร้อมใช้</div>
                </div>
            </div>
        </div>

        {{-- Tokens วันนี้ --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:46px;height:46px;border-radius:13px;font-size:18px;background:#e0a52e;">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.7rem;font-weight:800;color:var(--ink);line-height:1;"
                         x-text="formatNumber(summary.tokens_used_today)">{{ number_format($summary['tokens_used_today']) }}</div>
                    <div class="tp-muted" style="font-size:.78rem;margin-top:3px;">Tokens วันนี้</div>
                </div>
            </div>
        </div>

        {{-- Tokens เดือนนี้ --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:46px;height:46px;border-radius:13px;font-size:18px;background:#5689b8;">
                    <i class="fas fa-calendar-days"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.7rem;font-weight:800;color:var(--ink);line-height:1;"
                         x-text="formatNumber(summary.tokens_used_month)">{{ number_format($summary['tokens_used_month']) }}</div>
                    <div class="tp-muted" style="font-size:.78rem;margin-top:3px;">Tokens เดือนนี้</div>
                </div>
            </div>
        </div>

        {{-- Requests วันนี้ --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:46px;height:46px;border-radius:13px;font-size:18px;background:#d6824a;">
                    <i class="fas fa-arrow-trend-up"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.7rem;font-weight:800;color:var(--ink);line-height:1;"
                         x-text="formatNumber(summary.requests_today)">{{ number_format($summary['requests_today']) }}</div>
                    <div class="tp-muted" style="font-size:.78rem;margin-top:3px;">Requests วันนี้</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Providers ── --}}
    <div>
        <div class="tp-section-h" style="margin-bottom:12px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-server" style="color:var(--accent1);"></i>
            <span>Providers</span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
            {{-- provider ที่มี key อยู่แล้ว --}}
            @foreach($providers as $provider => $stats)
            <div class="tp-card tp-card-hover" style="display:flex;flex-direction:column;gap:14px;">
                {{-- หัวการ์ด --}}
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                        <div class="tp-num" style="width:44px;height:44px;border-radius:12px;flex:none;
                                    display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.95rem;
                                    background:linear-gradient(135deg,var(--deep1),var(--deep2));color:#fff;
                                    box-shadow:var(--raise);">
                            {{ strtoupper(substr($provider, 0, 2)) }}
                        </div>
                        <div style="min-width:0;">
                            <h3 style="margin:0;font-weight:700;color:var(--ink);font-size:1rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $stats['name'] }}
                            </h3>
                            <div class="tp-muted" style="font-size:.78rem;">{{ $stats['total_keys'] }} keys</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.ai-api-keys.provider', $provider) }}" class="tp-icon-btn" title="จัดการ {{ $stats['name'] }}">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                {{-- badge สถานะ --}}
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <span class="tp-pill" style="color:#5aa07e;background:color-mix(in srgb,#5aa07e 16%,transparent);">{{ $stats['active_keys'] }} active</span>
                    <span class="tp-pill" style="color:#b79ae8;background:color-mix(in srgb,#b79ae8 16%,transparent);">{{ $stats['available_keys'] }} พร้อมใช้</span>
                </div>

                {{-- การใช้งาน token / request --}}
                <div class="tp-inset-sm" style="border-radius:12px;padding:12px;display:flex;flex-direction:column;gap:8px;">
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;">
                        <span class="tp-muted">Tokens วันนี้</span>
                        <span class="tp-num" style="font-weight:700;color:var(--ink);">{{ number_format($stats['tokens_used_today']) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;">
                        <span class="tp-muted">Tokens เดือนนี้</span>
                        <span class="tp-num" style="font-weight:700;color:var(--ink);">{{ number_format($stats['tokens_used_month']) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;">
                        <span class="tp-muted">Requests วันนี้</span>
                        <span class="tp-num" style="font-weight:700;color:var(--ink);">{{ number_format($stats['requests_today']) }}</span>
                    </div>
                </div>

                {{-- ปุ่ม action --}}
                <div style="display:flex;gap:8px;margin-top:auto;">
                    <a href="{{ route('admin.ai-api-keys.create', $provider) }}" class="tp-btn tp-btn-sm" style="flex:1;justify-content:center;">
                        <i class="fas fa-plus"></i>
                        <span>เพิ่ม Key</span>
                    </a>
                    <a href="{{ route('admin.ai-api-keys.provider', $provider) }}" class="tp-btn tp-btn-primary tp-btn-sm" style="flex:1;justify-content:center;">
                        <i class="fas fa-sliders"></i>
                        <span>จัดการ</span>
                    </a>
                </div>
            </div>
            @endforeach

            {{-- provider ที่ยังไม่มี key (เพิ่มใหม่) --}}
            @foreach(array_diff_key($allProviders, $providers) as $provider => $name)
            <a href="{{ route('admin.ai-api-keys.create', $provider) }}"
               class="tp-card tp-card-hover"
               style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;
                      gap:10px;border:2px dashed var(--sd);text-decoration:none;min-height:180px;">
                <div class="tp-inset" style="width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-plus" style="color:var(--accent1);font-size:1.1rem;"></i>
                </div>
                <h3 style="margin:0;font-weight:600;color:var(--ink2);font-size:.95rem;">{{ $name }}</h3>
                <p class="tp-muted" style="margin:0;font-size:.82rem;">เพิ่ม API Key</p>
            </a>
            @endforeach
        </div>
    </div>

    {{-- ── โหมดการวนใช้ API Keys ── --}}
    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-arrows-rotate" style="color:var(--accent1);"></i>
            <span>โหมดการวนใช้ API Keys</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            @foreach($rotationModes as $mode => $description)
            <div class="tp-inset-sm" style="border-radius:12px;padding:14px;">
                <h4 style="margin:0 0 4px;font-weight:700;color:var(--ink);font-size:.92rem;">
                    {{ ucfirst(str_replace('_', ' ', $mode)) }}
                </h4>
                <p class="tp-muted" style="margin:0;font-size:.82rem;line-height:1.45;">{{ $description }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- empty state: ไม่มี provider ที่มี key เลย --}}
    @if(count($providers) === 0)
    <div class="tp-card" style="text-align:center;padding:48px 18px;">
        <i class="fas fa-inbox" style="font-size:2.4rem;color:var(--ink2);opacity:.5;"></i>
        <p class="tp-muted" style="margin:14px 0 0;">ยังไม่มี API Key — เริ่มต้นโดยกด "เพิ่ม API Key"</p>
    </div>
    @endif
</div>
@endsection

{{-- scripts stack: Alpine dashboard logic (คง endpoint/payload เดิม) --}}
@push('scripts')
<script>
function aiApiKeysDashboard() {
    return {
        loading: false,
        summary: @json($summary),

        init() {
            // รีเฟรชสถิติอัตโนมัติทุก 30 วินาที
            setInterval(() => this.refreshStats(), 30000);
        },

        async refreshStats() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.stats') }}');
                const data = await response.json();
                if (data.success) {
                    this.summary = data.data.summary;
                }
            } catch (error) {
                console.error('Error refreshing stats:', error);
            } finally {
                this.loading = false;
            }
        },

        formatNumber(num) {
            return new Intl.NumberFormat('th-TH').format(num || 0);
        }
    };
}
</script>
@endpush
