<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLineRichMenuRequest;
use App\Http\Requests\UpdateLineRichMenuRequest;
use App\Models\LineRichMenu;
use App\Services\LineRichMenuImageService;
use App\Services\WebPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LineRichMenuController extends Controller
{
    public function index()
    {
        $richMenus = LineRichMenu::orderBy('created_at', 'desc')->get();
        return view('admin.line-bot.rich-menus.index', compact('richMenus'));
    }

    public function create()
    {
        return view('admin.line-bot.rich-menus.create');
    }

    /**
     * สร้าง Rich Menu ใหม่
     *
     * @param StoreLineRichMenuRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreLineRichMenuRequest $request)
    {
        $validated = $request->validated();

        // Process และบันทึกภาพด้วย LineRichMenuImageService
        if ($request->hasFile('image')) {
            $imageService = app(LineRichMenuImageService::class);

            try {
                $result = $imageService->processAndStore(
                    $request->file('image'),
                    $validated['size'],
                    $request->input('needs_resize', false)
                );

                $validated['image_path'] = $result['path'];

                // Flash message บอกรายละเอียดภาพ
                session()->flash('info', sprintf(
                    '📸 ภาพถูกประมวลผลแล้ว: %dx%d px, ขนาด %s KB',
                    $result['width'],
                    $result['height'],
                    number_format($result['size_kb'], 2)
                ));

            } catch (\Exception $e) {
                return back()
                    ->withInput()
                    ->withErrors(['image' => 'เกิดข้อผิดพลาดในการประมวลผลภาพ: ' . $e->getMessage()]);
            }
        }

        // Decode areas JSON
        $validated['areas'] = json_decode($validated['areas'], true);
        $validated['created_by'] = Auth::id();

        // สร้าง Rich Menu
        $menu = LineRichMenu::create($validated);

        // ตั้งเป็น default ถ้าระบุ
        if ($request->input('is_default')) {
            $menu->setAsDefault();
        }

        return redirect()->route('admin.line-bot.rich-menu.index')
            ->with('success', '✅ สร้าง Rich Menu สำเร็จ!');
    }

    public function edit($id)
    {
        $richMenu = LineRichMenu::findOrFail($id);
        return view('admin.line-bot.rich-menus.edit', compact('richMenu'));
    }

    /**
     * อัปเดต Rich Menu
     *
     * @param UpdateLineRichMenuRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateLineRichMenuRequest $request, $id)
    {
        $menu = LineRichMenu::findOrFail($id);
        $validated = $request->validated();

        // Process และบันทึกภาพใหม่ (ถ้ามี)
        if ($request->hasFile('image')) {
            $imageService = app(LineRichMenuImageService::class);

            try {
                // ลบภาพเก่า
                if ($menu->image_path) {
                    $imageService->delete($menu->image_path);
                }

                // Process ภาพใหม่
                $result = $imageService->processAndStore(
                    $request->file('image'),
                    $validated['size'],
                    $request->input('needs_resize', false)
                );

                $validated['image_path'] = $result['path'];

                session()->flash('info', sprintf(
                    '📸 ภาพใหม่ถูกประมวลผลแล้ว: %dx%d px, ขนาด %s KB',
                    $result['width'],
                    $result['height'],
                    number_format($result['size_kb'], 2)
                ));

            } catch (\Exception $e) {
                return back()
                    ->withInput()
                    ->withErrors(['image' => 'เกิดข้อผิดพลาดในการประมวลผลภาพ: ' . $e->getMessage()]);
            }
        }

        // Decode areas JSON
        $validated['areas'] = json_decode($validated['areas'], true);

        // อัปเดต Rich Menu
        $menu->update($validated);

        // ตั้งเป็น default ถ้าระบุ (และยังไม่ใช่ default)
        if ($request->input('is_default') && !$menu->is_default) {
            $menu->setAsDefault();
        }

        return redirect()->route('admin.line-bot.rich-menu.index')
            ->with('success', '✅ อัปเดต Rich Menu สำเร็จ!');
    }

    public function destroy($id)
    {
        $menu = LineRichMenu::findOrFail($id);

        if ($menu->image_path) {
            Storage::disk('public')->delete($menu->image_path);
        }

        $menu->delete();

        return redirect()->route('admin.line-bot.rich-menu.index')
            ->with('success', 'ลบ Rich Menu สำเร็จ');
    }

    public function setDefault($id)
    {
        $menu = LineRichMenu::findOrFail($id);
        $menu->setAsDefault();

        return back()->with('success', 'ตั้งเป็น Rich Menu หลักสำเร็จ');
    }
}
