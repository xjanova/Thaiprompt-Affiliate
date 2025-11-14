#!/bin/bash

echo "🧹 กำลังล้าง cache ทั้งหมด..."

# ลบไฟล์ cache โดยตรง (ไม่ต้องใช้ artisan)
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*

echo "✅ ล้าง cache เสร็จแล้ว!"
echo ""
echo "📌 กรุณาทำตามนี้:"
echo "   1. รีโหลดเว็บเบราว์เซอร์ (Ctrl+Shift+R หรือ Cmd+Shift+R)"
echo "   2. ลอง logout แล้ว login ใหม่"
echo "   3. คลิกเมนู TPIX Blockchain หรือ Token Management"
echo ""
