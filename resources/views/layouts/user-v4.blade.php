{{--
 |----------------------------------------------------------------------------
 | User Layout V4 → วิ่งในเปลือกโนวาแล้ว (2026-10-29)
 |----------------------------------------------------------------------------
 | หน้าสมาชิกทุกหน้าที่ @extends('layouts.user-v4') ได้แถบบน/เมนูข้าง/แถบล่างมือถือชุดโนวา
 | (layouts.user-nova) โดยไม่ต้องแก้หน้าเลย:
 |   - เนื้อหาในหน้ายังใช้คลาส tp-* + ตัวแปร V4 (--ink, --surf, --accent1, --inset-sm ...)
 |   - nvmV4Bridge=true → user-nova โหลด theme-v4.css + public/theme-nova/nova-v4-bridge.css
 |     ซึ่งทับตัวแปรเหล่านั้นเป็นสีโนวา (งาช้าง/กรมท่า/ทอง) ทั้งโหมดสว่างและมืด
 |   - โหมดมืดใช้ค่าเดียวกับเดิม (localStorage tp_theme_v4.dark) — Theme Studio ฝั่งสมาชิกถูกถอดออก
 |
 | หน้าไหนออกแบบใหม่เป็นโนวาเต็มแล้ว → เปลี่ยนเป็น @extends('layouts.user-nova') ตรง ๆ
 | ส่วนเสริมเหมือนเดิม: @section('title') / @section('content') / @push('styles') / @push('scripts')
 --}}
@extends('layouts.user-nova', ['nvmV4Bridge' => true])
