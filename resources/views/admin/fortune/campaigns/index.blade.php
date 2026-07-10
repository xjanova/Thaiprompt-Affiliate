{{-- แคมเปญคอนเทนต์อัตโนมัติ — ธีม V4 "นวลทองคำ" --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
<div x-data="contentCampaignPage()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ──────────── Header ──────────── --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · แคมเปญคอนเทนต์อัตโนมัติ
            </div>
            <h1 class="tp-num" style="margin:0;font-size:26px;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-bullhorn" style="color:var(--accent1);"></i> แคมเปญคอนเทนต์อัตโนมัติ
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">
                หลายแคมเปญอิสระ — คนละแนว คนละเวลา · AI เขียนโพส + สร้างภาพให้เข้ากับเนื้อหาอัตโนมัติ
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @if($stats['enabled_campaigns'] > 0)
                <form action="{{ route('admin.fortune.content-campaigns.disable-all') }}" method="POST"
                      onsubmit="return confirm('หยุดโพสอัตโนมัติทุกแคมเปญทันที?')">
                    @csrf
                    <button type="submit" class="tp-btn" style="color:#d9534f;" title="Emergency: ปิดทุกแคมเปญทันที">
                        <i class="fas fa-hand"></i> หยุดทั้งหมด
                    </button>
                </form>
            @endif
            <button type="button" class="tp-btn tp-btn-primary" @click="showCreate = !showCreate">
                <i class="fas fa-plus"></i> สร้างแคมเปญใหม่
            </button>
        </div>
    </div>

    {{-- ──────────── KPI Grid ──────────── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        <div class="tp-card tp-raise" style="padding:18px;">
            <div class="tp-muted" style="font-size:12px;">แคมเปญทั้งหมด</div>
            <div class="tp-num" style="font-size:28px;margin-top:6px;">{{ $stats['total_campaigns'] }}</div>
            <div class="tp-spark" style="margin-top:8px;color:var(--ink2);font-size:12px;">
                <i class="fas fa-layer-group"></i> เปิดใช้ {{ $stats['enabled_campaigns'] }} แคมเปญ
            </div>
        </div>
        <div class="tp-card tp-raise" style="padding:18px;">
            <div class="tp-muted" style="font-size:12px;">โพสสำเร็จวันนี้</div>
            <div class="tp-num" style="font-size:28px;margin-top:6px;color:#5aa07e;">{{ $stats['posted_today'] }}</div>
            <div class="tp-spark" style="margin-top:8px;color:#5aa07e;font-size:12px;">
                <i class="fas fa-circle-check"></i> ส่งขึ้น FB เรียบร้อย
            </div>
        </div>
        <div class="tp-card tp-raise" style="padding:18px;">
            <div class="tp-muted" style="font-size:12px;">ล้มเหลววันนี้</div>
            <div class="tp-num" style="font-size:28px;margin-top:6px;color:{{ $stats['failed_today'] > 0 ? '#d9534f' : 'var(--ink2)' }};">{{ $stats['failed_today'] }}</div>
            <div class="tp-spark" style="margin-top:8px;color:{{ $stats['failed_today'] > 0 ? '#d9534f' : 'var(--ink2)' }};font-size:12px;">
                <i class="fas {{ $stats['failed_today'] > 0 ? 'fa-triangle-exclamation' : 'fa-circle-minus' }}"></i>
                {{ $stats['failed_today'] > 0 ? 'ตรวจสอบ error ด้านล่าง' : 'ไม่มีข้อผิดพลาด' }}
            </div>
        </div>
        <div class="tp-card tp-raise" style="padding:18px;">
            <div class="tp-muted" style="font-size:12px;">Pipeline ที่ใช้ร่วมกัน</div>
            <div style="font-size:13px;margin-top:8px;line-height:1.7;color:var(--ink2);">
                <i class="fas fa-robot"></i> AI เขียน + ภาพอัตโนมัติ<br>
                <i class="fab fa-facebook"></i> โพสขึ้น Page เดียวกัน
            </div>
        </div>
    </div>

    {{-- ──────────── Flash / Errors ──────────── --}}
    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px;border-left:4px solid #5aa07e;color:#5aa07e;">
            <i class="fas fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px;border-left:4px solid #d9534f;color:#d9534f;">
            <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="tp-card" style="padding:16px 18px;border-left:4px solid #d9534f;">
            <div style="display:flex;align-items:center;gap:8px;color:#d9534f;font-weight:600;margin-bottom:8px;">
                <i class="fas fa-circle-exclamation"></i> พบข้อผิดพลาด
            </div>
            <ul style="margin:0;padding-left:20px;color:var(--ink2);font-size:13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ──────────── สร้างแคมเปญใหม่ ──────────── --}}
    <div class="tp-card" style="padding:20px;" x-show="showCreate" x-cloak>
        <h2 style="margin:0 0 14px;font-size:17px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-plus-circle" style="color:var(--accent1);"></i> สร้างแคมเปญใหม่
        </h2>
        <form action="{{ route('admin.fortune.content-campaigns.store') }}" method="POST">
            @csrf
            {{-- marker ให้รู้ว่า validation error มาจากฟอร์มไหน (กันเด้งเปิดผิดฟอร์มแล้วสร้างซ้ำ) --}}
            <input type="hidden" name="_form" value="create">
            @include('admin.fortune.campaigns._form', ['campaign' => null])
            <div style="margin-top:16px;display:flex;gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-save"></i> สร้างแคมเปญ
                </button>
                <button type="button" class="tp-btn" @click="showCreate = false">ยกเลิก</button>
            </div>
        </form>
    </div>

    {{-- ──────────── รายการแคมเปญ ──────────── --}}
    @forelse($campaigns as $campaign)
        <div class="tp-card" style="padding:20px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                <div style="min-width:0;">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <h2 style="margin:0;font-size:17px;">
                            {{ $campaign->emoji }} {{ $campaign->name_th }}
                        </h2>
                        <span class="tp-pill {{ $campaign->is_enabled ? 'tp-pill-gold' : 'tp-pill-soft' }}" style="font-size:11px;">
                            {{ $campaign->is_enabled ? 'เปิดใช้งาน' : 'ปิดอยู่' }}
                        </span>
                        @if($campaign->topic_source === 'mystic_topics')
                            <span class="tp-pill tp-pill-soft" style="font-size:11px;">🌙 ใช้คลังสายมูเดิม</span>
                        @endif
                    </div>
                    @if($campaign->description_th)
                        <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">{{ Str::limit($campaign->description_th, 140) }}</p>
                    @endif
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;align-items:center;">
                        <span class="tp-muted" style="font-size:12px;"><i class="fas fa-clock"></i> เวลาโพส:</span>
                        @forelse($campaign->scheduleSlots() as $slot)
                            <span class="tp-pill tp-pill-soft" style="font-size:11px;">{{ $slot['key'] }}</span>
                        @empty
                            <span class="tp-muted" style="font-size:12px;">— ยังไม่ตั้งเวลา (จะไม่โพสอัตโนมัติ)</span>
                        @endforelse
                        <span class="tp-muted" style="font-size:12px;margin-left:8px;">
                            <i class="fas fa-paper-plane"></i> โพสแล้ว {{ number_format($campaign->post_count) }} ครั้ง
                            @if($campaign->last_posted_at)
                                · ล่าสุด {{ $campaign->last_posted_at->diffForHumans() }}
                            @endif
                        </span>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <form action="{{ route('admin.fortune.content-campaigns.toggle', $campaign) }}" method="POST">
                        @csrf
                        <button type="submit" class="tp-btn" title="เปิด/ปิดแคมเปญ">
                            <i class="fas {{ $campaign->is_enabled ? 'fa-pause' : 'fa-play' }}"></i>
                            {{ $campaign->is_enabled ? 'ปิด' : 'เปิด' }}
                        </button>
                    </form>
                    {{-- confirm ใช้ข้อความ fixed — ห้าม interpolate ชื่อแคมเปญเข้า JS (quote ในชื่อทำ confirm พัง = โพสโดยไม่ถาม) --}}
                    <form action="{{ route('admin.fortune.content-campaigns.publish-now', $campaign) }}" method="POST"
                          onsubmit="return confirm('โพสแคมเปญนี้ขึ้น Facebook เดี๋ยวนี้เลย?')">
                        @csrf
                        <button type="submit" class="tp-btn tp-btn-primary">
                            <i class="fas fa-paper-plane"></i> โพสทันที
                        </button>
                    </form>
                    <button type="button" class="tp-btn" @click="editId = (editId === {{ $campaign->id }} ? null : {{ $campaign->id }})">
                        <i class="fas fa-pen"></i> แก้ไข
                    </button>
                    <form action="{{ route('admin.fortune.content-campaigns.destroy', $campaign) }}" method="POST"
                          onsubmit="return confirm('ลบแคมเปญนี้ถาวร? (log โพสในระบบจะถูกลบด้วย แต่โพสบน FB ยังอยู่)')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-btn" style="color:#d9534f;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- ฟอร์มแก้ไข (ซ่อน/แสดง) --}}
            <div x-show="editId === {{ $campaign->id }}" x-cloak
                 style="margin-top:16px;padding-top:16px;border-top:1px solid var(--line,rgba(128,128,128,.2));">
                <form action="{{ route('admin.fortune.content-campaigns.update', $campaign) }}" method="POST">
                    @csrf
                    @method('PUT')
                    {{-- marker ให้รู้ว่า validation error มาจากฟอร์มไหน --}}
                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="_campaign_id" value="{{ $campaign->id }}">
                    @include('admin.fortune.campaigns._form', ['campaign' => $campaign])
                    <div style="margin-top:16px;display:flex;gap:10px;">
                        <button type="submit" class="tp-btn tp-btn-primary">
                            <i class="fas fa-save"></i> บันทึกแคมเปญ
                        </button>
                        <button type="button" class="tp-btn" @click="editId = null">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="tp-card" style="padding:40px;text-align:center;">
            <div style="font-size:40px;margin-bottom:10px;">📣</div>
            <div style="font-size:15px;font-weight:600;">ยังไม่มีแคมเปญ</div>
            <p class="tp-muted" style="font-size:13px;margin:8px 0 0;">
                กด "สร้างแคมเปญใหม่" หรือรัน seeder: <code>php artisan db:seed --class=FortuneContentCampaignSeeder</code>
            </p>
        </div>
    @endforelse

    {{-- ──────────── โพสล่าสุด ──────────── --}}
    <div class="tp-card" style="padding:20px;">
        <h2 style="margin:0 0 14px;font-size:17px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-history" style="color:var(--accent1);"></i> โพสล่าสุด
        </h2>
        <div style="overflow-x:auto;">
            <table class="ccp-table" style="width:100%;font-size:13px;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left;">วันที่/เวลา</th>
                        <th style="text-align:left;">แคมเปญ</th>
                        <th style="text-align:left;">หัวข้อ</th>
                        <th style="text-align:left;">สถานะ</th>
                        <th style="text-align:right;">ดู</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPosts as $p)
                        <tr>
                            <td style="white-space:nowrap;">{{ $p->post_date->format('d/m/Y') }} {{ $p->slot_time }}</td>
                            <td>{{ $p->campaign?->emoji }} {{ $p->campaign?->name_th ?? '—' }}</td>
                            <td>{{ Str::limit($p->sub_topic ?? '-', 60) }}</td>
                            <td>
                                @if($p->status === 'posted')
                                    <span class="tp-pill tp-pill-gold" style="font-size:11px;">โพสแล้ว</span>
                                @elseif($p->status === 'failed')
                                    <span class="tp-pill" style="font-size:11px;color:#d9534f;">ล้มเหลว</span>
                                @else
                                    <span class="tp-pill tp-pill-soft" style="font-size:11px;">{{ $p->status }}</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                @if($p->fb_post_url)
                                    <a href="{{ $p->fb_post_url }}" target="_blank" rel="noopener" class="tp-btn" style="font-size:11px;padding:4px 8px;">
                                        <i class="fab fa-facebook"></i>
                                    </a>
                                @endif
                                <button type="button" class="tp-btn" style="font-size:11px;padding:4px 8px;"
                                        @click="openPost({{ $p->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="tp-muted" style="text-align:center;padding:24px;">ยังไม่มีโพส</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ──────────── Modal รายละเอียดโพส ──────────── --}}
    <div x-show="modalPost" x-cloak
         style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px;"
         @click.self="modalPost = null">
        <div class="tp-card" style="max-width:640px;width:100%;max-height:85vh;overflow-y:auto;padding:22px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <h3 style="margin:0;font-size:16px;" x-text="modalPost ? (modalPost.campaign + ' — ' + modalPost.post_date + ' ' + modalPost.slot_time) : ''"></h3>
                <button type="button" class="tp-btn" @click="modalPost = null"><i class="fas fa-times"></i></button>
            </div>
            <template x-if="modalPost">
                <div style="display:flex;flex-direction:column;gap:12px;font-size:13px;">
                    <div><span class="tp-muted">หัวข้อ:</span> <span x-text="modalPost.sub_topic || '-'"></span></div>
                    <template x-if="modalPost.image_url">
                        <img :src="modalPost.image_url" alt="ภาพประกอบโพส" style="max-width:100%;border-radius:10px;">
                    </template>
                    <div>
                        <div class="tp-muted" style="margin-bottom:4px;">Caption:</div>
                        <div style="white-space:pre-wrap;background:rgba(128,128,128,.08);border-radius:8px;padding:12px;" x-text="modalPost.caption || '-'"></div>
                    </div>
                    <template x-if="modalPost.image_prompt">
                        <div>
                            <div class="tp-muted" style="margin-bottom:4px;">🎨 Image prompt (AI สร้างจากเนื้อหา · <span x-text="modalPost.image_provider || '-'"></span>):</div>
                            <div style="font-family:monospace;font-size:11px;background:rgba(128,128,128,.08);border-radius:8px;padding:10px;" x-text="modalPost.image_prompt"></div>
                        </div>
                    </template>
                    <template x-if="modalPost.error_message">
                        <div style="color:#d9534f;">
                            <div style="margin-bottom:4px;font-weight:600;">❌ Error:</div>
                            <div style="white-space:pre-wrap;" x-text="modalPost.error_message"></div>
                        </div>
                    </template>
                    <template x-if="modalPost.fb_post_url">
                        <a :href="modalPost.fb_post_url" target="_blank" rel="noopener" class="tp-btn tp-btn-primary" style="align-self:flex-start;">
                            <i class="fab fa-facebook"></i> เปิดโพสบน Facebook
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- scripts stack: Alpine component หน้าแคมเปญคอนเทนต์ (modal + AJAX รายละเอียดโพส) --}}
<style>
    [x-cloak] { display: none !important; }
    .ccp-table th, .ccp-table td { padding: 10px 12px; border-bottom: 1px solid color-mix(in srgb, var(--ink2) 18%, transparent); }
    .ccp-table thead th { color: var(--ink2); font-size: 12px; font-weight: 600; }
</style>
<script>
    /**
     * Alpine component หน้าแคมเปญคอนเทนต์
     * - showCreate: เปิด/ปิดฟอร์มสร้างแคมเปญ
     * - editId: แคมเปญที่กำลังแก้ไข (null = ไม่มี)
     * - modalPost: รายละเอียดโพสใน modal (fetch จาก AJAX endpoint)
     */
    function contentCampaignPage() {
        return {
            // validation พลาด → เปิดฟอร์มเดิมที่ user กรอกอยู่กลับมา (create หรือ edit ตาม marker)
            showCreate: {{ old('_form') === 'create' ? 'true' : 'false' }},
            editId: {{ old('_form') === 'edit' ? (int) old('_campaign_id') : 'null' }},
            modalPost: null,

            async openPost(id) {
                try {
                    const res = await fetch(`{{ url('/admin/fortune/content-campaigns/posts') }}/${id}`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    this.modalPost = await res.json();
                } catch (e) {
                    // ไม่โชว์ raw error ให้ user — log ไว้ให้ dev ดูใน console พอ
                    console.error('openPost failed:', e);
                    alert('โหลดรายละเอียดโพสไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                }
            },
        };
    }
</script>
@endpush
