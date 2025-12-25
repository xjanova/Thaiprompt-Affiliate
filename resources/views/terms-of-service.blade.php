@extends('layouts.app')

@section('title', 'เงื่อนไขการใช้งาน')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 md:p-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-6">เงื่อนไขการให้บริการ</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-8">อัปเดตล่าสุด: {{ now()->format('d/m/Y') }}</p>

            <div class="prose dark:prose-invert max-w-none space-y-6">
                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">1. การยอมรับเงื่อนไข</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        การเข้าถึงและใช้งานเว็บไซต์ {{ config('app.name') }} ("บริการ") ถือว่าคุณยอมรับและตกลงผูกพันตามเงื่อนไขการให้บริการนี้
                        หากคุณไม่เห็นด้วยกับเงื่อนไขเหล่านี้ โปรดหยุดการใช้งานบริการของเราทันที
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">2. การใช้งานบริการ</h2>
                    <div class="space-y-4">
                        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">2.1 คุณสมบัติผู้ใช้</h3>
                            <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                                <li>ต้องมีอายุไม่ต่ำกว่า 18 ปีบริบูรณ์</li>
                                <li>มีความสามารถในการทำนิติกรรมสัญญาตามกฎหมาย</li>
                                <li>ให้ข้อมูลที่ถูกต้องและครบถ้วนในการลงทะเบียน</li>
                            </ul>
                        </div>

                        <div class="p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">2.2 บัญชีผู้ใช้</h3>
                            <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                                <li>คุณต้องรักษาความลับของรหัสผ่านและข้อมูลบัญชี</li>
                                <li>คุณรับผิดชอบต่อการใช้งานทั้งหมดภายใต้บัญชีของคุณ</li>
                                <li>ห้ามแชร์หรือโอนบัญชีให้ผู้อื่น</li>
                                <li>แจ้งเราทันทีหากพบการใช้งานที่ไม่ได้รับอนุญาต</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">3. พฤติกรรมที่ห้าม</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                        เมื่อใช้บริการของเรา คุณตกลงที่จะไม่:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2 ml-4">
                        <li>ละเมิดกฎหมาย กฎระเบียบ หรือสิทธิของผู้อื่น</li>
                        <li>ใช้บริการเพื่อวัตถุประสงค์ที่ผิดกฎหมายหรือฉ้อโกง</li>
                        <li>อัปโหลดหรือแพร่กระจายไวรัส มัลแวร์ หรือโค้ดที่เป็นอันตราย</li>
                        <li>ทำ spam หรือส่งข้อความที่ไม่พึงประสงค์</li>
                        <li>ใช้ bot, crawler หรือเครื่องมืออัตโนมัติโดยไม่ได้รับอนุญาต</li>
                        <li>พยายามเข้าถึงระบบหรือข้อมูลที่ไม่ได้รับอนุญาต</li>
                        <li>ก๊อปปี้ แก้ไข หรือสร้างผลงานดัดแปลงจากบริการของเรา</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">4. ระบบ Affiliate และคอมมิชชั่น</h2>
                    <div class="space-y-4">
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                            การเข้าร่วมโปรแกรม Affiliate ของเรามีเงื่อนไขเพิ่มเติมดังนี้:
                        </p>
                        <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2 ml-4">
                            <li>คอมมิชชั่นจะถูกคำนวณตามกฎเกณฑ์ที่กำหนดและอาจเปลี่ยนแปลงได้</li>
                            <li>การถอนเงินต้องผ่านการตรวจสอบและมียอดขั้นต่ำตามที่กำหนด</li>
                            <li>การทุจริตหรือสร้างรายได้ผิดวิธีจะถูกระงับบัญชีและยกเลิกคอมมิชชั่น</li>
                            <li>เราขอสงวนสิทธิ์ในการปรับเปลี่ยนโครงสร้างคอมมิชชั่นโดยแจ้งล่วงหน้า</li>
                        </ul>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">5. ทรัพย์สินทางปัญญา</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        เนื้อหา ฟีเจอร์ และฟังก์ชันทั้งหมดในบริการของเรา (รวมถึงแต่ไม่จำกัดเพียง ข้อความ กราฟิก โลโก้ ไอคอน รูปภาพ คลิปเสียง
                        คลิปวิดีโอ และซอฟต์แวร์) เป็นทรัพย์สินของ {{ config('app.name') }} และได้รับการคุ้มครองโดยกฎหมายลิขสิทธิ์และทรัพย์สินทางปัญญา
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">6. การชำระเงินและการคืนเงิน</h2>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2 ml-4">
                        <li>การชำระเงินต้องทำผ่านช่องทางที่เรากำหนดเท่านั้น</li>
                        <li>ราคาและค่าธรรมเนียมอาจเปลี่ยนแปลงได้โดยไม่ต้องแจ้งล่วงหน้า</li>
                        <li>การคืนเงินจะพิจารณาเป็นรายกรณี ตามนโยบายการคืนเงินของเรา</li>
                        <li>บริการบางประเภทอาจไม่มีการคืนเงิน</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">7. การยกเลิกและระงับบัญชี</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                        เราขอสงวนสิทธิ์ในการระงับหรือยกเลิกบัญชีของคุณได้ทันที หากพบว่า:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2 ml-4">
                        <li>คุณละเมิดเงื่อนไขการใช้งานนี้</li>
                        <li>มีการทุจริตหรือกิจกรรมที่ผิดกฎหมาย</li>
                        <li>ให้ข้อมูลเท็จหรือทำให้เข้าใจผิด</li>
                        <li>ไม่ชำระเงินตามกำหนด</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">8. ข้อจำกัดความรับผิด</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        บริการของเราให้บริการตามสภาพ "AS IS" โดยไม่มีการรับประกันใดๆ ไม่ว่าโดยชัดแจ้งหรือโดยนัย
                        เราไม่รับผิดชอบต่อความเสียหายใดๆ ที่เกิดจากการใช้งานหรือไม่สามารถใช้งานบริการของเรา
                        รวมถึงการสูญเสียข้อมูล กำไร หรือโอกาสทางธุรกิจ
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">9. การเปลี่ยนแปลงเงื่อนไข</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        เราขอสงวนสิทธิ์ในการแก้ไขหรือปรับปรุงเงื่อนไขการให้บริการนี้ได้ตลอดเวลา
                        การเปลี่ยนแปลงที่สำคัญจะมีการประกาศผ่านทางเว็บไซต์ การใช้บริการต่อไปหลังจากการเปลี่ยนแปลงถือว่าคุณยอมรับเงื่อนไขใหม่
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">10. กฎหมายที่ใช้บังคับ</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        เงื่อนไขการให้บริการนี้อยู่ภายใต้บังคับและตีความตามกฎหมายไทย
                        ข้อพิพาทใดๆ ที่เกิดขึ้นจากหรือเกี่ยวข้องกับเงื่อนไขนี้จะอยู่ในเขตอำนาจศาลไทยเท่านั้น
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">11. ติดต่อเรา</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                        หากคุณมีคำถามเกี่ยวกับเงื่อนไขการให้บริการนี้ กรุณาติดต่อ:
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
