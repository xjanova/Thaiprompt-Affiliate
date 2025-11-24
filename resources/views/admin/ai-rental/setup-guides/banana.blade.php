{{-- Banana Dev Setup Guide --}}
{{-- คู่มือการตั้งค่า Banana Dev สำหรับ Serverless GPU --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30">
    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
        <i class="fas fa-list-ol text-cyan-400"></i>
        ขั้นตอนการตั้งค่า Banana Dev
    </h3>

    <div class="space-y-4">
        {{-- Step 1 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                1
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้างบัญชี Banana Dev</h4>
                <p class="text-white/70 mb-2">
                    - ไปที่ <a href="https://www.banana.dev" target="_blank" class="text-cyan-400 hover:underline">banana.dev</a><br>
                    - คลิก "Get Started" หรือ "Sign Up"<br>
                    - สมัครด้วย GitHub หรือ Email<br>
                    - ยืนยันอีเมลและเข้าสู่ระบบ
                </p>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                2
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">รับ API Key</h4>
                <p class="text-white/70 mb-3">
                    - ไปที่ Dashboard → Settings<br>
                    - คลิก "API Keys"<br>
                    - คลิก "Create New API Key"<br>
                    - คัดลอก API Key เก็บไว้ (จะแสดงครั้งเดียว!)
                </p>
                <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-key text-yellow-400 mt-0.5"></i>
                        <div class="text-yellow-300 text-sm">
                            <strong>สำคัญ:</strong> เก็บ API Key ให้ดี ใช้สำหรับเรียก model ของคุณ
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                3
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เตรียม Model</h4>
                <p class="text-white/70 mb-2">
                    - สร้างโฟลเดอร์โปรเจค<br>
                    - สร้างไฟล์ <code>app.py</code> (โค้ดหลัก)<br>
                    - สร้างไฟล์ <code>requirements.txt</code> (dependencies)<br>
                    - สร้างไฟล์ <code>download.py</code> (สำหรับดาวน์โหลด model weights)
                </p>
                <div class="bg-gray-900/50 rounded-lg p-3 font-mono text-xs text-cyan-300 border border-white/10">
                    <code>
# โครงสร้างไฟล์:<br>
my-model/<br>
├── app.py           # inference code<br>
├── requirements.txt # dependencies<br>
└── download.py      # download model weights
                    </code>
                </div>
            </div>
        </div>

        {{-- Step 4 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                4
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">ติดตั้ง Banana CLI</h4>
                <p class="text-white/70 mb-2">
                    - ติดตั้ง Banana CLI ด้วย pip<br>
                    - Login ด้วย API Key<br>
                    - ตรวจสอบการเชื่อมต่อ
                </p>
                <div class="bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-cyan-300 border border-white/10">
                    <code>
pip install banana-cli<br>
banana login --api-key YOUR_API_KEY<br>
banana whoami
                    </code>
                </div>
            </div>
        </div>

        {{-- Step 5 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                5
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">Deploy Model</h4>
                <p class="text-white/70 mb-2">
                    - รัน deploy command ในโฟลเดอร์โปรเจค<br>
                    - รอ build และ deploy (5-15 นาที)<br>
                    - รับ Model ID เมื่อสำเร็จ
                </p>
                <div class="bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-cyan-300 border border-white/10">
                    <code>
cd my-model/<br>
banana deploy
                    </code>
                </div>
            </div>
        </div>

        {{-- Step 6 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                6
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เรียกใช้ Model API</h4>
                <p class="text-white/70 mb-2">
                    - ใช้ API Key และ Model ID<br>
                    - เรียก inference ผ่าน HTTP POST<br>
                    - จ่ายเฉพาะเวลาที่ใช้งาน (Serverless!)
                </p>
                <div class="bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-cyan-300 border border-white/10">
                    <code>
import banana_dev as banana<br>
<br>
model_key = "YOUR_MODEL_KEY"<br>
api_key = "YOUR_API_KEY"<br>
<br>
result = banana.run(<br>
&nbsp;&nbsp;api_key,<br>
&nbsp;&nbsp;model_key,<br>
&nbsp;&nbsp;{"prompt": "Hello, AI!"}<br>
)
                    </code>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pricing Info --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30 bg-green-500/5">
    <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
        <i class="fas fa-dollar-sign text-green-400"></i>
        ราคา Banana Dev
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-white/80">
        <div class="bg-gray-900/30 rounded-lg p-4">
            <div class="text-lg font-bold text-green-400 mb-2">Serverless GPU</div>
            <ul class="space-y-1 text-sm">
                <li>• A100 40GB: ~$0.0015/วินาที</li>
                <li>• จ่ายเฉพาะเวลา inference</li>
                <li>• ไม่มีค่า idle time</li>
            </ul>
        </div>
        <div class="bg-gray-900/30 rounded-lg p-4">
            <div class="text-lg font-bold text-blue-400 mb-2">Free Tier</div>
            <ul class="space-y-1 text-sm">
                <li>• $10 free credits สำหรับผู้ใช้ใหม่</li>
                <li>• ไม่ต้องใส่บัตรเครดิต</li>
                <li>• ทดลองใช้ได้ทันที</li>
            </ul>
        </div>
    </div>
</div>

{{-- Tips --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30 bg-blue-500/5">
    <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
        <i class="fas fa-lightbulb text-yellow-400"></i>
        💡 Tips สำหรับ Banana Dev
    </h3>
    <ul class="space-y-2 text-white/80">
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span><strong>Serverless:</strong> จ่ายเฉพาะเวลาที่ใช้งานจริง ไม่มีค่า idle</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span><strong>Cold Start:</strong> การเรียกครั้งแรกอาจใช้เวลา 10-30 วินาที</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ <code>download.py</code> เพื่อ cache model weights และลด cold start</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>รองรับ Hugging Face models, Stable Diffusion, LLMs</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-green-400 mt-1"></i>
            <span>ใช้ Dashboard เพื่อดู logs และ monitor usage</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-exclamation-triangle text-yellow-400 mt-1"></i>
            <span><strong>สำคัญ:</strong> ตรวจสอบ memory usage ให้เหมาะกับ GPU ที่เลือก</span>
        </li>
    </ul>
</div>
