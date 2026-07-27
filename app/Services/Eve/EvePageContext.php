<?php

namespace App\Services\Eve;

use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * 📍 "ตอนนี้ Eve ยืนอยู่หน้าไหน" + "จะพาลูกค้าไปไหนได้บ้าง"
 *
 * widget ส่งชื่อ route ปัจจุบันมา → ตัวนี้แปลงเป็นคำอธิบายภาษาไทยใส่ prompt
 * และสร้าง "ปุ่มลัด" ที่ url ถูก resolve ฝั่งเซิร์ฟเวอร์ทั้งหมด
 *
 * 🔒 ห้าม echo url ที่ client ส่งมา — ทุก url สร้างจาก route() ของเราเอง
 *    และเช็ค RouteFacade::has() ก่อนเสมอ (route ถูกลบ/สะกดผิด = ข้าม ไม่ 500)
 */
class EvePageContext
{
    /** ลำดับสิทธิ์ — ใช้เทียบว่า tier ปัจจุบันสูงพอจะเห็นเมนูนั้นไหม */
    private const TIER_RANK = [
        EveActor::TIER_GUEST => 0,
        EveActor::TIER_CUSTOMER => 1,
        EveActor::TIER_SELLER => 1,
        EveActor::TIER_ADMIN => 2,
    ];

    /**
     * อธิบายหน้าปัจจุบันเป็นข้อความสั้นๆ ใส่ system prompt
     * (คืนสตริงว่างถ้าไม่รู้จักหน้านั้น → Eve ก็แค่ไม่พูดถึงหน้า)
     */
    public function describe(?string $routeName): string
    {
        $page = $this->lookup($routeName);
        if (! $page) {
            return '';
        }

        $parts = ["[📍 ลูกค้ากำลังอยู่หน้า: {$page['title']}]"];
        if (! empty($page['desc'])) {
            $parts[] = 'หน้านี้คือ: '.$page['desc'];
        }
        if (! empty($page['can'])) {
            $parts[] = 'สิ่งที่ทำได้ที่นี่: '.$page['can'];
        }
        $parts[] = 'ถ้าลูกค้าถามถึงสิ่งที่ทำที่หน้านี้ไม่ได้ ให้บอกวิธีไปหน้าที่ถูกต้อง (มีปุ่มลัดให้แล้ว)';

        return implode("\n", $parts);
    }

    /**
     * ดึงข้อมูลหน้าจากสมุดแผนที่ — เฉพาะชื่อที่อยู่ใน whitelist เท่านั้น
     *
     * @return array<string,mixed>|null
     */
    public function lookup(?string $routeName): ?array
    {
        if (! $routeName) {
            return null;
        }

        $pages = (array) config('eve_pages.pages', []);

        return $pages[$routeName] ?? null;
    }

    /**
     * ปุ่มลัดที่ tier นี้เห็นได้ + route มีอยู่จริง
     *
     * @return array<int,array{label:string,url:string}>
     */
    public function shortcutsFor(EveActor $actor, ?string $currentRoute = null): array
    {
        $rank = self::TIER_RANK[$actor->tier] ?? 0;
        $out = [];

        foreach ((array) config('eve_pages.shortcuts', []) as $item) {
            $needed = self::TIER_RANK[$item['tier'] ?? EveActor::TIER_GUEST] ?? 0;
            if ($rank < $needed) {
                continue;
            }

            // 🔒 หน้าที่ถูก middleware กันตาม role จริงๆ (/user/* = role:user, /seller/* = role:seller)
            //    ระดับ (rank) อย่างเดียวไม่พอ เพราะลูกค้ากับผู้ขายอยู่ระดับเดียวกันแต่คนละสิทธิ์
            //    ถ้าไม่กรองตรงนี้ ผู้ขายจะเห็นปุ่ม "แดชบอร์ดของฉัน" แล้วกดโดนเด้งทันที = ปุ่มหลอก
            if (! empty($item['only']) && ! in_array($actor->tier, (array) $item['only'], true)) {
                continue;
            }

            $routeName = $item['route'] ?? '';
            if ($routeName === '' || $routeName === $currentRoute) {
                continue; // ไม่เสนอปุ่มไปหน้าที่ยืนอยู่แล้ว
            }

            // ⚠️ กันพัง: route อาจถูกลบ/เปลี่ยนชื่อภายหลัง
            if (! RouteFacade::has($routeName)) {
                continue;
            }

            try {
                // 🎯 คะแนนความ "ตรงกับคนคนนี้" — ใช้จัดลำดับก่อนถูกตัดเหลือ 4 ปุ่ม
                //    ⚠️ สำคัญมาก: ผู้เรียกตัดด้วย array_slice(...,0,4) ถ้าปล่อยเรียงตาม config
                //    ปุ่มของ guest (ที่อยู่บนสุด) จะกินครบ 4 ช่องเสมอ → แอดมินไม่เคยเห็นปุ่ม
                //    "หลังบ้าน" และลูกค้าไม่เคยเห็น "กระเป๋าเงิน" เลยสักครั้ง
                $priority = self::TIER_RANK[$item['tier'] ?? EveActor::TIER_GUEST] ?? 0;
                if (! empty($item['only'])) {
                    $priority++;   // ปุ่มที่เจาะจง role นี้โดยเฉพาะ = ตรงที่สุด ขึ้นก่อน
                }

                $out[] = ['label' => $item['label'], 'url' => route($routeName), '_p' => $priority];
            } catch (\Throwable $e) {
                // route ต้องการพารามิเตอร์ → ข้าม (ปุ่มลัดต้องกดได้ทันทีเท่านั้น)
            }
        }

        // เรียงจาก "ตรงกับ role นี้ที่สุด" ลงมา — PHP 8 sort เสถียร ของที่คะแนนเท่ากันจึงคงลำดับใน config
        usort($out, fn ($a, $b) => $b['_p'] <=> $a['_p']);

        return array_map(fn ($x) => ['label' => $x['label'], 'url' => $x['url']], $out);
    }
}
