<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TranslationMapping;
use App\Models\LanguageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TranslationMappingController extends Controller
{
    /**
     * แสดงหน้าจัดการคำแปล
     */
    public function index(Request $request)
    {
        $query = TranslationMapping::query();

        // Filter by language pair
        if ($request->filled('source_lang')) {
            $query->where('source_lang', $request->source_lang);
        }

        if ($request->filled('target_lang')) {
            $query->where('target_lang', $request->target_lang);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('translation', 'like', "%{$search}%")
                    ->orWhere('context', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $mappings = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $languages = LanguageSetting::getEnabled();

        return view('admin.translations.index', compact('mappings', 'languages'));
    }

    /**
     * แสดงฟอร์มสร้างคำแปลใหม่
     */
    public function create()
    {
        $languages = LanguageSetting::getEnabled();
        return view('admin.translations.create', compact('languages'));
    }

    /**
     * บันทึกคำแปลใหม่
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'source_lang' => 'required|string|max:10',
            'target_lang' => 'required|string|max:10',
            'translation' => 'required|string',
            'context' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        TranslationMapping::create([
            'key' => $request->key,
            'source_lang' => $request->source_lang,
            'target_lang' => $request->target_lang,
            'translation' => $request->translation,
            'context' => $request->context,
            'priority' => $request->priority ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.translations.index')
            ->with('success', 'เพิ่มคำแปลสำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไขคำแปล
     */
    public function edit(TranslationMapping $mapping)
    {
        $languages = LanguageSetting::getEnabled();
        return view('admin.translations.edit', compact('mapping', 'languages'));
    }

    /**
     * อัพเดทคำแปล
     */
    public function update(Request $request, TranslationMapping $mapping)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'source_lang' => 'required|string|max:10',
            'target_lang' => 'required|string|max:10',
            'translation' => 'required|string',
            'context' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $mapping->update([
            'key' => $request->key,
            'source_lang' => $request->source_lang,
            'target_lang' => $request->target_lang,
            'translation' => $request->translation,
            'context' => $request->context,
            'priority' => $request->priority ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.translations.index')
            ->with('success', 'อัพเดทคำแปลสำเร็จ');
    }

    /**
     * ลบคำแปล
     */
    public function destroy(TranslationMapping $mapping)
    {
        $mapping->delete();

        return redirect()->route('admin.translations.index')
            ->with('success', 'ลบคำแปลสำเร็จ');
    }

    /**
     * Toggle active status
     */
    public function toggle(TranslationMapping $mapping)
    {
        $mapping->update([
            'is_active' => !$mapping->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $mapping->is_active,
            'message' => $mapping->is_active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว',
        ]);
    }

    /**
     * Bulk import translations
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mappings' => 'required|array',
            'mappings.*.key' => 'required|string',
            'mappings.*.source_lang' => 'required|string|max:10',
            'mappings.*.target_lang' => 'required|string|max:10',
            'mappings.*.translation' => 'required|string',
            'mappings.*.context' => 'nullable|string',
            'mappings.*.priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $count = 0;
        foreach ($request->mappings as $data) {
            TranslationMapping::createOrUpdateMapping(
                $data['key'],
                $data['source_lang'],
                $data['target_lang'],
                $data['translation'],
                $data['context'] ?? null,
                $data['priority'] ?? 0
            );
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "นำเข้า {$count} คำแปลสำเร็จ",
            'count' => $count,
        ]);
    }

    /**
     * Export translations
     */
    public function export(Request $request)
    {
        $query = TranslationMapping::query();

        if ($request->filled('source_lang')) {
            $query->where('source_lang', $request->source_lang);
        }

        if ($request->filled('target_lang')) {
            $query->where('target_lang', $request->target_lang);
        }

        if ($request->filled('active_only')) {
            $query->where('is_active', true);
        }

        $mappings = $query->orderBy('priority', 'desc')->get();

        $data = $mappings->map(function ($mapping) {
            return [
                'key' => $mapping->key,
                'source_lang' => $mapping->source_lang,
                'target_lang' => $mapping->target_lang,
                'translation' => $mapping->translation,
                'context' => $mapping->context,
                'priority' => $mapping->priority,
                'is_active' => $mapping->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ]);
    }
}
