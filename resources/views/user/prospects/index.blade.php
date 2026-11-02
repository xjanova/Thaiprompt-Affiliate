{{--
    ผู้มุ่งหวังของฉัน — ลิงก์เชิญสมัครผ่าน LINE (ธีมโนวาเต็ม)
    - MlmProspectController::index ดึงเฉพาะลิงก์ของสมาชิกคนนี้ · ไม่ได้เป็นสมาชิก MLM = $notMlmMember (paginator ว่าง)
    - ตัวกรอง search / type / status / date_from / date_to อ่านใน controller แล้ว (เดิมฟอร์มมีช่องแต่ไม่ถูกใช้)
    - 🔒 (2026-11-02) ไม่โชว์ LINE user ID ของผู้มุ่งหวังแล้ว (เป็นรหัสภายในของ LINE ไม่มีประโยชน์ต่อแม่ทีม)
    - 🐛 รูปโปรไฟล์เดิมอ่าน line_profile_picture ซึ่งไม่มีคอลัมน์ → คอลัมน์จริงคือ line_picture_url
--}}
@extends('layouts.user-nova')

@section('title', 'ผู้มุ่งหวังของฉัน')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    // สถานะ → [ป้าย, โทนชิป] (prospect จากระบบดูดวงใช้สถานะของ fortuneReferral)
    $statusInfo = function ($p) {
        if ($p->fortuneReferral) {
            return match ($p->fortuneReferral->status) {
                'pending' => ['รอกดลิงก์', 'warn'],
                'followed' => ['เพิ่มเพื่อน LINE แล้ว', 'info'],
                'converted' => ['สมัครสมาชิกแล้ว', 'ok'],
                'expired' => ['หมดอายุ', 'bad'],
                default => [$p->fortuneReferral->status, ''],
            };
        }

        return match ($p->status) {
            'pending' => ['รอดำเนินการ', 'warn'],
            'in_progress' => ['กำลังดำเนินการ', 'info'],
            'completed' => ['สำเร็จ', 'ok'],
            'expired' => ['หมดอายุ', 'bad'],
            'cancelled' => ['ยกเลิก', ''],
            default => [$p->status, ''],
        };
    };
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';
    $total = (int) ($stats['total'] ?? 0);
    $completed = (int) ($stats['completed'] ?? 0);
    $notMlm = isset($notMlmMember) && $notMlmMember;
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-pp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Prospects</div>
            <h1 id="nvm-pp-h">ผู้มุ่งหวังของฉัน</h1>
            <p class="nvm-ph__lead">ติดตามคนที่คุณส่งลิงก์เชิญให้สมัครผ่าน LINE ว่าถึงขั้นไหนแล้ว</p>
            @if($total > 0)
                <div class="nvm-pills">
                    <span class="nvm-pill nvm-pill--ok nvm-num"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>อัตราการแปลง {{ number_format($completed / $total * 100, 1) }}%</span>
                </div>
            @endif
            @unless($notMlm)
                <div class="nvm-hero__actions">
                    <a class="nvm-btn nvm-btn--gold" href="{{ route('user.prospects.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างลิงก์เชิญใหม่</a>
                </div>
            @endunless
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/mascot/present.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($notMlm)
    <section class="nvm-card nvm-alert" role="status">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>คุณยังไม่ได้เป็นสมาชิก MLM</b>
            <p>สมัครเป็นสมาชิก MLM ก่อน จึงจะสร้างลิงก์เชิญและติดตามผู้มุ่งหวังได้</p>
        </div>
        <div class="nvm-alert__side"><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.mlm.dashboard') }}">ไปหน้า MLM</a></div>
    </section>
@endif

<section class="nvm-tr-stats nvm-tr-stats--5" aria-label="สรุปผู้มุ่งหวัง">
    <div class="nvm-mini"><small>ทั้งหมด</small><b class="nvm-num">{{ number_format($total) }}</b></div>
    <div class="nvm-mini"><small>รอดำเนินการ</small><b class="nvm-num is-warn">{{ number_format((int) ($stats['pending'] ?? 0)) }}</b></div>
    <div class="nvm-mini"><small>กำลังดำเนินการ</small><b class="nvm-num is-info">{{ number_format((int) ($stats['in_progress'] ?? 0)) }}</b></div>
    <div class="nvm-mini"><small>สำเร็จ</small><b class="nvm-num is-in">{{ number_format($completed) }}</b></div>
    <div class="nvm-mini"><small>หมดอายุ</small><b class="nvm-num is-out">{{ number_format((int) ($stats['expired'] ?? 0)) }}</b></div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-pp-list-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Invitations</small><h2 id="nvm-pp-list-h">ลิงก์เชิญทั้งหมด</h2></div></div>

    <form method="GET" action="{{ route('user.prospects.index') }}" class="nvm-filter nvm-tr-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="pp-search">ค้นหา</label>
            <input type="search" id="pp-search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="ชื่อ LINE หรือ Token" class="nvm-input">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="pp-type">ประเภท</label>
            <select id="pp-type" name="type" class="nvm-input">
                <option value="">ทั้งหมด</option>
                <option value="fortune" @selected(request('type') === 'fortune')>จากระบบดูดวง</option>
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="pp-status">สถานะ</label>
            <select id="pp-status" name="status" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach(['pending' => 'รอดำเนินการ', 'in_progress' => 'กำลังดำเนินการ', 'completed' => 'สำเร็จ', 'expired' => 'หมดอายุ'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="pp-from">จากวันที่</label>
            <input type="date" id="pp-from" name="date_from" value="{{ request('date_from') }}" class="nvm-input">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="pp-to">ถึงวันที่</label>
            <input type="date" id="pp-to" name="date_to" value="{{ request('date_to') }}" class="nvm-input">
        </div>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>กรอง</button>
            <a class="nvm-btn nvm-btn--line" href="{{ route('user.prospects.index') }}">ล้าง</a>
        </div>
    </form>

    @if($prospects->count() > 0)
        <div class="nvm-table-wrap nvm-gap-t">
            <table class="nvm-table">
                <thead>
                    <tr><th>ผู้มุ่งหวัง</th><th>สถานะ</th><th>ผู้ที่สมัครแล้ว</th><th>สร้างเมื่อ</th><th class="r">จัดการ</th></tr>
                </thead>
                <tbody>
                    @foreach($prospects as $prospect)
                        @php [$stLabel, $stTone] = $statusInfo($prospect); @endphp
                        <tr>
                            <td>
                                <div class="nvm-tr-who">
                                    <span class="nvm-avatar">
                                        @if($prospect->line_picture_url)
                                            <img src="{{ $prospect->line_picture_url }}" alt="" loading="lazy">
                                        @else
                                            <svg class="nvm-ico"><use href="#nvm-i-line"/></svg>
                                        @endif
                                    </span>
                                    <div>
                                        <b>{{ $prospect->line_display_name ?? 'รอการคลิกลิงก์' }}</b>
                                        <small>
                                            @if($prospect->fortuneReferral)จากระบบดูดวง · @endif
                                            {{ $prospect->visit_count > 0 ? 'เปิดลิงก์ '.number_format($prospect->visit_count).' ครั้ง' : 'ยังไม่มีคนเปิดลิงก์' }}
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="nvm-chip {{ $stTone ? 'nvm-chip--'.$stTone : '' }}">{{ $stLabel }}</span></td>
                            <td>{{ $prospect->registeredUser?->name ?? 'ยังไม่สมัคร' }}</td>
                            <td class="nvm-num">{{ $thDate($prospect->created_at) }}</td>
                            <td class="r">
                                <div class="nvm-tr-rowacts">
                                    <a class="nvm-btn nvm-btn--line" href="{{ route('user.prospects.show', $prospect->id) }}">ดู</a>
                                    @if(in_array($prospect->status, ['pending', 'expired'], true))
                                        <form method="POST" action="{{ route('user.prospects.destroy', $prospect->id) }}" x-data @submit="if (! confirm('ลบลิงก์เชิญนี้?')) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="nvm-btn nvm-btn--line">ลบ</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($prospects->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $prospects->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/gift.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีผู้มุ่งหวัง</b>
            เริ่มสร้างเครือข่ายโดยสร้างลิงก์เชิญ แล้วแชร์ให้เพื่อน
            @unless($notMlm)
                <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.prospects.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างลิงก์เชิญคนแรก</a></div>
            @endunless
        </div>
    @endif
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-pp-tip-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Tips</small><h2 id="nvm-pp-tip-h">เคล็ดลับเพิ่มอัตราการสมัคร</h2></div></div>
    <ol class="nvm-steps">
        <li>ส่งลิงก์พร้อมข้อความส่วนตัว คนรับจะรู้สึกว่าคุณตั้งใจชวนจริงๆ</li>
        <li>ติดตามคนที่ยังไม่สมัครภายใน 24-48 ชั่วโมงหลังเปิดลิงก์</li>
        <li>ต่ออายุลิงก์ที่หมดอายุ อย่าปล่อยให้ผู้มุ่งหวังหลุดมือ</li>
    </ol>
</section>

</div>
@endsection
