{{--
 | ชุดไอคอนเส้น (SVG sprite) ของพื้นที่สมาชิกธีมโนวา — วางครั้งเดียวต้น <body> ใน layouts/user-nova
 | ใช้งาน: <svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>
 | มี gradient ทองสำหรับวงแหวนระดับ (#nvm-gold-stroke) และพื้นใต้กราฟเส้น (#nvm-spark-fill)
 --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="nvm-gold-stroke" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fbe3a8"/><stop offset=".5" stop-color="#f0c96a"/><stop offset="1" stop-color="#d4a64a"/></linearGradient>
        <linearGradient id="nvm-spark-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f0c96a" stop-opacity=".35"/><stop offset="1" stop-color="#f0c96a" stop-opacity="0"/></linearGradient>
    </defs>
    <symbol id="nvm-i-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-6h4v6"/></symbol>
    <symbol id="nvm-i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="2"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2"/></symbol>
    <symbol id="nvm-i-wallet" viewBox="0 0 24 24"><path d="M19 7V5.5A1.5 1.5 0 0 0 17.5 4H5a2 2 0 0 0 0 4h14a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V6"/><path d="M16 13.5h.01"/></symbol>
    <symbol id="nvm-i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M18 14.2a6.5 6.5 0 0 1 3.5 5.8"/></symbol>
    <symbol id="nvm-i-award" viewBox="0 0 24 24"><circle cx="12" cy="9" r="6"/><path d="m8.5 14 -1.5 7 5-2.5 5 2.5-1.5-7"/><path d="m12 6 1 2 2.2.3-1.6 1.5.4 2.2-2-1-2 1 .4-2.2-1.6-1.5L11 8z"/></symbol>
    <symbol id="nvm-i-card" viewBox="0 0 24 24"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><circle cx="8.5" cy="11" r="2"/><path d="M5.5 16a3.2 3.2 0 0 1 6 0M14 10h4.5M14 13.5h3"/></symbol>
    <symbol id="nvm-i-bag" viewBox="0 0 24 24"><path d="M5 8h14l-1 12.5H6z"/><path d="M9 10V6.5a3 3 0 0 1 6 0V10"/></symbol>
    <symbol id="nvm-i-cart" viewBox="0 0 24 24"><circle cx="9" cy="20" r="1.4"/><circle cx="17.5" cy="20" r="1.4"/><path d="M2.5 3.5h2.8l2.4 11.2h10.6l2.2-8H6.4"/></symbol>
    <symbol id="nvm-i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20.5a8 8 0 0 1 16 0"/></symbol>
    <symbol id="nvm-i-gear" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></symbol>
    <symbol id="nvm-i-bell" viewBox="0 0 24 24"><path d="M6 9a6 6 0 0 1 12 0c0 6.5 2.5 8 2.5 8h-17S6 15.5 6 9"/><path d="M10 20.5a2.2 2.2 0 0 0 4 0"/></symbol>
    <symbol id="nvm-i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></symbol>
    <symbol id="nvm-i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></symbol>
    <symbol id="nvm-i-moon" viewBox="0 0 24 24"><path d="M20.5 14.5A8.5 8.5 0 1 1 9.5 3.5a7 7 0 0 0 11 11"/></symbol>
    <symbol id="nvm-i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h10"/></symbol>
    <symbol id="nvm-i-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol>
    <symbol id="nvm-i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="nvm-i-out" viewBox="0 0 24 24"><path d="M12 19V5M6 11l6-6 6 6"/></symbol>
    <symbol id="nvm-i-send" viewBox="0 0 24 24"><path d="M21 3 10.5 13.5"/><path d="M21 3 14.5 21l-4-7.5L3 9.5z"/></symbol>
    <symbol id="nvm-i-history" viewBox="0 0 24 24"><path d="M3.5 12a8.5 8.5 0 1 0 2.5-6"/><path d="M3 3.5V8h4.5"/><path d="M12 7.5V12l3 2"/></symbol>
    <symbol id="nvm-i-qr" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/></symbol>
    <symbol id="nvm-i-copy" viewBox="0 0 24 24"><rect x="8" y="8" width="13" height="13" rx="2.5"/><path d="M16 8V5.5A2.5 2.5 0 0 0 13.5 3h-8A2.5 2.5 0 0 0 3 5.5v8A2.5 2.5 0 0 0 5.5 16H8"/></symbol>
    <symbol id="nvm-i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></symbol>
    <symbol id="nvm-i-up" viewBox="0 0 24 24"><path d="M7 17 17 7M8 7h9v9"/></symbol>
    <symbol id="nvm-i-down" viewBox="0 0 24 24"><path d="M7 7l10 10M17 8v9H8"/></symbol>
    <symbol id="nvm-i-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16.5 9.5"/></symbol>
    <symbol id="nvm-i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="nvm-i-x" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></symbol>
    <symbol id="nvm-i-cash" viewBox="0 0 24 24"><rect x="2.5" y="6" width="19" height="12" rx="2.5"/><circle cx="12" cy="12" r="2.8"/><path d="M6 9.5v.01M18 14.5v.01"/></symbol>
    <symbol id="nvm-i-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></symbol>
    <symbol id="nvm-i-id" viewBox="0 0 24 24"><rect x="3" y="4.5" width="18" height="15" rx="2.5"/><circle cx="9" cy="11" r="2.3"/><path d="M5.8 16.2a3.6 3.6 0 0 1 6.4 0M14.5 9.5h3.5M14.5 13h2.5"/></symbol>
    <symbol id="nvm-i-store" viewBox="0 0 24 24"><path d="M3.5 9 5 4h14l1.5 5"/><path d="M3.5 9a2.8 2.8 0 0 0 5.6 0 2.8 2.8 0 0 0 5.8 0 2.8 2.8 0 0 0 5.6 0"/><path d="M5 11.5V20h14v-8.5M10 20v-5h4v5"/></symbol>
    <symbol id="nvm-i-basket" viewBox="0 0 24 24"><path d="M3 10h18l-2 10H5z"/><path d="m8 10 3-6M16 10l-3-6M9 14v2.5M15 14v2.5"/></symbol>
    <symbol id="nvm-i-scooter" viewBox="0 0 24 24"><circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/><path d="M9 17h6l-2.5-7H9M15 6h2.5l3 8.5"/></symbol>
    <symbol id="nvm-i-moonstar" viewBox="0 0 24 24"><path d="M19.5 15A8 8 0 1 1 9 4.5 6.5 6.5 0 0 0 19.5 15"/><path d="m17 3 .7 1.6 1.6.7-1.6.7L17 7.6l-.7-1.6-1.6-.7 1.6-.7z"/></symbol>
    <symbol id="nvm-i-gift" viewBox="0 0 24 24"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v8.5h14V12M12 8v12.5"/><path d="M12 8C10.5 4 7 4 7 6s3 2 5 2c2 0 5 0 5-2s-3.5-2-5 2"/></symbol>
    <symbol id="nvm-i-line" viewBox="0 0 24 24"><path d="M12 3.5c-5 0-9 3.2-9 7.2 0 3.6 3.2 6.6 7.5 7.1.3.1.7.2.8.5.1.3 0 .7 0 1l-.1.8c0 .2-.2.9.8.5s5.5-3.2 7.5-5.5c1.4-1.5 2-2.9 2-4.4 0-4-4-7.2-9-7.2Z"/></symbol>
    <symbol id="nvm-i-fb" viewBox="0 0 24 24"><path d="M14.5 8H17V4.5h-2.5A4 4 0 0 0 10.5 8.5V11H8v3.5h2.5V21H14v-6.5h2.6l.4-3.5H14V9a1 1 0 0 1 .5-1Z"/></symbol>
    <symbol id="nvm-i-chev" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></symbol>
    <symbol id="nvm-i-logout" viewBox="0 0 24 24"><path d="M9.5 20H5.5A1.5 1.5 0 0 1 4 18.5v-13A1.5 1.5 0 0 1 5.5 4h4"/><path d="m15 16 4-4-4-4M19 12H9.5"/></symbol>
    <symbol id="nvm-i-swap" viewBox="0 0 24 24"><path d="M4 8h14l-3.5-3.5M20 16H6l3.5 3.5"/></symbol>
    <symbol id="nvm-i-camera" viewBox="0 0 24 24"><path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2.3l1.4-2.2h5.6L16.2 7h2.3A1.5 1.5 0 0 1 20 8.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5z"/><circle cx="12" cy="13" r="3.4"/></symbol>
    <symbol id="nvm-i-pin" viewBox="0 0 24 24"><path d="M9 3h6l-1 6 3.5 3.5v1.5h-11v-1.5L10 9z"/><path d="M12 14v7"/></symbol>
    <symbol id="nvm-i-lock" viewBox="0 0 24 24"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3M12 14.5v2.5"/></symbol>
    <symbol id="nvm-i-bank" viewBox="0 0 24 24"><path d="M3 9.5 12 4l9 5.5"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 20.5h18"/></symbol>
</svg>
