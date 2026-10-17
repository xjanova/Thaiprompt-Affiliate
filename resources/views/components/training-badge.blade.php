{{--
    🎓 ป้าย "ผ่านการอบรม" (แบบ B) — ตรากลมพื้นกรมท่า #042C53 ขอบทอง #EF9F27 หมวกบัณฑิตสีทอง #FAC775
    วางข้างป้ายยืนยันตัวตน (eKYC) เสมอ · ไม่ผ่าน = ไม่แสดงอะไรเลย

    ใช้:
      <x-training-badge :user="$user" role="seller" />            ← บทบาทเดียว
      <x-training-badge :user="$user" />                          ← ทุกบทบาทที่ผ่าน (โปรไฟล์ตัวเอง)
      <x-training-badge :user="$store->user_id" role="seller" label />  ← มีข้อความต่อท้าย
      <x-training-badge :badge="$payload['training_badge']" />   ← ใช้ payload ที่คำนวณไว้แล้ว

    props: user (User|int|null) · role (seller|rider|buyer|null) · badge (array|null) · size (px, ค่าเริ่มต้น 18) · label (bool)
    สีของตราคงที่ทั้งโหมดสว่าง/มืด (เป็นสีแบรนด์ของตรา) · ข้อความใช้สีตามธีม (โหมดมืดเป็นทอง)
--}}
@props(['user' => null, 'role' => null, 'badge' => null, 'size' => 18, 'label' => false])

@php
    $trainingBadges = [];
    if (is_array($badge)) {
        $trainingBadges = ! empty($badge['certified']) ? [$badge] : [];
    } elseif ($user !== null) {
        $trainingBadges = $role
            ? array_filter([\App\Services\Classroom\TrainingBadge::for($user, (string) $role)])
            : array_values(array_filter(\App\Services\Classroom\TrainingBadge::allFor($user)));
    }
    $sealSize = max(12, (int) $size);
@endphp

@if($trainingBadges !== [])
    @once
        <style>
            .tb-badge { display:inline-flex; align-items:center; gap:5px; vertical-align:middle; line-height:1; white-space:nowrap; }
            .tb-badge svg { flex:none; filter:drop-shadow(0 1px 1.5px rgba(4,44,83,.28)); }
            .tb-badge-text { font-size:12px; font-weight:700; color:#042C53; }
            .dark .tb-badge-text { color:#FAC775; }
            .tb-badge-refresh { opacity:.62; }
            .tb-badge-note { font-size:10.5px; font-weight:600; color:#b06f2f; }
            .dark .tb-badge-note { color:#EF9F27; }
        </style>
    @endonce
    @foreach($trainingBadges as $tb)
        @php
            $tbTitle = $tb['label'].(! empty($tb['needs_refresh']) ? ' (กติกาอัปเดตแล้ว ควรอบรมทบทวน)' : '');
        @endphp
        <span {{ $attributes->merge(['class' => 'tb-badge'.(! empty($tb['needs_refresh']) ? ' tb-badge-refresh' : '')]) }}
              role="img" aria-label="{{ $tbTitle }}" title="{{ $tbTitle }}" data-training-badge="{{ $tb['role'] }}">
            <svg width="{{ $sealSize }}" height="{{ $sealSize }}" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <circle cx="12" cy="12" r="10.7" fill="#042C53" stroke="#EF9F27" stroke-width="1.9"/>
                <path d="M12 6.4 19.2 9.7 12 13 4.8 9.7Z" fill="#FAC775"/>
                <path d="M8 11.5v2.9c0 1.3 1.8 2.3 4 2.3s4-1 4-2.3v-2.9L12 13.4Z" fill="#FAC775"/>
                <path d="M18.3 10.1v4.1" stroke="#FAC775" stroke-width="1.1" stroke-linecap="round"/>
                <circle cx="18.3" cy="14.8" r="0.95" fill="#FAC775"/>
            </svg>
            @if($label)
                <span class="tb-badge-text">{{ $tb['label'] }}</span>
                @if(! empty($tb['needs_refresh']))
                    <span class="tb-badge-note">ควรทบทวน</span>
                @endif
            @endif
        </span>
    @endforeach
@endif
