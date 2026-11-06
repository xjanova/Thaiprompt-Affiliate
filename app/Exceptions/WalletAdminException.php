<?php

namespace App\Exceptions;

use Exception;

/**
 * ข้อผิดพลาดจากการจัดการกระเป๋าเงินโดยแอดมินที่ "แสดงให้แอดมินเห็นได้"
 *
 * ข้อความเป็นภาษาไทยที่อ่านรู้เรื่อง — controller แสดง getMessage() ของ exception ชนิดนี้ได้ตรง ๆ
 * ส่วน exception ชนิดอื่น (เช่น QueryException) ต้อง log แล้วแสดงข้อความกลาง ห้ามโชว์ข้อความดิบ
 */
class WalletAdminException extends Exception {}
