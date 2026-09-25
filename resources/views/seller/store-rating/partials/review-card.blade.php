{{--
 | การ์ดรีวิวร้าน 1 รายการ + ฟอร์มตอบกลับ (POST seller.store-rating.respond ช่อง response)
 | ตัวแปร: $rating (StoreRating), $showLink (bool, ค่าเริ่มต้น true)
 --}}
@php
    $showLink = $showLink ?? true;
    $stars = str_repeat('★', (int) $rating->rating).str_repeat('☆', max(0, 5 - (int) $rating->rating));
    $ratingTone = (int) $rating->rating >= 4 ? 'ok' : ((int) $rating->rating === 3 ? 'warn' : 'bad');
@endphp

<div class="tp-card" style="display:flex; flex-direction:column; gap:10px;" x-data="{ reply: false }">
    <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
        <div style="display:flex; gap:10px; align-items:center; min-width:0;">
            <span class="tp-tile" style="width:40px; height:40px; font-size:16px; border-radius:50%;" aria-hidden="true">{{ mb_substr($rating->reviewer_name ?? 'ล', 0, 1) }}</span>
            <div style="min-width:0;">
                <div style="font-weight:800; font-size:13.5px;">{{ $rating->reviewer_name ?? 'ลูกค้า' }}</div>
                <div style="font-size:11.5px; color:var(--ink2);">
                    {{ optional($rating->created_at)->format('d/m/Y H:i') }}
                    @if($rating->is_verified_purchase) · ✔ ซื้อจริง@endif
                </div>
            </div>
        </div>
        <div style="display:flex; gap:6px; align-items:center;">
            <span style="color:var(--accent1); letter-spacing:1px; font-size:15px;" aria-label="{{ (int) $rating->rating }} ดาว">{{ $stars }}</span>
            <x-seller-kit.pill :tone="$ratingTone">{{ (int) $rating->rating }}/5</x-seller-kit.pill>
        </div>
    </div>

    @if($rating->comment)
        <div style="font-size:13.5px; line-height:1.7; overflow-wrap:anywhere;">{{ $rating->comment }}</div>
    @else
        <div style="font-size:12.5px; color:var(--ink2);">ไม่ได้เขียนความเห็น</div>
    @endif

    @if(is_array($rating->images) && count($rating->images))
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
            @foreach(array_slice($rating->images, 0, 4) as $img)
                <img src="{{ str_starts_with((string) $img, 'http') ? $img : \Illuminate\Support\Facades\Storage::url($img) }}" alt="รูปจากรีวิว" loading="lazy" style="width:64px; height:64px; object-fit:cover; border-radius:10px; box-shadow:var(--inset-sm);">
            @endforeach
        </div>
    @endif

    @if($rating->seller_response)
        <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px;">
            <div style="font-size:11.5px; font-weight:700; color:var(--deep1);">💬 ร้านตอบกลับ · {{ optional($rating->seller_responded_at)->format('d/m/Y H:i') }}</div>
            <div style="font-size:13px; line-height:1.65; margin-top:4px; overflow-wrap:anywhere;">{{ $rating->seller_response }}</div>
        </div>
    @endif

    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button type="button" class="tp-btn tp-btn-sm" @click="reply = !reply">{{ $rating->seller_response ? '✏️ แก้คำตอบ' : '💬 ตอบกลับ' }}</button>
        @if($showLink)
            <a href="{{ route('seller.store-rating.show', $rating) }}" class="tp-btn tp-btn-sm">ดูรายละเอียด</a>
        @endif
        @if((int) $rating->helpful_count > 0)
            <span style="font-size:12px; color:var(--ink2); align-self:center;">👍 มีประโยชน์ {{ number_format((int) $rating->helpful_count) }}</span>
        @endif
    </div>

    <form x-show="reply" x-cloak method="POST" action="{{ route('seller.store-rating.respond', $rating) }}" x-data="{ sending: false, text: @js((string) ($rating->seller_response ?? '')) }" @submit="sending = true">
        @csrf
        <label for="resp-{{ $rating->id }}" style="font-size:12.5px; font-weight:700;">คำตอบของร้าน (ลูกค้าและผู้ซื้อคนอื่นจะเห็น)</label>
        <textarea id="resp-{{ $rating->id }}" name="response" x-model="text" required maxlength="1000" rows="3" class="tp-input" style="margin-top:6px; resize:vertical;" placeholder="ขอบคุณลูกค้า และบอกสิ่งที่ร้านจะปรับปรุง"></textarea>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; gap:8px;">
            <span class="tp-num" style="font-size:11px; color:var(--ink2);" x-text="text.length + ' / 1000'"></span>
            <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" :disabled="sending" :style="{ opacity: sending ? .6 : 1 }"><span x-text="sending ? 'กำลังส่ง…' : 'ส่งคำตอบ'"></span></button>
        </div>
    </form>
</div>
