{{-- Vast.ai Setup Guide --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30">
    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
        <i class="fas fa-list-ol text-cyan-400"></i>
        ขั้นตอนการตั้งค่า Vast.ai
    </h3>

    <div class="space-y-4">
        {{-- Step 1 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                1
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้างบัญชี Vast.ai</h4>
                <p class="text-white/70 mb-2">
                    - ไปที่ <a href="https://vast.ai" target="_blank" class="text-cyan-400 hover:underline">vast.ai</a><br>
                    - คลิก "Sign Up"<br>
                    - สมัครด้วย Email หรือ OAuth<br>
                    - ยืนยันอีเมล
                </p>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                2
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เติมเครดิต</h4>
                <p class="text-white/70 mb-3">
                    - คลิก "Account" → "Add Credit"<br>
                    - เติมเงินขั้นต่ำ $5<br>
                    - รองรับ Credit Card, Crypto (Bitcoin, Ethereum)
                </p>
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-dollar-sign text-blue-400 mt-0.5"></i>
                        <div class="text-blue-300 text-sm">
                            <strong>ราคาถูกที่สุด:</strong> $0.10/hr (RTX 3060) - $1.50/hr (A100)
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                3
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เลือก GPU Instance</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "Console" เมนูด้านบน<br>
                    - เลือก GPU type และ VRAM ขั้นต่ำ<br>
                    - กรองตาม:<br>
                    &nbsp;&nbsp;• Reliability Score (⭐ ≥ 95%)<br>
                    &nbsp;&nbsp;• DLPerf (benchmark score)<br>
                    &nbsp;&nbsp;• Max Upload/Download Speed<br>
                    - เรียงตามราคาถูกสุด
                </p>
            </div>
        </div>

        {{-- Step 4 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                4
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">Rent Instance</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "RENT" ที่ instance ที่ต้องการ<br>
                    - เลือก Docker Image:<br>
                    &nbsp;&nbsp;• PyTorch (pytorch/pytorch:latest)<br>
                    &nbsp;&nbsp;• TensorFlow (tensorflow/tensorflow:latest-gpu)<br>
                    &nbsp;&nbsp;• Custom Image<br>
                    - กำหนด Disk Space (10GB - 500GB)<br>
                    - เลือก "On-Demand" หรือ "Interruptible" (ถูกกว่า)<br>
                    - คลิก "Create"
                </p>
            </div>
        </div>

        {{-- Step 5 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                5
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เชื่อมต่อผ่าน SSH</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "Connect" → copy SSH command:<br>
                </p>
                <div class="bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-cyan-300 border border-white/10">
                    <code>
ssh -p PORT_NUMBER root@SERVER_IP
                    </code>
                </div>
                <p class="text-white/70 mt-2">
                    - หรือใช้ Jupyter Notebook (ถ้า template รองรับ)<br>
                    - เข้าผ่าน browser: http://SERVER_IP:8080
                </p>
            </div>
        </div>

        {{-- Step 6 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                6
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">ตรวจสอบและ Destroy Instance</h4>
                <p class="text-white/70 mb-2">
                    - รันคำสั่ง <code>nvidia-smi</code> เพื่อตรวจสอบ GPU<br>
                    - เมื่อใช้งานเสร็จ คลิก "Destroy" ใน Console<br>
                    - <strong class="text-yellow-300">⚠️ ต้อง Destroy ไม่งั้นเสียเงินต่อเนื่อง!</strong>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Tips --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30 bg-blue-500/5">
    <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
        <i class="fas fa-lightbulb text-yellow-400"></i>
        💡 Tips สำหรับ Vast.ai
    </h3>
    <ul class="space-y-2 text-white/80">
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span><strong>Marketplace:</strong> เช่า GPU จากคนทั่วไป → ราคาถูกกว่าที่อื่น!</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>เลือก Reliability Score ≥ 95% เพื่อหลีกเลี่ยงปัญหา downtime</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ "Interruptible" mode สำหรับงานที่รัน checkpoint ได้</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>Upload files ผ่าน <code>scp</code> หรือ <code>rsync</code></span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-exclamation-triangle text-yellow-400 mt-1"></i>
            <span><strong>สำคัญ:</strong> Backup ข้อมูลก่อน Destroy! (ไม่มี persistent storage)</span>
        </li>
    </ul>
</div>
