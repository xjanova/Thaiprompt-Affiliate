<?php

namespace App\Services;

/**
 * Menu Registrar
 *
 * รวบรวมเมนูจาก Feature Providers
 * ทำให้แต่ละฟีเจอร์สามารถ register เมนูของตัวเองได้
 *
 * @version 3.0.0
 * @since 2025-11-15
 */
class MenuRegistrar
{
    /**
     * เมนูที่ register แล้วทั้งหมด
     *
     * @var array
     */
    protected $registeredMenus = [
        'admin' => [],
        'seller' => [],
        'user' => [],
    ];

    /**
     * Providers ที่ register แล้ว
     *
     * @var array
     */
    protected $providers = [];

    /**
     * Register Menu Provider
     *
     * @param object $provider Provider instance ที่มี method registerMenus()
     * @return void
     *
     * @example
     * $registrar->registerProvider(new GameMenuProvider());
     */
    public function registerProvider($provider): void
    {
        if (!method_exists($provider, 'registerMenus')) {
            throw new \InvalidArgumentException(
                'Provider must have registerMenus() method'
            );
        }

        $this->providers[] = $provider;

        // รวบรวมเมนูจาก provider
        $menus = $provider->registerMenus();

        foreach ($menus as $role => $roleMenus) {
            if (isset($this->registeredMenus[$role])) {
                $this->registeredMenus[$role] = array_merge(
                    $this->registeredMenus[$role],
                    $roleMenus
                );
            }
        }
    }

    /**
     * Register เมนูโดยตรง (ไม่ผ่าน Provider)
     *
     * @param string $role Role ที่จะเพิ่มเมนู
     * @param array $menus รายการเมนู
     * @return void
     *
     * @example
     * $registrar->registerMenus('admin', [
     *     ['id' => 'custom', 'label' => 'Custom Menu', 'route' => 'custom.index']
     * ]);
     */
    public function registerMenus(string $role, array $menus): void
    {
        if (!isset($this->registeredMenus[$role])) {
            $this->registeredMenus[$role] = [];
        }

        $this->registeredMenus[$role] = array_merge(
            $this->registeredMenus[$role],
            $menus
        );
    }

    /**
     * ดึงเมนูทั้งหมดสำหรับ role ที่กำหนด
     *
     * @param string $role Role ของผู้ใช้
     * @return array เมนูทั้งหมด
     */
    public function getMenusForRole(string $role): array
    {
        return $this->registeredMenus[$role] ?? [];
    }

    /**
     * ดึง providers ทั้งหมดที่ register แล้ว
     *
     * @return array
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * ล้างเมนูทั้งหมดสำหรับ role ที่กำหนด
     *
     * @param string $role Role ที่จะล้าง
     * @return void
     */
    public function clearMenus(string $role): void
    {
        $this->registeredMenus[$role] = [];
    }

    /**
     * ล้างเมนูทั้งหมดทุก role
     *
     * @return void
     */
    public function clearAllMenus(): void
    {
        $this->registeredMenus = [
            'admin' => [],
            'seller' => [],
            'user' => [],
        ];
        $this->providers = [];
    }

    /**
     * นับจำนวนเมนูที่ register แล้วสำหรับ role ที่กำหนด
     *
     * @param string $role
     * @return int
     */
    public function count(string $role): int
    {
        return count($this->registeredMenus[$role] ?? []);
    }

    /**
     * ตรวจสอบว่ามีเมนูสำหรับ role หรือไม่
     *
     * @param string $role
     * @return bool
     */
    public function hasMenus(string $role): bool
    {
        return !empty($this->registeredMenus[$role]);
    }
}
