{{--
 | 🪙 หน้าจ่ายด้วยเหรียญ TPIX (เชน 4289) — ธีม V4 (frontend-v4)
 | ข้อมูลจาก TpixPayController@show: $payment, $data (TpixPaymentPresenter::present), $order|null, $siblings, $canRequote
 |
 | ช่องทางจ่าย 3 แบบ (ทุกแบบต้องโอน "ยอดตรงทุกหลัก" — เลขท้ายเฉพาะใบใช้ยืนยันอัตโนมัติ):
 |   1) ปุ่มจ่ายด้วยกระเป๋าในเบราว์เซอร์ (MetaMask ฯลฯ — EIP-1193 ส่งยอด wei ตรงตัวให้เอง) → ส่งเลขธุรกรรมให้อัตโนมัติ
 |   2) สแกน QR (ลิงก์ EIP-681) จากกระเป๋ามือถือ / คัดลอกที่อยู่+ยอดไปโอนเอง → ระบบสแกนเจอเองภายใน ~1 นาที
 |   3) วางเลขธุรกรรม (tx hash) เพื่อยืนยันทันที
 | หน้านี้โพลสถานะทุก 5 วิ (เซิร์ฟเวอร์ตรวจบนเชนให้) — ห้ามรีโหลดเพื่อออกยอดใหม่ ยอดเดิมยังจ่ายได้จนหมดเวลา
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ชำระด้วยเหรียญ TPIX'.($order ? ' #'.$order->order_number : ''))

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="orders" />

<main style="flex:1; padding-bottom:40px;"
      x-data="tpixPay({{ \Illuminate\Support\Js::from([
          'data' => $data,
          'orderUrl' => $order ? route('orders.show', $order->id) : null,
          'successUrl' => $order ? route('checkout.success', $order->id) : null,
          'walletUrl' => \Illuminate\Support\Facades\Route::has('user.wallet.index') ? route('user.wallet.index') : null,
      ]) }})">

    <section class="sf-wrap" style="padding-top:22px; max-width:760px; text-align:center;">
        <span class="tp-tile" style="width:64px; height:64px; border-radius:20px; margin:0 auto; display:grid; place-items:center;">
            <svg viewBox="0 0 24 24" width="34" height="34" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#f5b72e"/><circle cx="12" cy="12" r="7.2" fill="none" stroke="#a86b05" stroke-width="1.4"/><path d="M8.6 8.4h6.8v1.9h-2.4V16h-2V10.3H8.6z" fill="#7c4a03"/></svg>
        </span>
        <h1 class="sf-h1" style="margin-top:12px;">ชำระด้วยเหรียญ TPIX</h1>
        <p class="tp-muted" style="margin:6px 0 0;">
            @if($order)
                คำสั่งซื้อ #{{ $order->order_number }}
            @else
                เติมเงินเข้ากระเป๋า
            @endif
            · <span class="tp-num">{{ $payment->reference }}</span>
        </p>
    </section>

    <section class="sf-wrap sf-stack" style="padding-top:18px; max-width:760px;">

        {{-- ===== สถานะ ===== --}}
        <template x-if="d.status === 'paid'">
            <div class="tp-card sf-note sf-note-ok" style="text-align:center;">
                <div style="font-size:18px; font-weight:800;"><i class="fas fa-circle-check"></i> ได้รับเหรียญแล้ว ชำระเงินสำเร็จ</div>
                <div class="tp-muted" style="font-size:12.5px; margin-top:4px;" x-show="d.tx_hash">
                    ธุรกรรม <a :href="d.explorer_tx_url" target="_blank" rel="noopener" class="tp-num" style="word-break:break-all;" x-text="d.tx_hash"></a>
                </div>
                <div style="margin-top:12px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                    <a x-show="cfg.successUrl" :href="cfg.successUrl" class="sf-btn3d">ดูคำสั่งซื้อที่สำเร็จ</a>
                    <a x-show="!cfg.successUrl && cfg.walletUrl" :href="cfg.walletUrl" class="sf-btn3d">ไปที่กระเป๋าเงิน</a>
                </div>
            </div>
        </template>
        <template x-if="d.status === 'credited_wallet'">
            <div class="tp-card sf-note sf-note-info">
                <div style="font-weight:800;"><i class="fas fa-wallet"></i> ได้รับเหรียญแล้ว — เติมมูลค่าเข้ากระเป๋าเงินของคุณ</div>
                <div style="font-size:13px; margin-top:4px;" x-text="note || 'คำสั่งซื้อนี้ปิดไปก่อนเหรียญมาถึง (หรือโอนหลังหมดเวลา) ระบบจึงเติมมูลค่าเป็นเงินบาทเข้ากระเป๋าแทน เงินไม่หาย ใช้ซื้อสินค้าต่อได้ทันที'"></div>
                <a x-show="cfg.walletUrl" :href="cfg.walletUrl" class="sf-btn3d is-soft" style="margin-top:10px;">ดูกระเป๋าเงิน</a>
            </div>
        </template>
        <template x-if="d.status === 'confirming'">
            <div class="tp-card sf-note sf-note-info">
                <div style="font-weight:800;"><i class="fas fa-link fa-beat-fade"></i> พบธุรกรรมแล้ว กำลังยืนยันบนเชน
                    <span class="tp-num" x-text="'(' + Math.min(d.confirmations, d.confirmations_required) + '/' + d.confirmations_required + ')'"></span>
                </div>
                <div class="tp-muted" style="font-size:12.5px; margin-top:4px;">เชน TPIX ออกบล็อกทุก ~2 วินาที รอไม่เกินครึ่งนาที</div>
            </div>
        </template>
        <template x-if="d.status === 'late'">
            <div class="tp-card sf-note sf-note-warn">ได้รับเหรียญหลังหมดเวลา — ระบบกำลังคิดมูลค่าตามราคาปัจจุบันและเติมเข้ากระเป๋าเงินของคุณ</div>
        </template>
        <template x-if="d.status === 'expired' || d.status === 'cancelled'">
            <div class="tp-card sf-note sf-note-warn">
                <div style="font-weight:800;"><i class="fas fa-clock"></i> <span x-text="d.status === 'cancelled' ? 'รายการนี้ถูกยกเลิก' : 'หมดเวลาชำระของยอดนี้แล้ว'"></span></div>
                <div style="font-size:12.5px; margin-top:4px;">ถ้าโอนไปแล้วก่อนหมดเวลา ระบบยังตรวจพบและยืนยันให้ · ถ้าโอนหลังหมดเวลา ระบบจะเติมมูลค่าเข้ากระเป๋าเงินแทน (เงินไม่หาย)</div>
                @if($canRequote)
                    <form method="POST" action="{{ route('tpix-pay.order', $order->id) }}" style="margin-top:10px;">
                        @csrf
                        <button type="submit" class="sf-btn3d">ออกยอดใหม่ตามราคาปัจจุบัน</button>
                    </form>
                @endif
            </div>
        </template>

        {{-- ===== ยอดที่ต้องโอน + การคำนวณ ===== --}}
        <div class="tp-card" style="text-align:center; padding:clamp(18px, 4vw, 30px);" x-show="!d.is_settled">
            <div class="tp-muted" style="font-size:12.5px;">ยอดที่ต้องโอน (ตรงทุกหลัก)</div>
            <div style="display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap; margin-top:2px;">
                <span class="tp-num" style="font-size:clamp(26px, 6vw, 38px); font-weight:800; color:var(--deep1); word-break:break-all;" x-text="d.amount_tpix_exact + ' TPIX'"></span>
                <button type="button" class="tp-btn tp-btn-sm" @click="copy(d.amount_tpix_exact, 'คัดลอกยอดแล้ว')"><i class="fas fa-copy"></i> คัดลอกยอด</button>
            </div>

            <div style="margin-top:14px; padding:12px 14px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm); text-align:left;">
                <div class="sf-row"><span>มูลค่าสินค้า</span><strong class="tp-num">฿{{ number_format((float) $payment->amount_thb, 2) }}</strong></div>
                <div class="sf-row" style="margin-top:6px;"><span>ราคา TPIX ที่ใช้คิด</span><strong class="tp-num">1 TPIX = {{ $data['rate_display'] }}</strong></div>
                @if($data['price_usd'] && $data['usd_thb'])
                    <div class="sf-row" style="margin-top:6px;"><span>ที่มาของราคา</span>
                        <strong class="tp-num" style="text-align:right;">${{ \App\Services\TpixPay\TpixAmount::display($data['price_usd'], 6, 4) }} × ฿{{ \App\Services\TpixPay\TpixAmount::display($data['usd_thb'], 4, 2) }}/USD</strong>
                    </div>
                @endif
                <div class="sf-row" style="margin-top:6px;"><span>คำนวณ</span>
                    <strong class="tp-num" style="text-align:right;">
                        @if($data['fixed_price_lines'] > 0)
                            รวมราคาเหรียญคงที่ของร้าน + ส่วนที่เหลือ ÷ {{ $data['rate_display'] }}
                        @else
                            ฿{{ number_format((float) $payment->amount_thb, 2) }} ÷ {{ $data['rate_display'] }}
                        @endif
                    </strong>
                </div>
                <div class="sf-row" style="margin-top:6px;"><span>เลขท้ายยืนยันอัตโนมัติ</span><strong class="tp-num">ทศนิยมตำแหน่งที่ 5–8</strong></div>
                <div class="tp-muted" style="font-size:11.5px; margin-top:8px; line-height:1.6;">
                    ราคาจริงจากพูล TPIX DEX (WTPIX/USDT) บนเชน TPIX {{ $data['chain_id'] }} คูณอัตราแลกเปลี่ยน USD/THB
                    @if($data['rate_basis'] === 'twap')
                        · ใช้ <b>ราคาเฉลี่ย 30 นาที</b> ซึ่งต่ำกว่าราคาขณะนี้ (กันราคาพุ่งชั่วคราว)
                    @endif
                </div>
            </div>

            <div x-show="left" x-cloak class="tp-muted" style="margin-top:12px; font-size:12.5px;">
                ยอดนี้ใช้ได้อีก <span class="tp-num" style="font-weight:800; color:var(--deep2); font-size:15px;" x-text="left"></span>
            </div>
        </div>

        {{-- ===== 1) จ่ายด้วยกระเป๋าในเบราว์เซอร์ ===== --}}
        <div class="tp-card sf-stack" style="gap:12px;" x-show="!d.is_settled && d.status !== 'cancelled'">
            <div class="tp-section-h"><i class="fas fa-wallet" style="color:var(--deep1);"></i> จ่ายด้วยกระเป๋าคริปโต</div>
            <button type="button" class="sf-btn3d is-block" @click="payWithWallet()" :disabled="sending">
                <i class="fas" :class="sending ? 'fa-spinner fa-spin' : 'fa-bolt'"></i>
                <span x-text="sending ? 'กำลังรอยืนยันในกระเป๋า…' : (hasWallet ? 'จ่ายด้วย MetaMask / กระเป๋าในเบราว์เซอร์' : 'เปิดหน้านี้ในแอป MetaMask')"></span>
            </button>
            <p class="tp-muted" style="margin:0; font-size:12px;">ระบบจะเพิ่มเครือข่าย TPIX Chain ({{ $data['chain_id'] }}) ให้อัตโนมัติ และกรอกยอดตรงทุกหลักให้ · ค่าแก๊สบนเชน TPIX = 0</p>
        </div>

        {{-- ===== 2) สแกน QR / โอนเอง ===== --}}
        <div class="tp-card" x-show="!d.is_settled && d.status !== 'cancelled'">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-qrcode" style="color:var(--deep1);"></i> สแกน QR หรือโอนจากกระเป๋าใดก็ได้</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; align-items:center;">
                <div style="text-align:center;">
                    <div style="display:inline-block; padding:12px; border-radius:20px; background:#fff; box-shadow:var(--card-shadow);">
                        <img src="{{ $data['qr_url'] }}" alt="QR โอนเหรียญ TPIX {{ $data['amount_tpix_exact'] }} TPIX" style="width:min(220px, 60vw); height:auto; display:block;">
                    </div>
                    <div class="tp-muted" style="font-size:11.5px; margin-top:6px;">QR มีที่อยู่ + ยอด + เชน 4289 ในตัว</div>
                </div>
                <div class="sf-stack" style="gap:10px;">
                    <div>
                        <div class="tp-muted" style="font-size:12px;">กระเป๋ารับเงิน (TPIX Chain)</div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <strong class="tp-num" style="font-size:13px; word-break:break-all;">{{ $data['receive_address'] }}</strong>
                            <button type="button" class="tp-btn tp-btn-sm" @click="copy(d.receive_address, 'คัดลอกที่อยู่แล้ว')" aria-label="คัดลอกที่อยู่กระเป๋า"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="sf-row"><span>เครือข่าย</span><strong>{{ $data['chain_name'] }} · Chain ID {{ $data['chain_id'] }}</strong></div>
                    <div class="sf-row"><span>RPC</span><strong class="tp-num" style="font-size:12px;">{{ $data['rpc_url'] }}</strong></div>
                    <div class="sf-note sf-note-warn" style="font-size:12px;"><i class="fas fa-triangle-exclamation"></i> โอนเหรียญ <b>TPIX บนเชน TPIX</b> เท่านั้น และต้องตรงทุกหลัก — ยอดไม่ตรงระบบจะยืนยันอัตโนมัติไม่ได้</div>
                </div>
            </div>
        </div>

        {{-- ===== 3) วางเลขธุรกรรม ===== --}}
        <div class="tp-card sf-stack" style="gap:10px;" x-show="!d.is_settled && d.status !== 'cancelled'">
            <div class="tp-section-h"><i class="fas fa-receipt" style="color:var(--deep1);"></i> โอนแล้ว? วางเลขธุรกรรมเพื่อยืนยันทันที</div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <input type="text" x-model.trim="txInput" inputmode="text" autocomplete="off" spellcheck="false"
                       placeholder="0x… (Transaction Hash 66 ตัวอักษร)" maxlength="80"
                       class="tp-input" style="flex:1; min-width:220px; min-height:46px; padding:0 14px; border-radius:14px; border:1px solid var(--line, rgba(0,0,0,.12)); background:var(--surf); color:var(--ink); font-family:ui-monospace, monospace; font-size:13px;">
                <button type="button" class="sf-btn3d is-alt" @click="submit(txInput)" :disabled="submitting || !txInput">
                    <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-check'"></i> ยืนยัน
                </button>
            </div>
            <p class="tp-muted" style="margin:0; font-size:12px;">ไม่วางก็ได้ — ระบบสแกนการโอนเข้ากระเป๋ารับเงินทุกนาทีและยืนยันให้เอง</p>
        </div>

        <template x-if="msg">
            <div class="sf-note" :class="msgType === 'error' ? 'sf-note-err' : (msgType === 'ok' ? 'sf-note-ok' : 'sf-note-info')" x-text="msg"></div>
        </template>

        <div class="tp-card" style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;" x-show="!d.is_settled && d.status !== 'cancelled'">
            <span class="tp-tile" style="width:46px; height:46px; font-size:18px;"><i class="fas fa-rotate" :class="checking && 'fa-spin'"></i></span>
            <div style="flex:1; min-width:200px;">
                <div style="font-weight:800; color:var(--ink);">ตรวจสถานะบนเชนอัตโนมัติ</div>
                <div class="tp-muted" style="font-size:12.5px;">เมื่อเหรียญเข้า หน้านี้จะเปลี่ยนเป็น "ชำระสำเร็จ" เอง ไม่ต้องรีเฟรช</div>
            </div>
        </div>

        @if($siblings->isNotEmpty())
            <div class="tp-card sf-stack" style="gap:10px;">
                <div class="tp-section-h"><i class="fas fa-store" style="color:var(--deep1);"></i> คำสั่งซื้ออื่นจากการสั่งครั้งนี้</div>
                <p class="tp-muted" style="margin:0; font-size:12.5px;">สินค้ามาจากหลายร้าน ระบบแยกเป็นคำสั่งซื้อละร้าน (ยอดเหรียญแยกกัน) — ชำระให้ครบทุกรายการ</p>
                @foreach($siblings as $sib)
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px 12px; border-radius:14px; background:var(--surf); box-shadow:var(--raise);">
                        <span style="flex:1; min-width:160px;"><strong class="tp-num">#{{ $sib->order_number }}</strong> · ฿{{ number_format((float) $sib->total_amount, 2) }}</span>
                        @if($sib->payment_status === 'paid')
                            <span class="tp-pill tp-pill-soft"><i class="fas fa-check"></i> ชำระแล้ว</span>
                        @elseif($sib->tpix_reference)
                            <a href="{{ route('tpix-pay.show', $sib->tpix_reference) }}" class="tp-btn tp-btn-sm tp-btn-primary" style="text-decoration:none;">จ่าย TPIX รายการนี้</a>
                        @else
                            <a href="{{ route('orders.show', $sib->id) }}" class="tp-btn tp-btn-sm" style="text-decoration:none;">ดูคำสั่งซื้อ</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <details class="tp-card" style="font-size:13px;">
            <summary style="cursor:pointer; font-weight:700;">วิธีเพิ่มเครือข่าย TPIX Chain ในกระเป๋าเอง</summary>
            <div class="sf-stack" style="gap:6px; margin-top:10px;">
                <div class="sf-row"><span>Network name</span><strong>{{ $data['chain_name'] }}</strong></div>
                <div class="sf-row"><span>RPC URL</span><strong class="tp-num">{{ $data['rpc_url'] }}</strong></div>
                <div class="sf-row"><span>Chain ID</span><strong class="tp-num">{{ $data['chain_id'] }}</strong></div>
                <div class="sf-row"><span>Currency symbol</span><strong>TPIX</strong></div>
                <div class="sf-row"><span>Block explorer</span><strong class="tp-num">{{ $data['explorer_url'] }}</strong></div>
            </div>
        </details>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
            @if($order)
                <a href="{{ route('orders.show', $order->id) }}" class="sf-btn3d is-soft">ดูคำสั่งซื้อ</a>
            @endif
            <a href="{{ \Illuminate\Support\Facades\Route::has('storefront.index') ? route('storefront.index') : url('/') }}" class="sf-btn3d is-soft">กลับไปร้านค้า</a>
        </div>
    </section>
</main>
<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * หน้าจ่าย TPIX: โพลสถานะทุก 5 วิ (เซิร์ฟเวอร์ตรวจเชนให้) · จ่ายผ่านกระเป๋าในเบราว์เซอร์ (EIP-1193) · ส่งเลขธุรกรรม
     */
    function tpixPay(cfg) {
        return {
            cfg: cfg,
            d: cfg.data,
            left: '',
            note: '',
            msg: '',
            msgType: 'info',
            txInput: '',
            sending: false,
            submitting: false,
            checking: false,
            hasWallet: typeof window.ethereum !== 'undefined',
            timer: null,
            init() {
                this.schedule();
                const tick = () => {
                    if (!this.d.expires_at) { this.left = ''; return; }
                    const ms = Math.max(0, new Date(this.d.expires_at).getTime() - Date.now());
                    const m = Math.floor(ms / 60000);
                    const s = Math.floor(ms % 60000 / 1000);
                    this.left = ms > 0 && !this.d.is_settled ? (String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0')) : '';
                };
                tick();
                setInterval(tick, 1000);
            },
            schedule() {
                clearTimeout(this.timer);
                if (this.d.is_settled || this.d.status === 'cancelled') { return; }
                // หมดเวลาแล้วยังตรวจต่อ (เงินที่โอนก่อนหมดเวลา) แต่ช้าลง
                const wait = ['expired', 'late'].includes(this.d.status) ? 15000 : 5000;
                this.timer = setTimeout(() => this.poll(), wait);
            },
            async poll() {
                if (this.checking) { return; }
                this.checking = true;
                try {
                    const res = await fetch(this.d.status_url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (res.ok) {
                        const json = await res.json();
                        if (json && json.data) { this.d = json.data; }
                    }
                } catch (e) {
                    // เครือข่ายสะดุด — รอบถัดไปลองใหม่
                } finally {
                    this.checking = false;
                    this.schedule();
                }
            },
            async payWithWallet() {
                if (this.sending) { return; }
                const eth = window.ethereum;
                if (!eth) {
                    // มือถือที่ไม่มีกระเป๋าในเบราว์เซอร์ → เปิดหน้านี้ในเบราว์เซอร์ของแอป MetaMask
                    window.location.href = 'https://metamask.app.link/dapp/' + window.location.host + window.location.pathname;
                    return;
                }
                this.sending = true;
                this.flash('', 'info');
                try {
                    const accounts = await eth.request({ method: 'eth_requestAccounts' });
                    if (!accounts || !accounts.length) { throw new Error('ไม่ได้เชื่อมต่อกระเป๋า'); }
                    try {
                        await eth.request({ method: 'wallet_switchEthereumChain', params: [{ chainId: this.d.chain_id_hex }] });
                    } catch (err) {
                        const code = err && (err.code || (err.data && err.data.originalError && err.data.originalError.code));
                        if (code !== 4902) { throw err; }
                        await eth.request({
                            method: 'wallet_addEthereumChain',
                            params: [{
                                chainId: this.d.chain_id_hex,
                                chainName: this.d.chain_name,
                                nativeCurrency: { name: 'TPIX', symbol: 'TPIX', decimals: 18 },
                                rpcUrls: [this.d.rpc_url],
                                blockExplorerUrls: [this.d.explorer_url],
                            }],
                        });
                    }
                    const hash = await eth.request({
                        method: 'eth_sendTransaction',
                        params: [{ from: accounts[0], to: this.d.receive_address, value: this.d.amount_wei_hex }],
                    });
                    this.flash('ส่งเหรียญแล้ว กำลังยืนยันบนเชน…', 'info');
                    await this.submit(hash);
                } catch (err) {
                    const code = err && err.code;
                    this.flash(code === 4001 ? 'ยกเลิกการโอนในกระเป๋าแล้ว' : 'โอนผ่านกระเป๋าไม่สำเร็จ: ' + ((err && err.message) || 'ไม่ทราบสาเหตุ'), 'error');
                } finally {
                    this.sending = false;
                }
            },
            async submit(hash) {
                hash = String(hash || '').trim();
                if (!/^0x[0-9a-fA-F]{64}$/.test(hash)) {
                    this.flash('รูปแบบเลขธุรกรรมไม่ถูกต้อง (ต้องขึ้นต้น 0x ตามด้วยตัวอักษร 64 ตัว)', 'error');
                    return;
                }
                if (this.submitting) { return; }
                this.submitting = true;
                try {
                    const res = await fetch(this.d.submit_url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ tx_hash: hash }),
                    });
                    const json = await res.json().catch(() => ({}));
                    if (json && json.data) { this.d = json.data; }
                    if (json.result === 'credited_wallet') { this.note = json.message || ''; }
                    this.flash(json.message || (res.ok ? 'รับเลขธุรกรรมแล้ว' : 'ยืนยันไม่สำเร็จ'), res.ok ? (this.d.is_settled ? 'ok' : 'info') : 'error');
                    if (res.ok) { this.txInput = ''; }
                } catch (e) {
                    this.flash('ส่งเลขธุรกรรมไม่สำเร็จ กรุณาลองใหม่ (ระบบยังสแกนหาการโอนให้อัตโนมัติ)', 'error');
                } finally {
                    this.submitting = false;
                    this.schedule();
                }
            },
            flash(text, type) {
                this.msg = text;
                this.msgType = type;
            },
            copy(text, done) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(String(text)).then(() => {
                        if (window.tpShop && window.tpShop.notify) { window.tpShop.notify(done || 'คัดลอกแล้ว', 'success'); }
                    }).catch(() => {});
                }
            },
        };
    }
</script>
@endpush
