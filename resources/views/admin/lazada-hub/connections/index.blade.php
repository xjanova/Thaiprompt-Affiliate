@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'การเชื่อมต่อ Lazada')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: Lazada Hub — การเชื่อมต่อ (รายการ credential)
     ════════════════════════════════════════════════════════════ --}}
<div x-data="lazadaConnections()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                <a href="{{ route('admin.lazada-hub.dashboard') }}" style="color:var(--accent2);text-decoration:none;">Lazada Hub</a> · การเชื่อมต่อ
            </div>
            <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-plug" style="color:var(--accent1);"></i>
                การเชื่อมต่อ API
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">กรอกคีย์ของ Lazada — ได้อันไหนมาก่อนกรอกอันนั้น แล้วกดทดสอบ</p>
        </div>
        <a href="{{ route('admin.lazada-hub.connections.create') }}" class="tp-btn tp-btn-primary">
            <i class="fas fa-plus"></i> <span>เพิ่มการเชื่อมต่อ</span>
        </a>
    </div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="tp-card" style="border-left:4px solid #5aa07e;color:var(--ink);font-size:.88rem;">
            <i class="fas fa-circle-check" style="color:#5aa07e;"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="border-left:4px solid #d9534f;color:var(--ink);font-size:.88rem;">
            <i class="fas fa-circle-exclamation" style="color:#d9534f;"></i> {{ session('error') }}
        </div>
    @endif

    {{-- ── คำแนะนำชนิดโปรแกรม ── --}}
    <div class="tp-card" style="border-left:4px solid var(--accent1);">
        <div style="font-weight:700;color:var(--ink);margin-bottom:6px;"><i class="fas fa-circle-info" style="color:var(--accent1);"></i> Lazada มี 2 โปรแกรม</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;font-size:.84rem;color:var(--ink2);line-height:1.6;">
            <div>
                <b style="color:var(--ink);">🔵 Seller (Open Platform)</b><br>
                จัดการร้านตัวเอง — ดึงสินค้า/ราคา/สต็อก/ออเดอร์ · เหมาะกับ "ขายเองบวกกำไร"<br>
                <a href="{{ $docLinks['seller'] }}" target="_blank" rel="noopener" style="color:var(--accent1);font-size:.78rem;"><i class="fas fa-external-link-alt"></i> เอกสาร API</a>
            </div>
            <div>
                <b style="color:var(--ink);">🟣 Affiliate (Involve Asia)</b><br>
                สร้างลิงก์ได้ค่าคอม ~6.5% · เหมาะกับโหมด "affiliate"<br>
                <a href="{{ $docLinks['affiliate'] }}" target="_blank" rel="noopener" style="color:var(--accent1);font-size:.78rem;"><i class="fas fa-external-link-alt"></i> เอกสาร Involve Asia</a>
            </div>
        </div>
    </div>

    {{-- ── Callback URL (เอาไปกรอกในแอป Lazada ตอนลงทะเบียน OAuth) ── --}}
    <div class="tp-card" style="border-left:4px solid var(--accent2);">
        <div style="font-weight:700;color:var(--ink);margin-bottom:6px;"><i class="fas fa-link" style="color:var(--accent2);"></i> Callback URL (สำหรับลงทะเบียนแอปบน Lazada)</div>
        <p class="tp-muted" style="margin:0 0 8px;font-size:.82rem;line-height:1.6;">
            นำลิงก์นี้ไปวางช่อง <b>Callback URL</b> ตอนสร้างแอปบน open.lazada.com — เมื่อกด “เชื่อมต่อ” Lazada จะเด้งกลับมาที่นี่พร้อม code เพื่อแลก Access Token ให้อัตโนมัติ
        </p>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <code class="tp-inset tp-num" style="padding:9px 12px;border-radius:10px;font-size:.8rem;color:var(--ink);word-break:break-all;flex:1;min-width:240px;">{{ $callbackUrl }}</code>
            <button type="button" class="tp-btn tp-btn-sm" @click="copyCallback()">
                <i class="fas" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                <span x-text="copied ? 'คัดลอกแล้ว' : 'คัดลอก'"></span>
            </button>
        </div>
        <p class="tp-muted" style="margin:8px 0 0;font-size:.74rem;">* ถ้ายังไม่มี OAuth จะวาง Access Token เองในหน้าแก้ไขก็ได้ (2 ทางเลือก)</p>
    </div>

    {{-- ── รายการการเชื่อมต่อ ── --}}
    @if($accounts->isEmpty())
        <div class="tp-card" style="text-align:center;padding:40px 20px;color:var(--ink2);">
            <i class="fas fa-plug" style="font-size:2rem;color:var(--ink2);opacity:.4;"></i>
            <p style="margin:12px 0 0;">ยังไม่มีการเชื่อมต่อ — กด "เพิ่มการเชื่อมต่อ" เพื่อเริ่ม</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($accounts as $acc)
                @php
                    $statusMap = [
                        'active' => ['สีพร้อมใช้', '#5aa07e', 'พร้อมใช้งาน'],
                        'inactive' => ['', 'var(--ink2)', 'ยังไม่ทดสอบ'],
                        'error' => ['', '#d9534f', 'มีปัญหา'],
                        'expired' => ['', '#e0a52e', 'token หมดอายุ'],
                    ];
                    $st = $statusMap[$acc->status] ?? ['', 'var(--ink2)', $acc->status];
                    $isAff = $acc->program_type === 'affiliate';
                @endphp
                <div class="tp-card" style="display:flex;flex-wrap:wrap;align-items:center;gap:14px;">
                    {{-- ไอคอนชนิด --}}
                    <div class="tp-inset" style="width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas {{ $isAff ? 'fa-link' : 'fa-store' }}" style="color:{{ $isAff ? '#b79ae8' : '#5a8fd0' }};font-size:1.15rem;"></i>
                    </div>

                    {{-- ชื่อ + ป้าย --}}
                    <div style="flex:1;min-width:180px;">
                        <div style="font-weight:700;color:var(--ink);font-size:.95rem;">{{ $acc->account_name }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:5px;align-items:center;">
                            <span class="tp-pill" style="background:{{ $isAff ? '#b79ae8' : '#5a8fd0' }};color:#fff;font-size:10px;font-weight:700;">{{ $isAff ? 'Affiliate' : 'Seller' }}</span>
                            <span class="tp-pill" style="background:{{ $st[1] }};color:#fff;font-size:10px;font-weight:700;">{{ $st[2] }}</span>
                            @if($acc->shop_id)
                                <span class="tp-muted tp-num" style="font-size:.72rem;">ร้าน: {{ $acc->shop_id }}</span>
                            @endif
                            @if($acc->last_sync_at)
                                <span class="tp-muted" style="font-size:.72rem;">sync ล่าสุด: {{ $acc->last_sync_at->diffForHumans() }}</span>
                            @endif
                        </div>
                        @if($acc->status === 'error' && $acc->last_error)
                            <div style="color:#d9534f;font-size:.74rem;margin-top:5px;word-break:break-word;">{{ \Illuminate\Support\Str::limit($acc->last_error, 140) }}</div>
                        @endif
                    </div>

                    {{-- ปุ่ม --}}
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        @unless($isAff)
                        <a href="{{ route('admin.lazada-hub.connections.connect', $acc) }}" class="tp-btn tp-btn-sm" style="color:#5a8fd0;">
                            <i class="fas fa-link"></i> <span>เชื่อมต่อ</span>
                        </a>
                        @endunless
                        <button type="button" class="tp-btn tp-btn-sm" @click="test({{ $acc->id }})" :disabled="testing === {{ $acc->id }}">
                            <i class="fas" :class="testing === {{ $acc->id }} ? 'fa-spinner fa-spin' : 'fa-vial'"></i>
                            <span>ทดสอบ</span>
                        </button>
                        <a href="{{ route('admin.lazada-hub.connections.edit', $acc) }}" class="tp-btn tp-btn-sm"><i class="fas fa-pen"></i> <span>แก้ไข</span></a>
                        <form method="POST" action="{{ route('admin.lazada-hub.connections.destroy', $acc) }}"
                              onsubmit="return confirm('ลบการเชื่อมต่อนี้?');" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="tp-btn tp-btn-sm" style="color:#d9534f;"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>

                    {{-- ผลทดสอบ (inline) --}}
                    <div x-show="result[{{ $acc->id }}]" x-cloak style="display:none;flex-basis:100%;font-size:.82rem;"
                         :style="result[{{ $acc->id }}]?.success ? 'color:#5aa07e;' : 'color:#d9534f;'">
                        <i class="fas" :class="result[{{ $acc->id }}]?.success ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                        <span x-text="result[{{ $acc->id }}]?.message"></span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function lazadaConnections() {
    return {
        testing: null,
        result: {},
        callbackUrl: @json($callbackUrl),
        copied: false,
        csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        copyCallback() {
            const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1500); };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.callbackUrl).then(done).catch(done);
            } else {
                done();
            }
        },

        async test(id) {
            this.testing = id;
            this.result[id] = null;
            try {
                const res = await fetch(`{{ url('admin/lazada-hub/connections') }}/${id}/test`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                });
                this.result[id] = await res.json();
            } catch (e) {
                this.result[id] = { success: false, message: 'เกิดข้อผิดพลาด: ' + e.message };
            } finally {
                this.testing = null;
            }
        }
    };
}
</script>
@endpush
