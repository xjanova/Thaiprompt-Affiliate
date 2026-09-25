{{--
 | รายการบทสนทนากับลูกค้า (ธีม V4) — ตัวแปร: $conversations (paginator ของ Order พร้อม messages ล่าสุด 1 ข้อความ)
 --}}
@if($conversations->count() > 0)
    <div style="display:flex; flex-direction:column;">
        @foreach($conversations as $order)
            @php
                $latestMessage = $order->messages->first();
                $sellerItems = $order->items->where('seller_id', auth()->id());
                $statusColor = \App\Support\Seller\SellerUi::orderStatusColor($order->status);
                $unread = (bool) $order->has_unread_messages;
            @endphp
            <a href="{{ route('seller.orders.tracking', ['orderId' => $order->id, 'tab' => 'chat']) }}"
               style="display:flex; gap:12px; align-items:flex-start; padding:15px 18px; text-decoration:none; color:var(--ink); border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);
                      {{ $unread ? 'background:color-mix(in srgb, var(--accent1) 8%, transparent);' : '' }}">
                <span class="tp-tile" style="width:44px; height:44px; border-radius:14px; font-size:16px; font-weight:800; position:relative;">
                    {{ mb_substr($order->user->name ?? 'ล', 0, 1) }}
                    @if($unread)
                        <span style="position:absolute; top:-3px; right:-3px; width:12px; height:12px; border-radius:50%; background:{{ \App\Support\Seller\SellerUi::BAD }}; box-shadow:0 0 0 2px var(--card-bg);"></span>
                    @endif
                </span>
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px;">
                        <span style="font-weight:800;">{{ $order->user->name ?? 'ลูกค้า' }}</span>
                        <span class="tp-num" style="font-size:11.5px; color:var(--ink2);">#{{ $order->order_number }}</span>
                        @if($unread)
                            <span class="sv4-pill" style="{{ \App\Support\Seller\SellerUi::pill(\App\Support\Seller\SellerUi::BAD) }}">ใหม่</span>
                        @endif
                    </div>
                    @if($latestMessage)
                        <div style="font-size:12.5px; color:{{ $unread ? 'var(--ink)' : 'var(--ink2)' }}; margin-top:3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; {{ $unread ? 'font-weight:700;' : '' }}">
                            @if($latestMessage->sender_type === 'seller')
                                <span style="color:var(--ink2); font-weight:500;">คุณ:</span>
                            @elseif($latestMessage->is_system_message)
                                <span style="color:var(--ink2); font-weight:500;">ระบบ:</span>
                            @endif
                            {{ \Illuminate\Support\Str::limit($latestMessage->message, 80) }}
                        </div>
                    @endif
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; font-size:11px; color:var(--ink2);">
                        <span>📦 {{ $sellerItems->count() }} สินค้า</span>
                        <span class="sv4-pill" style="{{ \App\Support\Seller\SellerUi::pill($statusColor) }} padding:3px 8px;">{{ $order->status_label }}</span>
                    </div>
                </div>
                <div style="font-size:11px; color:var(--ink2); white-space:nowrap; text-align:right;">
                    {{ $latestMessage ? $latestMessage->created_at->diffForHumans() : $order->updated_at->diffForHumans() }}
                    <div style="font-size:16px; margin-top:6px; color:var(--deep1);">›</div>
                </div>
            </a>
        @endforeach
    </div>
    <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
        {{ $conversations->appends(request()->query())->links('vendor.pagination.tp-v4') }}
    </div>
@else
    <x-seller-v4.empty icon="💬" :title="$emptyTitle ?? 'ยังไม่มีข้อความ'" :text="$emptyText ?? 'เมื่อลูกค้าส่งข้อความในคำสั่งซื้อ จะแสดงที่นี่'" />
@endif
