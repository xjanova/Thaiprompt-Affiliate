{{--
/**
 * Admin V3 Layout - Dynamic Layout Selector
 *
 * Layout นี้จะเลือก layout ที่เหมาะสมตาม iframe mode อัตโนมัติ:
 * - Iframe Mode ($isIframeMode = true): extend admin-content-only
 * - Normal Mode ($isIframeMode = false): extend admin-iframe
 *
 * @middleware DetectIframeMode ตั้งค่า $isIframeMode จาก ?iframe=1 parameter
 * @composer AdminLayoutComposer กำหนด $actualLayout
 *
 * @version 3.0 - Dynamic Layout Selection
 * @since 2026-01-19
 */
--}}

{{-- เลือก layout แบบ dynamic ตาม iframe mode --}}
@extends($isIframeMode ?? false ? 'layouts.admin-content-only' : 'layouts.admin-iframe')
