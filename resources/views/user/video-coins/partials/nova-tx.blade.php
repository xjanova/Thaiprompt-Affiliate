{{--
 | แถวธุรกรรม Video Coins หนึ่งรายการ (ธีมโนวา) — ใช้ในหน้า Video Coins + ประวัติธุรกรรม
 | รับ $tx (VideoCoinTransaction) · $showDate=true → โชว์วันที่เต็มแทนเวลาแบบ "x นาทีที่แล้ว"
 | เครื่องหมาย +/- อ่านจาก amount จริง (รายการหักเก็บติดลบ · ได้รับ/คืนเหรียญเก็บบวก)
 | เดิมดูจากชื่อประเภทขึ้นต้น earned ทำให้การคืนเหรียญ (adjustment) แสดงเป็นรายการหัก
 --}}
@php
    $vcTypes = [
        'earned_video' => ['ได้รับจากวิดีโอ', 'down'],
        'earned_quest' => ['ได้รับจากภารกิจ', 'award'],
        'earned_level_up' => ['โบนัสเลื่อนเลเวล', 'up'],
        'earned_referral' => ['ได้รับจากแนะนำ', 'users'],
        'earned_bonus' => ['โบนัส', 'gift'],
        'spent_exchange' => ['แลกเป็นเงิน', 'swap'],
        'spent_shop' => ['ซื้อสินค้า', 'cart'],
        'adjustment' => ['ปรับยอด / คืนเหรียญ', 'history'],
    ];
    $vcIn = (float) $tx->amount >= 0;
    [$vcLabel, $vcIcon] = $vcTypes[$tx->type] ?? [(string) $tx->type, $vcIn ? 'down' : 'up'];
@endphp
<li class="nvm-tx">
    <span class="nvm-tx__ic {{ $vcIn ? 'is-in' : '' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $vcIcon }}"/></svg></span>
    <div class="nvm-tx__t">
        <b>{{ $vcLabel }}</b>
        @if($tx->description)
            <small>{{ $tx->description }}</small>
        @endif
        <small class="nvm-num">คงเหลือ {{ number_format((float) $tx->balance_after, 2) }} VC</small>
    </div>
    <div class="nvm-tx__r">
        <span class="nvm-tx__amt nvm-num {{ $vcIn ? 'is-in' : '' }}">{{ $vcIn ? '+' : '-' }}{{ number_format(abs((float) $tx->amount), 2) }}</span>
        @if(! empty($showDate))
            <time class="nvm-num" datetime="{{ $tx->created_at?->toIso8601String() }}">{{ $tx->created_at?->format('d/m/Y H:i') }}</time>
        @else
            <time datetime="{{ $tx->created_at?->toIso8601String() }}">{{ $tx->created_at?->locale('th')->diffForHumans() }}</time>
        @endif
    </div>
</li>
