{{--
    สร้างเหรียญลูก (ธีมโนวาเต็ม) — ฟอร์ม 4 ขั้นใน Alpine ส่ง POST ไป user.tokens.store
    เดิมพังทั้งหน้า:
    - GET /user/tokens/create ตกไปหา route /{id} จึง 404 (แก้ลำดับ/whereNumber ใน routes/user.php แล้ว)
    - ฟอร์มไม่มี action/method → form.submit() ยิง GET กลับหน้าเดิม ไม่มีอะไรถูกบันทึก
    - checkbox ส่งค่า "on" ซึ่งกฎ boolean ไม่รับ · ช่อง github_url/twitter_handle/telegram_group ไม่ตรงชื่อที่ service อ่าน
    - ช่องหมวดหมู่และอัปโหลดโลโก้ไม่มีที่เก็บฝั่งเซิร์ฟเวอร์ จึงเอาออก
--}}
@extends('layouts.user-nova')

@section('title', 'สร้างเหรียญใหม่')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@php
    // ขั้นแรกที่มี error จากเซิร์ฟเวอร์ — เปิดฟอร์มไปที่ขั้นนั้นเลย
    $stepFields = [
        1 => ['name', 'symbol', 'description', 'total_supply', 'decimals'],
        2 => ['is_mintable', 'is_burnable', 'is_pausable', 'is_freezable'],
        3 => ['initial_price_tpix', 'website', 'whitepaper_url', 'github', 'twitter', 'telegram'],
    ];
    $startStep = 1;
    foreach ($stepFields as $n => $fields) {
        if ($errors->hasAny($fields)) {
            $startStep = $n;
            break;
        }
    }
    $flags = [
        ['is_mintable', 'Mintable', 'สร้างเหรียญเพิ่มได้หลังขึ้นเครือข่าย', false],
        ['is_burnable', 'Burnable', 'เผาเหรียญเพื่อลดจำนวนได้', true],
        ['is_pausable', 'Pausable', 'หยุดการโอนทั้งหมดชั่วคราวได้', false],
        ['is_freezable', 'Freezable', 'ระงับการโอนของบางที่อยู่ได้', false],
    ];
    $formData = [
        'name' => old('name', ''),
        'symbol' => old('symbol', ''),
        'description' => old('description', ''),
        'total_supply' => old('total_supply', ''),
        'decimals' => (string) old('decimals', '18'),
        'initial_price_tpix' => old('initial_price_tpix', ''),
        'website' => old('website', ''),
    ];
    foreach ($flags as [$key, , , $default]) {
        $formData[$key] = (bool) old($key, $default);
    }
    $gasCost = $estimatedCost['total_cost_tpix'] ?? null;
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tkc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tokens.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ตลาดเหรียญ</a>
            <div class="nvm-eyebrow nvm-latin">Create token</div>
            <h1 id="nvm-tkc-h">สร้างเหรียญของคุณ</h1>
            <p class="nvm-ph__lead">กรอกข้อมูลเหรียญ ทีมงานจะตรวจสอบก่อนนำขึ้น TPIX Blockchain</p>
            <div class="nvm-hero__actions">
                <a class="nvm-btn nvm-btn--ghost nvm-btn--sm" href="{{ route('user.tokens.tutorial') }}"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>อ่านคู่มือก่อน</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ข้อมูลยังไม่ครบหรือไม่ถูกต้อง</b><p>{{ $errors->first() }}</p></div>
    </section>
@endif

<div class="nvm-split">
    <form class="nvm-card nvm-box" method="POST" action="{{ route('user.tokens.store') }}" novalidate
          x-data="nvmTokenForm(@js($formData), {{ $startStep }})" x-on:submit="submit($event)" x-on:pageshow.window="submitting = false">
        @csrf

        <div class="nvm-sec-h">
            <div class="nvm-tabs" role="tablist" aria-label="ขั้นตอนการสร้างเหรียญ">
                @foreach(['ข้อมูลพื้นฐาน', 'คุณสมบัติ', 'ราคาและลิงก์', 'ตรวจสอบ'] as $i => $label)
                    <button type="button" role="tab" :class="step === {{ $i + 1 }} && 'is-on'" :aria-selected="step === {{ $i + 1 }}" x-on:click="goTo({{ $i + 1 }})">{{ $i + 1 }}. {{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- ขั้นที่ 1: ข้อมูลพื้นฐาน --}}
        <div class="nvm-form" x-ref="step1" x-show="step === 1">
            <div class="nvm-field">
                <label class="nvm-label" for="tkc-name">ชื่อเหรียญ <em>*</em></label>
                <input id="tkc-name" class="nvm-input @error('name') is-bad @enderror" type="text" name="name" x-model="f.name" required maxlength="100" placeholder="เช่น CoffeeCoin">
                @error('name')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="tkc-symbol">สัญลักษณ์ <em>*</em></label>
                <input id="tkc-symbol" class="nvm-input @error('symbol') is-bad @enderror" type="text" name="symbol" x-model="f.symbol" required maxlength="20" pattern="[A-Za-z0-9]+" autocapitalize="characters" placeholder="เช่น COFFEE">
                @error('symbol')<span class="nvm-err">{{ $message }}</span>@else<span class="nvm-help">ตัวอักษรอังกฤษและตัวเลขเท่านั้น ไม่เกิน 20 ตัว</span>@enderror
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="tkc-desc">คำอธิบาย</label>
                <textarea id="tkc-desc" class="nvm-input nvm-textarea @error('description') is-bad @enderror" name="description" x-model="f.description" maxlength="1000" placeholder="วัตถุประสงค์และการใช้งานของเหรียญ"></textarea>
                @error('description')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            <div class="nvm-minis">
                <div class="nvm-field">
                    <label class="nvm-label" for="tkc-supply">จำนวนทั้งหมด <em>*</em></label>
                    <input id="tkc-supply" class="nvm-input nvm-num @error('total_supply') is-bad @enderror" type="number" name="total_supply" x-model="f.total_supply" required min="1" step="1" inputmode="numeric" placeholder="1000000">
                    @error('total_supply')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="tkc-decimals">ทศนิยม <em>*</em></label>
                    <select id="tkc-decimals" class="nvm-input @error('decimals') is-bad @enderror" name="decimals" x-model="f.decimals" required>
                        @foreach(['18' => '18 (มาตรฐาน)', '8' => '8', '6' => '6', '2' => '2'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('decimals')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        {{-- ขั้นที่ 2: คุณสมบัติ (hidden 0 + checkbox 1 → ส่งค่า boolean เสมอ) --}}
        <div class="nvm-form" x-ref="step2" x-show="step === 2" x-cloak>
            @foreach($flags as [$key, $title, $hint])
                <label class="nvm-tx-pick">
                    <input type="hidden" name="{{ $key }}" value="0">
                    <input type="checkbox" name="{{ $key }}" value="1" x-model="f.{{ $key }}">
                    <span><b>{{ $title }}</b><small>{{ $hint }}</small></span>
                </label>
            @endforeach
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>Mintable / Pausable / Freezable ให้อำนาจผู้สร้างสูง ผู้ถือบางคนอาจมองว่าเสี่ยง เลือกเท่าที่จำเป็น</p>
        </div>

        {{-- ขั้นที่ 3: ราคาและลิงก์ --}}
        <div class="nvm-form" x-ref="step3" x-show="step === 3" x-cloak>
            <div class="nvm-field">
                <label class="nvm-label" for="tkc-price">ราคาเริ่มต้น (TPIX)</label>
                <input id="tkc-price" class="nvm-input nvm-num @error('initial_price_tpix') is-bad @enderror" type="number" name="initial_price_tpix" x-model="f.initial_price_tpix" min="0" step="0.00000001" inputmode="decimal" placeholder="เช่น 0.01">
                @error('initial_price_tpix')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            @foreach([
                ['website', 'เว็บไซต์', 'url', 'https://yourtoken.com', 255],
                ['whitepaper_url', 'Whitepaper', 'url', 'https://yourtoken.com/whitepaper.pdf', 500],
                ['github', 'GitHub', 'url', 'https://github.com/yourproject', 255],
                ['telegram', 'Telegram', 'url', 'https://t.me/yourgroup', 255],
                ['twitter', 'X (Twitter)', 'text', '@yourtoken', 255],
            ] as [$key, $label, $type, $placeholder, $max])
                <div class="nvm-field">
                    <label class="nvm-label" for="tkc-{{ $key }}">{{ $label }}</label>
                    <input id="tkc-{{ $key }}" class="nvm-input @error($key) is-bad @enderror" type="{{ $type }}" name="{{ $key }}" value="{{ old($key) }}" maxlength="{{ $max }}" placeholder="{{ $placeholder }}" @if($key === 'website') x-model="f.website" @endif>
                    @error($key)<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            @endforeach
        </div>

        {{-- ขั้นที่ 4: ตรวจสอบ --}}
        <div class="nvm-form" x-show="step === 4" x-cloak>
            <dl class="nvm-kv">
                <dt>ชื่อเหรียญ</dt><dd x-text="f.name || '-'"></dd>
                <dt>สัญลักษณ์</dt><dd x-text="(f.symbol || '-').toUpperCase()"></dd>
                <dt>จำนวนทั้งหมด</dt><dd class="nvm-num" x-text="f.total_supply ? Number(f.total_supply).toLocaleString('th-TH') : '-'"></dd>
                <dt>ทศนิยม</dt><dd class="nvm-num" x-text="f.decimals"></dd>
                <dt>ราคาเริ่มต้น</dt><dd class="nvm-num" x-text="f.initial_price_tpix ? f.initial_price_tpix + ' TPIX' : '-'"></dd>
                <dt>คุณสมบัติ</dt><dd x-text="flagSummary()"></dd>
            </dl>
            <div class="nvm-status is-warn">
                <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>
                <span>หลังส่ง เหรียญจะอยู่ในสถานะร่าง<small>ทีมงานตรวจสอบก่อนนำขึ้นเครือข่าย จำนวนทั้งหมดและสัญลักษณ์แก้ไขภายหลังไม่ได้</small></span>
            </div>
            <label class="nvm-check">
                <input type="checkbox" x-model="agreed" required>
                <span>ฉันตรวจสอบข้อมูลแล้ว และเข้าใจว่าการสร้างเหรียญต้องผ่านการอนุมัติก่อน</span>
            </label>
        </div>

        <div class="nvm-filter__btns nvm-gap-t">
            <button type="button" class="nvm-btn nvm-btn--line" x-show="step > 1" x-cloak x-on:click="goTo(step - 1)">ก่อนหน้า</button>
            <button type="button" class="nvm-btn nvm-btn--gold" x-show="step < 4" x-on:click="goTo(step + 1)">ถัดไป<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></button>
            <button type="submit" class="nvm-btn nvm-btn--gold" x-show="step === 4" x-cloak x-bind:disabled="! agreed || submitting">
                <svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="submitting ? 'กำลังบันทึก...' : 'สร้างเหรียญ'">สร้างเหรียญ</span>
            </button>
        </div>
    </form>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-tkc-a-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-tkc-a-h">ขั้นตอนหลังส่ง</h2></div></div>
        <ol class="nvm-steps">
            <li>ระบบบันทึกเหรียญเป็นร่าง ดูได้ที่ "เหรียญที่ฉันสร้าง" ในตลาดเหรียญ</li>
            <li>ทีมงานตรวจสอบข้อมูลและแจ้งผลให้ทราบ</li>
            <li>เมื่ออนุมัติ เหรียญจะถูกนำขึ้น TPIX Blockchain และแสดงในตลาด</li>
        </ol>
        @if($gasCost !== null)
            <dl class="nvm-kv nvm-gap-t">
                <dt>ค่า Gas โดยประมาณ</dt><dd class="nvm-num">{{ rtrim(rtrim(number_format((float) $gasCost, 8), '0'), '.') }} TPIX</dd>
            </dl>
            <p class="nvm-help">ประเมินจากราคา Gas ปัจจุบันของเครือข่าย ยอดจริงอาจต่างเล็กน้อย</p>
        @endif
    </aside>
</div>

</div>

@push('scripts')
<script>
    /**
     * ฟอร์มสร้างเหรียญ 4 ขั้น — ตรวจช่องบังคับของแต่ละขั้นก่อนไปขั้นถัดไป
     * (ฟอร์มใช้ novalidate เพราะช่องในขั้นที่ซ่อนอยู่ทำให้เบราว์เซอร์บล็อกการส่งแบบเงียบ ๆ)
     */
    function nvmTokenForm(initial, startStep) {
        return {
            f: initial,
            step: startStep,
            agreed: false,
            submitting: false,
            // ตรวจช่องในขั้นที่ระบุ ถ้าไม่ผ่านให้เปิดขั้นนั้นแล้วโชว์ข้อความของเบราว์เซอร์
            stepValid(n) {
                const box = this.$refs['step' + n];
                if (! box) return true;
                const bad = Array.from(box.querySelectorAll('input, select, textarea')).find((el) => ! el.checkValidity());
                if (! bad) return true;
                this.step = n;
                this.$nextTick(() => bad.reportValidity());
                return false;
            },
            goTo(n) {
                if (n < 1 || n > 4) return;
                for (let i = 1; i < n; i++) {
                    if (i >= this.step && ! this.stepValid(i)) return;
                }
                this.step = n;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            flagSummary() {
                const names = { is_mintable: 'Mintable', is_burnable: 'Burnable', is_pausable: 'Pausable', is_freezable: 'Freezable' };
                const on = Object.keys(names).filter((k) => this.f[k]).map((k) => names[k]);
                return on.length ? on.join(' · ') : 'ไม่มี';
            },
            submit(e) {
                for (let i = 1; i <= 3; i++) {
                    if (! this.stepValid(i)) { e.preventDefault(); return; }
                }
                if (! this.agreed || this.submitting) { e.preventDefault(); return; }
                this.submitting = true;
            },
        };
    }
</script>
@endpush
@endsection
