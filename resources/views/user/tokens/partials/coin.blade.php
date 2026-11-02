{{-- วงกลมเหรียญ: โลโก้ (ถ้ามี) หรือตัวอักษรแรกของสัญลักษณ์ — รับ $token, $size ('' | 'lg') --}}
@php
    $logo = (string) ($token->logo ?? '');
    // โลโก้เป็น URL เต็ม (https) หรือ path ใน storage — นอกนั้นไม่ใช้ กัน javascript:/data: แอบเข้ามา
    $logoUrl = $logo === '' ? null : (preg_match('#^https?://#i', $logo) ? $logo : (str_contains($logo, ':') ? null : asset('storage/'.ltrim($logo, '/'))));
@endphp
<span class="nvm-tx-coin {{ ($size ?? '') === 'lg' ? 'nvm-tx-coin--lg' : '' }}">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $token->symbol }}" loading="lazy">
    @else
        {{ mb_substr((string) $token->symbol, 0, 1) }}
    @endif
</span>
