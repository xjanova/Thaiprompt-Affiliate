@extends('layouts.user-nova')

@section('title', 'กระเป๋าเงิน')

@php
    // หน้ากระเป๋าเงิน — ธีมโนวาเต็ม (ภาพประกอบทอง/กรมท่าที่ public/images/nova/wallet/)
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
    [$balInt, $balDec] = explode('.', number_format((float) $wallet->balance, 2));
    $locked = $wallet->isLocked();
    $stats = is_array($statistics ?? null) ? $statistics : [];
    $cashback = $cashbackStats ?? [];
    $admin = $adminStats ?? [];
    // ช่องทางฝากเงิน: โชว์เฉพาะที่แอดมินเปิดไว้ (getAvailablePaymentMethods ส่งมาครบทุกช่องทางพร้อมธง enabled)
    $gateways = array_filter($availableGateways ?? [], fn ($g) => ! empty($g['enabled']));

    // เมนูด่วน: [ภาพ, ป้ายไทย, ป้ายอังกฤษ, route] — ซ่อนอันที่ route ไม่มีในระบบ
    $tiles = array_filter([
        ['images/nova/wallet/deposit.webp', 'ฝากเงิน', 'Deposit', 'user.wallet.deposit'],
        ['images/nova/wallet/withdraw.webp', 'ถอนเงิน', 'Withdraw', 'user.wallet.withdraw'],
        ['images/nova/wallet/transfer.webp', 'โอนเงิน', 'Transfer', 'user.wallet.transfer'],
        ['images/nova/deco/scroll.webp', 'ประวัติ', 'History', 'user.wallet.transactions'],
        ['images/nova/wallet/qr.webp', 'QR รับเงิน', 'My QR', 'user.wallet.qr-code'],
        ['images/nova/wallet/phone.webp', 'สแกนจ่าย', 'Scan & pay', 'user.wallet.qr-transfer'],
    ], fn ($t) => $nvR($t[3]));

    // การ์ดตัวเลข 4 ใบ (ยอดคงเหลืออยู่ในฮีโร่แล้ว)
    $kpis = [
        ['รายรับทั้งหมด', 'Total income', $baht($wallet->total_income), 'images/nova/wallet/deposit.webp', 'รวมทุกช่องทาง'],
        ['รายจ่ายทั้งหมด', 'Total spent', $baht($wallet->total_expense), 'images/nova/wallet/withdraw.webp', 'ถอน · โอน · ค่าธรรมเนียม'],
        ['Cashback ทั้งหมด', 'Cashback', $baht($cashback['total'] ?? 0), 'images/nova/deco/gift.webp', number_format((int) ($cashback['count'] ?? 0)).' ครั้ง'],
        ['ธุรกรรมทั้งหมด', 'Transactions', number_format((int) ($stats['transactions_count'] ?? 0)), 'images/nova/deco/scroll.webp', 'รายการ ตั้งแต่เปิดกระเป๋า'],
    ];

    // บัญชีรับเงิน: โชว์แค่ 4 ตัวท้าย (กันคนข้าง ๆ เห็นเลขบัญชีเต็ม — ดูเต็มได้ที่หน้าจัดการบัญชี)
    $maskNo = function ($no) {
        $no = preg_replace('/[^0-9A-Za-z]/', '', (string) $no);

        return $no === '' ? '' : '•••• '.substr($no, -4);
    };
@endphp

@section('content')

{{-- ── ฮีโร่: ยอดเงินคงเหลือ ─────────────────────────────── --}}
<section class="nvm-ph nvm-ph--wallet" aria-labelledby="nvm-wallet-h">
    <div class="nvm-ph__bg"></div>
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">My wallet · Thai Prompt</div>
            <h1 id="nvm-wallet-h">กระเป๋าเงินของฉัน</h1>
            <div class="nvm-bal">
                <small>ยอดเงินคงเหลือ</small>
                <b class="nvm-foil nvm-num">฿{{ $balInt }}<span>.{{ $balDec }}</span></b><em>{{ $wallet->currency }}</em>
            </div>
            <div class="nvm-pills">
                @if($locked)
                    <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>กระเป๋าถูกล็อก</span>
                @else
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>พร้อมใช้งาน</span>
                @endif
                @if(($cashback['this_month'] ?? 0) > 0)
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-gift"/></svg>Cashback เดือนนี้ {{ $baht($cashback['this_month']) }}</span>
                @endif
            </div>
            @if($wallet->wallet_address)
                <div class="nvm-copy">
                    <small class="nvm-latin">Wallet</small>
                    <code>{{ $wallet->wallet_address }}</code>
                    <button type="button" x-data @click="nvmCopy(@js($wallet->wallet_address), 'คัดลอก Wallet Address แล้ว')" aria-label="คัดลอก Wallet Address"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg></button>
                </div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="200" height="200">
    </div>
</section>

{{-- ── กระเป๋าถูกล็อก / หนี้ค้างชำระ (สำคัญสุด ขึ้นก่อน) ───── --}}
@if($locked)
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg></span>
        <div class="nvm-alert__txt">
            <b>กระเป๋าเงินของคุณถูกล็อก</b>
            <p>กรุณาติดต่อฝ่ายสนับสนุนเพื่อปลดล็อก@if($wallet->locked_until) · ล็อกจนถึง <span class="nvm-num">{{ $wallet->locked_until->format('d/m/Y H:i') }}</span>@endif</p>
        </div>
    </section>
@endif

@if(($debtSummary['has_active_debt'] ?? false) && $nvR('user.wallet.debts'))
    <a class="nvm-card nvm-alert nvm-alert--bad" href="{{ route('user.wallet.debts') }}">
        <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/scale.webp') }}" alt="" width="58" height="58">
        <div class="nvm-alert__txt">
            <b>คุณมีหนี้ค้างชำระ</b>
            <p>{{ number_format((int) ($debtSummary['debt_count'] ?? 0)) }} รายการ · ระบบจะหักจากรายได้อัตโนมัติ 50%</p>
        </div>
        <div class="nvm-alert__side">
            <b class="nvm-num">{{ $baht($debtSummary['total_debt'] ?? 0) }}</b>
            <small>ดูรายละเอียด →</small>
        </div>
    </a>
@endif

{{-- ── เมนูด่วน ─────────────────────────────────────────── --}}
@if(count($tiles))
    <nav class="nvm-tiles" aria-label="เมนูกระเป๋าเงิน">
        @foreach($tiles as [$img, $label, $en, $route])
            <a class="nvm-card nvm-tile" href="{{ route($route) }}">
                <img src="{{ asset($img) }}" alt="" width="76" height="76" loading="lazy">
                <b>{{ $label }}</b>
                <small class="nvm-latin">{{ $en }}</small>
            </a>
        @endforeach
    </nav>
@endif

{{-- ── การ์ดตัวเลข ──────────────────────────────────────── --}}
<section class="nvm-kpis" aria-label="สรุปกระเป๋าเงิน">
    @foreach($kpis as [$label, $en, $value, $img, $foot])
        <div class="nvm-card nvm-kpi nvm-rv">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
            <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
        </div>
    @endforeach
</section>

<div class="nvm-split">
    {{-- ── รายการล่าสุด ─────────────────────────────────── --}}
    <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-tx-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Recent activity</small><h2 id="nvm-tx-h">รายการธุรกรรมล่าสุด</h2></div>
            @if($nvR('user.wallet.transactions'))
                <a href="{{ route('user.wallet.transactions') }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            @endif
        </div>
        @if($recentTransactions->isNotEmpty())
            <ul class="nvm-txs">
                @foreach($recentTransactions as $tx)
                    @include('user.wallet.partials.nova-tx', ['tx' => $tx])
                @endforeach
            </ul>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีรายการธุรกรรม</b>
                เริ่มฝากเงินเข้ากระเป๋า แล้วรายการจะแสดงที่นี่
                @if($nvR('user.wallet.deposit'))
                    <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.wallet.deposit') }}">ฝากเงินเลย</a></div>
                @endif
            </div>
        @endif
    </section>

    <div class="nvm-stack">
        {{-- สรุปตามช่วงเวลา — เลือกคีย์เอง ห้ามวน $statistics ทั้งก้อน (last_transaction เคยพา ip/user_agent มาโผล่หน้าจอ) --}}
        <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-sum-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Summary</small><h2 id="nvm-sum-h">สรุปการเงิน</h2></div></div>
            <div class="nvm-minis">
                <div class="nvm-mini"><small>รายรับเดือนนี้</small><b class="nvm-num is-in">{{ $baht($stats['this_month_income'] ?? 0) }}</b></div>
                <div class="nvm-mini"><small>รายจ่ายเดือนนี้</small><b class="nvm-num is-out">{{ $baht($stats['this_month_expense'] ?? 0) }}</b></div>
                <div class="nvm-mini"><small>รายรับ 30 วัน</small><b class="nvm-num is-in">{{ $baht($stats['last_30_days_income'] ?? 0) }}</b></div>
                <div class="nvm-mini"><small>รายจ่าย 30 วัน</small><b class="nvm-num is-out">{{ $baht($stats['last_30_days_expense'] ?? 0) }}</b></div>
                @if(! empty($stats['last_transaction']))
                    @php $lastTx = $stats['last_transaction']; @endphp
                    <div class="nvm-mini nvm-mini--wide">
                        <small>ธุรกรรมล่าสุด · <span class="nvm-num">{{ $lastTx['created_at'] ?? '' }}</span></small>
                        <b class="nvm-num">{{ $lastTx['formatted_amount'] ?? '—' }}</b>
                        <small>{{ $lastTx['type_label'] ?? '—' }} · {{ $lastTx['status_label'] ?? '—' }}</small>
                    </div>
                @endif
            </div>
        </section>

        @if(($cashback['total'] ?? 0) > 0)
            <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-cb-h">
                <div class="nvm-sec-h">
                    <div><small class="nvm-latin">Cashback</small><h2 id="nvm-cb-h">เงินคืนของคุณ</h2></div>
                    <span class="nvm-chip nvm-chip--gold">{{ number_format((int) ($cashback['count'] ?? 0)) }} ครั้ง</span>
                </div>
                <div class="nvm-minis">
                    <div class="nvm-mini"><small>เดือนนี้</small><b class="nvm-num is-in">{{ $baht($cashback['this_month'] ?? 0) }}</b></div>
                    <div class="nvm-mini"><small>30 วันล่าสุด</small><b class="nvm-num is-in">{{ $baht($cashback['last_30_days'] ?? 0) }}</b></div>
                    <div class="nvm-mini nvm-mini--wide"><small>เฉลี่ยต่อครั้ง</small><b class="nvm-num">{{ ($cashback['count'] ?? 0) > 0 ? $baht($cashback['total'] / $cashback['count']) : $baht(0) }}</b></div>
                </div>
            </section>
        @endif

        @if(($admin['total'] ?? 0) > 0)
            <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-adm-h">
                <div class="nvm-sec-h">
                    <div><small class="nvm-latin">Credits</small><h2 id="nvm-adm-h">เงินจากแอดมิน/ระบบ</h2></div>
                    <span class="nvm-chip">{{ number_format((int) ($admin['count'] ?? 0)) }} ครั้ง</span>
                </div>
                <div class="nvm-minis">
                    <div class="nvm-mini nvm-mini--wide"><small>รวมทั้งหมด</small><b class="nvm-num">{{ $baht($admin['total']) }}</b></div>
                    <div class="nvm-mini"><small>เดือนนี้</small><b class="nvm-num">{{ $baht($admin['this_month'] ?? 0) }}</b></div>
                    <div class="nvm-mini"><small>30 วันล่าสุด</small><b class="nvm-num">{{ $baht($admin['last_30_days'] ?? 0) }}</b></div>
                </div>
            </section>
        @endif

        @if(count($gateways))
            <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-gw-h">
                <div class="nvm-sec-h">
                    <div><small class="nvm-latin">Top up</small><h2 id="nvm-gw-h">ช่องทางการฝากเงิน</h2></div>
                    @if($nvR('user.wallet.deposit'))
                        <a href="{{ route('user.wallet.deposit') }}">ฝากเงิน<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
                    @endif
                </div>
                <div class="nvm-gws">
                    @foreach($gateways as $gateway)
                        <span class="nvm-gw"><span aria-hidden="true">{{ $gateway['icon'] ?? '💰' }}</span>{{ $gateway['name'] ?? 'Gateway' }}</span>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

{{-- ── บัญชีรับเงิน (สำหรับถอน) ──────────────────────────── --}}
<section class="nvm-rv" aria-labelledby="nvm-pm-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Payout accounts</small><h2 id="nvm-pm-h">ช่องทางรับเงิน</h2></div>
        @if($nvR('user.wallet.payment-methods'))
            <a href="{{ route('user.wallet.payment-methods') }}">จัดการ<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
        @endif
    </div>
    @if($paymentMethods->isNotEmpty())
        <div class="nvm-pms">
            @foreach($paymentMethods as $method)
                @php
                    [$pmIcon, $pmType] = match ($method->type) {
                        'promptpay' => ['qr', 'PromptPay'],
                        'bank_transfer' => ['bank', 'Bank account'],
                        'paypal' => ['send', 'PayPal'],
                        default => ['cash', 'Account'],
                    };
                @endphp
                <div class="nvm-pm">
                    <div class="nvm-pm__top">
                        <span class="nvm-pm__type nvm-latin"><svg class="nvm-ico"><use href="#nvm-i-{{ $pmIcon }}"/></svg>{{ $pmType }}</span>
                        @if($method->is_default)<span class="nvm-pill nvm-pill--gold">ค่าเริ่มต้น</span>@endif
                    </div>
                    <div class="nvm-pm__name">{{ $method->name }}</div>
                    @if($method->type === 'bank_transfer')
                        <div class="nvm-pm__sub">{{ $method->bank_name }} · {{ $method->account_name }}</div>
                        <div class="nvm-pm__no nvm-num">{{ $maskNo($method->account_number) }}</div>
                    @elseif($method->type === 'promptpay')
                        <div class="nvm-pm__sub">{{ $method->account_name }}</div>
                        <div class="nvm-pm__no nvm-num">{{ $maskNo($method->account_number) }}</div>
                    @elseif($method->type === 'paypal')
                        <div class="nvm-pm__sub">{{ $method->paypal_email }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="nvm-card nvm-alert nvm-alert--info">
            <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/bank.webp') }}" alt="" width="58" height="58" loading="lazy">
            <div class="nvm-alert__txt">
                <b>ยังไม่มีบัญชีรับเงิน</b>
                <p>เพิ่มบัญชีธนาคารหรือพร้อมเพย์ไว้ก่อน เพื่อถอนเงินได้ทันทีเมื่อต้องการ</p>
            </div>
            @if($nvR('user.wallet.payment-methods'))
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.wallet.payment-methods') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มบัญชี</a>
            @endif
        </div>
    @endif
</section>
@endsection
