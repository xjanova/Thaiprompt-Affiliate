#!/bin/bash
# ======================================================
# 🔧 Script สำหรับรันบนเซิร์ฟเวอร์ เพื่อเตรียม SSH Deploy Key
# สำหรับ GitHub Actions Auto-Deploy
# ======================================================
#
# วิธีใช้:
#   ssh admin@your-server
#   bash setup-github-deploy.sh
#
# Script จะ:
# 1. สร้าง SSH key pair สำหรับ deploy
# 2. แสดงค่าที่ต้องใส่ใน GitHub Secrets
# ======================================================

echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   🔧 Setup GitHub Actions Deploy Key                     ║"
echo "║   สำหรับ Thaiprompt-Affiliate Auto-Deploy                ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# 1. Detect SSH Host (server IP)
echo "📡 กำลังตรวจหา Server IP..."
# Try multiple methods to get the real IP
SERVER_IP=""

# Method 1: hostname -I (most reliable on Linux)
if command -v hostname &>/dev/null; then
    SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}')
fi

# Method 2: ip addr (fallback)
if [ -z "$SERVER_IP" ] && command -v ip &>/dev/null; then
    SERVER_IP=$(ip -4 addr show scope global | grep inet | head -1 | awk '{print $2}' | cut -d/ -f1)
fi

# Method 3: curl external IP
if [ -z "$SERVER_IP" ]; then
    SERVER_IP=$(curl -s -4 ifconfig.me 2>/dev/null || curl -s -4 icanhazip.com 2>/dev/null)
fi

echo "   ✅ Server IP: ${SERVER_IP:-'ไม่พบ - กรุณาระบุเอง'}"

# 2. Detect SSH User
SSH_USER=$(whoami)
echo "   ✅ SSH User: $SSH_USER"

# 3. Detect SSH Port
SSH_PORT=$(grep -E "^Port " /etc/ssh/sshd_config 2>/dev/null | awk '{print $2}')
if [ -z "$SSH_PORT" ]; then
    SSH_PORT="22"
fi
echo "   ✅ SSH Port: $SSH_PORT"

# 4. Verify deploy path
DEPLOY_PATH="/home/admin/domains/main.thaiprompt.online/public_html"
if [ -d "$DEPLOY_PATH" ]; then
    echo "   ✅ Deploy Path: $DEPLOY_PATH (พบแล้ว)"
else
    echo "   ⚠️  Deploy Path: $DEPLOY_PATH (ไม่พบ! กรุณาตรวจสอบ)"
fi

# 5. Generate SSH key pair for deploy
KEY_NAME="github_deploy_tpaffiliate"
KEY_PATH="$HOME/.ssh/$KEY_NAME"

echo ""
echo "🔑 กำลังสร้าง SSH Deploy Key..."

if [ -f "$KEY_PATH" ]; then
    echo "   ⚠️  Key มีอยู่แล้วที่ $KEY_PATH"
    read -p "   ต้องการสร้างใหม่หรือไม่? (y/N): " RECREATE
    if [ "$RECREATE" = "y" ] || [ "$RECREATE" = "Y" ]; then
        rm -f "$KEY_PATH" "$KEY_PATH.pub"
    else
        echo "   ใช้ key เดิม"
    fi
fi

if [ ! -f "$KEY_PATH" ]; then
    ssh-keygen -t ed25519 -C "github-actions-deploy@thaiprompt-affiliate" -f "$KEY_PATH" -N "" -q
    echo "   ✅ สร้าง key สำเร็จ"
fi

# 6. Add public key to authorized_keys
if ! grep -qF "$(cat "$KEY_PATH.pub")" "$HOME/.ssh/authorized_keys" 2>/dev/null; then
    mkdir -p "$HOME/.ssh"
    chmod 700 "$HOME/.ssh"
    cat "$KEY_PATH.pub" >> "$HOME/.ssh/authorized_keys"
    chmod 600 "$HOME/.ssh/authorized_keys"
    echo "   ✅ เพิ่ม public key ใน authorized_keys แล้ว"
else
    echo "   ✅ Public key มีอยู่ใน authorized_keys แล้ว"
fi

# 7. Verify deploy.sh exists and is executable
if [ -f "$DEPLOY_PATH/deploy.sh" ]; then
    echo "   ✅ deploy.sh พบที่ $DEPLOY_PATH/deploy.sh"
    chmod +x "$DEPLOY_PATH/deploy.sh"
else
    echo "   ⚠️  deploy.sh ไม่พบที่ $DEPLOY_PATH/deploy.sh"
fi

# ======================================================
# OUTPUT: ค่าที่ต้องใส่ใน GitHub Secrets
# ======================================================
echo ""
echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   📋 ค่าสำหรับ GitHub Secrets                            ║"
echo "║   ก๊อปค่าด้านล่างไปให้ Claude                            ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""
echo "=========================================="
echo "SECRET: SSH_HOST"
echo "=========================================="
echo "$SERVER_IP"
echo ""
echo "=========================================="
echo "SECRET: SSH_USER"
echo "=========================================="
echo "$SSH_USER"
echo ""
echo "=========================================="
echo "SECRET: SSH_PORT"
echo "=========================================="
echo "$SSH_PORT"
echo ""
echo "=========================================="
echo "SECRET: SSH_PRIVATE_KEY"
echo "=========================================="
cat "$KEY_PATH"
echo ""
echo "=========================================="
echo "SECRET: DEPLOY_PATH (ตั้งแล้ว)"
echo "=========================================="
echo "$DEPLOY_PATH"
echo ""
echo "=========================================="
echo "SECRET: APP_URL (ตั้งแล้ว)"
echo "=========================================="
echo "https://main.thaiprompt.online"
echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   ✅ เสร็จแล้ว! ก๊อปทั้งหมดด้านบนส่งให้ Claude           ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""
