@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: Usage Logs ของ AI API Keys (ธีม V4 "นวลทองคำ")
     ── filter bar + ตารางประวัติแบ่งหน้า + modal ดู error เต็ม
     ── คงฟังก์ชัน/field/route/Alpine เดิม 100%
     ════════════════════════════════════════════════════════════ --}}
@php
    // หา key ปัจจุบัน (กรณีดูเฉพาะ key เดียว) — แปลงจาก inline @php เดิม
    $currentKey = $keyId ? \App\Models\AiApiKey::find($keyId) : null;
@endphp
<div x-data="logsView()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header: ปุ่มย้อนกลับ + eyebrow + h1 + สรุปจำนวน ── --}}
    <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;">
        {{-- ปุ่มย้อนกลับ → provider ของ key (ถ้ามี) หรือ index --}}
        <a href="{{ ($keyId && $currentKey) ? route('admin.ai-api-keys.provider', $currentKey->provider) : route('admin.ai-api-keys.index') }}"
           class="tp-icon-btn" title="ย้อนกลับ" style="margin-top:4px;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                หลังบ้าน · AI · Usage Logs
            </div>
            <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;">
                Usage Logs
                @if($keyId && $currentKey)
                    <span class="tp-muted" style="font-size:1rem;font-weight:500;">— {{ $currentKey->name }}</span>
                @endif
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">
                ประวัติการใช้งาน API Keys
                @if($logs->total() > 0)
                    ({{ number_format($logs->total()) }} รายการ)
                @endif
            </p>
        </div>
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="tp-card">
        <form method="GET" action="{{ $keyId ? route('admin.ai-api-keys.key.logs', $keyId) : route('admin.ai-api-keys.logs') }}"
              style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;">
            @unless($keyId)
            <div>
                <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Provider</label>
                <select name="provider" class="tp-input">
                    <option value="">ทุก Provider</option>
                    @foreach($providers as $key => $name)
                        <option value="{{ $key }}" {{ request('provider') === $key ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endunless
            <div>
                <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">สถานะ</label>
                <select name="success" class="tp-input">
                    <option value="">ทุกสถานะ</option>
                    <option value="false" {{ request('success') === 'false' ? 'selected' : '' }}>ล้มเหลว เท่านั้น</option>
                    <option value="true" {{ request('success') === 'true' ? 'selected' : '' }}>สำเร็จ เท่านั้น</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm">
                    <i class="fas fa-filter"></i>
                    <span>กรอง</span>
                </button>
                <a href="{{ $keyId ? route('admin.ai-api-keys.key.logs', $keyId) : route('admin.ai-api-keys.logs') }}"
                   class="tp-btn tp-btn-sm">
                    <i class="fas fa-xmark"></i>
                    <span>ล้าง</span>
                </a>
            </div>
        </form>
    </div>

    {{-- ── Logs Table ── --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);white-space:nowrap;">เวลา</th>
                        @unless($keyId)
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);white-space:nowrap;">Key</th>
                        @endunless
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);white-space:nowrap;">Type</th>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);white-space:nowrap;">Model</th>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);white-space:nowrap;">สถานะ</th>
                        <th style="text-align:right;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);white-space:nowrap;">Tokens</th>
                        <th style="text-align:right;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);white-space:nowrap;">เวลาตอบ</th>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);white-space:nowrap;">Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr style="{{ ! $log->is_success ? 'background:var(--a1soft);' : '' }}"
                        onmouseover="this.style.background='var(--surf)'"
                        onmouseout="this.style.background='{{ ! $log->is_success ? 'var(--a1soft)' : 'transparent' }}'">
                        {{-- เวลา --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);white-space:nowrap;">
                            <div class="tp-num" style="font-size:.85rem;color:var(--ink);">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                            <div class="tp-muted" style="font-size:.72rem;">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        {{-- Key (เฉพาะหน้ารวม) --}}
                        @unless($keyId)
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);">
                            @if($log->apiKey)
                                <div style="display:flex;flex-direction:column;">
                                    <span style="font-size:.85rem;font-weight:600;color:var(--ink);">{{ $log->apiKey->name }}</span>
                                    <span class="tp-muted" style="font-size:.72rem;">
                                        {{ \App\Models\AiApiKey::PROVIDERS[$log->apiKey->provider] ?? $log->apiKey->provider }}
                                    </span>
                                </div>
                            @else
                                <span class="tp-muted" style="font-size:.72rem;font-style:italic;">— ลบแล้ว —</span>
                            @endif
                        </td>
                        @endunless
                        {{-- Type --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);">
                            <span style="font-size:.72rem;font-family:monospace;color:var(--ink2);">{{ $log->request_type ?? '-' }}</span>
                        </td>
                        {{-- Model --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);">
                            <span style="font-size:.78rem;color:var(--ink);">{{ $log->model ?? '-' }}</span>
                        </td>
                        {{-- สถานะ --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);">
                            @if($log->is_success)
                                <span class="tp-pill" style="color:#5aa07e;background:color-mix(in srgb,#5aa07e 16%,transparent);">
                                    <i class="fas fa-circle-check"></i> สำเร็จ
                                </span>
                            @else
                                <span class="tp-pill" style="color:#d46a6a;background:color-mix(in srgb,#d46a6a 16%,transparent);">
                                    <i class="fas fa-circle-xmark"></i> ล้มเหลว
                                </span>
                            @endif
                        </td>
                        {{-- Tokens --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);text-align:right;">
                            <div class="tp-num" style="font-size:.85rem;color:var(--ink);font-weight:600;">{{ number_format($log->total_tokens) }}</div>
                            @if($log->input_tokens || $log->output_tokens)
                                <div class="tp-muted" style="font-size:.72rem;font-family:monospace;">
                                    in {{ number_format($log->input_tokens) }} / out {{ number_format($log->output_tokens) }}
                                </div>
                            @endif
                        </td>
                        {{-- เวลาตอบ --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);text-align:right;">
                            @if($log->response_time_ms !== null)
                                <span class="tp-num" style="font-size:.85rem;color:var(--ink2);">{{ number_format($log->response_time_ms) }} ms</span>
                            @else
                                <span class="tp-muted" style="font-size:.72rem;">-</span>
                            @endif
                        </td>
                        {{-- Error (คลิกเพื่อ expand เต็ม) --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);max-width:24rem;">
                            @if(! empty($log->error_message))
                                {{-- คลิกเพื่อ expand เต็ม (long messages) --}}
                                <button type="button"
                                        @click="showLog({
                                            time: @js($log->created_at->format('Y-m-d H:i:s')),
                                            key: @js($log->apiKey?->name ?? '— ลบแล้ว —'),
                                            error: @js($log->error_message),
                                        })"
                                        style="text-align:left;font-size:.72rem;font-family:monospace;color:#d46a6a;
                                               max-width:20rem;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
                                               cursor:pointer;background:none;border:none;padding:0;text-decoration:underline;text-underline-offset:2px;"
                                        title="คลิกเพื่อดูเต็ม">
                                    {{ \Illuminate\Support\Str::limit($log->error_message, 80) }}
                                </button>
                            @else
                                <span class="tp-muted" style="font-size:.72rem;">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $keyId ? 7 : 8 }}" style="padding:48px 24px;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;">
                                <i class="fas fa-file-lines" style="font-size:2.4rem;color:var(--ink2);opacity:.4;margin-bottom:12px;"></i>
                                <p class="tp-muted" style="margin:0;">ยังไม่มี usage logs</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--sd);">
            {{ $logs->withQueryString()->links() }}
        </div>
        @endif
    </div>

    {{-- ── 🔍 Log Detail Modal — ดูเต็ม error message ── --}}
    <div x-show="logModal.open"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="logModal.open = false"
         style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.5);"
         @click.self="logModal.open = false">
        <div class="tp-card"
             style="max-width:48rem;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;padding:0;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            {{-- หัว modal --}}
            <div style="padding:16px 20px;border-bottom:1px solid var(--sd);display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div style="min-width:0;">
                    <h3 class="tp-section-h" style="margin:0;font-size:1.05rem;">รายละเอียด Error</h3>
                    <p class="tp-muted" style="font-size:.72rem;margin:2px 0 0;">
                        <span x-text="logModal.key"></span> · <span x-text="logModal.time"></span>
                    </p>
                </div>
                <button type="button" @click="logModal.open = false" class="tp-icon-btn" title="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            {{-- เนื้อหา error --}}
            <div style="flex:1;overflow-y:auto;padding:20px;">
                <pre class="tp-inset" style="border-radius:12px;padding:16px;font-size:.85rem;color:#d46a6a;white-space:pre-wrap;word-break:break-word;font-family:monospace;margin:0;"
                     x-text="logModal.error"></pre>
            </div>
            {{-- ปุ่ม footer --}}
            <div style="padding:16px 20px;border-top:1px solid var(--sd);display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                <button type="button" @click="copyError()" class="tp-btn tp-btn-sm">
                    <i class="fas fa-copy"></i>
                    <span>คัดลอก</span>
                </button>
                <button type="button" @click="logModal.open = false" class="tp-btn tp-btn-primary tp-btn-sm">
                    <i class="fas fa-check"></i>
                    <span>ปิด</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- scripts stack: Alpine logsView (คง state/method เดิม 100%) --}}
@push('scripts')
<script>
function logsView() {
    return {
        logModal: {
            open: false,
            time: '',
            key: '',
            error: '',
        },

        showLog(payload) {
            this.logModal = {
                open: true,
                time: payload.time || '',
                key: payload.key || '',
                error: payload.error || '',
            };
        },

        async copyError() {
            try {
                await navigator.clipboard.writeText(this.logModal.error);
                alert('คัดลอกแล้ว');
            } catch (e) {
                alert('คัดลอกไม่สำเร็จ');
            }
        },
    };
}
</script>
@endpush
