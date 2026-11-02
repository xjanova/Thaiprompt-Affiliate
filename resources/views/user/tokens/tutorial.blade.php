{{--
    คู่มือสร้างเหรียญลูกบน TPIX (ธีมโนวาเต็ม) — หน้าสอนล้วน ไม่มีข้อมูลจากฐานข้อมูล
    - แท็บ 4 หน้า (ภาพรวม · ขั้นตอน · คุณสมบัติ · คำถาม) สลับด้วย Alpine
    - ตัดตัวเลขที่ระบบไม่ได้คิดจริงออก (เดิมเขียนว่าค่าสร้าง 100 TPIX / ทีมงานตอบ 24 ชม.) และลิงก์ช่วยเหลือ "#" ที่กดไม่ได้
--}}
@extends('layouts.user-nova')

@section('title', 'คู่มือสร้างเหรียญ')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@php
    $tabs = ['overview' => 'ภาพรวม', 'steps' => 'ขั้นตอนการสร้าง', 'features' => 'คุณสมบัติเหรียญ', 'faq' => 'คำถามที่พบบ่อย'];
    $features = [
        ['Mintable', 'สร้างเหรียญเพิ่มได้หลังขึ้นเครือข่าย', 'ระบบรางวัล · เกม · แต้มสะสม', 'ใช้อย่างระวัง'],
        ['Burnable', 'เผาเหรียญเพื่อลดจำนวนได้', 'เหรียญที่ต้องการลด supply', 'แนะนำ'],
        ['Pausable', 'หยุดการโอนทั้งหมดชั่วคราวได้', 'กรณีฉุกเฉิน · เหตุด้านความปลอดภัย', 'ใช้อย่างระวัง'],
        ['Freezable', 'ระงับการโอนของบางที่อยู่ได้', 'ป้องกันการถูกแฮก · การปฏิบัติตามกฎ', 'ใช้อย่างระวัง'],
    ];
    $faqs = [
        ['ค่าใช้จ่ายในการสร้างเหรียญเท่าไหร่?', 'การนำเหรียญขึ้นเครือข่ายต้องใช้ค่า Gas ของ TPIX Blockchain หน้าสร้างเหรียญจะแสดงค่า Gas โดยประมาณตามราคาเครือข่ายขณะนั้น'],
        ['นานแค่ไหนกว่าเหรียญจะพร้อมใช้?', 'หลังส่งข้อมูล เหรียญจะอยู่ในสถานะร่างจนกว่าทีมงานตรวจสอบ เมื่ออนุมัติแล้วจึงนำขึ้นเครือข่าย ระบบจะแจ้งเตือนผลให้ทราบ'],
        ['แก้ไขข้อมูลเหรียญหลังสร้างได้ไหม?', 'จำนวนทั้งหมด สัญลักษณ์ และคุณสมบัติของสัญญาแก้ไม่ได้หลังขึ้นเครือข่าย ส่วนข้อมูลประกอบอย่างเว็บไซต์หรือลิงก์ติดต่อทีมงานให้ช่วยแก้ได้'],
        ['เหรียญของฉันซื้อขายได้ที่ไหน?', 'เมื่อเหรียญพร้อมใช้งาน สามารถเพิ่มสภาพคล่องบน TPIX DEX เพื่อให้ผู้อื่นแลกเปลี่ยนได้'],
        ['ต้องเขียนโค้ดเองไหม?', 'ไม่ต้อง ระบบสร้างสัญญาอัจฉริยะมาตรฐาน ERC-20 ให้ตามข้อมูลที่กรอก'],
        ['สัญญาปลอดภัยแค่ไหน?', 'ใช้โค้ดมาตรฐาน ERC-20 แต่ความน่าเชื่อถือของเหรียญขึ้นกับโครงการของคุณเอง ควรอธิบายวัตถุประสงค์ให้ชัดและเปิดคุณสมบัติเท่าที่จำเป็น'],
    ];
@endphp

@section('content')

<div class="nvm-stack" x-data="{ tab: 'overview' }">

<section class="nvm-ph" aria-labelledby="nvm-tkt-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tokens.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ตลาดเหรียญ</a>
            <div class="nvm-eyebrow nvm-latin">Beginner guide</div>
            <h1 id="nvm-tkt-h">สร้างเหรียญของคุณเอง</h1>
            <p class="nvm-ph__lead">เรียนรู้ขั้นตอนการสร้างเหรียญบน TPIX Blockchain ตั้งแต่กรอกข้อมูลจนพร้อมใช้งาน ไม่ต้องเขียนโค้ด</p>
            <div class="nvm-hero__actions">
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.tokens.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เริ่มสร้างเหรียญ</a>
                <button type="button" class="nvm-btn nvm-btn--ghost" x-on:click="tab = 'steps'">อ่านขั้นตอนก่อน</button>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-tx-facts" aria-label="สรุปการสร้างเหรียญ">
    <div><b>4 ขั้นตอน</b><small>กรอกฟอร์มสั้น ๆ</small></div>
    <div><b>ERC-20</b><small>สัญญามาตรฐาน</small></div>
    <div><b>0 โค้ด</b><small>ไม่ต้องเขียนเอง</small></div>
    <div><b>ตรวจก่อน</b><small>ทีมงานอนุมัติก่อนขึ้นเชน</small></div>
</section>

<div class="nvm-tabs" role="tablist" aria-label="หัวข้อคู่มือ">
    @foreach($tabs as $key => $label)
        <button type="button" role="tab" :class="tab === @js($key) && 'is-on'" :aria-selected="tab === @js($key)" x-on:click="tab = @js($key)">{{ $label }}</button>
    @endforeach
</div>

{{-- ภาพรวม --}}
<div class="nvm-split" x-show="tab === 'overview'">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tkt-o1">
        <div class="nvm-sec-h"><div><small class="nvm-latin">What is a token</small><h2 id="nvm-tkt-o1">เหรียญ (Token) คืออะไร?</h2></div></div>
        <p class="nvm-desc">เหรียญคือสินทรัพย์ดิจิทัลที่สร้างบน Blockchain ใช้เป็นเหรียญประจำโครงการของคุณได้ เช่น</p>
        <ul class="nvm-tx-ul nvm-gap-t">
            <li><b>ระบบสมาชิก</b> — ใช้เป็นรางวัลหรือสิทธิพิเศษ</li>
            <li><b>เกม</b> — เป็นเงินในเกม</li>
            <li><b>ธุรกิจ</b> — แลกสินค้าหรือบริการ</li>
            <li><b>ชุมชน</b> — ให้สิทธิ์โหวตหรือร่วมบริหาร</li>
        </ul>
    </section>
    <aside class="nvm-card nvm-box" aria-labelledby="nvm-tkt-o2">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Why TPIX</small><h2 id="nvm-tkt-o2">ทำไมต้องสร้างบน TPIX?</h2></div></div>
        <ul class="nvm-tx-ul">
            <li>ค่าธรรมเนียมเครือข่ายต่ำ</li>
            <li>ยืนยันธุรกรรมเร็ว</li>
            <li>ใช้งานง่าย ไม่ต้องเขียนโค้ด</li>
            <li>เชื่อมกับ TPIX DEX และกระเป๋า TPIX ในระบบ</li>
        </ul>
    </aside>
</div>

{{-- ขั้นตอน --}}
<section class="nvm-card nvm-box" x-show="tab === 'steps'" x-cloak aria-labelledby="nvm-tkt-s">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Step by step</small><h2 id="nvm-tkt-s">ขั้นตอนการสร้างเหรียญ</h2></div></div>
    <ol class="nvm-steps nvm-steps--lg">
        <li><div><b>กรอกข้อมูลพื้นฐาน</b><p>ชื่อเหรียญ (เช่น CoffeeCoin) สัญลักษณ์ 2-20 ตัว (เช่น COFFEE) จำนวนทั้งหมด และทศนิยม (มาตรฐาน 18) — คิดจำนวนให้ดี แก้ภายหลังไม่ได้ ยกเว้นเปิด Mintable</p></div></li>
        <li><div><b>เลือกคุณสมบัติ</b><p>Mintable · Burnable · Pausable · Freezable เลือกเท่าที่จำเป็น ดูคำอธิบายในแท็บ "คุณสมบัติเหรียญ"</p></div></li>
        <li><div><b>ตั้งราคาและใส่ลิงก์</b><p>ราคาเริ่มต้นเป็น TPIX (เช่น 1 เหรียญ = 0.01 TPIX) พร้อมเว็บไซต์ Whitepaper และช่องทางติดต่อ เพื่อให้ผู้ถือเชื่อมั่น</p></div></li>
        <li><div><b>ตรวจสอบและส่ง</b><p>ตรวจชื่อ สัญลักษณ์ จำนวน และคุณสมบัติให้ถูกต้อง แล้วกดสร้าง เหรียญจะเป็นร่างจนทีมงานอนุมัติและนำขึ้นเครือข่าย</p></div></li>
    </ol>
    <div class="nvm-gap-t"><a class="nvm-btn nvm-btn--gold" href="{{ route('user.tokens.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เริ่มสร้างเหรียญเลย</a></div>
</section>

{{-- คุณสมบัติ --}}
<section class="nvm-card nvm-box" x-show="tab === 'features'" x-cloak aria-labelledby="nvm-tkt-f">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Token features</small><h2 id="nvm-tkt-f">คุณสมบัติที่เลือกได้</h2></div></div>
    <div class="nvm-table-wrap">
        <table class="nvm-table">
            <thead><tr><th>คุณสมบัติ</th><th>คำอธิบาย</th><th>เหมาะกับ</th><th>คำแนะนำ</th></tr></thead>
            <tbody>
                @foreach($features as [$name, $desc, $fit, $advice])
                    <tr>
                        <td><b>{{ $name }}</b></td>
                        <td>{{ $desc }}</td>
                        <td>{{ $fit }}</td>
                        <td><span class="nvm-chip {{ $advice === 'แนะนำ' ? 'nvm-chip--ok' : 'nvm-chip--warn' }}">{{ $advice }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>คุณสมบัติที่ให้อำนาจผู้สร้างสูง (Mintable / Pausable / Freezable) อาจทำให้ผู้ถือมองว่าเสี่ยง ควรอธิบายเหตุผลในคำอธิบายเหรียญ</p>
</section>

{{-- คำถามที่พบบ่อย --}}
<div class="nvm-split" x-show="tab === 'faq'" x-cloak>
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tkt-q">
        <div class="nvm-sec-h"><div><small class="nvm-latin">FAQ</small><h2 id="nvm-tkt-q">คำถามที่พบบ่อย</h2></div></div>
        <div class="nvm-tx-faq">
            @foreach($faqs as [$q, $a])
                <details>
                    <summary>{{ $q }}<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></summary>
                    <p>{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>
    <aside class="nvm-card nvm-box" aria-labelledby="nvm-tkt-help">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Need help</small><h2 id="nvm-tkt-help">ยังมีคำถาม?</h2></div></div>
        <p class="nvm-desc">ส่งคำถามถึงทีมงานได้ทางระบบตั๋วช่วยเหลือ หรือหน้าติดต่อเรา</p>
        <div class="nvm-filter__btns nvm-gap-t">
            @if(\Illuminate\Support\Facades\Route::has('user.tickets.create'))
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.tickets.create') }}"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>เปิดตั๋ว</a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('contact'))
                <a class="nvm-btn nvm-btn--line" href="{{ route('contact') }}">ติดต่อเรา</a>
            @endif
        </div>
    </aside>
</div>

</div>
@endsection
