@extends('layouts.app')

@section('title', 'นโยบายความเป็นส่วนตัว')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 md:p-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-6">นโยบายความเป็นส่วนตัว</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-8">อัปเดตล่าสุด: {{ now()->format('d/m/Y') }}</p>

            <div class="prose dark:prose-invert max-w-none space-y-6">
                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">1. บทนำ</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        {{ config('app.name') }} ("เรา", "บริษัท") ให้ความสำคัญกับความเป็นส่วนตัวของผู้ใช้บริการ ("คุณ")
                        นโยบายความเป็นส่วนตัวฉบับนี้อธิบายวิธีการที่เราเก็บรวบรวม ใช้ เปิดเผย และปกป้องข้อมูลส่วนบุคคลของคุณ
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">2. ข้อมูลที่เราเก็บรวบรวม</h2>
                    <div class="space-y-4">
                        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">2.1 ข้อมูลที่คุณให้โดยตรง</h3>
                            <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                                <li>ชื่อ-นามสกุล</li>
                                <li>อีเมล</li>
                                <li>หมายเลขโทรศัพท์</li>
                                <li>ที่อยู่</li>
                                <li>ข้อมูลบัญชีธนาคาร (สำหรับการรับเงิน)</li>
                            </ul>
                        </div>

                        <div class="p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">2.2 ข้อมูลที่เก็บอัตโนมัติ</h3>
                            <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                                <li>IP Address</li>
                                <li>ประเภทเบราว์เซอร์และอุปกรณ์</li>
                                <li>พฤติกรรมการใช้งานเว็บไซต์</li>
                                <li>ข้อมูล Cookies</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">3. วัตถุประสงค์ในการใช้ข้อมูล</h2>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2 ml-4">
                        <li>เพื่อให้บริการและดำเนินการตามที่คุณร้องขอ</li>
                        <li>เพื่อปรับปรุงและพัฒนาบริการของเรา</li>
                        <li>เพื่อส่งข้อมูลข่าวสารและโปรโมชั่น (หากคุณยินยอม)</li>
                        <li>เพื่อป้องกันการทุจริตและรักษาความปลอดภัย</li>
                        <li>เพื่อปฏิบัติตามกฎหมายที่เกี่ยวข้อง</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">4. การแชร์ข้อมูล</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                        เราจะไม่ขาย แลกเปลี่ยน หรือให้เช่าข้อมูลส่วนบุคคลของคุณแก่บุคคลที่สาม ยกเว้นในกรณีต่อไปนี้:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2 ml-4">
                        <li>เมื่อได้รับความยินยอมจากคุณ</li>
                        <li>กับผู้ให้บริการของเรา (เช่น ผู้ให้บริการชำระเงิน, ระบบอีเมล)</li>
                        <li>เมื่อกฎหมายกำหนดให้ต้องเปิดเผย</li>
                        <li>เพื่อปกป้องสิทธิและความปลอดภัยของเราและผู้อื่น</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">5. ความปลอดภัยของข้อมูล</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        เราใช้มาตรการรักษาความปลอดภัยทางเทคนิคและองค์กรที่เหมาะสม เช่น การเข้ารหัสข้อมูล (SSL/TLS),
                        การควบคุมการเข้าถึง, และการสำรองข้อมูลเป็นประจำ เพื่อปกป้องข้อมูลของคุณจากการสูญหาย การใช้งานที่ไม่ได้รับอนุญาต
                        และการเปิดเผยโดยไม่ได้รับอนุญาต
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">6. สิทธิของคุณ</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                        ภายใต้ พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) คุณมีสิทธิ์:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2 ml-4">
                        <li>เข้าถึงและขอสำเนาข้อมูลส่วนบุคคลของคุณ</li>
                        <li>แก้ไขข้อมูลที่ไม่ถูกต้องหรือไม่สมบูรณ์</li>
                        <li>ลบหรือทำลายข้อมูลของคุณ</li>
                        <li>คัดค้านการประมวลผลข้อมูลของคุณ</li>
                        <li>ขอให้โอนย้ายข้อมูลของคุณ</li>
                        <li>ถอนความยินยอมที่เคยให้ไว้</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">7. การเก็บรักษาข้อมูล</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        เราจะเก็บรักษาข้อมูลส่วนบุคคลของคุณตามระยะเวลาที่จำเป็นเพื่อให้บรรลุวัตถุประสงค์ที่ระบุไว้ในนโยบายนี้
                        หรือตามที่กฎหมายกำหนด หลังจากนั้นข้อมูลจะถูกลบหรือทำให้ไม่สามารถระบุตัวตนได้
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">8. การเปลี่ยนแปลงนโยบาย</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        เราอาจปรับปรุงนโยบายความเป็นส่วนตัวนี้เป็นครั้งคราว การเปลี่ยนแปลงที่สำคัญจะมีการแจ้งให้คุณทราบผ่านทางเว็บไซต์หรืออีเมล
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">9. ติดต่อเรา</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                        หากคุณมีคำถามเกี่ยวกับนโยบายความเป็นส่วนตัวนี้ หรือต้องการใช้สิทธิของคุณ กรุณาติดต่อ:
                    </p>
                    <div class="p-6 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300"><strong>Email:</strong> support@thaiprompt.com</p>
                        <p class="text-gray-700 dark:text-gray-300"><strong>LINE:</strong> @thaiprompt</p>
                        <p class="text-gray-700 dark:text-gray-300"><strong>โทรศัพท์:</strong> 02-XXX-XXXX</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
