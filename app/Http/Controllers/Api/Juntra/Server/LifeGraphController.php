<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Services\Fortune\LifeGraphService;
use App\Support\ThaiProvinces;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 📈 (2026-11-04) กราฟชีวิตตามตำรามหาทักษา ให้โรงละครคำทำนายของเว็บ จันทรา.online
 *
 * ทำไมต้องมาขอที่นี่: คะแนนรายปีต้องใช้ผังดาวกำเนิดเต็ม (ThaiAstrologyService) ซึ่งเว็บไม่มี
 * ⇒ คำนวณที่เดียวกับรูปกราฟที่ลูกค้า LINE ได้ (LifeGraphService::forInputs) ผลจึงตรงกันทุกจุด
 *
 *   POST /api/v1/juntra/server/fortune/lifegraph {birth_date: Y-m-d, birth_time?: H:i, birth_place?: ข้อความ}
 *        200 {data: กราฟฉบับย่อ} · 422 = ผูกดวงไม่ได้ (เว็บต้องไม่แต่งกราฟแทน)
 *
 * ไม่คิดเงิน ไม่ใช้ AI — ส่งเฉพาะที่หน้าเว็บวาด (ไม่ส่ง components/weights/natal ทั้งก้อน)
 */
class LifeGraphController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'birth_date' => 'required|date_format:Y-m-d|before_or_equal:today|after:1900-01-01',
            'birth_time' => 'nullable|date_format:H:i',
            'birth_place' => 'nullable|string|max:191',
        ]);

        $hour = null;
        if (! empty($data['birth_time'])) {
            [$h, $m] = array_map('intval', explode(':', $data['birth_time']));
            $hour = $h + $m / 60;
        }
        // ที่เกิดเป็นข้อความอิสระจากโปรไฟล์เว็บ — หาจังหวัดไม่เจอ = ใช้พิกัดกรุงเทพ (เหมือนผังของบอท)
        $province = ! empty($data['birth_place']) ? ThaiProvinces::resolve((string) $data['birth_place']) : null;

        $g = LifeGraphService::forInputs($data['birth_date'], $hour, $province);
        if ($g === null) {
            return response()->json(['success' => false, 'data' => null, 'error' => 'ผูกดวงจากวันเกิดนี้ไม่ได้'], 422);
        }

        return response()->json(['success' => true, 'data' => self::compact($g, $province)]);
    }

    /** กราฟฉบับที่หน้าเว็บใช้ — ตัวเลขชุดเดียวกับรูป LifeGraphImage */
    public static function compact(array $g, ?string $province = null): array
    {
        $pr = $g['profile'];
        $cur = $g['current'];
        $pick = static fn (array $list): array => array_map(static fn ($p) => [
            'age' => $p['age'],
            'year_be' => $p['year_be'],
            'score' => $p['score'],
            'main_planet_th' => $p['main_planet_th'],
            'sub_planet_th' => $p['sub_planet_th'],
        ], $list);

        return [
            'version' => $g['version'],
            'max_age' => $g['max_age'],
            'profile' => [
                'birth_day_th' => $pr['birth_day_th'],
                'birth_lord_th' => $pr['birth_lord_th'],
                'birth_time' => $pr['birth_time'],
                'lagna' => $pr['lagna'],
                'province' => $province,
            ],
            'current' => [
                'age' => $cur['age'],
                'age_decimal' => $cur['age_decimal'],
                'period' => $cur['period'] ? [
                    'planet_th' => $cur['period']['planet_th'],
                    'from_age' => $cur['period']['from_age'],
                    'to_age' => $cur['period']['to_age'],
                    'tone' => $cur['period']['tone'],
                ] : null,
                'sub' => $cur['sub'] ? ['planet_th' => $cur['sub']['planet_th'], 'until_date' => $cur['sub']['until_date']] : null,
            ],
            // [อายุ, คะแนน] — คู่ตัวเลขล้วน ไม่มีเหตุผลรายปี (ก้อนนั้นใหญ่และหน้าเว็บไม่ได้ใช้)
            'years' => array_map(static fn ($y) => [$y['age'], $y['score']], $g['years']),
            'periods' => array_map(static fn ($p) => [
                'planet' => $p['planet'],
                'planet_th' => $p['planet_th'],
                'from_age' => $p['from_age'],
                'to_age' => $p['to_age'],
                'hex' => $p['hex'],
                'tone' => $p['tone'],
                'text' => $p['text'],
            ], $g['periods']),
            'peaks' => $pick($g['peaks']),
            'lows' => $pick($g['lows']),
            'card' => array_values((array) ($g['reading']['card'] ?? [])),
            'notes' => array_values($g['notes']),
        ];
    }
}
