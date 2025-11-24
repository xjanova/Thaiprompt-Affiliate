{{-- Google Colab Setup Guide --}}
<div class="glass-fusion rounded-2xl p-6 border border-white/30">
    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
        <i class="fas fa-list-ol text-cyan-400"></i>
        ขั้นตอนการตั้งค่า Google Colab
    </h3>

    <div class="space-y-4">
        {{-- Step 1 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                1
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">สร้าง Google Account (ถ้ายังไม่มี)</h4>
                <p class="text-white/70 mb-2">
                    - ไปที่ <a href="https://accounts.google.com" target="_blank" class="text-cyan-400 hover:underline">accounts.google.com</a><br>
                    - คลิก "สร้างบัญชี" และทำตามขั้นตอน<br>
                    - ยืนยันอีเมลและเบอร์โทรศัพท์
                </p>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                2
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เข้าสู่ Google Colab</h4>
                <p class="text-white/70 mb-2">
                    - ไปที่ <a href="https://colab.research.google.com" target="_blank" class="text-cyan-400 hover:underline">colab.research.google.com</a><br>
                    - ล็อกอินด้วย Google Account ของคุณ<br>
                    - คลิก "File" → "New notebook" เพื่อสร้าง notebook ใหม่
                </p>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                3
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">เปิดใช้งาน GPU</h4>
                <p class="text-white/70 mb-3">
                    - คลิก "Runtime" → "Change runtime type"<br>
                    - เลือก "Hardware accelerator" เป็น "GPU"<br>
                    - เลือก GPU type (T4 สำหรับ free tier)<br>
                    - คลิก "Save"
                </p>
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-3">
                    <div class="text-blue-400 text-sm font-semibold mb-1">💡 Free Tier Limits:</div>
                    <div class="text-white/70 text-sm">
                        - Tesla T4 GPU ฟรี<br>
                        - 12 ชั่วโมงต่อ session<br>
                        - 90 นาที idle timeout<br>
                        - 12GB RAM
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
                <h4 class="text-white font-bold mb-2">ติดตั้ง Hugging Face libraries</h4>
                <p class="text-white/70 mb-2">รันคำสั่งนี้ใน cell แรก:</p>
                <div class="bg-gray-900/50 border border-gray-700 rounded-lg p-4 mb-2">
                    <code class="text-green-400 text-sm">
!pip install transformers diffusers accelerate torch<br>
!pip install huggingface_hub
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
                <h4 class="text-white font-bold mb-2">Login Hugging Face (Optional)</h4>
                <p class="text-white/70 mb-2">สำหรับใช้ private models:</p>
                <div class="bg-gray-900/50 border border-gray-700 rounded-lg p-4 mb-2">
                    <code class="text-green-400 text-sm">
from huggingface_hub import login<br>
login()  # จะขึ้นให้ใส่ HF token
                    </code>
                </div>
                <p class="text-white/60 text-sm">
                    หรือใช้: <code class="text-cyan-400">!huggingface-cli login</code>
                </p>
            </div>
        </div>

        {{-- Step 6 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                6
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">ทดสอบ GPU</h4>
                <p class="text-white/70 mb-2">ตรวจสอบว่า GPU พร้อมใช้งาน:</p>
                <div class="bg-gray-900/50 border border-gray-700 rounded-lg p-4 mb-2">
                    <code class="text-green-400 text-sm">
import torch<br>
print(f"CUDA available: {torch.cuda.is_available()}")<br>
print(f"GPU name: {torch.cuda.get_device_name(0)}")<br>
print(f"GPU memory: {torch.cuda.get_device_properties(0).total_memory / 1024**3:.2f} GB")
                    </code>
                </div>
            </div>
        </div>

        {{-- Step 7 --}}
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                7
            </div>
            <div class="flex-1">
                <h4 class="text-white font-bold mb-2">โหลดและใช้งาน Model</h4>
                <p class="text-white/70 mb-2">ตัวอย่างการใช้ Stable Diffusion:</p>
                <div class="bg-gray-900/50 border border-gray-700 rounded-lg p-4 mb-2">
                    <code class="text-green-400 text-sm text-xs">
from diffusers import StableDiffusionPipeline<br>
import torch<br>
<br>
pipe = StableDiffusionPipeline.from_pretrained(<br>
&nbsp;&nbsp;&nbsp;&nbsp;"runwayml/stable-diffusion-v1-5",<br>
&nbsp;&nbsp;&nbsp;&nbsp;torch_dtype=torch.float16<br>
).to("cuda")<br>
<br>
image = pipe("A beautiful sunset over the ocean").images[0]<br>
image.show()
                    </code>
                </div>
            </div>
        </div>
    </div>

    {{-- Pro Tips --}}
    <div class="mt-6 bg-gradient-to-r from-purple-500/10 to-pink-500/10 border border-purple-500/30 rounded-xl p-4">
        <h4 class="text-purple-300 font-bold mb-3 flex items-center gap-2">
            <i class="fas fa-lightbulb"></i>
            Pro Tips
        </h4>
        <ul class="text-white/70 space-y-2 text-sm">
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>ใช้ <code class="text-cyan-400">torch.cuda.empty_cache()</code> เพื่อเคลียร์ GPU memory</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>Mount Google Drive: <code class="text-cyan-400">from google.colab import drive; drive.mount('/content/drive')</code></span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>ใช้ <code class="text-cyan-400">%%time</code> เพื่อวัดเวลาการทำงานของแต่ละ cell</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>Colab Pro ($9.99/เดือน) ได้ GPU ดีกว่า (V100, A100) และ runtime นานขึ้น</span>
            </li>
        </ul>
    </div>
</div>
