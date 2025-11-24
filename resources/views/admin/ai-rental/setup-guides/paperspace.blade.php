{{-- Paperspace Gradient Setup Guide --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30">
    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
        <i class="fas fa-list-ol text-cyan-400"></i>
        ขั้นตอนการตั้งค่า Paperspace Gradient
    </h3>

    <div class="space-y-4">
        {{-- Step 1 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                1
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้างบัญชี Paperspace</h4>
                <p class="text-white/70 mb-2">
                    - ไปที่ <a href="https://console.paperspace.com" target="_blank" class="text-cyan-400 hover:underline">console.paperspace.com</a><br>
                    - คลิก "Sign Up"<br>
                    - สมัครด้วย Email หรือ Google<br>
                    - ยืนยันอีเมลและยืนยัน Phone Number
                </p>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                2
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เพิ่ม Payment Method</h4>
                <p class="text-white/70 mb-3">
                    - คลิก "Account" → "Billing"<br>
                    - เพิ่มบัตรเครดิต (รองรับ Visa, Mastercard)<br>
                    - ไม่มีค่าธรรมเนียมเริ่มต้น (Pay-as-you-go)
                </p>
                <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-gift text-green-400 mt-0.5"></i>
                        <div class="text-green-300 text-sm">
                            <strong>ฟรี:</strong> $10 credit สำหรับผู้ใช้ใหม่!
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
                <h4 class="text-white font-bold mb-2">สร้าง Gradient Notebook</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "Gradient" → "Notebooks"<br>
                    - คลิก "Create Notebook"<br>
                    - เลือก Template:<br>
                    &nbsp;&nbsp;• PyTorch<br>
                    &nbsp;&nbsp;• TensorFlow<br>
                    &nbsp;&nbsp;• Fast.ai<br>
                    &nbsp;&nbsp;• Blank (custom)
                </p>
            </div>
        </div>

        {{-- Step 4 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                4
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เลือก GPU Instance</h4>
                <p class="text-white/70 mb-2">
                    - เลือก Machine Type:<br>
                    &nbsp;&nbsp;• Free GPU (M4000 8GB VRAM) - ฟรี!<br>
                    &nbsp;&nbsp;• P4000 (8GB) - $0.51/hr<br>
                    &nbsp;&nbsp;• RTX 4000 (8GB) - $0.56/hr<br>
                    &nbsp;&nbsp;• RTX 5000 (16GB) - $0.82/hr<br>
                    &nbsp;&nbsp;• A4000 (16GB) - $0.76/hr<br>
                    &nbsp;&nbsp;• A100 (80GB) - $3.09/hr<br>
                    - เลือก Auto-shutdown (เช่น หยุดหลัง 1 ชม.)<br>
                    - คลิก "Start Notebook"
                </p>
            </div>
        </div>

        {{-- Step 5 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                5
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">ใช้งาน Jupyter Notebook</h4>
                <p class="text-white/70 mb-2">
                    - รอ notebook start up (30-60 วินาที)<br>
                    - Jupyter Notebook จะเปิดในเบราว์เซอร์<br>
                    - สร้าง .ipynb file ใหม่หรือ upload ไฟล์เดิม<br>
                    - ตรวจสอบ GPU ด้วยคำสั่ง:
                </p>
                <div class="bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-cyan-300 border border-white/10">
                    <code>
!nvidia-smi<br>
import torch<br>
print(f'GPU: {torch.cuda.get_device_name(0)}')
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
                <h4 class="text-white font-bold mb-2">Stop Notebook</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "Stop Notebook" เมื่อใช้เสร็จ<br>
                    - ไฟล์จะถูกเก็บใน "/storage" (persistent storage)<br>
                    - <strong class="text-yellow-300">⚠️ Stop ทันทีเมื่อไม่ใช้!</strong>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Tips --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30 bg-blue-500/5">
    <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
        <i class="fas fa-lightbulb text-yellow-400"></i>
        💡 Tips สำหรับ Paperspace
    </h3>
    <ul class="space-y-2 text-white/80">
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span><strong>Free GPU:</strong> M4000 ใช้ได้ฟรี! (จำกัด availability)</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ "/storage" สำหรับเก็บไฟล์แบบถาวร ($5/100GB/เดือน)</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>Upload dataset ผ่าน Jupyter หรือใช้ "Gradient Data" service</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ตั้ง "Auto-shutdown" เพื่อหลีกเลี่ยงค่าใช้จ่ายเกิน</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ "Gradient CLI" สำหรับ automation และ deployment</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-exclamation-triangle text-yellow-400 mt-1"></i>
            <span><strong>สำคัญ:</strong> Free GPU มี queue - อาจต้องรอ!</span>
        </li>
    </ul>
</div>
