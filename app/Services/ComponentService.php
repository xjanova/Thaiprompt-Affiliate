<?php

namespace App\Services;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

/**
 * ComponentService
 *
 * Service สำหรับ render Arrow X components แบบ dynamic
 */
class ComponentService
{
    /**
     * Render component แบบ dynamic
     *
     * @param string $component ชื่อ component (e.g., 'arrow-x.button')
     * @param array $props Props สำหรับ component
     * @param string|null $slot เนื้อหาภายใน component
     * @return string HTML ที่ render แล้ว
     */
    public function render(string $component, array $props = [], ?string $slot = null): string
    {
        // สร้าง component tag
        $tag = "<x-{$component}";

        // เพิ่ม props
        foreach ($props as $key => $value) {
            if (is_bool($value)) {
                $tag .= $value ? " :{$key}=\"true\"" : " :{$key}=\"false\"";
            } elseif (is_array($value) || is_object($value)) {
                $encoded = htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8');
                $tag .= " :{$key}=\"json_decode('{$encoded}', true)\"";
            } else {
                $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $tag .= " {$key}=\"{$escaped}\"";
            }
        }

        $tag .= ">";

        // เพิ่ม slot content
        if ($slot !== null) {
            $tag .= $slot;
        }

        $tag .= "</x-{$component}>";

        // Render blade template
        return Blade::render($tag);
    }

    /**
     * Render button component
     *
     * @param string $text ข้อความปุ่ม
     * @param array $options ตัวเลือก
     * @return string
     */
    public function button(string $text, array $options = []): string
    {
        return $this->render('arrow-x.button', $options, $text);
    }

    /**
     * Render badge component
     *
     * @param string $text ข้อความ badge
     * @param array $options ตัวเลือก
     * @return string
     */
    public function badge(string $text, array $options = []): string
    {
        return $this->render('arrow-x.badge', $options, $text);
    }

    /**
     * Render alert component
     *
     * @param string $message ข้อความแจ้งเตือน
     * @param string $type ประเภท (success/warning/error/info)
     * @param array $options ตัวเลือกเพิ่มเติม
     * @return string
     */
    public function alert(string $message, string $type = 'info', array $options = []): string
    {
        $props = array_merge(['type' => $type], $options);
        return $this->render('arrow-x.alert', $props, $message);
    }

    /**
     * Render stat card component
     *
     * @param string $title หัวข้อ
     * @param string $value ค่าสถิติ
     * @param array $options ตัวเลือกเพิ่มเติม
     * @return string
     */
    public function statCard(string $title, string $value, array $options = []): string
    {
        $props = array_merge([
            'title' => $title,
            'value' => $value,
        ], $options);

        return $this->render('arrow-x.card.stat', $props);
    }

    /**
     * Render form input component
     *
     * @param string $name ชื่อ input
     * @param array $options ตัวเลือก
     * @return string
     */
    public function input(string $name, array $options = []): string
    {
        $props = array_merge(['name' => $name], $options);
        return $this->render('arrow-x.form.input', $props);
    }

    /**
     * Render table component
     *
     * @param array $headers หัวตาราง
     * @param string $rows HTML ของแถวข้อมูล
     * @param array $options ตัวเลือกเพิ่มเติม
     * @return string
     */
    public function table(array $headers, string $rows, array $options = []): string
    {
        $props = array_merge(['headers' => $headers], $options);
        return $this->render('arrow-x.table', $props, $rows);
    }

    /**
     * สร้าง component config สำหรับ JavaScript
     *
     * @param array $components รายการ components
     * @return string JSON config
     */
    public function generateJsConfig(array $components = []): string
    {
        $config = [
            'components' => $components,
            'theme' => [
                'colors' => [
                    'purple' => 'from-purple-500 via-pink-500 to-orange-500',
                    'blue' => 'from-blue-500 via-cyan-500 to-teal-500',
                    'green' => 'from-green-500 via-emerald-500 to-teal-500',
                    'orange' => 'from-orange-500 via-red-500 to-pink-500',
                    'pink' => 'from-pink-500 via-rose-500 to-red-500',
                    'red' => 'from-red-500 via-orange-500 to-yellow-500',
                ],
            ],
        ];

        return json_encode($config, JSON_PRETTY_PRINT);
    }

    /**
     * ตรวจสอบว่า component มีอยู่จริงหรือไม่
     *
     * @param string $component ชื่อ component
     * @return bool
     */
    public function exists(string $component): bool
    {
        // แปลง component name เป็น view path
        $viewPath = 'components.' . str_replace('.', '/', $component);

        return View::exists($viewPath);
    }

    /**
     * ดึงรายการ components ทั้งหมด
     *
     * @return array
     */
    public function getAvailableComponents(): array
    {
        return [
            'cards' => [
                'arrow-x.card.stat',
                'arrow-x.card.info',
                'arrow-x.card.gradient',
            ],
            'buttons' => [
                'arrow-x.button',
            ],
            'badges' => [
                'arrow-x.badge',
            ],
            'alerts' => [
                'arrow-x.alert',
            ],
            'forms' => [
                'arrow-x.form.input',
                'arrow-x.form.select',
                'arrow-x.form.checkbox',
            ],
            'navigation' => [
                'arrow-x.sidebar',
                'arrow-x.sidebar.item',
                'arrow-x.navbar',
                'arrow-x.navbar.notification',
                'arrow-x.navbar.user-menu',
            ],
            'data' => [
                'arrow-x.table',
                'arrow-x.modal',
            ],
        ];
    }

    /**
     * สร้าง preview HTML สำหรับ component
     *
     * @param string $component ชื่อ component
     * @param array $variants ตัวอย่าง variants
     * @return string
     */
    public function generatePreview(string $component, array $variants = []): string
    {
        $html = "<div class='space-y-4'>";
        $html .= "<h3 class='font-bold text-lg'>{$component}</h3>";

        if (empty($variants)) {
            // Default preview
            $html .= $this->render($component, [], 'Preview');
        } else {
            // Multiple variants
            foreach ($variants as $label => $props) {
                $html .= "<div>";
                $html .= "<p class='text-sm text-gray-600 mb-2'>{$label}</p>";
                $html .= $this->render($component, $props, $label);
                $html .= "</div>";
            }
        }

        $html .= "</div>";

        return $html;
    }
}
