<?php

namespace App\Http\Controllers\Api\Admin\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * เติมฟิลด์ paging แบบแบน (current_page / last_page / per_page / total) ข้าง ๆ data
 *
 * ใช้กับ endpoint เดิมที่คืนรูป Resource collection ({data, links, meta}) — เพิ่มอย่างเดียว ไม่ลบคีย์เดิม
 * แอปแอดมินอ่านแบบแบนได้เหมือน endpoint ใหม่ ส่วน client เดิม (Warroom) ยังอ่าน data/meta ได้ตามเดิม
 */
trait FlatPaging
{
    /**
     * @param  array<string, mixed>  $payload  ผลของ ResourceCollection->response()->getData(true)
     * @return array<string, mixed>
     */
    protected function withFlatPaging(array $payload, LengthAwarePaginator $page): array
    {
        return $payload + [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ];
    }
}
