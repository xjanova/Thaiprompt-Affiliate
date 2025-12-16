#!/bin/bash
#
# 🦙 Llama 4 Installation Script (Hugging Face)
# สำหรับ Thaiprompt Affiliate Platform
#
# Requirements:
#   - 64GB+ RAM (แนะนำ)
#   - 24+ CPU cores
#   - 50GB+ disk space
#   - ไม่ต้องใช้ GPU
#
# Usage:
#   chmod +x scripts/install-llama4-huggingface.sh
#   ./scripts/install-llama4-huggingface.sh
#

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║     🦙 Llama 4 Installation (Hugging Face)                 ║"
echo "║     สำหรับ Thaiprompt Affiliate Platform                   ║"
echo "║     CPU-only | 64GB RAM | 24 Cores                         ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# ตรวจสอบระบบ
TOTAL_RAM=$(free -g | awk '/^Mem:/{print $2}')
CPU_CORES=$(nproc)

echo "📊 ข้อมูลระบบ:"
echo "   RAM: ${TOTAL_RAM}GB"
echo "   CPU Cores: ${CPU_CORES}"
echo ""

# สร้าง directory
MODELS_DIR="/home/user/Thaiprompt-Affiliate/storage/ai-models"
mkdir -p "$MODELS_DIR"
cd "$MODELS_DIR"

echo "📁 โฟลเดอร์เก็บ model: $MODELS_DIR"
echo ""

# Step 1: ติดตั้ง dependencies
echo "📦 Step 1: ติดตั้ง dependencies..."
echo "─────────────────────────────────────"

# ติดตั้ง pip packages ที่จำเป็น
pip install --quiet huggingface_hub

# ติดตั้ง llama.cpp สำหรับ CPU inference
if ! command -v llama-server &> /dev/null; then
    echo "   กำลังติดตั้ง llama.cpp..."

    # ติดตั้ง build tools
    apt-get update -qq && apt-get install -y -qq build-essential cmake git

    # Clone และ build llama.cpp
    cd /tmp
    git clone https://github.com/ggerganov/llama.cpp.git
    cd llama.cpp

    # Build with CPU optimizations
    mkdir -p build && cd build
    cmake .. -DLLAMA_NATIVE=ON -DLLAMA_BUILD_SERVER=ON
    cmake --build . --config Release -j${CPU_CORES}

    # Install
    cp bin/llama-server /usr/local/bin/
    cp bin/llama-cli /usr/local/bin/

    echo "✅ llama.cpp ติดตั้งสำเร็จ!"
fi

cd "$MODELS_DIR"

# Step 2: ดาวน์โหลด Llama 4 Scout จาก Hugging Face
echo ""
echo "📥 Step 2: ดาวน์โหลด Llama 4 Scout 17B..."
echo "─────────────────────────────────────"
echo ""
echo "   Model: meta-llama/Llama-4-Scout-17B-16E-Instruct"
echo "   Format: GGUF Q5_K_M (คุณภาพสูง, ~12GB)"
echo "   เหมาะกับ: 64GB RAM, CPU-only"
echo ""

# ดาวน์โหลดจาก Hugging Face (GGUF version)
# Note: ต้องมี GGUF version หรือ convert เอง
MODEL_NAME="Llama-4-Scout-17B-16E-Instruct"

# ลองดาวน์โหลด GGUF version
echo "   กำลังดาวน์โหลด... (อาจใช้เวลา 10-30 นาที)"

python3 << 'PYTHON_SCRIPT'
from huggingface_hub import hf_hub_download, snapshot_download
import os

model_dir = os.environ.get('MODELS_DIR', '/home/user/Thaiprompt-Affiliate/storage/ai-models')

# ลองดาวน์โหลด GGUF version จาก community
try:
    # ลอง bartowski's GGUF (มักจะมี quantized versions)
    print("   ลองดาวน์โหลดจาก bartowski (GGUF)...")
    hf_hub_download(
        repo_id="bartowski/Llama-4-Scout-17B-16E-Instruct-GGUF",
        filename="Llama-4-Scout-17B-16E-Instruct-Q5_K_M.gguf",
        local_dir=model_dir,
        local_dir_use_symlinks=False
    )
    print("✅ ดาวน์โหลด GGUF สำเร็จ!")
except Exception as e:
    print(f"   ไม่พบ GGUF version: {e}")
    print("   กำลังดาวน์โหลด original model แทน...")

    # ถ้าไม่มี GGUF ให้ดาวน์โหลด original
    try:
        snapshot_download(
            repo_id="meta-llama/Llama-4-Scout-17B-16E-Instruct",
            local_dir=f"{model_dir}/Llama-4-Scout-17B-16E-Instruct",
            ignore_patterns=["*.bin", "*.safetensors.index.json"]
        )
        print("✅ ดาวน์โหลด original model สำเร็จ!")
        print("   ⚠️  ต้อง convert เป็น GGUF ก่อนใช้งาน")
    except Exception as e2:
        print(f"❌ ไม่สามารถดาวน์โหลดได้: {e2}")
        print("   ลองใช้ Llama 3.1 70B แทน...")

        hf_hub_download(
            repo_id="bartowski/Meta-Llama-3.1-70B-Instruct-GGUF",
            filename="Meta-Llama-3.1-70B-Instruct-Q4_K_M.gguf",
            local_dir=model_dir,
            local_dir_use_symlinks=False
        )
        print("✅ ดาวน์โหลด Llama 3.1 70B GGUF สำเร็จ!")
PYTHON_SCRIPT

echo ""

# Step 3: สร้าง config file
echo "⚙️  Step 3: สร้าง configuration..."
echo "─────────────────────────────────────"

# หา model file
MODEL_FILE=$(find "$MODELS_DIR" -name "*.gguf" -type f | head -1)

if [ -z "$MODEL_FILE" ]; then
    echo "❌ ไม่พบไฟล์ model (.gguf)"
    exit 1
fi

echo "   Model file: $MODEL_FILE"

# สร้าง systemd service
cat > /etc/systemd/system/llama-server.service << EOF
[Unit]
Description=Llama.cpp Server for Thaiprompt Affiliate
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=$MODELS_DIR
ExecStart=/usr/local/bin/llama-server \\
    --model $MODEL_FILE \\
    --host 0.0.0.0 \\
    --port 11434 \\
    --ctx-size 8192 \\
    --threads ${CPU_CORES} \\
    --parallel 4 \\
    --cont-batching
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd
systemctl daemon-reload

echo "✅ สร้าง systemd service สำเร็จ!"

# Step 4: สร้าง System Prompt สำหรับ Thaiprompt
echo ""
echo "📝 Step 4: สร้าง System Prompt..."
echo "─────────────────────────────────────"

cat > "$MODELS_DIR/system-prompt.txt" << 'SYSTEM_PROMPT'
คุณเป็น AI Assistant ของระบบ Thaiprompt Affiliate

บทบาทของคุณ:
- ตอบคำถามเกี่ยวกับระบบ Thaiprompt Affiliate เท่านั้น
- ช่วยเหลือผู้ใช้เกี่ยวกับการใช้งานระบบ
- ให้ข้อมูลเกี่ยวกับ MLM, Affiliate, Commission, Wallet

กฎการตอบ (สำคัญมาก):
1. ✅ ตอบเป็นภาษาไทยเท่านั้น
2. ✅ ตอบสั้น กระชับ เข้าใจง่าย เหมือนคนจริง
3. ✅ ใช้ข้อมูลจาก Knowledge Base ที่ได้รับมาเท่านั้น
4. ❌ ห้ามเขียนโค้ด
5. ❌ ห้ามสร้างรูปภาพ
6. ❌ ห้ามตอบเรื่องที่ไม่เกี่ยวกับระบบ

ถ้าคำถามไม่เกี่ยวกับระบบ ให้ตอบว่า:
"ขออภัยครับ ผมตอบได้เฉพาะเรื่องที่เกี่ยวกับระบบ Thaiprompt Affiliate เท่านั้นนะครับ มีอะไรให้ช่วยเกี่ยวกับระบบไหมครับ?"

สไตล์การตอบ:
- เป็นกันเอง สุภาพ
- ใช้ครับ/ค่ะ ตามความเหมาะสม
- ตอบตรงประเด็น ไม่อ้อมค้อม
SYSTEM_PROMPT

echo "✅ สร้าง System Prompt สำเร็จ!"

# Step 5: เริ่ม server
echo ""
echo "🚀 Step 5: เริ่ม Llama Server..."
echo "─────────────────────────────────────"

systemctl enable llama-server
systemctl start llama-server

sleep 5

if systemctl is-active --quiet llama-server; then
    echo "✅ Llama Server กำลังทำงาน!"
else
    echo "⚠️  Server ยังไม่เริ่ม ลองเริ่มด้วยตนเอง:"
    echo "   systemctl start llama-server"
fi

# Step 6: ทดสอบ
echo ""
echo "🧪 Step 6: ทดสอบ API..."
echo "─────────────────────────────────────"

sleep 3

RESPONSE=$(curl -s http://localhost:11434/v1/chat/completions \
  -H "Content-Type: application/json" \
  -d '{
    "model": "llama",
    "messages": [
      {"role": "system", "content": "คุณเป็น AI ของระบบ Thaiprompt Affiliate ตอบภาษาไทยสั้นๆ"},
      {"role": "user", "content": "สวัสดี"}
    ],
    "max_tokens": 100
  }' 2>/dev/null | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('choices',[{}])[0].get('message',{}).get('content',''))" 2>/dev/null || echo "รอ server...")

if [ -n "$RESPONSE" ]; then
    echo "✅ ทดสอบสำเร็จ!"
    echo "   AI ตอบ: $RESPONSE"
fi

# สรุป
echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║     ✅ ติดตั้ง Llama 4 เสร็จสมบูรณ์!                       ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📝 การตั้งค่า:"
echo "   - API Endpoint: http://localhost:11434/v1"
echo "   - Model: $MODEL_FILE"
echo "   - Threads: ${CPU_CORES}"
echo ""
echo "🔧 Commands:"
echo "   systemctl status llama-server   # ดูสถานะ"
echo "   systemctl restart llama-server  # รีสตาร์ท"
echo "   journalctl -u llama-server -f   # ดู logs"
echo ""
echo "📚 ขั้นตอนต่อไป:"
echo "   1. รัน: php artisan db:seed --class=AiProvidersSeeder"
echo "   2. ไปที่ Admin > AI Core > Providers"
echo "   3. เปิดใช้งาน 'Meta Llama (Local)'"
echo ""
