<?php

namespace App\Contracts;

/**
 * Messaging Platform Interface
 *
 * Interface สำหรับ platform ที่ใช้ส่งข้อความ (Facebook, LINE, etc.)
 * ระบบดูดวงสามารถทำงานกับหลาย platform ได้
 */
interface MessagingPlatformInterface
{
    /**
     * ส่งข้อความตอบกลับ
     *
     * @param string $recipientId ID ของผู้รับ (PSID, LINE User ID, etc.)
     * @param string $message ข้อความที่จะส่ง
     * @param array $options ตัวเลือกเพิ่มเติม (quick_replies, buttons, etc.)
     * @return bool สำเร็จหรือไม่
     */
    public function sendMessage(string $recipientId, string $message, array $options = []): bool;

    /**
     * ส่งข้อความแบบ Rich (Flex Message, Template, etc.)
     *
     * @param string $recipientId ID ของผู้รับ
     * @param array $richContent เนื้อหาแบบ rich (รูปแบบตาม platform)
     * @return bool
     */
    public function sendRichMessage(string $recipientId, array $richContent): bool;

    /**
     * ส่งรูปภาพ
     *
     * @param string $recipientId ID ของผู้รับ
     * @param string $imageUrl URL ของรูปภาพ
     * @param string|null $previewUrl URL ของรูป preview (optional)
     * @return bool
     */
    public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null): bool;

    /**
     * ส่ง Quick Replies / Quick Action Buttons
     *
     * @param string $recipientId ID ของผู้รับ
     * @param string $message ข้อความ
     * @param array $quickReplies รายการ quick replies
     * @return bool
     */
    public function sendQuickReplies(string $recipientId, string $message, array $quickReplies): bool;

    /**
     * ดึงโปรไฟล์ผู้ใช้
     *
     * @param string $userId ID ของผู้ใช้
     * @return array|null โปรไฟล์ผู้ใช้ หรือ null ถ้าดึงไม่ได้
     */
    public function getUserProfile(string $userId): ?array;

    /**
     * ตรวจสอบว่า webhook event เป็นข้อความหรือไม่
     *
     * @param array $event Webhook event
     * @return bool
     */
    public function isMessageEvent(array $event): bool;

    /**
     * ดึงข้อความจาก webhook event
     *
     * @param array $event Webhook event
     * @return string|null ข้อความ หรือ null
     */
    public function getMessageText(array $event): ?string;

    /**
     * ดึง User ID จาก webhook event
     *
     * @param array $event Webhook event
     * @return string|null User ID
     */
    public function getUserIdFromEvent(array $event): ?string;

    /**
     * ดึงชื่อ platform
     *
     * @return string ชื่อ platform (facebook, line, etc.)
     */
    public function getPlatformName(): string;

    /**
     * ตรวจสอบว่า platform รองรับ Rich Message หรือไม่
     *
     * @return bool
     */
    public function supportsRichMessage(): bool;
}
