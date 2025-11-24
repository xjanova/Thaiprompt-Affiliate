{{-- Kaggle Notebooks Setup Guide --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30">
    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
        <i class="fas fa-list-ol text-cyan-400"></i>
        ขั้นตอนการตั้งค่า Kaggle Notebooks
    </h3>

    <div class="space-y-4">
        {{-- Step 1 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                1
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้างบัญชี Kaggle</h4>
                <p class="text-white/70 mb-2">
                    - ไปที่ <a href="https://www.kaggle.com" target="_blank" class="text-cyan-400 hover:underline">kaggle.com</a><br>
                    - คลิก "Register" และสมัครด้วยอีเมลหรือ Google Account<br>
                    - ยืนยัน Phone Number เพื่อรับ GPU ฟรี
                </p>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                2
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้าง Notebook ใหม่</h4>
                <p class="text-white/70 mb-2">
                    - คลิก "Code" เมนูด้านบน<br>
                    - คลิก "New Notebook"<br>
                    - Kaggle จะสร้าง notebook พร้อม environment ให้อัตโนมัติ
                </p>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                3
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เปิดใช้งาน GPU Accelerator</h4>
                <p class="text-white/70 mb-3">
                    - คลิกที่ไอคอน Settings (⚙️) ด้านขวา<br>
                    - เลือก "Accelerator" → "GPU T4 x2" (ฟรี!)<br>
                    - Kaggle ให้ GPU ฟรี 30 ชม./สัปดาห์<br>
                    - คลิก "Save" และรอ notebook restart
                </p>
                <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-gift text-green-400 mt-0.5"></i>
                        <div class="text-green-300 text-sm">
                            <strong>ฟรี:</strong> GPU T4 x2 (30GB VRAM) ใช้ได้ 30 ชม./สัปดาห์
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
                <p class="text-white/70 mb-2">รันโค้ดนี้เพื่อตรวจสอบ GPU:</p>
                <div class="bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-cyan-300 border border-white/10">
                    <code>
!nvidia-smi<br>
import torch<br>
print(f"GPU Available: {torch.cuda.is_available()}")<br>
print(f"GPU Count: {torch.cuda.device_count()}")<br>
print(f"GPU Name: {torch.cuda.get_device_name(0)}")
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
                <h4 class="text-white font-bold mb-2">Upload ไฟล์และ Dataset</h4>
                <p class="text-white/70 mb-2">
                    - คลิกไอคอน "Add Data" ด้านขวา<br>
                    - เลือก dataset จาก Kaggle หรือ upload file<br>
                    - ไฟล์จะอยู่ใน /kaggle/input/
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Tips --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30 bg-blue-500/5">
    <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
        <i class="fas fa-lightbulb text-yellow-400"></i>
        💡 Tips สำหรับ Kaggle
    </h3>
    <ul class="space-y-2 text-white/80">
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ "Internet On" ถ้าต้องการดาวน์โหลด packages</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>Save notebook เป็น "Version" เพื่อเก็บผลลัพธ์</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ "Persist Session" ถ้าต้องการ state คงอยู่</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>GPU session หมดอายุหลัง 9 ชม. ต่อครั้ง</span>
        </li>
    </ul>
</div>
