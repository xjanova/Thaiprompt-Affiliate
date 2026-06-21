@extends('layouts.admin-v4')

@section('title', 'คำถามที่รอแอดมินตอบ')

@section('content')
{{-- ───────────────────────────────────────────────────────────────
     หน้า "คำถามที่รอแอดมินตอบ" — ธีม V4 นวลทองคำ
     คำถามที่ AI "จันทรา" ตอบไม่ได้ → บันทึกไว้ให้แอดมินตอบกลับ
     ฟังก์ชันคงเดิม 100%: ค้นหา/กรอง (GET form) + ตอบ(modal Alpine + Js::from)
                          + ส่งซ้ำ(POST resend) + ลบ(POST destroy)
     ──────────────────────────────────────────────────────────── --}}
<div x-data="savedQuestions()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ───── Header ───── --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · คำถามรอตอบ
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                คำถามที่รอแอดมินตอบ 📝
            </h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px; max-width:560px;">
                คำถามที่ AI “จันทรา” ตอบไม่ได้ จะถูกบันทึกไว้ที่นี่ เพื่อให้แอดมินมาตอบกลับ — คำตอบจะถูกส่งกลับหาผู้ใช้อัตโนมัติ
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <span class="tp-pill tp-pill-soft" style="display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-clock" style="color:#e0a52e;"></i>
                รอตอบ <strong class="tp-num">{{ number_format($stats['pending']) }}</strong>
            </span>
        </div>
    </div>

    {{-- ───── KPI grid ───── --}}
    @php
        // ไทล์สถิติ — icon + สี status ตาม DESIGN KIT
        $kpis = [
            ['label' => 'ทั้งหมด',    'value' => $stats['total'],    'icon' => 'fa-layer-group', 'color' => 'var(--accent1)'],
            ['label' => 'รอตอบ',     'value' => $stats['pending'],  'icon' => 'fa-hourglass-half', 'color' => '#e0a52e'],
            ['label' => 'ตอบแล้ว',   'value' => $stats['replied'],  'icon' => 'fa-circle-check', 'color' => '#5aa07e'],
            ['label' => 'LINE',      'value' => $stats['line'],     'icon' => 'fa-comment-dots', 'color' => '#5aa07e'],
            ['label' => 'Facebook',  'value' => $stats['facebook'], 'icon' => 'fa-facebook-messenger', 'color' => '#5689b8', 'brand' => true],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
        @foreach($kpis as $kpi)
            <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
                <div class="tp-tile" style="flex-shrink:0;">
                    <i class="{{ ($kpi['brand'] ?? false) ? 'fab' : 'fas' }} {{ $kpi['icon'] }}" style="color:{{ $kpi['color'] }};"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:12px; color:var(--ink2); font-weight:600;">{{ $kpi['label'] }}</div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; line-height:1.1; color:{{ $kpi['color'] }};">
                        {{ number_format($kpi['value']) }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ───── Filters (GET form — คง name/value เดิม) ───── --}}
    <div class="tp-card">
        <form method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
            {{-- ค้นหา --}}
            <div class="tp-well tp-input" style="flex:1; min-width:220px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-magnifying-glass" style="color:var(--ink2);"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาคำถาม, ชื่อผู้ใช้, User ID..."
                       style="width:100%; background:transparent; border:0; outline:0; color:var(--ink); font-size:14px;">
            </div>

            {{-- กรองสถานะ --}}
            <div class="tp-well tp-input" style="padding:0; min-width:160px;">
                <select name="status" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                    <option value="">สถานะทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ รอตอบ</option>
                    <option value="replied" {{ request('status') === 'replied' ? 'selected' : '' }}>✅ ตอบแล้ว</option>
                </select>
            </div>

            {{-- กรอง platform --}}
            <div class="tp-well tp-input" style="padding:0; min-width:160px;">
                <select name="platform" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                    <option value="">ทุก Platform</option>
                    <option value="line" {{ request('platform') === 'line' ? 'selected' : '' }}>💬 LINE</option>
                    <option value="facebook" {{ request('platform') === 'facebook' ? 'selected' : '' }}>👥 Facebook</option>
                </select>
            </div>

            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-magnifying-glass"></i> ค้นหา
            </button>

            @if(request()->hasAny(['search','status','platform']))
                @if(Route::has('admin.fortune.saved-questions.index'))
                    <a href="{{ route('admin.fortune.saved-questions.index') }}" class="tp-btn">
                        <i class="fas fa-rotate-left"></i> ล้าง
                    </a>
                @endif
            @endif
        </form>
    </div>

    {{-- ───── Questions List ───── --}}
    <div style="display:flex; flex-direction:column; gap:14px;">
        @forelse($questions as $q)
            @php
                // สีแถบซ้าย + meta ตามสถานะ
                $accentBar = $q->is_replied ? '#5aa07e' : '#e0a52e';
                $isFb      = $q->platform === 'facebook';
            @endphp
            <div class="tp-card tp-card-hover" style="position:relative; overflow:hidden; padding-left:22px;">
                {{-- แถบสถานะด้านซ้าย --}}
                <span style="position:absolute; left:0; top:0; bottom:0; width:5px; background:{{ $accentBar }};"></span>

                <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:16px;">
                    {{-- ───── ข้อมูลคำถาม ───── --}}
                    <div style="flex:1; min-width:260px;">
                        {{-- badges --}}
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:10px;">
                            {{-- Platform --}}
                            @if($isFb)
                                <span class="tp-pill" style="background:rgba(86,137,184,.16); color:#5689b8;">
                                    👥 Facebook
                                </span>
                            @else
                                <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">
                                    💬 LINE
                                </span>
                            @endif

                            {{-- Status --}}
                            @if($q->is_replied)
                                <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">
                                    <i class="fas fa-circle-check"></i> ตอบแล้ว
                                </span>
                            @else
                                <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#e0a52e;">
                                    <i class="fas fa-hourglass-half"></i> รอตอบ
                                </span>
                            @endif

                            {{-- Reason (accessor reason_label) --}}
                            <span class="tp-pill tp-pill-soft">
                                <i class="fas fa-circle-info" style="color:var(--ink2);"></i> {{ $q->reason_label }}
                            </span>

                            <span class="tp-muted" style="font-size:12px;">
                                <i class="far fa-clock"></i> {{ $q->created_at->diffForHumans() }}
                            </span>
                        </div>

                        {{-- ผู้ใช้ --}}
                        <div class="tp-muted" style="font-size:13px; margin-bottom:10px;">
                            <i class="fas fa-user-circle" style="color:var(--accent1);"></i>
                            {{ $q->user_name ?: $q->platform_user_id }}
                        </div>

                        {{-- คำถาม --}}
                        <div class="tp-inset" style="padding:12px 14px; margin-bottom:10px; border-radius:12px;">
                            <div style="font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; margin-bottom:4px;">
                                <i class="fas fa-question"></i> คำถาม
                            </div>
                            <p style="margin:0; color:var(--ink); font-size:14px; white-space:pre-wrap; word-break:break-word;">{{ $q->question }}</p>
                        </div>

                        {{-- AI ตอบ (ถ้ามี) --}}
                        @if($q->ai_response)
                            <div class="tp-inset-sm" style="padding:12px 14px; margin-bottom:10px; border-radius:12px; border-left:3px solid #5689b8;">
                                <div style="font-size:11px; font-weight:700; color:#5689b8; letter-spacing:.3px; margin-bottom:4px;">
                                    <i class="fas fa-robot"></i> AI ตอบ
                                </div>
                                <p style="margin:0; color:var(--ink2); font-size:13px; white-space:pre-wrap; word-break:break-word;">{{ $q->ai_response }}</p>
                            </div>
                        @endif

                        {{-- แอดมินตอบ (ถ้าตอบแล้ว) --}}
                        @if($q->is_replied)
                            <div class="tp-inset-sm" style="padding:12px 14px; border-radius:12px; border-left:3px solid #5aa07e;">
                                <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; margin-bottom:5px;">
                                    <span style="font-size:11px; font-weight:700; color:#5aa07e; letter-spacing:.3px;">
                                        <i class="fas fa-user-shield"></i> แอดมินตอบ
                                        @if($q->replied_at)
                                            <span class="tp-muted" style="font-weight:500;">· {{ $q->replied_at->diffForHumans() }}</span>
                                        @endif
                                    </span>
                                    @if($q->is_sent_to_user)
                                        <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">
                                            <i class="fas fa-paper-plane"></i> ส่งถึงผู้ใช้แล้ว
                                        </span>
                                    @else
                                        <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#e0a52e;">
                                            <i class="fas fa-triangle-exclamation"></i> ยังไม่ได้ส่ง
                                        </span>
                                    @endif
                                </div>
                                <p style="margin:0; color:var(--ink); font-size:14px; white-space:pre-wrap; word-break:break-word;">{{ $q->admin_reply }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- ───── ปุ่ม Action ───── --}}
                    <div style="display:flex; flex-direction:column; gap:8px; flex-shrink:0; min-width:140px;">
                        @if(!$q->is_replied)
                            {{-- ใช้ Js::from() แทน addslashes — escape ขึ้นบรรทัดใหม่/U+2028/quote ได้ครบ
                                 (addslashes ไม่ escape newline → คำถามหลายบรรทัดจาก LINE/FB ทำให้ JS พัง กดตอบไม่ได้) --}}
                            <button type="button"
                                    @click="openReply({{ $q->id }}, {{ \Illuminate\Support\Js::from($q->question) }}, {{ \Illuminate\Support\Js::from($q->platform ?? 'line') }})"
                                    class="tp-btn tp-btn-primary tp-btn-sm" style="width:100%; justify-content:center;">
                                <i class="fas fa-pen"></i> ตอบ
                            </button>
                        @elseif(!$q->is_sent_to_user)
                            {{-- ส่งซ้ำ — POST resend (คง action/method/@csrf เดิม) --}}
                            <form method="POST" action="{{ route('admin.fortune.saved-questions.resend', $q) }}" style="margin:0;">
                                @csrf
                                <button type="submit" class="tp-btn tp-btn-sm" style="width:100%; justify-content:center; background:#5aa07e; color:#fff;">
                                    <i class="fas fa-paper-plane"></i> ส่งซ้ำ ({{ $isFb ? 'FB' : 'LINE' }})
                                </button>
                            </form>
                        @endif

                        {{-- ลบ — POST destroy + @method('DELETE') (คงเดิม + confirm) --}}
                        <form method="POST" action="{{ route('admin.fortune.saved-questions.destroy', $q) }}"
                              onsubmit="return confirm('ลบคำถามนี้?')" style="margin:0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="tp-btn tp-btn-sm" style="width:100%; justify-content:center; background:#d9534f; color:#fff;">
                                <i class="fas fa-trash-can"></i> ลบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            {{-- Empty state --}}
            <div class="tp-card" style="text-align:center; padding:48px 20px;">
                <i class="fas fa-inbox" style="font-size:42px; color:var(--ink2); opacity:.6;"></i>
                <p class="tp-muted" style="margin:14px 0 0; font-size:15px;">ยังไม่มีคำถามที่รอตอบ</p>
            </div>
        @endforelse
    </div>

    {{-- ───── Pagination ───── --}}
    @if($questions->hasPages())
        <div class="tp-card" style="display:flex; justify-content:center; padding:12px;">
            {{ $questions->links() }}
        </div>
    @endif

    {{-- ───── Reply Modal (Alpine — มี x-data root จาก wrapper ด้านบน) ───── --}}
    <div x-show="showReplyModal" x-cloak
         @keydown.escape.window="showReplyModal = false"
         style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,.5); padding:16px;">
        <div @click.outside="showReplyModal = false"
             class="tp-card tp-raise"
             style="width:100%; max-width:520px; max-height:90vh; overflow-y:auto;">
            {{-- หัว modal + platform indicator --}}
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
                <div class="tp-tile" style="flex-shrink:0;">
                    <i class="fas fa-pen" style="color:var(--accent1);"></i>
                </div>
                <h3 class="tp-num" style="font-size:18px; font-weight:800; margin:0;">ตอบคำถาม</h3>
                <span x-show="replyPlatform === 'facebook'" x-cloak class="tp-pill" style="background:rgba(86,137,184,.16); color:#5689b8;">
                    👥 Facebook
                </span>
                <span x-show="replyPlatform === 'line' || !replyPlatform" class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">
                    💬 LINE
                </span>
            </div>

            {{-- แสดงคำถาม --}}
            <div class="tp-inset" style="padding:12px 14px; margin-bottom:14px; border-radius:12px;">
                <div style="font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; margin-bottom:4px;">
                    <i class="fas fa-question"></i> คำถาม
                </div>
                <p style="margin:0; color:var(--ink); font-size:14px; white-space:pre-wrap; word-break:break-word;" x-text="replyQuestion"></p>
            </div>

            {{-- แจ้งช่องทางส่งกลับ --}}
            <div class="tp-inset-sm" style="padding:11px 14px; margin-bottom:14px; border-radius:12px; border-left:3px solid #e0a52e;">
                <span style="font-size:13px; color:var(--ink2);">
                    <i class="fas fa-paper-plane" style="color:#e0a52e;"></i>
                    คำตอบจะถูกส่งกลับหาผู้ใช้ผ่าน
                    <strong x-text="replyPlatform === 'facebook' ? 'Facebook Messenger' : 'LINE'" style="color:var(--ink);"></strong>
                    อัตโนมัติ
                </span>
            </div>

            {{-- ฟอร์มตอบ — action สร้างจาก replyId (คง path/method/@csrf/field เดิม) --}}
            <form :action="'/admin/fortune/saved-questions/' + replyId + '/reply'" method="POST">
                @csrf
                <div class="tp-well tp-input" style="padding:0; margin-bottom:16px;">
                    <textarea name="admin_reply" rows="4" placeholder="พิมพ์คำตอบ..." required
                              style="width:100%; background:transparent; border:0; outline:0; padding:12px 14px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" @click="showReplyModal = false" class="tp-btn">
                        ยกเลิก
                    </button>
                    <button type="submit" class="tp-btn tp-btn-primary">
                        <i class="fas fa-paper-plane"></i> ส่งคำตอบ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ───── Alpine component: จัดการ modal ตอบคำถาม (logic เดิมคงไว้ 100%) ─────
function savedQuestions() {
    return {
        showReplyModal: false,
        replyId: null,
        replyQuestion: '',
        replyPlatform: 'line',
        // เปิด modal ตอบ — รับ id/คำถาม/platform จากปุ่ม (ผ่าน Js::from)
        openReply(id, question, platform) {
            this.replyId = id;
            this.replyQuestion = question;
            this.replyPlatform = platform || 'line';
            this.showReplyModal = true;
        }
    }
}
</script>
@endpush
