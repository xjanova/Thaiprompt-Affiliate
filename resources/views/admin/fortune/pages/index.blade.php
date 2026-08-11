@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- 🏬 สาขา / เพจแม่หมอ — ระบบหลายเพจ (2026-08-10)
     1 แถว = 1 เพจ Facebook  ค่าที่เว้นว่าง = ใช้ค่ากลางจากหน้า "ตั้งค่าระบบดูดวง" --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · สาขา</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">สาขา / เพจแม่หมอ 🏬</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                เพิ่มเพจใหม่ที่นี่ — บิลและลูกค้าจะถูกติดป้ายสาขาให้อัตโนมัติ
            </div>
        </div>
        <a href="{{ route('admin.fortune.pages.create') }}" class="tp-btn">
            <i class="fas fa-sliders"></i> เพิ่มแบบกรอกเอง
        </a>
    </div>

    {{-- ===== 🔑 โหมดง่าย: ใส่แค่ไอดีเพจ ===== --}}
    <div class="tp-card" style="padding:22px;">
        @if($hasUserToken)
            <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-bolt"></i> เพิ่มเพจใหม่ — ใส่แค่ไอดีเพจ</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-bottom:14px;">
                เชื่อมบัญชีไว้แล้ว ✅ {{ $userTokenCheckedAt ? '(ตรวจล่าสุด '.$userTokenCheckedAt->format('d/m/y H:i').')' : '' }}
                — ระบบจะไปดึงชื่อเพจและกุญแจของเพจมาให้เอง
            </div>

            {{-- ⏳ นาฬิกา 2 เรือนของ token — หมดคนละเวลา พังคนละแบบ
                 ต้องเห็นตรงนี้ก่อน ไม่ใช่ไปรู้ตอนบอทเงียบแล้ว --}}
            @php
                // ⚠️ ว่างทั้งคู่ = "ยังไม่เคยตรวจ" ไม่ใช่ "ไม่มีวันหมด"
                //    (เชื่อมบัญชีไว้ก่อนที่ระบบจะตรวจอายุเป็น) — ห้ามโชว์ว่าปลอดภัยทั้งที่ไม่รู้
                $tpTokenKnown = $tokenHealth['expiresAt'] !== null || $tokenHealth['dataAccessAt'] !== null;

                // สีตามความเร่งด่วน: หมดแล้ว=แดง / เหลือ ≤7 วัน=ส้ม / ที่เหลือ=สีปกติ
                $tpTokenTone = function (?int $days) {
                    if ($days === null) { return 'var(--ink2)'; }
                    if ($days < 0) { return '#d9534f'; }
                    if ($days <= 7) { return '#c98a3a'; }
                    return 'var(--ink2)';
                };
                $tpTokenWhen = function (?int $days) {
                    if ($days === null) { return 'ไม่มีวันหมดอายุ'; }
                    if ($days < 0) { return 'หมดอายุแล้ว '.abs($days).' วัน'; }
                    return 'อีก '.$days.' วัน';
                };
            @endphp
            @if($tpTokenKnown)
                <div style="display:flex; flex-wrap:wrap; gap:8px 18px; margin-bottom:14px; font-size:12px;">
                    <span style="color:{{ $tpTokenTone($tokenHealth['expiresInDays']) }};">
                        🔑 กุญแจบัญชี: {{ $tpTokenWhen($tokenHealth['expiresInDays']) }}
                        @if($tokenHealth['expiresAt'])
                            <span style="color:var(--ink2);">({{ $tokenHealth['expiresAt']->format('d/m/y') }})</span>
                        @endif
                    </span>
                    <span style="color:{{ $tpTokenTone($tokenHealth['dataAccessInDays']) }};">
                        👤 สิทธิ์อ่านข้อมูลผู้ใช้: {{ $tpTokenWhen($tokenHealth['dataAccessInDays']) }}
                        @if($tokenHealth['dataAccessAt'])
                            <span style="color:var(--ink2);">({{ $tokenHealth['dataAccessAt']->format('d/m/y') }})</span>
                        @endif
                    </span>
                </div>
            @else
                <div style="font-size:12px; color:var(--ink2); margin-bottom:14px;">
                    ⏳ ยังไม่ทราบอายุ token — เปิด “เปลี่ยนบัญชีที่เชื่อมไว้” ด้านล่างแล้ววาง token ใหม่
                    ระบบจะต่ออายุให้เป็นแบบยาวและตรวจวันหมดอายุให้ในคราวเดียว
                </div>
            @endif

            @if($tokenHealth['dataAccessInDays'] !== null && $tokenHealth['dataAccessInDays'] < 0)
                <div style="font-size:12px; color:#d9534f; margin:-6px 0 14px; line-height:1.9;">
                    ⚠️ สิทธิ์อ่านข้อมูลผู้ใช้หมดแล้ว — บอทยังส่งข้อความได้ปกติ แต่<strong>อ่านชื่อลูกค้าไม่ได้</strong>
                    และการจับคู่บัญชีตอนลูกค้าล็อกอินด้วย Facebook จะพัง (error_subcode 33) → วาง token ใหม่เพื่อรีเซ็ตนาฬิกา 90 วัน
                </div>
            @endif

            <form method="POST" action="{{ route('admin.fortune.pages.quick-add') }}"
                  style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                @csrf
                <div class="tp-well tp-input" style="padding:0; flex:1; min-width:240px;">
                    <input type="text" name="external_page_id" required placeholder="วางไอดีเพจที่นี่ (ตัวเลขล้วน)"
                           style="width:100%; background:transparent; border:0; outline:0; padding:12px 14px; color:var(--ink); font-size:14px; font-family:monospace;">
                </div>
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-plus"></i> เพิ่มเพจ</button>
            </form>

            <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <form method="POST" action="{{ route('admin.fortune.pages.discover') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="tp-btn"><i class="fas fa-wand-magic-sparkles"></i> ดึงเพจทั้งหมดของฉันมาเพิ่มทีเดียว</button>
                </form>
                <span style="font-size:12px; color:var(--ink2);">ปุ่มนี้รีเฟรชกุญแจของเพจเดิมให้ด้วย — ใช้ตอนบอทเงียบเพราะ token หมดอายุ</span>
            </div>

            <details style="margin-top:14px;">
                <summary style="cursor:pointer; font-size:12.5px; color:var(--ink2);">เปลี่ยนบัญชีที่เชื่อมไว้</summary>
                @include('admin.fortune.pages._connect-form')
            </details>
        @else
            <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-link"></i> เชื่อมบัญชีก่อน (ทำครั้งเดียว)</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-bottom:6px; line-height:1.9;">
                Facebook บังคับว่า <strong>แต่ละเพจต้องมีกุญแจของตัวเอง</strong> — กุญแจของเพจ A ส่งข้อความในเพจ B ไม่ได้<br>
                เชื่อมบัญชีเจ้าของไว้ครั้งเดียว แล้วหลังจากนั้น <strong>เพิ่มเพจใหม่ใส่แค่ไอดีเพจ</strong> ระบบไปเอากุญแจมาเอง
            </div>
            @include('admin.fortune.pages._connect-form')
        @endif
    </div>

    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid #5aa07e;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid #d9534f;">{{ session('error') }}</div>
    @endif

    @if($orphanBills > 0)
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid #d6824a;">
            ⚠️ มีบิล <strong>{{ number_format($orphanBills) }}</strong> ใบที่ยังไม่มีสาขา
            (บิลเก่าก่อนเปิดระบบสาขา หรือถูกสร้างจากคำสั่ง artisan)
            — ดูได้ที่ <a href="{{ route('admin.fortune.bills.index', ['fortune_page' => 'none']) }}">ศูนย์รวมบิล → ไม่ระบุสาขา</a>
        </div>
    @endif

    {{-- ===== ตารางสาขา ===== --}}
    <div class="tp-card" style="padding:22px;">
        @if($pages->isEmpty())
            <div style="text-align:center; padding:44px 16px; color:var(--ink2);">
                <div style="font-size:34px; margin-bottom:8px;">🏬</div>
                <div style="font-size:14px;">ยังไม่มีสาขา — กด “เพิ่มสาขาใหม่” เพื่อเริ่ม</div>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="min-width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2);">
                            @foreach(['สาขา','ช่องทาง','Page ID','ลูกค้า','บิลทั้งหมด','จ่ายแล้ว','รายได้','สถานะ','จัดการ'] as $th)
                                <th style="padding:10px 12px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; {{ $th === 'จัดการ' ? 'text-align:right;' : '' }}">{{ $th }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pages as $p)
                            @php $s = $stats[$p->id] ?? null; @endphp
                            <tr style="border-top:1px solid var(--line);">
                                <td style="padding:11px 12px;">
                                    <div style="font-weight:700;">{{ $p->display_label }}</div>
                                    <div style="font-family:monospace; font-size:11px; color:var(--ink2);">{{ $p->code }}</div>
                                    @if($p->is_default)
                                        <span class="tp-pill tp-pill-soft" style="font-size:10px; font-weight:700;">⭐ สาขาหลัก</span>
                                    @endif
                                </td>
                                <td style="padding:11px 12px; white-space:nowrap;">{{ $p->platform === 'line' ? 'LINE' : 'Facebook' }}</td>
                                <td style="padding:11px 12px; font-family:monospace; font-size:11px;">{{ $p->external_page_id }}</td>
                                <td style="padding:11px 12px;">{{ number_format((int) ($s->customers ?? 0)) }}</td>
                                <td style="padding:11px 12px;">{{ number_format((int) ($s->total_bills ?? 0)) }}</td>
                                <td style="padding:11px 12px;">{{ number_format((int) ($s->paid_bills ?? 0)) }}</td>
                                <td style="padding:11px 12px; font-weight:700; color:#5aa07e;">฿{{ number_format((float) ($s->revenue ?? 0), 0) }}</td>
                                <td style="padding:11px 12px;">
                                    @if($p->is_active)
                                        <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#3f7a5c; font-weight:700;">เปิด</span>
                                    @else
                                        <span class="tp-pill" style="background:rgba(140,140,150,.16); color:#6b6b73; font-weight:700;">ปิด</span>
                                    @endif
                                    @if(empty($p->page_access_token) && $hasGlobalPageToken)
                                        <span class="tp-pill" title="สาขานี้ไม่ได้ใส่กุญแจของตัวเอง — ใช้กุญแจกลางจากหน้าช่องทางรับข้อความ"
                                              style="background:rgba(140,140,150,.16); color:#6b6b73; font-size:10px; font-weight:700;">ใช้กุญแจกลาง</span>
                                    @elseif(empty($p->page_access_token))
                                        <span class="tp-pill" title="ยังไม่มี Page Access Token ทั้งของสาขาและของกลาง — สาขานี้ตอบลูกค้าไม่ได้"
                                              style="background:rgba(217,83,79,.16); color:#b1413d; font-size:10px; font-weight:700;">ไม่มี Token</span>
                                    @endif
                                </td>
                                <td style="padding:11px 12px; text-align:right; white-space:nowrap;">
                                    <a href="{{ route('admin.fortune.bills.index', ['fortune_page' => $p->id]) }}" class="tp-btn tp-btn-sm">บิล</a>
                                    <a href="{{ route('admin.fortune.pages.edit', $p) }}" class="tp-btn tp-btn-sm">แก้ไข</a>
                                    <form method="POST" action="{{ route('admin.fortune.pages.test', $p) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tp-btn tp-btn-sm">ทดสอบ Token</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.fortune.pages.toggle', $p) }}" style="display:inline;"
                                          onsubmit="return confirm('{{ $p->is_active ? 'ปิด' : 'เปิด' }}สาขา {{ $p->name }} ใช่ไหม?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="tp-btn tp-btn-sm">{{ $p->is_active ? 'ปิด' : 'เปิด' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== วิธีเพิ่มเพจ ===== --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-list-check"></i> ขั้นตอนเพิ่มเพจใหม่</div>
        <ol style="margin:0; padding-left:20px; line-height:2; font-size:13.5px;">
            <li>เข้า Meta App เดิม → Messenger → Settings → <strong>เพิ่มเพจใหม่เข้าแอป</strong> (ต้องทำ ไม่งั้น Facebook ไม่ส่ง webhook มา)</li>
            <li>Subscribe webhook fields ของเพจนั้น: <code>messages</code>, <code>messaging_postbacks</code>,
                <code>message_reactions</code>, <code>messaging_referrals</code>, <code>feed</code></li>
            <li>กลับมาหน้านี้ → <strong>วางไอดีเพจในช่องด้านบน → กดเพิ่มเพจ</strong> (ชื่อเพจกับกุญแจ ระบบดึงให้เอง)</li>
            <li>กด “ทดสอบ Token” ที่แถวของเพจนั้น — ต้องขึ้นชื่อเพจที่ถูกต้อง</li>
            <li>ทักเพจใหม่ทดสอบ → เช็คว่าบิลขึ้นชื่อสาขาถูก</li>
        </ol>
        <div style="margin-top:14px; font-size:12.5px; color:var(--ink2); line-height:1.9;">
            ⚠️ <strong>PSID ของ Facebook เป็นของเพจ ไม่ใช่ของคน</strong> — ลูกค้าคนเดิมที่ทัก 2 เพจจะถูกนับเป็น 2 คน
            โควต้าดูดวงฟรี การแบน และความจำบุคลิก จึงแยกกันรายสาขา (ตั้งใจให้เป็นแบบนี้ในเฟสแรก)
        </div>
    </div>
</div>
@endsection
