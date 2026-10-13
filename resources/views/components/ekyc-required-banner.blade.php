{{--
 | 🪪 แถบ "ยืนยันตัวตนในแอปก่อนสั่งซื้อ" (เว็บ) — 2026-10-13
 | แสดงเมื่อ session มีคีย์ App\Services\Ekyc\EkycGate::FLASH_KEY (controller ตั้งตอนผู้ซื้อที่ยังไม่ยืนยันตัวตนกดสั่งซื้อ / เปิดหน้าชำระเงิน)
 | ใช้ใน layout: frontend-v4, storefront, user-v4 → <x-ekyc-required-banner />
 | ผู้ซื้อยืนยันตัวตนครั้งเดียวในแอป Thai Prompt APP (AI eKYC) แล้วสั่งผ่านเว็บ/LINE ได้ด้วย
 --}}
@if(session(\App\Services\Ekyc\EkycGate::FLASH_KEY))
    <div x-data="{ open: true }" x-show="open" x-transition.opacity
         class="fixed inset-x-0 bottom-4 z-[60] flex justify-center px-4 pointer-events-none"
         role="alert" aria-live="polite">
        <div class="pointer-events-auto w-full max-w-lg rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md shadow-2xl p-4 sm:p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-lg">
                    <i class="fas fa-id-card" aria-hidden="true"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">ยืนยันตัวตนก่อนสั่งซื้อ</p>
                    <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        {{ \App\Services\Ekyc\EkycGate::BUYER_MESSAGE }} ใช้เวลาประมาณ 1 นาที
                    </p>
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <a href="{{ \App\Services\Ekyc\EkycGate::downloadUrl() }}"
                           class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-amber-600 hover:to-orange-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            <i class="fas fa-mobile-alt" aria-hidden="true"></i> ดาวน์โหลดแอป Thai Prompt APP
                        </a>
                        <button type="button" @click="open = false"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                            ไว้ทีหลัง
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
