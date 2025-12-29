<?php

namespace App\Services;

/**
 * NFC Template Records Builder
 *
 * สร้าง NDEF records สำหรับ templates ต่างๆ
 * แยกออกมาจาก config เพื่อให้ config สามารถ serialize ได้
 */
class NfcTemplateRecordsBuilder
{
    /**
     * สร้าง records สำหรับ template ที่กำหนด
     *
     * @param  string  $templateKey  ชื่อ template
     * @param  array  $data  ข้อมูลสำหรับสร้าง records
     */
    public static function build(string $templateKey, array $data): array
    {
        return match ($templateKey) {
            'member_card' => self::buildMemberCard($data),
            'business_card' => self::buildBusinessCard($data),
            'product_info' => self::buildProductInfo($data),
            'access_card' => self::buildAccessCard($data),
            'wifi_share' => self::buildWifiShare($data),
            'url_shortcut' => self::buildUrlShortcut($data),
            'social_links' => self::buildSocialLinks($data),
            default => [],
        };
    }

    /**
     * สร้าง records สำหรับบัตรสมาชิก
     */
    public static function buildMemberCard(array $data): array
    {
        return [
            ['type' => 'text', 'data' => "MEMBER:{$data['member_id']}"],
            ['type' => 'text', 'data' => "NAME:{$data['member_name']}"],
            ['type' => 'text', 'data' => "CARD:{$data['card_number']}"],
            ['type' => 'text', 'data' => 'RANK:'.($data['rank'] ?? 'Member')],
            ['type' => 'text', 'data' => 'EXPIRE:'.($data['expiry_date'] ?? 'N/A')],
            ['type' => 'url', 'data' => url("/nfc/verify/{$data['card_number']}")],
        ];
    }

    /**
     * สร้าง records สำหรับนามบัตร
     */
    public static function buildBusinessCard(array $data): array
    {
        $records = [
            ['type' => 'text', 'data' => "NAME:{$data['name']}"],
            ['type' => 'text', 'data' => "TEL:{$data['phone']}"],
            ['type' => 'text', 'data' => "EMAIL:{$data['email']}"],
        ];

        if (! empty($data['position'])) {
            $records[] = ['type' => 'text', 'data' => "POSITION:{$data['position']}"];
        }

        if (! empty($data['company'])) {
            $records[] = ['type' => 'text', 'data' => "COMPANY:{$data['company']}"];
        }

        if (! empty($data['website'])) {
            $records[] = ['type' => 'url', 'data' => $data['website']];
        }

        // vCard
        $vcard = "BEGIN:VCARD\nVERSION:3.0\n";
        $vcard .= "FN:{$data['name']}\n";
        if (! empty($data['company'])) {
            $vcard .= "ORG:{$data['company']}\n";
        }
        if (! empty($data['position'])) {
            $vcard .= "TITLE:{$data['position']}\n";
        }
        $vcard .= "TEL:{$data['phone']}\n";
        $vcard .= "EMAIL:{$data['email']}\n";
        if (! empty($data['website'])) {
            $vcard .= "URL:{$data['website']}\n";
        }
        if (! empty($data['address'])) {
            $vcard .= "ADR:{$data['address']}\n";
        }
        $vcard .= 'END:VCARD';

        $records[] = ['type' => 'text', 'data' => $vcard, 'mediaType' => 'text/vcard'];

        return $records;
    }

    /**
     * สร้าง records สำหรับข้อมูลสินค้า
     */
    public static function buildProductInfo(array $data): array
    {
        $records = [
            ['type' => 'text', 'data' => "PRODUCT:{$data['product_name']}"],
            ['type' => 'text', 'data' => "SKU:{$data['sku']}"],
            ['type' => 'text', 'data' => "PRICE:{$data['price']} THB"],
        ];

        if (! empty($data['category'])) {
            $records[] = ['type' => 'text', 'data' => "CATEGORY:{$data['category']}"];
        }

        if (! empty($data['description'])) {
            $records[] = ['type' => 'text', 'data' => "DESC:{$data['description']}"];
        }

        if (! empty($data['product_url'])) {
            $records[] = ['type' => 'url', 'data' => $data['product_url']];
        }

        return $records;
    }

    /**
     * สร้าง records สำหรับบัตรเข้า-ออก
     */
    public static function buildAccessCard(array $data): array
    {
        return [
            ['type' => 'text', 'data' => "EMP:{$data['employee_id']}"],
            ['type' => 'text', 'data' => "NAME:{$data['employee_name']}"],
            ['type' => 'text', 'data' => 'DEPT:'.($data['department'] ?? 'N/A')],
            ['type' => 'text', 'data' => 'ACCESS:'.($data['access_level'] ?? 'Standard')],
            ['type' => 'text', 'data' => 'VALID:'.($data['valid_from'] ?? 'N/A').' - '.($data['valid_until'] ?? 'N/A')],
        ];
    }

    /**
     * สร้าง records สำหรับ WiFi Share
     */
    public static function buildWifiShare(array $data): array
    {
        // WiFi NDEF format: WIFI:T:WPA;S:MyNetwork;P:MyPassword;H:false;;
        $hidden = isset($data['hidden']) && $data['hidden'] ? 'true' : 'false';
        $wifiString = "WIFI:T:{$data['encryption']};S:{$data['ssid']};P:{$data['password']};H:{$hidden};;";

        return [
            ['type' => 'text', 'data' => $wifiString, 'mediaType' => 'application/vnd.wfa.wsc'],
        ];
    }

    /**
     * สร้าง records สำหรับ URL Shortcut
     */
    public static function buildUrlShortcut(array $data): array
    {
        $records = [];

        if (! empty($data['title'])) {
            $records[] = ['type' => 'text', 'data' => $data['title']];
        }

        $records[] = ['type' => 'url', 'data' => $data['url']];

        return $records;
    }

    /**
     * สร้าง records สำหรับ Social Media Links
     */
    public static function buildSocialLinks(array $data): array
    {
        $records = [
            ['type' => 'text', 'data' => "NAME:{$data['name']}"],
        ];

        if (! empty($data['facebook'])) {
            $records[] = ['type' => 'url', 'data' => $data['facebook']];
        }

        if (! empty($data['line'])) {
            $records[] = ['type' => 'text', 'data' => "LINE:{$data['line']}"];
        }

        if (! empty($data['instagram'])) {
            $records[] = ['type' => 'url', 'data' => $data['instagram']];
        }

        if (! empty($data['twitter'])) {
            $records[] = ['type' => 'url', 'data' => $data['twitter']];
        }

        if (! empty($data['tiktok'])) {
            $records[] = ['type' => 'url', 'data' => $data['tiktok']];
        }

        return $records;
    }
}
