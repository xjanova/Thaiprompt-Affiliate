{{-- RunPod Setup Guide --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30">
    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
        <i class="fas fa-list-ol text-cyan-400"></i>
        ขั้นตอนการตั้งค่า RunPod
    </h3>

    <div class="space-y-4">
        {{-- Step 1 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                1
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้างบัญชี RunPod</h4>
                <p class="text-white/70 mb-2">
                    - ไปที่ <a href="https://www.runpod.io" target="_blank" class="text-cyan-400 hover:underline">runpod.io</a><br>
                    - คลิก "Sign Up"<br>
                    - สมัครด้วย Email, Google หรือ GitHub<br>
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
                    - คลิก "Billing" เมนูด้านซ้าย<br>
                    - เลือก "Add Funds"<br>
                    - เติมเงินขั้นต่ำ $10 (รองรับ Credit Card, Crypto)<br>
                    - รอ payment confirmation
                </p>
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                        <div class="text-blue-300 text-sm">
                            <strong>ราคา:</strong> เริ่มต้น $0.20/hr (RTX 3060 Ti) - $4.00/hr (A100 80GB)
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
                <h4 class="text-white font-bold mb-2">เช่า GPU Pod</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "Pods" เมนูด้านซ้าย<br>
                    - เลือก "Deploy" → "GPU Cloud"<br>
                    - เลือก GPU type ที่ต้องการ (RTX 3060 Ti, RTX 4090, A100)<br>
                    - เลือก Template (PyTorch, TensorFlow, Stable Diffusion, etc.)<br>
                    - กำหนด Disk size (10GB - 1000GB)<br>
                    - คลิก "Deploy On-Demand" หรือ "Deploy Spot" (ถูกกว่า 50%)
                </p>
            </div>
        </div>

        {{-- Step 4 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                4
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เชื่อมต่อกับ Pod</h4>
                <p class="text-white/70 mb-2">
                    - รอ Pod start up (30-60 วินาที)<br>
                    - คลิก "Connect" → เลือก method:<br>
                    &nbsp;&nbsp;• Jupyter Notebook (เปิดในเบราว์เซอร์)<br>
                    &nbsp;&nbsp;• Web Terminal<br>
                    &nbsp;&nbsp;• SSH (ใช้ key authentication)<br>
                    &nbsp;&nbsp;• HTTP/HTTPS (Deploy API)
                </p>
            </div>
        </div>

        {{-- Step 5 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                5
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">ตรวจสอบและใช้งาน GPU</h4>
                <p class="text-white/70 mb-2">เปิด Terminal และรันคำสั่ง:</p>
                <div class="bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-cyan-300 border border-white/10">
                    <code>
nvidia-smi<br>
python3 -c "import torch; print(f'GPU: {torch.cuda.get_device_name(0)}')"
                    </code>
                </div>
            </div>
        </div>

        {{-- Step 6 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                6
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">Pause/Stop Pod</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "Pause" เพื่อหยุดชั่วคราว (เก็บ storage ไว้)<br>
                    - คลิก "Stop" เพื่อหยุดแบบถาวร (ลบ Pod)<br>
                    - <strong class="text-yellow-300">⚠️ Pause จะยังเสียค่า storage!</strong>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Tips --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30 bg-blue-500/5">
    <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
        <i class="fas fa-lightbulb text-yellow-400"></i>
        💡 Tips สำหรับ RunPod
    </h3>
    <ul class="space-y-2 text-white/80">
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span><strong>Spot Pods</strong> ถูกกว่า On-Demand 50% แต่อาจโดน interrupt</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ "RunPod Network Storage" สำหรับเก็บข้อมูลแบบถาวร ($0.10/GB/เดือน)</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>Upload files ผ่าน Jupyter หรือใช้ <code>wget/git clone</code></span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ "GraphQL API" สำหรับ automation</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-exclamation-triangle text-yellow-400 mt-1"></i>
            <span><strong>สำคัญ:</strong> Stop Pod เมื่อไม่ใช้เพื่อประหยัดเงิน!</span>
        </li>
    </ul>
</div>
