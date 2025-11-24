{{-- Lightning AI Setup Guide --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30">
    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
        <i class="fas fa-list-ol text-cyan-400"></i>
        ขั้นตอนการตั้งค่า Lightning AI
    </h3>

    <div class="space-y-4">
        {{-- Step 1 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                1
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้างบัญชี Lightning AI</h4>
                <p class="text-white/70 mb-2">
                    - ไปที่ <a href="https://lightning.ai" target="_blank" class="text-cyan-400 hover:underline">lightning.ai</a><br>
                    - คลิก "Start for free"<br>
                    - สมัครด้วย GitHub, Google หรืออีเมล<br>
                    - ยืนยันอีเมล (ไม่ต้องใส่บัตรเครดิต!)
                </p>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                2
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้าง Studio ใหม่</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "Create" → "Studio"<br>
                    - เลือก template (Blank, PyTorch, TensorFlow, etc.)<br>
                    - ตั้งชื่อโปรเจค<br>
                    - เลือก "Free GPU" (T4 16GB VRAM)
                </p>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                3
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เปิด VSCode Environment</h4>
                <p class="text-white/70 mb-3">
                    - Lightning จะเปิด VSCode online ให้อัตโนมัติ<br>
                    - รอ environment setup (1-2 นาที)<br>
                    - จะได้ Terminal พร้อม GPU access
                </p>
                <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-gift text-green-400 mt-0.5"></i>
                        <div class="text-green-300 text-sm">
                            <strong>ฟรี:</strong> GPU T4 (16GB VRAM) + 100GB Storage + 22 GPU Hours/เดือน
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 4 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                4
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">ตรวจสอบ GPU</h4>
                <p class="text-white/70 mb-2">เปิด Terminal และรันคำสั่ง:</p>
                <div class="bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-cyan-300 border border-white/10">
                    <code>
nvidia-smi<br>
python3 -c "import torch; print(f'CUDA Available: {torch.cuda.is_available()}')"
                    </code>
                </div>
            </div>
        </div>

        {{-- Step 5 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                5
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">Upload โค้ดและข้อมูล</h4>
                <p class="text-white/70 mb-2">
                    - Drag & drop ไฟล์เข้า VSCode<br>
                    - หรือใช้ Git clone repository<br>
                    - ใช้ pip install packages ได้ตามต้องการ<br>
                    - ไฟล์จะถูกเก็บไว้ใน /content/
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Tips --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30 bg-blue-500/5">
    <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
        <i class="fas fa-lightbulb text-yellow-400"></i>
        💡 Tips สำหรับ Lightning AI
    </h3>
    <ul class="space-y-2 text-white/80">
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ "Requirements.txt" เพื่อติดตั้ง dependencies อัตโนมัติ</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>สามารถ Deploy แอพเป็น public URL ได้ฟรี!</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ "Lightning Data" สำหรับ Dataset ขนาดใหญ่</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>Session หมดอายุหลัง idle 10 นาที (ฟรี tier)</span>
        </li>
    </ul>
</div>
