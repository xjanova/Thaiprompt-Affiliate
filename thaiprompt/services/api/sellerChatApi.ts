/**
 * แชทออเดอร์ฝั่งร้าน — SellerOrderApiController (messages / sendMessage)
 *
 * - สัญญาเดียวกับแชทฝั่งผู้ซื้อ (shopApi getOrderMessages/sendOrderMessage): หน้า 1 = ข้อความล่าสุด เรียงใหม่ → เก่า
 * - ออเดอร์ร้านอื่น = 404 ORDER_NOT_FOUND · ไม่ใช่ร้าน = 403 NOT_A_SELLER · ออเดอร์ยกเลิก = 409 CHAT_CLOSED
 * - client_message_id กันส่งซ้ำเมื่อส่งใหม่หลังเน็ตหลุด (ส่งรหัสเดิม = ได้ข้อความเดิม — ฝั่งผู้ซื้อก็รองรับ)
 */

import { apiGet, apiPost, type ApiResult } from './client';
import type { OrderChatMessage, OrderChatPage } from '@/components/shop/OrderChatPanel';

export interface SellerOrderChatPage extends OrderChatPage {
  pagination: { current_page: number; last_page: number; per_page: number; total: number };
  chat?: { can_send: boolean; customer_name: string };
}

/** GET /seller/orders/{id}/messages — เปิดอ่าน = ข้อความลูกค้าถูกนับว่าอ่านแล้ว */
export const getSellerOrderMessages = (
  orderId: number,
  params: { page?: number; per_page?: number } = {}
): Promise<ApiResult<SellerOrderChatPage>> =>
  apiGet<SellerOrderChatPage>(`/seller/orders/${orderId}/messages`, params, {
    fallbackMessage: 'โหลดข้อความไม่สำเร็จ ลองใหม่อีกครั้งนะ',
  });

/** POST /seller/orders/{id}/messages (JSON ข้อความล้วน — timeout ปกติ ไม่ค้างนานแบบอัปโหลดไฟล์) */
export const sendSellerOrderMessage = (
  orderId: number,
  message: string,
  clientMessageId?: string
): Promise<ApiResult<OrderChatMessage>> =>
  apiPost<OrderChatMessage>(
    `/seller/orders/${orderId}/messages`,
    { message, ...(clientMessageId ? { client_message_id: clientMessageId } : {}) },
    { fallbackMessage: 'ส่งข้อความไม่สำเร็จ ลองใหม่อีกครั้งนะ' }
  );
