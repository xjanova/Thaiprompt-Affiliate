{{--
    สร้างตั๋วช่วยเหลือใหม่ (ธีมโนวาเต็ม)
    รับ category_id / subject (≤255) / description (≤5000) / priority (low|medium|high) — ตรงกับ TicketController::store
--}}
@extends('layouts.user-nova')

@section('title', 'สร้างตั๋วช่วยเหลือ')

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");
    $priorities = [
        'low' => ['ไม่เร่งด่วน', 'สอบถามทั่วไป รอได้'],
        'medium' => ['ปกติ', 'แนะนำสำหรับเรื่องส่วนใหญ่'],
        'high' => ['เร่งด่วน', 'กระทบการใช้งาน/เงินในบัญชี'],
    ];
@endphp

@section('content')

<div class="nvm-stack" x-data="{ len: @js(mb_strlen((string) old('description', ''))), busy: false }" @pageshow.window="busy = false">

<section class="nvm-ph" aria-labelledby="nvm-tkc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tickets.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ตั๋วของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">New ticket</div>
            <h1 id="nvm-tkc-h">สร้างตั๋วช่วยเหลือ</h1>
            <p class="nvm-ph__lead">เล่าปัญหาให้ละเอียด ทีมงานจะตอบกลับในตั๋วนี้ และแจ้งเตือนคุณเมื่อมีคำตอบ</p>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('support', 'deco/scroll') }}" alt="" width="200" height="200">
    </div>
</section>

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>ส่งตั๋วไม่สำเร็จ</b>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    </section>
@endif

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tkf-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Details</small><h2 id="nvm-tkf-h">รายละเอียดตั๋ว</h2></div></div>
        <form method="POST" action="{{ route('user.tickets.store') }}" class="nvm-form" @submit="busy = true">
            @csrf

            <div class="nvm-field">
                <span class="nvm-label" id="nvm-tkcat-lbl">เรื่องที่ต้องการติดต่อ <em>*</em></span>
                @if($categories->isEmpty())
                    <p class="nvm-err">ยังไม่มีหมวดหมู่ให้เลือก กรุณาติดต่อทีมงานผ่านช่องทางอื่นชั่วคราว</p>
                @else
                    <div class="nvm-opts" role="radiogroup" aria-labelledby="nvm-tkcat-lbl">
                        @foreach($categories as $category)
                            <label class="nvm-opt nvm-opt--cat" @if($category->color) style="--tk-c: {{ $category->color }}" @endif>
                                <input type="radio" name="category_id" value="{{ $category->id }}" required @checked((string) old('category_id') === (string) $category->id)>
                                <span class="nvm-opt__ic" aria-hidden="true"><i class="{{ $category->icon ?: 'fa-solid fa-folder' }}"></i></span>
                                <span><b>{{ $category->name }}</b>@if($category->description)<small>{{ $category->description }}</small>@endif</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('category_id')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            <div class="nvm-field">
                <label class="nvm-label" for="tk-subject">หัวข้อ <em>*</em></label>
                <input id="tk-subject" type="text" name="subject" required maxlength="255" class="nvm-input @error('subject') is-bad @enderror" value="{{ old('subject') }}" placeholder="เช่น ถอนเงินแล้วยังไม่เข้าบัญชี">
                @error('subject')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            <div class="nvm-field">
                <label class="nvm-label" for="tk-desc">รายละเอียด <em>*</em></label>
                <textarea id="tk-desc" name="description" required maxlength="5000" rows="7" class="nvm-input nvm-textarea @error('description') is-bad @enderror" placeholder="เกิดอะไรขึ้น เมื่อไร ทำขั้นตอนไหนอยู่ และเห็นข้อความอะไรบนจอ" @input="len = $event.target.value.length">{{ old('description') }}</textarea>
                <span class="nvm-help nvm-num"><span x-text="len.toLocaleString()">0</span> / 5,000 ตัวอักษร</span>
                @error('description')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            <div class="nvm-field">
                <span class="nvm-label" id="nvm-tkpri-lbl">ความเร่งด่วน</span>
                <div class="nvm-opts nvm-opts--3" role="radiogroup" aria-labelledby="nvm-tkpri-lbl">
                    @foreach($priorities as $val => [$label, $hint])
                        <label class="nvm-opt">
                            <input type="radio" name="priority" value="{{ $val }}" @checked(old('priority', 'medium') === $val)>
                            <span><b>{{ $label }}</b><small>{{ $hint }}</small></span>
                        </label>
                    @endforeach
                </div>
                @error('priority')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            <div class="nvm-filter__btns">
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.tickets.index') }}">ยกเลิก</a>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy || @js($categories->isEmpty())"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="busy ? 'กำลังส่ง…' : 'ส่งตั๋ว'">ส่งตั๋ว</span></button>
            </div>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-tkt-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Tips</small><h2 id="nvm-tkt-h">ให้ทีมงานช่วยได้เร็วขึ้น</h2></div></div>
        <ol class="nvm-steps">
            <li>เลือกเรื่องให้ตรง ตั๋วจะถึงทีมที่ดูแลเรื่องนั้นโดยตรง</li>
            <li>หัวข้อสั้นๆ บอกปัญหาในประโยคเดียว</li>
            <li>เรื่องเงิน ให้ระบุเลขรายการ วันเวลา และยอดเงิน</li>
            <li>ปัญหาการใช้งาน ให้บอกอุปกรณ์ เบราว์เซอร์/แอป และขั้นตอนที่ทำ</li>
            <li>อย่าใส่รหัสผ่าน PIN หรือรหัส OTP ในตั๋ว ทีมงานไม่เคยขอข้อมูลเหล่านี้</li>
        </ol>
    </aside>
</div>

</div>
@endsection
