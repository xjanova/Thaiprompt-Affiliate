@extends('layouts.admin-v4')

@section('title', 'ข้อความสำเร็จรูป')

@section('content')
{{--
    💬 ข้อความสำเร็จรูป (ธีม V4 นวลทองคำ)

    ปรับปรุงเพิ่มจากของเดิม:
    - ปุ่มแก้ไขเดิมแค่ alert("coming soon") ทั้งที่ route PUT มีอยู่จริง
      → ต่อสายให้ใช้งานได้จริง (เติมค่าเดิมลงฟอร์ม + ส่ง PUT ไป /{id})
    - รวมโมดัลสร้าง/แก้ไข/ดูเนื้อหา มาคุมด้วย Alpine ตัวเดียว แทน raw JS 3 ก้อน
--}}
@php
    $respList = collect($responses ?? []);
    // ส่งข้อมูลให้ Alpine ใช้เติมฟอร์มตอนกดแก้ไข / กดดู
    $respPayload = $respList->map(fn ($r) => [
        'id' => $r->id,
        'title' => $r->title,
        'shortcode' => $r->shortcode,
        'content' => $r->content,
        'category_id' => $r->category_id,
        'is_public' => (bool) $r->is_public,
        'is_active' => (bool) ($r->is_active ?? true),
    ])->values();
@endphp

<div x-data="cannedResponses()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ · ข้อความสำเร็จรูป</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ข้อความสำเร็จรูป 💬</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">จัดการข้อความสำเร็จรูปสำหรับตอบกลับอย่างรวดเร็ว</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px;">
            <a href="{{ route('admin.tickets.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
            <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="openCreate()">
                <i class="fas fa-plus"></i> เพิ่มข้อความ
            </button>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    @php
        $kpis = [
            [$respList->count(),                        'ข้อความทั้งหมด', 'fa-comments',     null],
            [$respList->where('is_active', true)->count(),  'เปิดใช้งาน',   'fa-circle-check', '#5aa07e'],
            [$respList->where('is_public', true)->count(),  'สาธารณะ',      'fa-globe',        '#b79ae8'],
            [$respList->unique('category_id')->count(),     'หมวดหมู่',      'fa-folder',       '#5689b8'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        @foreach($kpis as [$value, $label, $icon, $color])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;{{ $color ? ' background:'.$color.';' : '' }}">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($value) }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        @foreach(['หัวข้อ','ชอร์ตโค้ด','หมวดหมู่','แท็ก','สาธารณะ','สถานะ'] as $th)
                            <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $th }}</th>
                        @endforeach
                        <th style="padding:14px 16px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($respList as $response)
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- หัวข้อ --}}
                            <td style="padding:14px 16px;">
                                <div style="font-size:13.5px; font-weight:700; color:var(--ink);">{{ $response->title }}</div>
                                <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ $response->created_at?->format('d/m/Y') }}</div>
                            </td>
                            {{-- ชอร์ตโค้ด --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill tp-pill-gold" style="font-family:monospace;">{{ $response->shortcode }}</span>
                            </td>
                            {{-- หมวดหมู่ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($response->category)
                                    <span class="tp-pill tp-pill-soft">{{ $response->category->name }}</span>
                                @else
                                    <span style="color:var(--ink2); font-size:12px;">ทุกหมวด</span>
                                @endif
                            </td>
                            {{-- แท็ก --}}
                            <td style="padding:14px 16px; font-size:12.5px; color:var(--ink2);">
                                @php $tags = is_array($response->tags) ? $response->tags : array_filter(explode(',', (string) $response->tags)); @endphp
                                @forelse(array_slice($tags, 0, 3) as $tag)
                                    <span class="tp-pill tp-pill-soft" style="margin-right:3px;">{{ trim($tag) }}</span>
                                @empty
                                    <span style="color:var(--ink2);">—</span>
                                @endforelse
                            </td>
                            {{-- สาธารณะ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($response->is_public)
                                    <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;"><i class="fas fa-globe"></i> ใช่</span>
                                @else
                                    <span class="tp-pill" style="background:rgba(214,130,74,.18); color:#a4622f;"><i class="fas fa-lock"></i> ไม่</span>
                                @endif
                            </td>
                            {{-- สถานะ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($response->is_active ?? true)
                                    <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">● เปิดใช้งาน</span>
                                @else
                                    <span class="tp-pill" style="background:color-mix(in srgb, var(--ink2) 18%, transparent); color:var(--ink2);">● ปิดใช้งาน</span>
                                @endif
                            </td>
                            {{-- จัดการ --}}
                            <td style="padding:14px 16px; text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:7px;">
                                    <button type="button" class="tp-btn tp-btn-sm" title="ดูเนื้อหา" @click="openView({{ $response->id }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="tp-btn tp-btn-sm" title="แก้ไข" @click="openEdit({{ $response->id }})">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.tickets.canned-responses.destroy', $response->id) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('ลบข้อความ &quot;{{ $response->title }}&quot; ใช่หรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-btn tp-btn-sm" title="ลบ" style="color:#d9534f;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:0;">
                                <div style="text-align:center; color:var(--ink2); padding:44px 0;">
                                    <i class="fas fa-inbox" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                    <div style="font-size:14px; font-weight:600;">ยังไม่มีข้อความสำเร็จรูป</div>
                                    <div style="font-size:12px; margin-top:4px;">สร้างเทมเพลตตอบกลับอันแรกของคุณ</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== โมดัลสร้าง/แก้ไข ===== --}}
    <div x-show="open" x-cloak style="position:fixed; inset:0; z-index:50; overflow-y:auto;">
        <div style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:16px;">
            <div style="position:fixed; inset:0; background:rgba(0,0,0,.55);" @click="open = false"></div>

            <div class="tp-card" style="position:relative; max-width:640px; width:100%; padding:0; overflow:hidden;">
                <form :action="formAction" method="POST">
                    @csrf
                    <template x-if="editingId">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div style="padding:18px 20px; background:linear-gradient(120deg, color-mix(in srgb, #5aa07e 24%, transparent), transparent 75%); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                        <div class="tp-section-h" style="margin:0;">
                            <i class="fas fa-comment-dots"></i>
                            <span x-text="editingId ? 'แก้ไขข้อความสำเร็จรูป' : 'เพิ่มข้อความสำเร็จรูป'"></span>
                        </div>
                        <button type="button" class="tp-icon-btn" @click="open = false" title="ปิด"><i class="fas fa-times"></i></button>
                    </div>

                    <div style="padding:20px; display:flex; flex-direction:column; gap:16px;">
                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                หัวข้อ <span style="color:#d9534f;">*</span>
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="text" name="title" x-model="form.title" required
                                       style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                            </div>
                        </div>

                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                ชอร์ตโค้ด <span style="color:#d9534f;">*</span>
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="text" name="shortcode" x-model="form.shortcode" required placeholder="/greeting"
                                       style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; font-family:monospace;">
                            </div>
                            <div style="font-size:11px; color:var(--ink2); margin-top:5px;">รูปแบบ: /shortcode (เช่น /greeting, /thanks)</div>
                        </div>

                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                เนื้อหา <span style="color:#d9534f;">*</span>
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <textarea name="content" x-model="form.content" rows="6" required
                                          style="width:100%; background:transparent; border:none; outline:none; padding:12px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit; line-height:1.6;"></textarea>
                            </div>
                            <div style="font-size:11px; color:var(--ink2); margin-top:5px;">ตัวแปรที่ใช้ได้: {user_name}, {ticket_number}, {agent_name}</div>
                        </div>

                        <div>
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หมวดหมู่ (ไม่บังคับ)</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <select name="category_id" x-model="form.category_id"
                                        style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                                    <option value="">ทุกหมวดหมู่</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="tp-well" style="display:flex; align-items:center; gap:10px; padding:12px 14px; cursor:pointer;">
                            <input type="checkbox" name="is_public" value="1" x-model="form.is_public"
                                   style="accent-color:#5aa07e; width:16px; height:16px; cursor:pointer;">
                            <span style="font-size:13px; font-weight:600; color:var(--ink);">
                                <i class="fas fa-globe" style="color:#5aa07e; margin-right:5px;"></i>สาธารณะ (เจ้าหน้าที่ทุกคนเห็น)
                            </span>
                        </label>
                    </div>

                    <div style="padding:16px 20px; display:flex; justify-content:flex-end; gap:10px; box-shadow:var(--inset-sm);">
                        <button type="button" class="tp-btn" @click="open = false">ยกเลิก</button>
                        <button type="submit" class="tp-btn tp-btn-primary" style="font-weight:700;">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== โมดัลดูเนื้อหา ===== --}}
    <div x-show="viewOpen" x-cloak style="position:fixed; inset:0; z-index:50; overflow-y:auto;">
        <div style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:16px;">
            <div style="position:fixed; inset:0; background:rgba(0,0,0,.55);" @click="viewOpen = false"></div>

            <div class="tp-card" style="position:relative; max-width:640px; width:100%; padding:0; overflow:hidden;">
                <div style="padding:18px 20px; background:linear-gradient(120deg, color-mix(in srgb, #5689b8 24%, transparent), transparent 75%); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                    <div class="tp-section-h" style="margin:0;"><i class="fas fa-eye"></i> <span x-text="viewTitle"></span></div>
                    <button type="button" class="tp-icon-btn" @click="viewOpen = false" title="ปิด"><i class="fas fa-times"></i></button>
                </div>
                <div style="padding:20px;">
                    <div class="tp-well" style="padding:14px;">
                        <pre style="margin:0; font-size:13px; color:var(--ink); white-space:pre-wrap; font-family:monospace; line-height:1.65;" x-text="viewContent"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cannedResponses() {
    return {
        open: false,
        viewOpen: false,
        editingId: null,
        viewTitle: '',
        viewContent: '',
        responses: @js($respPayload),
        storeUrl: @js(route('admin.tickets.canned-responses.store')),
        form: { title: '', shortcode: '', content: '', category_id: '', is_public: true },

        get formAction() {
            return this.editingId ? (this.storeUrl + '/' + this.editingId) : this.storeUrl;
        },

        find(id) {
            return this.responses.find(x => x.id === id);
        },

        openCreate() {
            this.editingId = null;
            this.form = { title: '', shortcode: '', content: '', category_id: '', is_public: true };
            this.open = true;
        },

        openEdit(id) {
            const r = this.find(id);
            if (!r) return;
            this.editingId = id;
            this.form = {
                title: r.title || '',
                shortcode: r.shortcode || '',
                content: r.content || '',
                category_id: r.category_id || '',
                is_public: !!r.is_public,
            };
            this.open = true;
        },

        openView(id) {
            const r = this.find(id);
            if (!r) return;
            this.viewTitle = r.title || '';
            this.viewContent = r.content || '';
            this.viewOpen = true;
        },

        init() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') { this.open = false; this.viewOpen = false; }
            });
        },
    };
}
</script>
@endpush
@endsection
