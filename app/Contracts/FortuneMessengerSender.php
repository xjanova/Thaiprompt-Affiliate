<?php

namespace App\Contracts;

/**
 * ✈️ ผู้ส่งข้อความ "ทรง Messenger" — ชุดเมธอดที่ตัวเรนเดอร์ Facebook ของ FortuneChannelManager เรียกจริง
 *
 * ## ทำไมต้องมี (2026-09-13)
 * ตัวเรนเดอร์ FB (`sendFacebookResponse` + helper ~25 ตัว) ครอบ action ~230 แบบของแม่หมอ
 * ทั้งบิล QR · เมนูราคา · Celtic · บับเบิ้ล · เสียง — แต่เรียกบริการส่งแค่ 8 เมธอดข้างล่างนี้
 * (นับจาก `$fbService->` ทุกจุดในไฟล์)
 *
 * ⇒ บริการไหนที่ทำ 8 เมธอดนี้ได้ = ใช้ตัวเรนเดอร์ FB ทั้งก้อนได้ทันที ไม่ต้องเขียนเรนเดอร์ใหม่
 *   - FacebookWebhookService  → Graph API (ของเดิม ไม่เปลี่ยนพฤติกรรม)
 *   - TelegramFortuneService  → แปลง quick reply / button template เป็นปุ่ม inline ของ Telegram
 *
 * ⚠️ ตัวเรนเดอร์ต้องไม่ hardcode 'facebook' — ใช้ `getPlatformName()` แทนทุกจุดที่ส่งชื่อช่องทางต่อ
 *    (ไม่งั้น job/ตัวนับที่แตกออกไปจะผูกลูกค้า Telegram เป็นลูกค้า FB)
 *
 * รูปทรงข้อมูลเป็นของ Facebook ทั้งหมด (quick reply = ['title','payload'], template = attachment.payload)
 */
interface FortuneMessengerSender
{
    /**
     * ส่งข้อความตัวอักษร — options['quick_replies'] = แนบปุ่ม
     */
    public function sendMessage(string $recipientId, string $message, array $options = []): bool;

    /**
     * ส่งข้อความพร้อมปุ่มตัวเลือก — ปุ่ม = [['title' => ..., 'payload' => ...]] (รับทรง LINE label/text ด้วย)
     */
    public function sendQuickReplies(string $recipientId, string $message, array $quickReplies, array $options = []): bool;

    /**
     * ส่งรูปจาก URL สาธารณะ
     */
    public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null, array $options = []): bool;

    /**
     * ส่ง template ทรง FB (`attachment.payload.template_type` = button | generic)
     */
    public function sendButtonTemplate(string $recipientId, array $templatePayload, array $options = []): bool;

    /**
     * ส่งการ์ด generic (รูป + หัวข้อ + ปุ่ม)
     */
    public function sendGenericTemplate(string $recipientId, array $elements, array $options = []): bool;

    /**
     * ส่งไฟล์เสียงจาก URL สาธารณะ
     */
    public function sendAudio(string $recipientId, string $audioUrl, array $options = []): bool;

    /**
     * สถานะ "กำลังพิมพ์..."
     */
    public function sendTypingIndicator(string $recipientId, bool $on = true): void;

    /**
     * ชื่อช่องทาง ('facebook' / 'telegram') — ใช้ส่งต่อให้ job/ตัวนับ แทนการ hardcode
     */
    public function getPlatformName(): string;
}
