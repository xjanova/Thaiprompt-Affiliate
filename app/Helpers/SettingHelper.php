<?php

/**
 * Setting Helper Functions
 *
 * ฟังก์ชันช่วยสำหรับจัดการ Settings ในระบบ
 */

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * ดึงค่า setting จาก database
     *
     * @param  string  $key  คีย์ของ setting ที่ต้องการ
     * @param  mixed  $default  ค่าเริ่มต้นหากไม่พบ setting
     * @return mixed ค่าของ setting หรือค่าเริ่มต้น
     *
     * @example
     * setting('app_name', 'Default App')
     * setting('storefront_primary_color', '#F97316')
     */
    function setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('set_setting')) {
    /**
     * บันทึกค่า setting ลง database
     *
     * @param  string  $key  คีย์ของ setting
     * @param  mixed  $value  ค่าที่ต้องการบันทึก
     * @param  string  $type  ประเภทข้อมูล (string, boolean, integer, float, array, json)
     * @param  string  $group  กลุ่มของ setting
     * @return \App\Models\Setting
     *
     * @example
     * set_setting('storefront_primary_color', '#F97316', 'string', 'storefront')
     */
    function set_setting(string $key, $value, string $type = 'string', string $group = 'general')
    {
        return Setting::set($key, $value, $type, $group);
    }
}
