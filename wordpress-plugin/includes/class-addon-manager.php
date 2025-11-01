<?php
/**
 * Add-on Management Class
 *
 * จัดการ Add-ons สำหรับ TP-Affiliate Pro
 * - ตรวจสอบ Core License ก่อนเปิดใช้ Add-on
 * - จัดการ License แยกสำหรับแต่ละ Add-on
 * - API สำหรับตรวจสอบ Add-on
 */

class TP_License_Addon_Manager
{
    /**
     * Add-ons ที่รองรับ
     */
    private static $available_addons = array(
        'mlm' => array(
            'name' => 'MLM Add-on',
            'slug' => 'tp-affiliate-mlm',
            'description' => 'ระบบ Multi-Level Marketing สำหรับ TP-Affiliate',
            'version' => '1.0.0',
            'price' => 4900,
            'requires_core' => '1.0.0',
        ),
        'payment-gateway' => array(
            'name' => 'Payment Gateway Add-on',
            'slug' => 'tp-affiliate-payment',
            'description' => 'เชื่อมต่อ Payment Gateway หลายช่องทาง',
            'version' => '1.0.0',
            'price' => 2900,
            'requires_core' => '1.0.0',
        ),
        'analytics' => array(
            'name' => 'Advanced Analytics Add-on',
            'slug' => 'tp-affiliate-analytics',
            'description' => 'วิเคราะห์ข้อมูลแบบละเอียด',
            'version' => '1.0.0',
            'price' => 1900,
            'requires_core' => '1.0.0',
        ),
    );

    /**
     * รายการ Add-ons ทั้งหมด
     */
    public static function get_available_addons()
    {
        return self::$available_addons;
    }

    /**
     * ตรวจสอบว่า Add-on มีอยู่หรือไม่
     */
    public static function addon_exists($addon_slug)
    {
        return isset(self::$available_addons[$addon_slug]);
    }

    /**
     * ข้อมูล Add-on
     */
    public static function get_addon_info($addon_slug)
    {
        return self::$available_addons[$addon_slug] ?? null;
    }

    /**
     * ตรวจสอบว่ามี Core License หรือไม่
     */
    public static function has_core_license($customer_email)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        $core_license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE customer_email = %s AND license_type = 'core' AND status = 'active'",
            $customer_email
        ));

        return $core_license !== null;
    }

    /**
     * ตรวจสอบว่าเปิดใช้งาน Add-on แล้วหรือยัง
     */
    public static function is_addon_activated($license_key, $addon_slug)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        // ค้นหา addon license
        $addon_license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s AND license_type = %s AND status = 'active'",
            $license_key,
            $addon_slug
        ));

        if (!$addon_license) {
            return false;
        }

        // ตรวจสอบว่ามี Core License หรือไม่
        if ($addon_license->parent_license_id) {
            $core_license = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND status = 'active'",
                $addon_license->parent_license_id
            ));

            return $core_license !== null;
        }

        return false;
    }

    /**
     * รายการ Add-ons ที่เปิดใช้งาน
     */
    public static function get_activated_addons($license_key)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        // ค้นหา core license
        $core_license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s AND license_type = 'core'",
            $license_key
        ));

        if (!$core_license) {
            return array();
        }

        // ค้นหา add-ons ที่เชื่อมโยงกับ core license
        $addon_licenses = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE parent_license_id = %d AND status = 'active'",
            $core_license->id
        ));

        $activated_addons = array();

        foreach ($addon_licenses as $addon_license) {
            $addon_info = self::get_addon_info($addon_license->license_type);

            if ($addon_info) {
                $activated_addons[] = array(
                    'slug' => $addon_license->license_type,
                    'name' => $addon_info['name'],
                    'version' => $addon_info['version'],
                    'license_key' => $addon_license->license_key,
                    'activated_at' => $addon_license->activated_at,
                    'expires_at' => $addon_license->expires_at,
                );
            }
        }

        return $activated_addons;
    }

    /**
     * สร้าง Add-on License หลังการซื้อ
     */
    public static function generate_addon_license($core_license_id, $addon_slug, $order_id, $expires_days = 365)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        // ตรวจสอบ Core License
        $core_license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $core_license_id
        ));

        if (!$core_license) {
            return new WP_Error('invalid_core_license', 'Core license not found');
        }

        // ตรวจสอบว่า Add-on มีอยู่จริง
        if (!self::addon_exists($addon_slug)) {
            return new WP_Error('invalid_addon', 'Add-on does not exist');
        }

        // สร้าง License Key สำหรับ Add-on
        $license_key = self::generate_license_key($addon_slug);

        // บันทึก License
        $wpdb->insert(
            $table,
            array(
                'license_key' => $license_key,
                'product_id' => 0, // TODO: Get from WooCommerce
                'order_id' => $order_id,
                'customer_email' => $core_license->customer_email,
                'customer_name' => $core_license->customer_name,
                'license_type' => $addon_slug,
                'parent_license_id' => $core_license_id,
                'status' => 'active',
                'domain' => $core_license->domain,
                'ip_address' => $core_license->ip_address,
                'installation_id' => $core_license->installation_id,
                'max_activations' => 1,
                'activation_count' => 0,
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expires_days} days")),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );

        if ($wpdb->insert_id) {
            return array(
                'license_key' => $license_key,
                'addon_slug' => $addon_slug,
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expires_days} days")),
            );
        }

        return new WP_Error('failed_to_create', 'Failed to create add-on license');
    }

    /**
     * สร้าง License Key สำหรับ Add-on
     */
    private static function generate_license_key($addon_slug)
    {
        $prefix = strtoupper(substr($addon_slug, 0, 3));
        $random = strtoupper(bin2hex(random_bytes(16)));

        return sprintf(
            '%s-%s-%s-%s-%s',
            $prefix,
            substr($random, 0, 4),
            substr($random, 4, 4),
            substr($random, 8, 4),
            substr($random, 12, 4)
        );
    }

    /**
     * เพิ่ม Add-on Product Types ใน WooCommerce
     */
    public static function register_addon_product_types()
    {
        foreach (self::$available_addons as $slug => $addon) {
            // สามารถสร้าง Custom Product Type สำหรับแต่ละ Add-on ได้
        }
    }
}
