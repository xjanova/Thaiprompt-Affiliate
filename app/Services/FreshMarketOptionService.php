<?php

namespace App\Services;

use App\Exceptions\FreshMarketException;
use App\Models\FreshMarketCartItem;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketListingOption;
use App\Models\FreshMarketListingOptionGroup;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * FreshMarketOptionService - ตัวเลือกสินค้าตลาดสด
 *
 * 1) คำนวณราคาต่อบรรทัดฝั่งเซิร์ฟเวอร์เท่านั้น: ราคาต่อชิ้น = ราคาสินค้า + Σ ราคาเพิ่มของตัวเลือกที่เลือก
 *    ตรวจกติกากลุ่ม (บังคับ, เลือกเดี่ยว/หลายอย่าง, ขั้นต่ำ/สูงสุด, ตัวเลือกต้องเป็นของสินค้านี้, ตัวเลือกยังเปิดขาย)
 *    ไม่เชื่อราคาที่ client ส่งมาเด็ดขาด
 * 2) ร้านจัดการกลุ่มตัวเลือก/ตัวเลือก (สร้าง/แก้/ลบ/รูป) ทั้งแบบส่งทั้งชุด (sync) และทีละรายการ
 */
class FreshMarketOptionService
{
    /** จำนวนกลุ่มสูงสุดต่อสินค้า */
    public const MAX_GROUPS = 10;

    /** จำนวนตัวเลือกสูงสุดต่อกลุ่ม */
    public const MAX_OPTIONS_PER_GROUP = 30;

    /** โฟลเดอร์รูปตัวเลือก (disk public) — ไฟล์ที่อัปโหลดใหม่แยกโฟลเดอร์ตามสินค้า: fresh-market/options/{listing_id}/ */
    public const IMAGE_DIR = 'fresh-market/options';

    /** โฟลเดอร์รูปสินค้า (disk public) */
    public const LISTING_IMAGE_DIR = 'fresh-market';

    /** รูปคลังของระบบที่ร้านเลือกเป็นรูปตัวเลือกได้ (ไม่ใช่ไฟล์ของร้านใด — ไม่ถูกลบ) */
    public const STOCK_IMAGE_PREFIX = '/images/taladsod/';

    // ╔══════════════════════════════════════════╗
    // ║  คำนวณราคา + ตรวจกติกา                   ║
    // ╚══════════════════════════════════════════╝

    /**
     * ตรวจตัวเลือกที่เลือก + คำนวณราคาของหนึ่งบรรทัด (สินค้า + ตัวเลือก × จำนวน)
     *
     * @param  mixed  $optionIds  รหัสตัวเลือกที่ผู้ซื้อเลือก (array ของ id)
     * @param  bool  $applyDefaults  true = กลุ่มบังคับที่ยังไม่ได้เลือก ใช้ตัวเลือกที่ถูกที่สุดให้อัตโนมัติ (ช่องทางที่ยังเลือกตัวเลือกไม่ได้ เช่น LINE)
     * @return array{listing_id: int, quantity: int, image_url: ?string, option_ids: array<int,int>, selected_options: array<int, array>, base_price: float, options_price: float, unit_price: float, line_total: float, note: ?string}
     *
     * @throws FreshMarketException INVALID_QUANTITY | INVALID_OPTION | OPTION_UNAVAILABLE | OPTION_REQUIRED | OPTION_LIMIT
     */
    public function resolveLine(FreshMarketListing $listing, mixed $optionIds, int $quantity, ?string $note = null, bool $applyDefaults = false): array
    {
        if ($quantity < 1 || $quantity > 999) {
            throw FreshMarketException::make('INVALID_QUANTITY', 'จำนวนสินค้าไม่ถูกต้อง (1-999)', 422);
        }

        $ids = FreshMarketCartItem::normalizeOptionIds($optionIds);

        if (count($ids) > self::MAX_GROUPS * self::MAX_OPTIONS_PER_GROUP) {
            throw FreshMarketException::make('INVALID_OPTION', 'ตัวเลือกสินค้าไม่ถูกต้อง', 422);
        }

        $groups = $this->groupsOf($listing);

        // ดัชนีตัวเลือกของสินค้านี้เท่านั้น → id จากสินค้าอื่น/ที่ถูกลบ = ไม่ผ่าน
        $index = [];
        foreach ($groups as $group) {
            foreach ($group->options as $option) {
                $index[(int) $option->id] = $group;
            }
        }

        $chosen = [];

        foreach ($ids as $id) {
            $group = $index[$id] ?? null;

            if (! $group) {
                throw FreshMarketException::make(
                    'INVALID_OPTION',
                    'ตัวเลือกที่เลือกไม่ตรงกับสินค้า "'.$listing->title.'" กรุณาเลือกตัวเลือกใหม่',
                    422
                );
            }

            $option = $group->options->firstWhere('id', $id);

            if (! $option->is_available) {
                throw FreshMarketException::make(
                    'OPTION_UNAVAILABLE',
                    'ตัวเลือก "'.$option->name.'" หมดชั่วคราว กรุณาเลือกตัวเลือกอื่น',
                    409
                );
            }

            $chosen[(int) $group->id][(int) $option->id] = $option;
        }

        foreach ($groups as $group) {
            $picked = $chosen[(int) $group->id] ?? [];
            $min = $group->minRequired();
            $max = $group->maxAllowed();

            // ช่องทางที่ยังเลือกตัวเลือกไม่ได้ → เติมตัวเลือกที่ถูกที่สุดที่ยังเปิดขายจนครบขั้นต่ำ
            if ($applyDefaults && count($picked) < $min) {
                $candidates = $group->options
                    ->where('is_available', true)
                    ->reject(fn ($o) => isset($picked[(int) $o->id]))
                    ->sortBy(fn ($o) => sprintf('%012.2f-%06d-%09d', (float) $o->price_delta, (int) $o->sort_order, (int) $o->id));

                foreach ($candidates as $candidate) {
                    if (count($picked) >= $min) {
                        break;
                    }
                    $picked[(int) $candidate->id] = $candidate;
                }

                $chosen[(int) $group->id] = $picked;
            }

            if (count($picked) < $min) {
                throw FreshMarketException::make(
                    'OPTION_REQUIRED',
                    $min === 1
                        ? 'กรุณาเลือกตัวเลือก "'.$group->name.'" ของ "'.$listing->title.'"'
                        : 'กรุณาเลือกตัวเลือก "'.$group->name.'" อย่างน้อย '.$min.' อย่าง',
                    422
                );
            }

            if ($max !== null && count($picked) > $max) {
                throw FreshMarketException::make(
                    'OPTION_LIMIT',
                    'ตัวเลือก "'.$group->name.'" เลือกได้ไม่เกิน '.$max.' อย่าง',
                    422
                );
            }
        }

        // snapshot เรียงตามลำดับกลุ่ม/ตัวเลือกที่ร้านจัดไว้
        $selected = [];
        $finalIds = [];
        $optionsPrice = 0.0;

        foreach ($groups as $group) {
            foreach ($group->options as $option) {
                if (! isset($chosen[(int) $group->id][(int) $option->id])) {
                    continue;
                }

                $delta = round((float) $option->price_delta, 2);
                $optionsPrice += $delta;
                $finalIds[] = (int) $option->id;
                $selected[] = [
                    'group_id' => (int) $group->id,
                    'group_name' => $group->name,
                    'option_id' => (int) $option->id,
                    'name' => $option->name,
                    'price_delta' => $delta,
                ];
            }
        }

        $basePrice = round((float) $listing->price, 2);
        $optionsPrice = round($optionsPrice, 2);
        $unitPrice = round($basePrice + $optionsPrice, 2);

        if ($unitPrice < 0) {
            throw FreshMarketException::make('INVALID_OPTION', 'ราคาสินค้าหลังเลือกตัวเลือกไม่ถูกต้อง', 422);
        }

        sort($finalIds);
        $note = $note !== null ? trim($note) : null;

        // รูปของบรรทัด: ตัวเลือกแบบเลือกเดียวที่มีรูป (เช่น กะเพรากุ้ง) ไม่งั้นใช้รูปหลักสินค้า
        $lineImage = null;
        foreach ($groups as $group) {
            if ($group->isMulti()) {
                continue;
            }

            foreach ($group->options as $option) {
                if (isset($chosen[(int) $group->id][(int) $option->id]) && $option->image_url) {
                    $lineImage = $option->image_url;
                    break 2;
                }
            }
        }

        return [
            'listing_id' => (int) $listing->id,
            'quantity' => $quantity,
            'image_url' => $lineImage ?? $listing->primary_image,
            'option_ids' => $finalIds,
            'selected_options' => $selected,
            'base_price' => $basePrice,
            'options_price' => $optionsPrice,
            'unit_price' => $unitPrice,
            'line_total' => round($unitPrice * $quantity, 2),
            'note' => $note !== null && $note !== '' ? mb_substr($note, 0, 255) : null,
        ];
    }

    /**
     * กลุ่มตัวเลือก + ตัวเลือกของสินค้า (ใช้ relation ที่โหลดไว้แล้วถ้ามี)
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, FreshMarketListingOptionGroup>
     */
    public function groupsOf(FreshMarketListing $listing): Collection
    {
        if (! $listing->exists) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        if (! $listing->relationLoaded('optionGroups')) {
            $listing->load('optionGroups.options');
        } else {
            $listing->optionGroups->loadMissing('options');
        }

        return $listing->optionGroups;
    }

    /**
     * กลุ่มตัวเลือกในรูป API (ฝั่งผู้ซื้อซ่อนตัวเลือกที่ปิดขายได้)
     */
    public function groupsForApi(FreshMarketListing $listing, bool $includeUnavailable = true): array
    {
        return $this->groupsOf($listing)
            ->map(fn (FreshMarketListingOptionGroup $g) => $g->toApiArray($includeUnavailable))
            ->values()
            ->all();
    }

    // ╔══════════════════════════════════════════╗
    // ║  ร้านจัดการตัวเลือก                      ║
    // ╚══════════════════════════════════════════╝

    /**
     * กฎตรวจข้อมูลกลุ่มตัวเลือกแบบทั้งชุด (ใช้ได้ทั้ง API และฟอร์มเว็บ)
     *
     * @param  string  $prefix  ชื่อช่อง (เช่น option_groups)
     */
    public static function groupsRules(string $prefix = 'option_groups'): array
    {
        return [
            $prefix => 'nullable|array|max:'.self::MAX_GROUPS,
            "{$prefix}.*.id" => 'nullable|integer',
            "{$prefix}.*.name" => 'required|string|max:100',
            "{$prefix}.*.selection_type" => 'nullable|in:single,multi',
            "{$prefix}.*.is_required" => 'nullable|boolean',
            "{$prefix}.*.min_select" => 'nullable|integer|min:0|max:'.self::MAX_OPTIONS_PER_GROUP,
            "{$prefix}.*.max_select" => 'nullable|integer|min:0|max:'.self::MAX_OPTIONS_PER_GROUP,
            "{$prefix}.*.sort_order" => 'nullable|integer|min:0|max:1000',
            "{$prefix}.*.options" => 'required|array|min:1|max:'.self::MAX_OPTIONS_PER_GROUP,
        ] + self::optionRules("{$prefix}.*.options.*");
    }

    /**
     * กฎตรวจข้อมูลกลุ่มเดียว (ไม่บังคับส่ง options — ส่งมา = แทนที่ตัวเลือกทั้งกลุ่ม)
     */
    public static function singleGroupRules(bool $partial = false): array
    {
        $req = $partial ? 'sometimes|required' : 'required';

        return [
            'name' => $req.'|string|max:100',
            'selection_type' => 'nullable|in:single,multi',
            'is_required' => 'nullable|boolean',
            'min_select' => 'nullable|integer|min:0|max:'.self::MAX_OPTIONS_PER_GROUP,
            'max_select' => 'nullable|integer|min:0|max:'.self::MAX_OPTIONS_PER_GROUP,
            'sort_order' => 'nullable|integer|min:0|max:1000',
            'options' => ($partial ? 'sometimes|' : '').'array|min:1|max:'.self::MAX_OPTIONS_PER_GROUP,
        ] + self::optionRules('options.*');
    }

    /**
     * กฎตรวจข้อมูลตัวเลือก
     *
     * @param  string  $prefix  '' = ตัวเลือกเดี่ยว, 'options.*' = ในกลุ่ม
     */
    public static function optionRules(string $prefix = '', bool $partial = false): array
    {
        $p = $prefix === '' ? '' : $prefix.'.';
        $req = $partial ? 'sometimes|required' : 'required';

        return [
            "{$p}id" => 'nullable|integer',
            "{$p}name" => $req.'|string|max:100',
            "{$p}price_delta" => 'nullable|numeric|min:0|max:100000',
            "{$p}is_available" => 'nullable|boolean',
            "{$p}sort_order" => 'nullable|integer|min:0|max:1000',
            // รับเฉพาะรูปในเว็บเราเอง (/storage/... หรือ /images/...) — กันลิงก์ภายนอก
            // saveOption() ตรวจต่อว่าเป็นรูปของสินค้านี้/รูปคลังระบบเท่านั้น (ชี้ไปไฟล์ของร้านอื่นไม่ได้)
            "{$p}image_url" => ['nullable', 'string', 'max:255', 'regex:#^/(storage|images)/[A-Za-z0-9._/\-]+$#'],
            "{$p}image" => 'nullable|image|max:5120',
            "{$p}remove_image" => 'nullable|boolean',
        ];
    }

    /**
     * ข้อความผิดพลาดภาษาไทยของกฎด้านบน
     */
    public static function validationMessages(): array
    {
        return [
            'option_groups.*.name.required' => 'กรุณากรอกชื่อกลุ่มตัวเลือก',
            'option_groups.*.options.required' => 'แต่ละกลุ่มต้องมีตัวเลือกอย่างน้อย 1 รายการ',
            'option_groups.*.options.min' => 'แต่ละกลุ่มต้องมีตัวเลือกอย่างน้อย 1 รายการ',
            'option_groups.*.options.*.name.required' => 'กรุณากรอกชื่อตัวเลือก',
            'option_groups.*.options.*.price_delta.min' => 'ราคาเพิ่มต้องไม่ติดลบ',
            'option_groups.*.options.*.image.image' => 'รูปตัวเลือกต้องเป็นไฟล์รูปภาพ',
            'option_groups.*.options.*.image.max' => 'รูปตัวเลือกต้องไม่เกิน 5MB',
            'option_groups.*.options.*.image_url.regex' => 'ลิงก์รูปตัวเลือกไม่ถูกต้อง',
            'option_groups.max' => 'ตั้งกลุ่มตัวเลือกได้ไม่เกิน '.self::MAX_GROUPS.' กลุ่ม',
            'options.*.name.required' => 'กรุณากรอกชื่อตัวเลือก',
            'options.min' => 'กลุ่มต้องมีตัวเลือกอย่างน้อย 1 รายการ',
            'name.required' => 'กรุณากรอกชื่อ',
            'price_delta.min' => 'ราคาเพิ่มต้องไม่ติดลบ',
            'image_url.regex' => 'ลิงก์รูปไม่ถูกต้อง',
        ];
    }

    /**
     * แปลงค่า option_groups ที่แอปส่งมาเป็น JSON string (multipart) ให้เป็น array ก่อนตรวจ
     */
    public static function normalizeGroupsInput(Request $request, string $key = 'option_groups'): void
    {
        $value = $request->input($key);

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $request->merge([$key => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * บันทึกกลุ่มตัวเลือกทั้งชุด (กลุ่ม/ตัวเลือกที่มี id = แก้ไข, ไม่มี id = สร้างใหม่, ที่ไม่ได้ส่งมา = ลบ)
     *
     * @param  array  $groups  ข้อมูลที่ผ่าน groupsRules() แล้ว
     * @param  Request|null  $request  ใช้หาไฟล์รูป {prefix}.{i}.options.{j}.image
     * @return \Illuminate\Database\Eloquent\Collection<int, FreshMarketListingOptionGroup>
     */
    public function syncGroups(FreshMarketListing $listing, array $groups, ?Request $request = null, string $prefix = 'option_groups'): Collection
    {
        if (count($groups) > self::MAX_GROUPS) {
            throw FreshMarketException::make('TOO_MANY_OPTION_GROUPS', 'ตั้งกลุ่มตัวเลือกได้ไม่เกิน '.self::MAX_GROUPS.' กลุ่ม', 422);
        }

        DB::transaction(function () use ($listing, $groups, $request, $prefix) {
            $existing = FreshMarketListingOptionGroup::where('listing_id', $listing->id)->lockForUpdate()->get()->keyBy('id');
            // รูปตัวเลือกเดิมทั้งหมด → หลังบันทึกเสร็จ ลบไฟล์ที่ไม่มีใครใช้แล้ว (กันไฟล์ค้างบน disk)
            $previousImages = FreshMarketListingOption::where('listing_id', $listing->id)->pluck('image_url')->filter()->all();
            $keep = [];

            $position = 0;

            // ใช้ key เดิมของฟอร์ม (อาจไม่เรียงต่อกันหลังลบแถวฝั่ง client) เพื่อหาไฟล์รูปให้ตรงแถว
            foreach ($groups as $gi => $data) {
                if (! is_array($data)) {
                    continue;
                }

                $group = isset($data['id']) ? $existing->get((int) $data['id']) : null;
                // ไม่ระบุลำดับ = ตามลำดับที่ส่งมา
                $data['sort_order'] ??= $position;
                // จำนวนกลุ่มตรวจไว้แล้วด้านบน (กลุ่มเก่าจะถูกลบทีหลัง) → ไม่ตรวจเพดานซ้ำระหว่างสร้าง
                $saved = $this->saveGroup($listing, $data, $group, $request, "{$prefix}.{$gi}", $position++, false);
                $keep[] = (int) $saved->id;
            }

            FreshMarketListingOptionGroup::where('listing_id', $listing->id)
                ->when(! empty($keep), fn ($q) => $q->whereNotIn('id', $keep))
                ->delete();

            $this->deleteImagesAfterCommit($previousImages, (int) $listing->id);
        });

        $listing->unsetRelation('optionGroups');

        return $this->groupsOf($listing);
    }

    /**
     * สร้าง/แก้ไขกลุ่มเดียว (ถ้าส่ง options มา = แทนที่ตัวเลือกทั้งกลุ่มตาม id)
     *
     * @param  string  $filePrefix  ตำแหน่งไฟล์รูปใน request เช่น "option_groups.0" (ว่าง = ใช้ "options.{j}.image")
     */
    public function saveGroup(
        FreshMarketListing $listing,
        array $data,
        ?FreshMarketListingOptionGroup $group = null,
        ?Request $request = null,
        string $filePrefix = '',
        int $defaultSort = 0,
        bool $enforceLimit = true
    ): FreshMarketListingOptionGroup {
        return DB::transaction(function () use ($listing, $data, $group, $request, $filePrefix, $defaultSort, $enforceLimit) {
            if ($group && (int) $group->listing_id !== (int) $listing->id) {
                throw FreshMarketException::make('OPTION_GROUP_NOT_FOUND', 'ไม่พบกลุ่มตัวเลือกของสินค้านี้', 404);
            }

            if (! $group) {
                $count = FreshMarketListingOptionGroup::where('listing_id', $listing->id)->count();

                if ($enforceLimit && $count >= self::MAX_GROUPS) {
                    throw FreshMarketException::make('TOO_MANY_OPTION_GROUPS', 'ตั้งกลุ่มตัวเลือกได้ไม่เกิน '.self::MAX_GROUPS.' กลุ่ม', 422);
                }

                $group = new FreshMarketListingOptionGroup(['listing_id' => $listing->id]);
            }

            $attributes = [];
            foreach (['name', 'selection_type', 'min_select', 'sort_order'] as $field) {
                if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                    $attributes[$field] = $field === 'name' ? mb_substr(trim((string) $data[$field]), 0, 100) : $data[$field];
                }
            }
            // max_select ว่าง/null = ไม่จำกัด
            if (array_key_exists('max_select', $data)) {
                $attributes['max_select'] = ($data['max_select'] === null || $data['max_select'] === '') ? null : (int) $data['max_select'];
            }
            if (array_key_exists('is_required', $data) && $data['is_required'] !== null) {
                $attributes['is_required'] = filter_var($data['is_required'], FILTER_VALIDATE_BOOLEAN);
            }

            if (! $group->exists) {
                $attributes += [
                    'selection_type' => FreshMarketListingOptionGroup::TYPE_SINGLE,
                    'is_required' => false,
                    'min_select' => 0,
                    'sort_order' => $defaultSort,
                ];
            }

            $group->fill($attributes);
            $group->listing_id = $listing->id;
            $group->save();

            if (array_key_exists('options', $data) && is_array($data['options'])) {
                $this->syncOptions($group, $data['options'], $request, $filePrefix === '' ? 'options' : "{$filePrefix}.options");
            }

            return $group->fresh('options');
        });
    }

    /**
     * แทนที่ตัวเลือกทั้งกลุ่ม (id เดิม = แก้, ไม่มี id = สร้าง, ไม่ได้ส่ง = ลบ)
     */
    protected function syncOptions(FreshMarketListingOptionGroup $group, array $options, ?Request $request, string $filePrefix): void
    {
        if (count($options) > self::MAX_OPTIONS_PER_GROUP) {
            throw FreshMarketException::make('TOO_MANY_OPTIONS', 'ตัวเลือกในกลุ่มเดียวได้ไม่เกิน '.self::MAX_OPTIONS_PER_GROUP.' รายการ', 422);
        }

        $existing = FreshMarketListingOption::where('group_id', $group->id)->get()->keyBy('id');
        $keep = [];
        $position = 0;

        // key เดิมของฟอร์ม → หาไฟล์รูป {prefix}.{key}.image ได้ตรงแถว
        foreach ($options as $oi => $data) {
            if (! is_array($data)) {
                continue;
            }

            $option = isset($data['id']) ? $existing->get((int) $data['id']) : null;
            $data['sort_order'] ??= $position;
            $file = $request?->file("{$filePrefix}.{$oi}.image");
            $saved = $this->saveOption($group, $data, $option, $file instanceof UploadedFile ? $file : null, $position++);
            $keep[] = (int) $saved->id;
        }

        $removed = FreshMarketListingOption::where('group_id', $group->id)
            ->when(! empty($keep), fn ($q) => $q->whereNotIn('id', $keep));
        $removedImages = (clone $removed)->pluck('image_url')->filter()->all();
        $removed->delete();

        // ตัวเลือกที่ถูกลบ → ลบไฟล์รูปหลังบันทึกเสร็จ (เฉพาะไฟล์ที่ไม่มีตัวเลือก/ออเดอร์ไหนใช้แล้ว)
        $this->deleteImagesAfterCommit($removedImages, (int) $group->listing_id);
    }

    /**
     * สร้าง/แก้ไขตัวเลือกเดียว
     */
    public function saveOption(
        FreshMarketListingOptionGroup $group,
        array $data,
        ?FreshMarketListingOption $option = null,
        ?UploadedFile $image = null,
        int $defaultSort = 0
    ): FreshMarketListingOption {
        if ($option && (int) $option->group_id !== (int) $group->id) {
            throw FreshMarketException::make('OPTION_NOT_FOUND', 'ไม่พบตัวเลือกในกลุ่มนี้', 404);
        }

        if (! $option) {
            $count = FreshMarketListingOption::where('group_id', $group->id)->count();

            if ($count >= self::MAX_OPTIONS_PER_GROUP) {
                throw FreshMarketException::make('TOO_MANY_OPTIONS', 'ตัวเลือกในกลุ่มเดียวได้ไม่เกิน '.self::MAX_OPTIONS_PER_GROUP.' รายการ', 422);
            }

            $option = new FreshMarketListingOption([
                'price_delta' => 0,
                'is_available' => true,
                'sort_order' => $defaultSort,
            ]);
        }

        if (array_key_exists('name', $data)) {
            $option->name = mb_substr(trim((string) $data['name']), 0, 100);
        }
        if (array_key_exists('price_delta', $data) && $data['price_delta'] !== null && $data['price_delta'] !== '') {
            $option->price_delta = round(max(0, (float) $data['price_delta']), 2);
        }
        if (array_key_exists('is_available', $data) && $data['is_available'] !== null) {
            $option->is_available = filter_var($data['is_available'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $option->sort_order = (int) $data['sort_order'];
        }

        $listingId = (int) $group->listing_id;
        $oldImage = $option->exists ? $option->image_url : null;

        if ($image) {
            $option->image_url = $this->storeImage($image, $listingId);
        } elseif (! empty($data['remove_image']) && filter_var($data['remove_image'], FILTER_VALIDATE_BOOLEAN)) {
            $option->image_url = null;
        } elseif (! empty($data['image_url']) && (string) $data['image_url'] !== (string) $option->image_url) {
            // รับ image_url เฉพาะรูปที่สินค้านี้ใช้อยู่แล้ว หรือรูปคลังของระบบ
            // (กันร้านหนึ่งชี้ไปไฟล์ของร้านอื่น แล้วลบตัวเลือกเพื่อลบไฟล์ของเขา)
            $url = (string) $data['image_url'];

            if (! $this->imageUsableByListing($url, $listingId)) {
                throw FreshMarketException::make(
                    'INVALID_OPTION_IMAGE',
                    'ใช้ได้เฉพาะรูปที่อัปโหลดให้สินค้านี้หรือรูปจากคลังของระบบ กรุณาอัปโหลดรูปตัวเลือกใหม่',
                    422
                );
            }

            $option->image_url = $url;
        }

        $option->group_id = $group->id;
        $option->listing_id = $listingId;
        $option->save();

        // เปลี่ยน/เอารูปออก → ลบไฟล์เดิมหลังบันทึกเสร็จ (เฉพาะเมื่อไม่มีตัวเลือก/ออเดอร์ไหนใช้แล้ว)
        if ($oldImage && $oldImage !== $option->image_url) {
            $this->deleteImagesAfterCommit([$oldImage], $listingId);
        }

        return $option;
    }

    /**
     * อัปโหลดรูปตัวเลือก → path สาธารณะ (/storage/...)
     *
     * @param  int|null  $listingId  เก็บในโฟลเดอร์ของสินค้า (fresh-market/options/{listing_id}/) เพื่อให้ลบได้เฉพาะไฟล์ของสินค้านั้น
     */
    public function storeImage(UploadedFile $image, ?int $listingId = null): string
    {
        $dir = $listingId ? self::IMAGE_DIR.'/'.$listingId : self::IMAGE_DIR;
        $path = $image->store($dir, 'public');

        return '/storage/'.$path;
    }

    /**
     * รูปนี้ตั้งเป็นรูปตัวเลือกของสินค้านี้ได้หรือไม่
     *
     * ได้เฉพาะ: รูปคลังของระบบ (/images/taladsod/...) · รูปที่ตัวเลือกอื่นของสินค้านี้ใช้อยู่ · รูปสินค้านี้เอง
     */
    public function imageUsableByListing(string $url, int $listingId): bool
    {
        if (! self::isLocalImagePath($url)) {
            return false;
        }

        if (str_starts_with($url, self::STOCK_IMAGE_PREFIX)) {
            return true;
        }

        if (FreshMarketListingOption::where('listing_id', $listingId)->where('image_url', $url)->exists()) {
            return true;
        }

        $listing = DB::table('fresh_market_listings')->where('id', $listingId)->first(['main_image_url', 'images']);

        if (! $listing) {
            return false;
        }

        $images = is_string($listing->images) ? json_decode($listing->images, true) : null;

        return $listing->main_image_url === $url
            || (is_array($images) && in_array($url, $images, true));
    }

    /**
     * รูปอยู่ในเว็บเราเองหรือไม่ (/storage/... หรือ /images/...)
     */
    public static function isLocalImagePath(string $path): bool
    {
        return (bool) preg_match('#^/(storage|images)/[A-Za-z0-9._/\-]+$#', $path) && ! str_contains($path, '..');
    }

    // ╔══════════════════════════════════════════╗
    // ║  ลบไฟล์รูป (เฉพาะไฟล์ที่ไม่มีใครใช้แล้ว)   ║
    // ╚══════════════════════════════════════════╝

    /**
     * ลบรูปตัวเลือกที่อัปโหลดไว้ออกจาก disk — ปลอดภัยต่อการเรียกซ้ำ
     *
     * ลบจริงเฉพาะเมื่อ:
     * - เป็นไฟล์ในโฟลเดอร์รูปตัวเลือกของเรา (ไม่แตะ /images ของระบบ)
     * - ถ้าไฟล์อยู่ในโฟลเดอร์ของสินค้า (fresh-market/options/{id}/) ต้องเป็นสินค้าเจ้าของ ($ownerListingId)
     * - ไม่มีตัวเลือกไหน (ทุกสินค้า) และไม่มีรายการในออเดอร์ไหนอ้างถึงไฟล์นี้แล้ว (รูปในใบเสร็จ/ออเดอร์เก่าไม่หาย)
     *
     * @return bool true = ลบไฟล์แล้ว
     */
    public function deleteStoredImage(?string $url, ?int $ownerListingId = null): bool
    {
        if (! $url || ! self::isLocalImagePath($url) || ! str_starts_with($url, '/storage/'.self::IMAGE_DIR.'/')) {
            return false;
        }

        $relative = substr($url, strlen('/storage/'));

        if ($ownerListingId !== null
            && preg_match('#^'.preg_quote(self::IMAGE_DIR, '#').'/(\d+)/#', $relative, $m)
            && (int) $m[1] !== $ownerListingId) {
            return false;
        }

        return $this->deleteFileIfUnused($url, $relative);
    }

    /**
     * ลบรูปสินค้า (fresh-market/xxx.jpg) ที่เอาออกจากสินค้าแล้ว — เฉพาะเมื่อไม่มีสินค้า/ตัวเลือก/ออเดอร์ไหนใช้อยู่
     *
     * @return bool true = ลบไฟล์แล้ว
     */
    public function deleteListingImageIfUnused(?string $url): bool
    {
        $prefix = '/storage/'.self::LISTING_IMAGE_DIR.'/';

        if (! $url || ! self::isLocalImagePath($url) || ! str_starts_with($url, $prefix)) {
            return false;
        }

        // รูปตัวเลือกมีกติกาเจ้าของของมันเอง → ใช้ deleteStoredImage()
        if (str_starts_with($url, '/storage/'.self::IMAGE_DIR.'/')) {
            return false;
        }

        return $this->deleteFileIfUnused($url, substr($url, strlen('/storage/')));
    }

    /**
     * ยังมีข้อมูลไหนอ้างถึงรูปนี้อยู่หรือไม่ (ตัวเลือก · รายการในออเดอร์ · รูปสินค้า รวมสินค้าที่ลบแบบ soft delete)
     */
    public function isImageReferenced(string $url): bool
    {
        if (DB::table('fresh_market_listing_options')->where('image_url', $url)->exists()) {
            return true;
        }

        if (DB::table('fresh_market_order_items')->where('image_url', $url)->exists()) {
            return true;
        }

        return DB::table('fresh_market_listings')
            ->where(fn ($q) => $q->where('main_image_url', $url)->orWhereJsonContains('images', $url))
            ->exists();
    }

    /**
     * ลงคิวลบไฟล์รูปตัวเลือกหลัง transaction commit (rollback = ไม่ลบ)
     *
     * @param  array<int, string|null>  $urls
     */
    public function deleteImagesAfterCommit(array $urls, int $ownerListingId): void
    {
        $urls = array_values(array_unique(array_filter($urls, fn ($u) => is_string($u) && $u !== '')));

        if (empty($urls)) {
            return;
        }

        DB::afterCommit(function () use ($urls, $ownerListingId) {
            foreach ($urls as $url) {
                $this->deleteStoredImage($url, $ownerListingId);
            }
        });
    }

    /**
     * ลบไฟล์บน disk public เมื่อไม่มีใครอ้างถึงแล้ว
     */
    protected function deleteFileIfUnused(string $url, string $relative): bool
    {
        try {
            if ($this->isImageReferenced($url)) {
                return false;
            }

            return Storage::disk('public')->delete($relative);
        } catch (\Throwable $e) {
            // ลบไฟล์ไม่ได้ไม่ใช่เรื่องใหญ่ (ไฟล์ค้างดีกว่ารูปในออเดอร์หาย) — บันทึกไว้แล้วข้าม
            Log::warning('FreshMarket: ลบไฟล์รูปไม่สำเร็จ', ['url' => $url, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
