/**
 * จุดเริ่มแอป (custom entry ของ expo-router)
 *
 * ต้องประกาศ background task ตำแหน่งไรเดอร์ (TaskManager.defineTask ใน services/location)
 * ที่ global scope ก่อนแอปเริ่ม — ตอนระบบปลุกแอปขึ้นมาเบื้องหลังเพื่อส่งตำแหน่งระหว่างส่งงาน
 * จะได้หา task เจอเสมอ (ไม่ต้องรอหน้าจอใดๆ โหลด)
 */
import './services/location';
import 'expo-router/entry';
