{{--
    แจ้งเตือนของฉัน (ธีมโนวาเต็ม · ฝั่งสมาชิกเท่านั้น — ฝั่งผู้ขายยังใช้ notifications/index ในเปลือกผู้ขาย)
    - กรอง ทั้งหมด / ยังไม่อ่าน / สำคัญ ผ่าน ?filter= (NotificationController::index)
    - เลือกหลายรายการ → อ่านแล้ว / ลบ (bulk endpoints ตอบ JSON แล้ว reload)
    - ลบ = ลบถาวร ต้องกดยืนยันอีกครั้งเสมอ · action_url ผ่านตัวกรอง scheme ใน Notification::getActionUrlAttribute แล้ว
--}}
@extends('layouts.user-nova')

@section('title', 'แจ้งเตือน')

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");
    $tabs = ['' => 'ทั้งหมด', 'unread' => 'ยังไม่อ่าน', 'important' => 'สำคัญ'];
    $tabCount = ['unread' => $unreadCount, 'important' => $importantCount];
    $icon = fn ($type) => match ($type) {
        'deposit' => 'fa-arrow-down',
        'withdrawal' => 'fa-building-columns',
        'transfer' => 'fa-paper-plane',
        'commission' => 'fa-coins',
        'wallet' => 'fa-wallet',
        'team_transfer', 'team_transfer_request', 'team_transfer_approval', 'team_transfer_completed' => 'fa-people-arrows',
        'system' => 'fa-gear',
        'alert' => 'fa-triangle-exclamation',
        'announcement' => 'fa-bullhorn',
        'debt' => 'fa-file-invoice-dollar',
        default => 'fa-bell',
    };
    $tone = fn ($color) => match ($color) {
        'green' => 'ok',
        'blue' => 'info',
        'red' => 'bad',
        'orange', 'yellow' => 'warn',
        'purple' => 'gold',
        default => '',
    };
    $ids = $notifications->pluck('id')->map(fn ($id) => (int) $id)->values();
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-nt-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Notifications</div>
            <h1 id="nvm-nt-h">แจ้งเตือน</h1>
            <p class="nvm-ph__lead">ความเคลื่อนไหวของเงิน คอมมิชชั่น ทีม และประกาศจากระบบ รวมไว้ที่เดียว</p>
            <div class="nvm-bal">
                <small>ยังไม่ได้อ่าน</small>
                <b class="nvm-foil nvm-num">{{ number_format($unreadCount) }}<span> รายการ</span></b>
            </div>
            <div class="nvm-pills">
                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('user.notifications.read-all') }}" x-data="{ busy: false }" @submit="busy = true">
                        @csrf
                        <button type="submit" class="nvm-pill nvm-pill--gold nvm-nt-pillbtn" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>อ่านทั้งหมดแล้ว</button>
                    </form>
                @endif
                <a class="nvm-pill nvm-pill--glass" href="{{ route('user.email.preferences') }}"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>ตั้งค่าอีเมลแจ้งเตือน</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('bell', 'deco/bell') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-ntl-h" x-data="nvmNotifs(@js($ids))">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Inbox</small><h2 id="nvm-ntl-h">รายการแจ้งเตือน</h2></div>
        <nav class="nvm-tabs" aria-label="กรองแจ้งเตือน">
            @foreach($tabs as $key => $label)
                <a href="{{ route('user.notifications.index', $key ? ['filter' => $key] : []) }}" class="{{ (string) $filter === (string) $key ? 'is-on' : '' }}" @if((string) $filter === (string) $key) aria-current="page" @endif>
                    {{ $label }}@if(($tabCount[$key] ?? 0) > 0)<span class="nvm-chip {{ $key === 'unread' ? 'nvm-chip--info' : 'nvm-chip--warn' }} nvm-num">{{ $tabCount[$key] > 99 ? '99+' : $tabCount[$key] }}</span>@endif
                </a>
            @endforeach
        </nav>
    </div>

    @if($notifications->count())
        <div class="nvm-nt-bar">
            <label class="nvm-check">
                <input type="checkbox" :checked="all" @change="toggleAll($event.target.checked)">
                <span x-text="sel.length ? 'เลือกแล้ว ' + sel.length + ' รายการ' : 'เลือกทั้งหมดในหน้านี้'">เลือกทั้งหมดในหน้านี้</span>
            </label>
            <div class="nvm-nt-bar__acts" x-show="sel.length" x-cloak>
                <template x-if="! askDel">
                    <div class="nvm-filter__btns">
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="bulk('read')" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>อ่านแล้ว</button>
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="askDel = true" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-close"/></svg>ลบ</button>
                    </div>
                </template>
                <template x-if="askDel">
                    <div class="nvm-confirm nvm-confirm--row">
                        <p x-text="'ลบ ' + sel.length + ' รายการถาวร? กู้คืนไม่ได้'"></p>
                        <div class="nvm-filter__btns">
                            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="askDel = false" :disabled="busy">ยกเลิก</button>
                            <button type="button" class="nvm-btn nvm-btn--bad nvm-btn--sm" @click="bulk('delete')" :disabled="busy" x-text="busy ? 'กำลังลบ…' : 'ยืนยันลบ'">ยืนยันลบ</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <ul class="nvm-nts">
            @foreach($notifications as $n)
                @php $t = $tone($n->color); @endphp
                <li class="nvm-nt {{ $n->is_read ? '' : 'is-new' }}" :class="sel.includes({{ (int) $n->id }}) ? 'is-sel' : ''" x-data="{ ask: false }">
                    <label class="nvm-nt__pick">
                        <span class="nvm-sr">เลือก {{ $n->title }}</span>
                        <input type="checkbox" value="{{ (int) $n->id }}" x-model.number="sel">
                    </label>
                    <span class="nvm-tx__ic {{ $t ? 'nvm-nt__ic--'.$t : '' }}" aria-hidden="true"><i class="fa-solid {{ $icon($n->type) }}"></i></span>
                    <div class="nvm-nt__body">
                        <div class="nvm-nt__head">
                            <b>{{ $n->title }}</b>
                            @unless($n->is_read)<span class="nvm-nt__dot" title="ยังไม่อ่าน"></span><span class="nvm-sr">ยังไม่อ่าน</span>@endunless
                            @if($n->is_important)<span class="nvm-chip nvm-chip--warn">สำคัญ</span>@endif
                            @if(in_array($n->priority, ['high', 'urgent'], true))<span class="nvm-chip nvm-chip--bad">{{ $n->priority_label }}</span>@endif
                        </div>
                        <p>{{ $n->message }}</p>
                        <div class="nvm-nt__meta">
                            <time class="nvm-num" datetime="{{ $n->created_at->toIso8601String() }}" title="{{ $n->created_at->format('d/m/Y H:i') }}">{{ $n->created_at->diffForHumans() }}</time>
                            <span class="nvm-chip">{{ $n->type_label }}</span>
                            @if($n->action_url && $n->action_text)
                                <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ $n->action_url }}" @unless($n->is_read) @click="seen({{ (int) $n->id }})" @endunless>{{ $n->action_text }}<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('user.notifications.destroy', $n->id) }}" class="nvm-confirm nvm-confirm--row" x-show="ask" x-cloak>
                            @csrf
                            @method('DELETE')
                            <p>ลบแจ้งเตือนนี้ถาวร? กู้คืนไม่ได้</p>
                            <div class="nvm-filter__btns">
                                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = false">ยกเลิก</button>
                                <button type="submit" class="nvm-btn nvm-btn--bad nvm-btn--sm">ยืนยันลบ</button>
                            </div>
                        </form>
                    </div>
                    <div class="nvm-nt__acts" x-show="! ask">
                        @unless($n->is_read)
                            <form method="POST" action="{{ route('user.notifications.read', $n->id) }}">
                                @csrf
                                <button type="submit" class="nvm-nt__btn" title="ทำเครื่องหมายว่าอ่านแล้ว" aria-label="ทำเครื่องหมายว่าอ่านแล้ว"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('user.notifications.archive', $n->id) }}">
                            @csrf
                            <button type="submit" class="nvm-nt__btn" title="ซ่อนจากรายการ" aria-label="ซ่อนจากรายการ"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></button>
                        </form>
                        <button type="button" class="nvm-nt__btn is-bad" title="ลบ" aria-label="ลบแจ้งเตือนนี้" @click="ask = true"><svg class="nvm-ico"><use href="#nvm-i-close"/></svg></button>
                    </div>
                </li>
            @endforeach
        </ul>

        @if($notifications->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $notifications->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ $nvArt('bell', 'deco/bell') }}" alt="" width="86" height="86" loading="lazy">
            <b>{{ match ($filter) { 'unread' => 'อ่านครบทุกรายการแล้ว', 'important' => 'ไม่มีแจ้งเตือนสำคัญ', default => 'ยังไม่มีแจ้งเตือน' } }}</b>
            {{ $filter ? 'ดูรายการทั้งหมดได้ที่แท็บ "ทั้งหมด"' : 'เมื่อมีเงินเข้า-ออก คอมมิชชั่น หรือประกาศใหม่ จะแสดงที่นี่' }}
            @if($filter)
                <div><a class="nvm-btn nvm-btn--line" href="{{ route('user.notifications.index') }}">ดูทั้งหมด</a></div>
            @endif
        </div>
    @endif
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', function () {
        var csrf = function () { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; };
        var json = function (url, body) {
            return fetch(url, {
                method: 'POST', keepalive: true,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(body || {}),
            });
        };

        window.Alpine.data('nvmNotifs', function (ids) {
            return {
                ids: ids, sel: [], busy: false, askDel: false,
                get all() { return this.ids.length > 0 && this.ids.every((id) => this.sel.includes(id)); },
                toggleAll(on) { this.sel = on ? this.ids.slice() : []; this.askDel = false; },
                // กดปุ่มในแจ้งเตือน = อ่านแล้ว (keepalive ส่งต่อได้แม้หน้ากำลังเปลี่ยน)
                seen(id) { json(@js(route('user.notifications.read', ['id' => '__id__'])).replace('__id__', id)).catch(function () {}); },
                async bulk(kind) {
                    if (this.busy || ! this.sel.length) return;
                    this.busy = true;
                    try {
                        var res = await json(kind === 'delete' ? @js(route('user.notifications.bulk-delete')) : @js(route('user.notifications.bulk-mark-as-read')), { notification_ids: this.sel });
                        var data = await res.json().catch(function () { return null; });
                        if (res.ok && data && data.success) { window.location.reload(); return; }
                        window.showNotification(res.status === 419 ? 'หน้านี้เปิดค้างนานเกินไป รีเฟรชหน้าแล้วลองใหม่' : ((data && data.message) || 'ทำรายการไม่สำเร็จ ลองใหม่อีกครั้ง'), 'error');
                    } catch (e) {
                        window.showNotification('เชื่อมต่อไม่ได้ ตรวจอินเทอร์เน็ตแล้วลองใหม่', 'error');
                    }
                    this.busy = false; this.askDel = false;
                },
            };
        });
    });
</script>
@endpush
