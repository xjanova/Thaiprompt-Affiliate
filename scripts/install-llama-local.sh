#!/bin/bash
#
# Script สำหรับติดตั้ง Llama AI บน Local Server
# รองรับ CPU-only inference (ไม่ต้องใช้ GPU)
#
# วิธีใช้:
#   chmod +x scripts/install-llama-local.sh
#   ./scripts/install-llama-local.sh
#
# Requirements:
#   - Linux server (Ubuntu/Debian/CentOS)
#   - 8GB+ RAM (แนะนำ 16GB)
#   - 10GB+ disk space
#   - Internet connection (สำหรับดาวน์โหลด)
#

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║     🦙 Llama Local Installation Script                     ║"
echo "║     สำหรับ Thaiprompt Affiliate Platform                   ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# ตรวจสอบ RAM
TOTAL_RAM=$(free -g | awk '/^Mem:/{print $2}')
echo "📊 ตรวจสอบระบบ..."
echo "   RAM ทั้งหมด: ${TOTAL_RAM}GB"
echo "   CPU cores: $(nproc)"
echo ""

if [ "$TOTAL_RAM" -lt 8 ]; then
    echo "⚠️  WARNING: RAM น้อยกว่า 8GB อาจทำให้ระบบช้า"
    echo "   แนะนำ: ใช้ model ขนาดเล็ก (llama3.2:3b)"
fi

# Step 1: ติดตั้ง Ollama
echo ""
echo "📦 Step 1: ติดตั้ง Ollama..."
echo "─────────────────────────────────────"

if command -v ollama &> /dev/null; then
    echo "✅ Ollama ติดตั้งแล้ว: $(ollama --version)"
else
    echo "   กำลังติดตั้ง Ollama..."
    curl -fsSL https://ollama.com/install.sh | sh

    if command -v ollama &> /dev/null; then
        echo "✅ ติดตั้ง Ollama สำเร็จ!"
    else
        echo "❌ ติดตั้ง Ollama ล้มเหลว"
        exit 1
    fi
fi

# Step 2: เริ่ม Ollama service
echo ""
echo "🚀 Step 2: เริ่ม Ollama service..."
echo "─────────────────────────────────────"

# ลองใช้ systemd ก่อน
if command -v systemctl &> /dev/null; then
    sudo systemctl enable ollama 2>/dev/null || true
    sudo systemctl start ollama 2>/dev/null || true
fi

# ถ้า systemd ไม่ทำงาน ให้ start แบบ manual
if ! pgrep -x "ollama" > /dev/null; then
    echo "   เริ่ม Ollama ด้วยตนเอง..."
    nohup ollama serve > /dev/null 2>&1 &
    sleep 3
fi

if pgrep -x "ollama" > /dev/null; then
    echo "✅ Ollama service กำลังทำงาน"
else
    echo "❌ ไม่สามารถเริ่ม Ollama service ได้"
    echo "   ลอง: ollama serve"
    exit 1
fi

# Step 3: ดาวน์โหลด Llama model
echo ""
echo "📥 Step 3: ดาวน์โหลด Llama model..."
echo "─────────────────────────────────────"

# เลือก model ตาม RAM
if [ "$TOTAL_RAM" -ge 16 ]; then
    MODEL="llama3.1:8b"
    echo "   เลือก: Llama 3.1 8B (RAM เพียงพอสำหรับ model ใหญ่)"
elif [ "$TOTAL_RAM" -ge 8 ]; then
    MODEL="llama3.2:3b"
    echo "   เลือก: Llama 3.2 3B (เหมาะสมกับ RAM ที่มี)"
else
    MODEL="llama3.2:1b"
    echo "   เลือก: Llama 3.2 1B (สำหรับ RAM น้อย)"
fi

echo "   กำลังดาวน์โหลด ${MODEL}... (อาจใช้เวลา 5-30 นาที)"
ollama pull $MODEL

echo "✅ ดาวน์โหลด ${MODEL} สำเร็จ!"

# Step 4: ทดสอบ
echo ""
echo "🧪 Step 4: ทดสอบ Llama..."
echo "─────────────────────────────────────"

RESPONSE=$(curl -s http://localhost:11434/api/generate -d "{
  \"model\": \"$MODEL\",
  \"prompt\": \"สวัสดี ตอบสั้นๆ ว่าคุณพร้อมทำงานแล้ว\",
  \"stream\": false
}" | jq -r '.response // .error' 2>/dev/null || echo "Connection failed")

if [ "$RESPONSE" != "Connection failed" ] && [ -n "$RESPONSE" ]; then
    echo "✅ Llama ตอบกลับ: $RESPONSE"
else
    echo "⚠️  ไม่สามารถทดสอบได้ แต่ model ติดตั้งแล้ว"
fi

# Step 5: แสดงข้อมูลการตั้งค่า
echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║     ✅ ติดตั้งเสร็จสมบูรณ์!                                ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📝 การตั้งค่าสำหรับ Thaiprompt Affiliate:"
echo "─────────────────────────────────────"
echo ""
echo "   1. ไปที่ Admin Panel > AI Core > Providers"
echo ""
echo "   2. เปิดใช้งาน 'Meta Llama (Self-Hosted)'"
echo ""
echo "   3. ตั้งค่า:"
echo "      - API Endpoint: http://localhost:11434/v1"
echo "      - Model: ${MODEL}"
echo ""
echo "   4. ทดสอบการเชื่อมต่อ"
echo ""
echo "─────────────────────────────────────"
echo "🔧 Commands ที่มีประโยชน์:"
echo "   ollama list          # แสดง models ที่ติดตั้ง"
echo "   ollama pull <model>  # ดาวน์โหลด model เพิ่ม"
echo "   ollama run <model>   # รัน model แบบ interactive"
echo "   ollama serve         # เริ่ม API server"
echo ""
echo "📚 Models แนะนำ:"
echo "   llama3.2:1b   - 1B params, ~1GB RAM, เร็วมาก"
echo "   llama3.2:3b   - 3B params, ~4GB RAM, สมดุล"
echo "   llama3.1:8b   - 8B params, ~8GB RAM, คุณภาพดี"
echo "   llama3.1:70b  - 70B params, ~40GB RAM, ดีที่สุด"
echo ""
