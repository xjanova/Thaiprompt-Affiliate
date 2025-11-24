{{-- Lambda Labs Setup Guide --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30">
    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
        <i class="fas fa-list-ol text-cyan-400"></i>
        ขั้นตอนการตั้งค่า Lambda Labs
    </h3>

    <div class="space-y-4">
        {{-- Step 1 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                1
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้างบัญชี Lambda Labs</h4>
                <p class="text-white/70 mb-2">
                    - ไปที่ <a href="https://cloud.lambdalabs.com" target="_blank" class="text-cyan-400 hover:underline">cloud.lambdalabs.com</a><br>
                    - คลิก "Sign Up"<br>
                    - สมัครด้วย Email<br>
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
                    - คลิก "Add Credit"<br>
                    - เติมเงินขั้นต่ำ $25<br>
                    - ใช้ Credit Card (Visa, Mastercard, Amex)
                </p>
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-dollar-sign text-blue-400 mt-0.5"></i>
                        <div class="text-blue-300 text-sm">
                            <strong>ราคา:</strong> $1.10/hr (A100 40GB) - $1.50/hr (A100 80GB)
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
                <h4 class="text-white font-bold mb-2">เพิ่ม SSH Key</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "SSH Keys" เมนูด้านซ้าย<br>
                    - คลิก "Add SSH Key"<br>
                    - Paste public key ของคุณ (id_rsa.pub)<br>
                    - ตั้งชื่อ key และคลิก "Add Key"
                </p>
                <div class="bg-gray-900/50 rounded-lg p-3 font-mono text-xs text-cyan-300 border border-white/10">
                    <code>
# สร้าง SSH key (ถ้ายังไม่มี):<br>
ssh-keygen -t rsa -b 4096<br>
cat ~/.ssh/id_rsa.pub
                    </code>
                </div>
            </div>
        </div>

        {{-- Step 4 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                4
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">Launch Instance</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "Instances" เมนูด้านซ้าย<br>
                    - คลิก "Launch Instance"<br>
                    - เลือก GPU type (A100 40GB / 80GB)<br>
                    - เลือก Region (US-West, US-East, US-South)<br>
                    - เลือก SSH Key ที่เพิ่มไว้<br>
                    - คลิก "Launch Instance"
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
                    - รอ instance start (1-2 นาที)<br>
                    - Copy SSH command จาก instance detail:<br>
                </p>
                <div class="bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-cyan-300 border border-white/10">
                    <code>
ssh ubuntu@IP_ADDRESS
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
                <h4 class="text-white font-bold mb-2">ตรวจสอบและ Terminate</h4>
                <p class="text-white/70 mb-2">
                    - รันคำสั่ง <code>nvidia-smi</code> เพื่อตรวจสอบ GPU<br>
                    - PyTorch, TensorFlow, CUDA ติดตั้งไว้แล้ว!<br>
                    - เมื่อใช้เสร็จ → คลิก "Terminate Instance"<br>
                    - <strong class="text-yellow-300">⚠️ Terminate ทันทีเมื่อไม่ใช้!</strong>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Tips --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30 bg-blue-500/5">
    <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
        <i class="fas fa-lightbulb text-yellow-400"></i>
        💡 Tips สำหรับ Lambda Labs
    </h3>
    <ul class="space-y-2 text-white/80">
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span><strong>Performance:</strong> A100 GPU ที่เร็วที่สุดสำหรับ Deep Learning</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>Pre-installed: PyTorch 2.0+, TensorFlow, CUDA 12, Jupyter</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ <code>tmux</code> หรือ <code>screen</code> เพื่อ keep session alive</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>Backup ด้วย <code>rsync</code> หรือ upload ไป S3/GCS</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-exclamation-triangle text-yellow-400 mt-1"></i>
            <span><strong>สำคัญ:</strong> Instance หมดอายุ 6 เดือนถ้าไม่ใช้งาน (ข้อมูลหาย!)</span>
        </li>
    </ul>
</div>
