@extends('layouts.admin-v4')

@section('title', 'โพสต์เฟซบุ๊กส่วนตัว')

@php
    // สีฟ้าเฟซบุ๊ก — ใช้ทั้งหน้าให้จำได้ว่าเป็นงานโพสต์ FB
    $fb = '#1877F2';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $statusColor = [
        'ready' => '#16a34a',
        'posted' => $fb,
        'generating' => '#d97706',
        'draft' => 'var(--ink2)',
        'failed' => '#dc2626',
        'skipped' => 'var(--ink2)',
    ];
    $hasBusy = $preparing || $drafts->contains(fn ($d) => $d->status === 'generating');
    $readyCount = $drafts->filter(fn ($d) => $d->status === 'ready')->count();
    $postedCount = $drafts->filter(fn ($d) => $d->status === 'posted')->count();
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="personalPosts(@js([
        'statusUrl' => route('admin.personal-posts.status', ['date' => $date->toDateString()]),
        'profileUrl' => $settings['profile_url'],
        'busy' => $hasBusy,
        'snapshot' => $drafts->map(fn ($d) => $d->status.'@'.$d->updated_at?->timestamp)->values(),
     ]))">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · โพสต์ส่วนตัว</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0; color:{{ $fb }};">
                <i class="fab fa-facebook"></i> โพสต์เฟซบุ๊กส่วนตัว
            </h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px; max-width:620px;">
                ระบบเตรียมบทความและภาพไว้วันละ 4 โพสต์ จากเทรนด์ Google และข่าวเทคของวันนั้น
                พี่กด <b>"เปิดพร้อมโพสต์"</b> แล้วกดโพสต์เองบนเฟซบุ๊ก — ระบบไม่โพสต์แทน
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <form method="POST" action="{{ route('admin.personal-posts.prepare') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                <button type="submit" class="tp-btn" style="background:{{ $fb }}; color:#fff;" @disabled($hasBusy)>
                    <i class="fas fa-wand-magic-sparkles"></i> เตรียมช่องที่ยังว่าง
                </button>
            </form>
            <form method="POST" action="{{ route('admin.personal-posts.prepare') }}"
                  @submit="if (!confirm('เลือกหัวข้อใหม่และสร้างใหม่ทุกช่อง? บทความที่แก้ไว้จะถูกแทนที่ (ช่องที่โพสต์แล้วไม่ถูกแตะ)')) $event.preventDefault()">
                @csrf
                <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                <input type="hidden" name="force" value="1">
                <button type="submit" class="tp-btn" @disabled($hasBusy)>
                    <i class="fas fa-rotate"></i> เลือกหัวข้อใหม่ทั้งหมด
                </button>
            </form>
        </div>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:12px 16px; border-left:4px solid #dc2626; color:#dc2626; font-size:13px;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    {{-- ===== แถบเลือกวัน ===== --}}
    <div class="tp-card" style="padding:10px; display:flex; gap:6px; overflow-x:auto;">
        @foreach($days as $d)
            @php
                $dc = $counts->get($d['date']) ?? collect();
                $dPosted = (int) $dc->where('status', 'posted')->sum('c');
                $dTotal = (int) $dc->sum('c');
            @endphp
            <a href="{{ route('admin.personal-posts.index', ['date' => $d['date']]) }}"
               style="flex:none; padding:8px 14px; border-radius:12px; text-decoration:none; font-size:12.5px; font-weight:{{ $d['active'] ? 700 : 500 }};
                      {{ $d['active'] ? "background:{$fb}; color:#fff;" : 'color:var(--ink2);' }}">
                {{ $d['label'] }}
                @if($dTotal > 0)<span style="opacity:.8; font-size:11px;"> · {{ $dPosted }}/{{ $dTotal }}</span>@endif
            </a>
        @endforeach
    </div>

    {{-- ===== สรุป ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px;">
        <div class="tp-card" style="padding:14px;">
            <div class="tp-num" style="font-size:22px; font-weight:800; color:#16a34a;">{{ $readyCount }}</div>
            <div style="font-size:12px; color:var(--ink2);">พร้อมโพสต์</div>
        </div>
        <div class="tp-card" style="padding:14px;">
            <div class="tp-num" style="font-size:22px; font-weight:800; color:{{ $fb }};">{{ $postedCount }}</div>
            <div style="font-size:12px; color:var(--ink2);">โพสต์แล้ว</div>
        </div>
        <div class="tp-card" style="padding:14px;">
            <div style="font-size:13px; font-weight:700;">{{ $date->locale('th')->translatedFormat('l j F Y') }}</div>
            <div style="font-size:12px; color:var(--ink2);">ระบบเตรียมเองทุกวัน 05:30 น.</div>
        </div>
    </div>

    <div x-show="busy" x-cloak class="tp-card" style="padding:12px 16px; border-left:4px solid #d97706; font-size:13px; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-spinner fa-spin" style="color:#d97706;"></i>
        กำลังสร้างบทความและภาพ ใช้เวลาช่องละประมาณ 1 นาที — หน้านี้จะอัปเดตเองเมื่อเสร็จ
    </div>

    {{-- ===== การ์ดโพสต์ 4 ช่อง ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr)); gap:16px;">
        @for($slot = 1; $slot <= \App\Models\PersonalPostDraft::SLOTS_PER_DAY; $slot++)
            @php $draft = $drafts->get($slot); @endphp

            @if(! $draft)
                <div class="tp-card" style="padding:28px 18px; text-align:center; color:var(--ink2);">
                    <div style="font-size:30px; opacity:.5;">📝</div>
                    <div style="font-weight:700; margin-top:6px;">ช่องที่ {{ $slot }} ยังว่าง</div>
                    <div style="font-size:12px; margin-top:4px;">กด "เตรียมช่องที่ยังว่าง" ด้านบน หรือรอระบบเตรียมให้ตอนเช้า</div>
                </div>
                @continue
            @endif

            <div class="tp-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column; {{ $draft->status === 'posted' ? 'opacity:.75;' : '' }}"
                 x-data="{ editing: false }">
                {{-- หัวการ์ด --}}
                <div style="padding:12px 16px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; border-bottom:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                    <span class="tp-num" style="font-weight:800; color:{{ $fb }};">#{{ $slot }}</span>
                    <span class="tp-pill tp-pill-soft" style="font-size:10.5px;">{{ $draft->source_type === 'tech_news' ? '💻' : '📈' }} {{ $draft->source_label }}</span>
                    <span style="font-size:11.5px; font-weight:700; color:{{ $statusColor[$draft->status] ?? 'var(--ink2)' }};">
                        @if($draft->status === 'generating')<i class="fas fa-spinner fa-spin"></i>@endif
                        {{ $draft->status_label }}
                    </span>
                    @if($draft->posted_at)
                        <span style="font-size:11px; color:var(--ink2);">· {{ $draft->posted_at->format('H:i') }} น.</span>
                    @endif
                    @if($draft->source_url)
                        <a href="{{ $draft->source_url }}" target="_blank" rel="noopener noreferrer" style="margin-left:auto; font-size:11.5px; color:var(--ink2);">
                            {{ $draft->source_name ?: 'ข่าวต้นทาง' }} <i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i>
                        </a>
                    @endif
                </div>

                <div style="padding:12px 16px 0;">
                    <div style="font-weight:800; font-size:15px;">{{ $draft->topic ?: $draft->source_title }}</div>
                    @if($draft->source_title && $draft->source_title !== $draft->topic)
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ $draft->source_title }}</div>
                    @endif
                </div>

                {{-- ภาพ --}}
                <div style="padding:12px 16px 0;">
                    @if($draft->image_url)
                        <img src="{{ $draft->image_url }}" alt="ภาพประกอบโพสต์ช่องที่ {{ $slot }}" loading="lazy"
                             style="width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:14px; display:block;">
                    @else
                        <div class="tp-well" style="width:100%; aspect-ratio:16/9; border-radius:14px; display:grid; place-items:center; color:var(--ink2); font-size:13px;">
                            {{ $draft->status === 'generating' ? 'กำลังสร้างภาพ...' : 'ยังไม่มีภาพ' }}
                        </div>
                    @endif
                    @if($draft->image_provider)
                        <div style="font-size:10.5px; color:var(--ink2); margin-top:4px;">ภาพโดย {{ $draft->image_provider }}</div>
                    @endif
                </div>

                {{-- บทความ --}}
                <div style="padding:12px 16px; flex:1;">
                    @if($draft->error)
                        <div style="font-size:12px; color:{{ $draft->status === 'failed' ? '#dc2626' : '#d97706' }}; margin-bottom:8px;">⚠️ {{ $draft->error }}</div>
                    @endif

                    <div x-show="!editing" style="white-space:pre-wrap; font-size:13.5px; line-height:1.65; max-height:320px; overflow:auto;"
                         id="caption-{{ $draft->id }}">{{ $draft->caption ?: ($draft->status === 'generating' ? 'กำลังเขียนบทความ...' : 'ยังไม่มีบทความ') }}</div>

                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.personal-posts.caption', $draft) }}">
                        @csrf
                        <textarea name="caption" rows="12" class="tp-input" style="width:100%; font-size:13px; line-height:1.6;" maxlength="5000">{{ $draft->caption }}</textarea>
                        <div style="display:flex; gap:8px; margin-top:8px;">
                            <button type="submit" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-floppy-disk"></i> บันทึก</button>
                            <button type="button" class="tp-btn tp-btn-sm" @click="editing = false">ยกเลิก</button>
                        </div>
                    </form>
                </div>

                {{-- ปุ่ม --}}
                <div style="padding:12px 16px 16px; display:flex; flex-direction:column; gap:8px; border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                    @if($draft->isPostable())
                        <button type="button" class="tp-btn" style="background:{{ $fb }}; color:#fff; justify-content:center; font-weight:700; min-height:44px;"
                                @click="openReady(@js($draft->caption), @js($draft->image_url ? route('admin.personal-posts.image', $draft) : null))">
                            <i class="fab fa-facebook"></i> เปิดพร้อมโพสต์
                        </button>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:6px;">
                            <button type="button" class="tp-btn tp-btn-sm" @click="shareToApp(@js($draft->caption), @js($draft->image_url), {{ $slot }})">
                                <i class="fas fa-mobile-screen"></i> แชร์เข้าแอป
                            </button>
                            <button type="button" class="tp-btn tp-btn-sm" @click="copyText(@js($draft->caption))">
                                <i class="fas fa-copy"></i> คัดลอกบทความ
                            </button>
                            @if($draft->image_url)
                                <a href="{{ route('admin.personal-posts.image', $draft) }}" class="tp-btn tp-btn-sm"><i class="fas fa-download"></i> ดาวน์โหลดภาพ</a>
                            @endif
                        </div>
                    @endif

                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        @if($draft->isPostable())
                            <form method="POST" action="{{ route('admin.personal-posts.posted', $draft) }}">
                                @csrf
                                @if($draft->status === 'posted')
                                    <input type="hidden" name="undo" value="1">
                                    <button type="submit" class="tp-btn tp-btn-sm"><i class="fas fa-rotate-left"></i> ยังไม่ได้โพสต์</button>
                                @else
                                    <button type="submit" class="tp-btn tp-btn-sm" style="color:#16a34a;"><i class="fas fa-check"></i> โพสต์แล้ว</button>
                                @endif
                            </form>
                        @endif

                        @if($draft->status !== 'posted')
                            <button type="button" class="tp-btn tp-btn-sm" @click="editing = !editing" @disabled($draft->status === 'generating')>
                                <i class="fas fa-pen"></i> แก้บทความ
                            </button>
                            @foreach(['text' => ['fa-file-lines', 'เขียนใหม่'], 'image' => ['fa-image', 'ภาพใหม่'], 'both' => ['fa-arrows-rotate', 'ใหม่ทั้งคู่']] as $mode => [$icon, $label])
                                <form method="POST" action="{{ route('admin.personal-posts.regenerate', $draft) }}"
                                      @submit="if (!confirm(@js("สร้าง{$label}ให้ช่องที่ {$slot}? ของเดิมจะถูกแทนที่"))) $event.preventDefault()">
                                    @csrf
                                    <input type="hidden" name="mode" value="{{ $mode }}">
                                    <button type="submit" class="tp-btn tp-btn-sm" @disabled($draft->status === 'generating')><i class="fas {{ $icon }}"></i> {{ $label }}</button>
                                </form>
                            @endforeach
                            <form method="POST" action="{{ route('admin.personal-posts.skip', $draft) }}">
                                @csrf
                                @if($draft->status === 'skipped')
                                    <input type="hidden" name="undo" value="1">
                                    <button type="submit" class="tp-btn tp-btn-sm"><i class="fas fa-rotate-left"></i> เอากลับมา</button>
                                @else
                                    <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--ink2);" @disabled($draft->status === 'generating')><i class="fas fa-forward"></i> ข้าม</button>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endfor
    </div>

    {{-- ===== วิธีใช้ ===== --}}
    <div class="tp-card" style="padding:16px; font-size:12.5px; color:var(--ink2); line-height:1.7;">
        <div style="font-weight:700; color:var(--ink); margin-bottom:4px;">วิธีโพสต์</div>
        <div>💻 <b>คอม:</b> กด "เปิดพร้อมโพสต์" → ระบบคัดลอกบทความ ดาวน์โหลดภาพ และเปิดหน้าโปรไฟล์ → กดช่องโพสต์ วาง (Ctrl+V) แนบภาพ แล้วกดโพสต์</div>
        <div>📱 <b>มือถือ:</b> กด "แชร์เข้าแอป" → เลือกแอป Facebook → ภาพแนบให้แล้ว แตะค้างในช่องข้อความแล้ววาง → กดโพสต์</div>
        <div>✅ โพสต์เสร็จกด "โพสต์แล้ว" เพื่อจำว่าช่องไหนลงไปแล้ว · ภาพสร้างด้วย AI ควรเปิด "ป้าย AI" ตอนโพสต์</div>
    </div>

    {{-- ===== ตั้งค่า ===== --}}
    <details class="tp-card" style="padding:16px;">
        <summary style="cursor:pointer; font-weight:700;"><i class="fas fa-gear"></i> ตั้งค่า</summary>
        <form method="POST" action="{{ route('admin.personal-posts.settings') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; margin-top:14px; align-items:end;">
            @csrf
            <div style="grid-column:1/-1;">
                <label style="{{ $lbl }}">ลิงก์โปรไฟล์เฟซบุ๊กที่จะเปิด</label>
                <input type="url" name="profile_url" value="{{ old('profile_url', $settings['profile_url']) }}" class="tp-input" required maxlength="500">
            </div>
            <div>
                <label style="{{ $lbl }}">AI เขียนบทความ</label>
                <select name="text_provider" class="tp-input">
                    @foreach($textProviders as $k => $v)
                        <option value="{{ $k }}" @selected($settings['text_provider'] === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">โมเดลภาพ (OpenAI แบบ ChatGPT)</label>
                <select name="image_model" class="tp-input">
                    @foreach($imageModels as $k => $v)
                        <option value="{{ $k }}" @selected($settings['image_model'] === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <label style="display:flex; align-items:center; gap:8px; font-size:13px;">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1" @checked($settings['enabled'])>
                เตรียมโพสต์อัตโนมัติทุกเช้า
            </label>
            <div>
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-floppy-disk"></i> บันทึก</button>
            </div>
        </form>
    </details>
</div>
@endsection

@push('scripts')
<script>
    /**
     * หน้าเตรียมโพสต์ส่วนตัว — คัดลอก/แชร์/เปิดเฟซบุ๊ก + เฝ้าสถานะระหว่างสร้าง
     */
    function personalPosts(cfg) {
        return {
            busy: cfg.busy,
            timer: null,

            init() {
                if (this.busy) this.poll();
            },

            destroy() {
                if (this.timer) clearTimeout(this.timer);
            },

            notify(type, message) {
                window.dispatchEvent(new CustomEvent('notify', { detail: { type, message } }));
            },

            // ถามสถานะทุก 6 วินาที — เปลี่ยนเมื่อไหร่รีโหลดหน้า
            poll() {
                this.timer = setTimeout(async () => {
                    try {
                        const res = await fetch(cfg.statusUrl, { headers: { Accept: 'application/json' } });
                        if (res.ok) {
                            const data = await res.json();
                            const snap = data.drafts.map(d => d.status + '@' + d.updated_at);
                            const stillBusy = data.preparing || data.drafts.some(d => d.status === 'generating');
                            if (JSON.stringify(snap) !== JSON.stringify(cfg.snapshot) || !stillBusy) {
                                window.location.reload();
                                return;
                            }
                        }
                    } catch (e) { /* เน็ตสะดุด — ถามใหม่รอบหน้า */ }
                    this.poll();
                }, 6000);
            },

            // คัดลอกแบบทันที (ต้องเรียกในจังหวะคลิก ขณะหน้านี้ยังมีโฟกัส) — คืน true/false
            copySync(text) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', '');
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                let ok = false;
                try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                ta.remove();
                return ok;
            },

            // คัดลอกด้วย Clipboard API (ต้องเรียกตอนหน้านี้ยังมีโฟกัส) — ไม่แจ้งเตือนเอง
            async copyAsync(text) {
                try {
                    await navigator.clipboard.writeText(text);
                    return true;
                } catch (e) {
                    return false;
                }
            },

            // ปุ่ม "คัดลอกบทความ" — แจ้งผลครั้งเดียว
            async copyText(text) {
                const ok = this.copySync(text) || await this.copyAsync(text);
                this.notify(ok ? 'success' : 'error', ok ? 'คัดลอกบทความแล้ว' : 'คัดลอกไม่ได้ — กดแก้บทความแล้วคัดลอกเอง');
                return ok;
            },

            // 💻 คอม: คัดลอก + ดาวน์โหลดภาพ + เปิดโปรไฟล์ (ทุกอย่างต้องอยู่ในจังหวะคลิก)
            openReady(caption, downloadUrl) {
                // ⚠️ คัดลอกก่อนเปิดแท็บ — พอแท็บเฟซบุ๊กขึ้นมา หน้านี้เสียโฟกัส คลิปบอร์ดจะเขียนไม่ได้
                const copySync = this.copySync(caption);
                const copied = copySync ? Promise.resolve(true) : this.copyAsync(caption);
                if (downloadUrl) {
                    const a = document.createElement('a');
                    a.href = downloadUrl;
                    a.download = '';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }
                // ⚠️ ห้ามใส่ 'noopener' ใน features — window.open จะคืน null เสมอ (เช็คป๊อปอัปโดนบล็อกไม่ได้)
                const win = window.open(cfg.profileUrl, '_blank');
                if (win) win.opener = null;

                copied.then((ok) => {
                    const imgPart = downloadUrl ? ' + ดาวน์โหลดภาพ' : '';
                    if (!win) {
                        this.notify('error', 'เบราว์เซอร์บล็อกป๊อปอัป — อนุญาตป๊อปอัปให้เว็บนี้ แล้วกดอีกครั้ง'
                            + (ok ? ' (คัดลอกบทความไว้แล้ว)' : ''));
                    } else if (ok) {
                        this.notify('success', 'คัดลอกบทความ' + imgPart + 'แล้ว — วางในช่องโพสต์แล้วแนบภาพได้เลย');
                    } else {
                        this.notify('error', 'เปิดเฟซบุ๊กแล้ว แต่คัดลอกบทความไม่ได้ — กลับมากด "คัดลอกบทความ" อีกครั้ง');
                    }
                });
            },

            // 📱 มือถือ: แชร์ไฟล์ภาพเข้าแอป Facebook (FB ไม่รับข้อความจากแอปอื่น จึงคัดลอกให้ก่อน)
            async shareToApp(caption, imageUrl, slot) {
                await this.copyText(caption);
                if (!imageUrl || !navigator.canShare) {
                    window.open(cfg.profileUrl, '_blank', 'noopener');
                    return;
                }
                try {
                    const blob = await (await fetch(imageUrl)).blob();
                    const file = new File([blob], 'post-' + slot + '.png', { type: blob.type || 'image/png' });
                    if (navigator.canShare({ files: [file] })) {
                        await navigator.share({ files: [file], text: caption });
                        return;
                    }
                } catch (e) {
                    if (e && e.name === 'AbortError') return; // ผู้ใช้กดยกเลิกเอง
                }
                this.notify('error', 'เครื่องนี้แชร์ไฟล์ไม่ได้ — ใช้ปุ่ม "เปิดพร้อมโพสต์" แทน');
            },
        };
    }
</script>
@endpush
