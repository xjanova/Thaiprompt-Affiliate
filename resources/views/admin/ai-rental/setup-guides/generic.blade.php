{{-- Generic Setup Guide Template --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30">
    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
        <i class="fas fa-list-ol text-cyan-400"></i>
        ขั้นตอนการตั้งค่าทั่วไป
    </h3>

    <div class="space-y-4">
        {{-- Step 1 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                1
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้างบัญชี</h4>
                <p class="text-white/70">
                    - ไปที่เว็บไซต์ของ provider<br>
                    - คลิก "Sign Up" หรือ "Register"<br>
                    - กรอกข้อมูลและยืนยันอีเมล
                </p>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                2
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">ตั้งค่า Billing (สำหรับ Paid tiers)</h4>
                <p class="text-white/70">
                    - เพิ่มวิธีการชำระเงิน (บัตรเครดิต/เดบิต)<br>
                    - ตั้งค่า spending limits (ถ้ามี)<br>
                    - เลือกแพ็กเกจที่เหมาะสม
                </p>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                3
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้าง API Key</h4>
                <p class="text-white/70 mb-3">
                    - ไปที่หน้า Settings หรือ API Keys<br>
                    - คลิก "Create New API Key"<br>
                    - คัดลอกและเก็บ API key ไว้อย่างปลอดภัย
                </p>
                <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-3">
                    <div class="text-yellow-400 text-sm font-semibold mb-1">⚠️ ความปลอดภัย:</div>
                    <div class="text-white/70 text-sm">
                        - อย่าแชร์ API key กับใคร<br>
                        - ใช้ environment variables แทนการ hardcode<br>
                        - Rotate keys เป็นประจำ
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
                <h4 class="text-white font-bold mb-2">เลือก GPU Instance</h4>
                <p class="text-white/70">
                    - เลือกประเภท GPU ที่ต้องการ<br>
                    - กำหนด RAM และ Storage<br>
                    - เลือก region ที่ใกล้ที่สุด (ลด latency)
                </p>
            </div>
        </div>

        {{-- Step 5 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                5
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">Setup Environment</h4>
                <p class="text-white/70 mb-2">ติดตั้ง dependencies พื้นฐาน:</p>
                <div class="bg-gray-900/50 border border-gray-700 rounded-lg p-4">
                    <code class="text-green-400 text-sm">
pip install transformers diffusers torch accelerate<br>
pip install huggingface_hub datasets
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
                <h4 class="text-white font-bold mb-2">เริ่มใช้งาน</h4>
                <p class="text-white/70">
                    - Deploy model ของคุณ<br>
                    - ทดสอบ inference<br>
                    - Monitor usage และ costs
                </p>
            </div>
        </div>
    </div>

    {{-- Common Tips --}}
    <div class="mt-6 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/30 rounded-xl p-4">
        <h4 class="text-cyan-300 font-bold mb-3 flex items-center gap-2">
            <i class="fas fa-lightbulb"></i>
            เคล็ดลับทั่วไป
        </h4>
        <ul class="text-white/70 space-y-2 text-sm">
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>เริ่มต้นด้วย smallest GPU instance เพื่อทดสอบก่อน</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>ตั้ง spending alerts เพื่อป้องกันค่าใช้จ่ายเกิน</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>ใช้ spot instances หรือ preemptible VMs เพื่อประหยัดค่าใช้จ่าย</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>หยุด instances เมื่อไม่ใช้งานเพื่อไม่เสียค่าใช้จ่ายเปล่าๆ</span>
            </li>
        </ul>
    </div>

    {{-- Need Help --}}
    <div class="mt-4 bg-purple-500/10 border border-purple-500/30 rounded-xl p-4">
        <h4 class="text-purple-300 font-bold mb-2 flex items-center gap-2">
            <i class="fas fa-question-circle"></i>
            ต้องการความช่วยเหลือ?
        </h4>
        <p class="text-white/70 text-sm">
            ตรวจสอบ official documentation ของ provider หรือติดต่อ support team ของพวกเขาโดยตรง
        </p>
    </div>
</div>
