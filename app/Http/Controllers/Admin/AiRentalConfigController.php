<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRentalCloudConfig;
use App\Models\AiRentalCloudProvider;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * My Configurations Controller
 *
 * จัดการ Cloud GPU Configurations ของผู้ใช้ (API Keys, Settings)
 */
class AiRentalConfigController extends Controller
{
    /**
     * แสดงรายการ Configurations ของผู้ใช้
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // ดึง configs ของ user
        $query = AiRentalCloudConfig::where('user_id', $user->id)
            ->with('cloudProvider');

        // กรองตาม provider
        if ($providerId = $request->input('provider')) {
            $query->where('cloud_provider_id', $providerId);
        }

        // กรองตามสถานะ
        if ($request->has('active')) {
            $active = $request->input('active');
            $query->where('is_active', $active === '1');
        }

        $configs = $query->latest()->paginate(10)->withQueryString();

        // ดึง providers ทั้งหมดสำหรับ filter
        $providers = AiRentalCloudProvider::active()->ordered()->get();

        // สถิติ
        $stats = [
            'total' => AiRentalCloudConfig::where('user_id', $user->id)->count(),
            'active' => AiRentalCloudConfig::where('user_id', $user->id)->active()->count(),
            'has_error' => AiRentalCloudConfig::where('user_id', $user->id)->where('status', 'error')->count(),
        ];

        return view('admin.ai-rental.configs.index', compact('configs', 'providers', 'stats'));
    }

    /**
     * แสดงฟอร์มสร้าง Configuration ใหม่
     *
     * @return View
     */
    public function create(): View
    {
        // ดึง providers ทั้งหมดที่เปิดใช้งาน
        $providers = AiRentalCloudProvider::active()->ordered()->get();

        return view('admin.ai-rental.configs.create', compact('providers'));
    }

    /**
     * บันทึก Configuration ใหม่
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Validation
        $validated = $request->validate([
            'config_name' => 'required|string|max:255',
            'cloud_provider_id' => 'required|exists:ai_rental_cloud_providers,id',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'region' => 'nullable|string|max:100',
            'gpu_type' => 'nullable|string|max:100',
            'max_instances' => 'nullable|integer|min:1|max:10',
            'auto_shutdown' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // เช็คว่าชื่อซ้ำหรือไม่ (สำหรับ user คนนี้)
        $exists = AiRentalCloudConfig::where('user_id', $user->id)
            ->where('config_name', $validated['config_name'])
            ->where('cloud_provider_id', $validated['cloud_provider_id'])
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Configuration ชื่อนี้มีอยู่แล้วสำหรับ Provider นี้');
        }

        // สร้าง GPU config
        $gpuConfig = [
            'region' => $validated['region'] ?? null,
            'gpu_type' => $validated['gpu_type'] ?? null,
            'max_instances' => $validated['max_instances'] ?? 1,
            'auto_shutdown' => $validated['auto_shutdown'] ?? false,
        ];

        // สร้าง config
        $config = AiRentalCloudConfig::create([
            'user_id' => $user->id,
            'cloud_provider_id' => $validated['cloud_provider_id'],
            'config_name' => $validated['config_name'],
            'api_key' => $validated['api_key'] ?? null, // จะ encrypt อัตโนมัติใน model
            'api_secret' => $validated['api_secret'] ?? null,
            'gpu_config' => $gpuConfig,
            'is_active' => $validated['is_active'] ?? true,
            'is_default' => $validated['is_default'] ?? false,
            'status' => 'ready',
        ]);

        // ถ้าตั้งเป็น default ให้ยกเลิก default อื่นๆ
        if ($config->is_default) {
            $config->setAsDefault();
        }

        return redirect()
            ->route('admin.ai-rental.configs.index')
            ->with('success', "เพิ่ม Configuration '{$config->config_name}' สำเร็จ!");
    }

    /**
     * แสดงรายละเอียด Configuration
     *
     * @param AiRentalCloudConfig $config
     * @return View
     */
    public function show(AiRentalCloudConfig $config): View
    {
        // เช็คสิทธิ์
        $this->authorize('view', $config);

        // โหลด relationships
        $config->load(['cloudProvider', 'deployments']);

        // สถิติการใช้งาน
        $stats = [
            'total_deployments' => $config->deployments()->count(),
            'running_deployments' => $config->deployments()->running()->count(),
            'total_hours' => $config->deployments()->sum('total_hours'),
            'total_cost' => $config->deployments()->sum('total_cost'),
            'total_requests' => $config->deployments()->sum('total_requests'),
        ];

        return view('admin.ai-rental.configs.show', compact('config', 'stats'));
    }

    /**
     * แสดงฟอร์มแก้ไข Configuration
     *
     * @param AiRentalCloudConfig $config
     * @return View
     */
    public function edit(AiRentalCloudConfig $config): View
    {
        // เช็คสิทธิ์
        $this->authorize('update', $config);

        // ดึง providers
        $providers = AiRentalCloudProvider::active()->ordered()->get();

        return view('admin.ai-rental.configs.edit', compact('config', 'providers'));
    }

    /**
     * อัพเดท Configuration
     *
     * @param Request $request
     * @param AiRentalCloudConfig $config
     * @return RedirectResponse
     */
    public function update(Request $request, AiRentalCloudConfig $config): RedirectResponse
    {
        // เช็คสิทธิ์
        $this->authorize('update', $config);

        // Validation
        $validated = $request->validate([
            'config_name' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'region' => 'nullable|string|max:100',
            'gpu_type' => 'nullable|string|max:100',
            'max_instances' => 'nullable|integer|min:1|max:10',
            'auto_shutdown' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // เช็คชื่อซ้ำ (ยกเว้นตัวเอง)
        $exists = AiRentalCloudConfig::where('user_id', $config->user_id)
            ->where('config_name', $validated['config_name'])
            ->where('cloud_provider_id', $config->cloud_provider_id)
            ->where('id', '!=', $config->id)
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Configuration ชื่อนี้มีอยู่แล้ว');
        }

        // อัพเดท GPU config
        $gpuConfig = [
            'region' => $validated['region'] ?? null,
            'gpu_type' => $validated['gpu_type'] ?? null,
            'max_instances' => $validated['max_instances'] ?? 1,
            'auto_shutdown' => $validated['auto_shutdown'] ?? false,
        ];

        // อัพเดท
        $config->update([
            'config_name' => $validated['config_name'],
            'api_key' => $validated['api_key'] ?? $config->api_key,
            'api_secret' => $validated['api_secret'] ?? $config->api_secret,
            'gpu_config' => $gpuConfig,
            'is_active' => $validated['is_active'] ?? $config->is_active,
            'is_default' => $validated['is_default'] ?? $config->is_default,
        ]);

        // ถ้าตั้งเป็น default
        if ($config->is_default) {
            $config->setAsDefault();
        }

        return redirect()
            ->route('admin.ai-rental.configs.index')
            ->with('success', "แก้ไข Configuration '{$config->config_name}' สำเร็จ!");
    }

    /**
     * ลบ Configuration
     *
     * @param AiRentalCloudConfig $config
     * @return RedirectResponse
     */
    public function destroy(AiRentalCloudConfig $config): RedirectResponse
    {
        // เช็คสิทธิ์
        $this->authorize('delete', $config);

        // เช็คว่ามี deployments ที่กำลังรันอยู่หรือไม่
        if ($config->deployments()->running()->exists()) {
            return redirect()
                ->back()
                ->with('error', "ไม่สามารถลบได้ เนื่องจากมี deployments ที่กำลังรันอยู่");
        }

        $name = $config->config_name;

        // ลบ (soft delete)
        $config->delete();

        return redirect()
            ->route('admin.ai-rental.configs.index')
            ->with('success', "ลบ Configuration '{$name}' สำเร็จ!");
    }

    /**
     * ทดสอบการเชื่อมต่อ
     *
     * @param AiRentalCloudConfig $config
     * @return RedirectResponse
     */
    public function testConnection(AiRentalCloudConfig $config): RedirectResponse
    {
        // เช็คสิทธิ์
        $this->authorize('update', $config);

        // TODO: เพิ่ม logic ทดสอบการเชื่อมต่อกับ Provider
        // ตอนนี้แค่ update status เป็น ready

        $config->update([
            'status' => 'ready',
            'last_used_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'ทดสอบการเชื่อมต่อสำเร็จ! (Mock test)');
    }

    /**
     * ตั้งเป็น Default Configuration
     *
     * @param AiRentalCloudConfig $config
     * @return RedirectResponse
     */
    public function setDefault(AiRentalCloudConfig $config): RedirectResponse
    {
        // เช็คสิทธิ์
        $this->authorize('update', $config);

        $config->setAsDefault();

        return redirect()
            ->back()
            ->with('success', "ตั้ง '{$config->config_name}' เป็น Default แล้ว");
    }
}
