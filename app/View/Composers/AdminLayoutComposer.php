<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Admin Layout Composer
 *
 * เลือก layout ให้อัตโนมัติตาม iframe mode
 * - Iframe Mode: ใช้ admin-content-only.blade.php
 * - Normal Mode: ใช้ admin-iframe.blade.php
 *
 * @version 1.0
 * @since 2026-01-19
 */
class AdminLayoutComposer
{
    /**
     * เลือก layout ตาม mode
     *
     * @param View $view
     * @return void
     */
    public function compose(View $view): void
    {
        // ดึง $isIframeMode จาก request attributes (ที่ middleware ตั้งไว้)
        $isIframeMode = request()->attributes->get('iframe_mode', false);

        // เลือก layout ที่เหมาะสม
        $actualLayout = $isIframeMode
            ? 'layouts.admin-content-only'
            : 'layouts.admin-iframe';

        // ส่ง layout ไปให้ view
        $view->with('actualLayout', $actualLayout);
        $view->with('isIframeMode', $isIframeMode);
    }
}
