@extends('layouts.app')

@section('title', 'นโยบายคุกกี้')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 md:p-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-6">นโยบายคุกกี้</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-8">อัปเดตล่าสุด: {{ now()->format('d/m/Y') }}</p>

            <div class="prose dark:prose-invert max-w-none space-y-6">
                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">คุกกี้คืออะไร?</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        คุกกี้ คือไฟล์ข้อความขนาดเล็กที่เว็บไซต์จัดเก็บไว้ในคอมพิวเตอร์หรืออุปกรณ์มือถือของคุณเมื่อคุณเยี่ยมชมเว็บไซต์
                        คุกกี้ช่วยให้เว็บไซต์จดจำการตั้งค่าและความชอบของคุณ เพื่อปรับปรุงประสบการณ์การใช้งาน
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">เราใช้คุกกี้อย่างไร?</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                        เว็บไซต์ของเราใช้คุกกี้หลายประเภทเพื่อวัตถุประสงค์ที่แตกต่างกัน ดังนี้:
                    </p>

                    <div class="space-y-6">
                        <!-- Necessary Cookies -->
                        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-l-4 border-blue-600">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">1. คุกกี้ที่จำเป็น (Necessary Cookies)</h3>
                            <p class="text-gray-700 dark:text-gray-300 mb-2">
                                คุกกี้เหล่านี้มีความจำเป็นสำหรับการทำงานของเว็บไซต์และไม่สามารถปิดได้ในระบบของเรา
                                โดยปกติจะถูกตั้งค่าเพื่อตอบสนองต่อการกระทำของคุณ เช่น:
                            </p>
                            <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                                <li>การเข้าสู่ระบบ</li>
                                <li>การตั้งค่าความเป็นส่วนตัว</li>
                                <li>การรักษาความปลอดภัยของเว็บไซต์</li>
                                <li>การจดจำสินค้าในตะกร้า</li>
                            </ul>
                        </div>

                        <!-- Analytics Cookies -->
                        <div class="p-6 bg-green-50 dark:bg-green-900/20 rounded-lg border-l-4 border-green-600">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">2. คุกกี้วิเคราะห์ (Analytics Cookies)</h3>
                            <p class="text-gray-700 dark:text-gray-300 mb-2">
                                คุกกี้เหล่านี้ช่วยให้เราเข้าใจว่าผู้เยี่ยมชมใช้งานเว็บไซต์อย่างไร เพื่อปรับปรุงประสบการณ์การใช้งาน เช่น:
                            </p>
                            <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                                <li>จำนวนผู้เยี่ยมชมและการเข้าชมหน้าต่างๆ</li>
                                <li>ระยะเวลาที่ใช้ในเว็บไซต์</li>
                                <li>แหล่งที่มาของผู้เยี่ยมชม (เช่น Google, Facebook)</li>
                                <li>พฤติกรรมการท่องเว็บและความสนใจ</li>
                                <li>คำค้นหาที่ใช้ในเว็บไซต์</li>
                                <li>สินค้าที่เข้าชม</li>
                            </ul>
                        </div>

                        <!-- Marketing Cookies -->
                        <div class="p-6 bg-purple-50 dark:bg-purple-900/20 rounded-lg border-l-4 border-purple-600">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">3. คุกกี้การตลาด (Marketing Cookies)</h3>
                            <p class="text-gray-700 dark:text-gray-300 mb-2">
                                คุกกี้เหล่านี้ใช้เพื่อติดตามผู้เยี่ยมชมในเว็บไซต์ต่างๆ เพื่อแสดงโฆษณาที่เกี่ยวข้องและน่าสนใจสำหรับผู้ใช้แต่ละราย:
                            </p>
                            <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                                <li>แสดงโฆษณาที่ตรงกับความสนใจของคุณ</li>
                                <li>จำกัดจำนวนครั้งที่คุณเห็นโฆษณา</li>
                                <li>วัดประสิทธิภาพของแคมเปญโฆษณา</li>
                            </ul>
                        </div>

                        <!-- Preference Cookies -->
                        <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border-l-4 border-yellow-600">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">4. คุกกี้การตั้งค่า (Preference Cookies)</h3>
                            <p class="text-gray-700 dark:text-gray-300 mb-2">
                                คุกกี้เหล่านี้จดจำการตั้งค่าของคุณ เช่น:
                            </p>
                            <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1 ml-4">
                                <li>ภาษาที่คุณเลือก</li>
                                <li>ภูมิภาคที่คุณอยู่</li>
                                <li>การตั้งค่าการแสดงผล (เช่น โหมดมืด)</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลที่เราเก็บรวบรวม</h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">
                        เมื่อคุณยินยอมให้ใช้คุกกี้วิเคราะห์ เราจะเก็บข้อมูลดังต่อไปนี้:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2 ml-4">
                        <li><strong>ข้อมูลอุปกรณ์:</strong> ประเภทอุปกรณ์, เบราว์เซอร์, ระบบปฏิบัติการ</li>
                        <li><strong>ข้อมูลการเข้าชม:</strong> หน้าที่เข้าชม, เวลาที่ใช้, การคลิก</li>
                        <li><strong>แหล่งที่มา:</strong> URL อ้างอิง, พารามิเตอร์ UTM</li>
                        <li><strong>ที่อยู่ IP:</strong> เพื่อระบุตำแหน่งทั่วไปและป้องกันการฉ้อโกง</li>
                        <li><strong>พฤติกรรม:</strong> คำค้นหา, สินค้าที่ดู, ความสนใจ</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">การจัดการคุกกี้</h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">
                        คุณสามารถจัดการการตั้งค่าคุกกี้ได้ทุกเมื่อผ่านทางแบนเนอร์คุกกี้ หรือผ่านการตั้งค่าเบราว์เซอร์ของคุณ
                    </p>
                    <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300 font-medium">
                            หมายเหตุ: การปิดใช้งานคุกกี้บางประเภทอาจส่งผลต่อการทำงานของเว็บไซต์และบริการที่เราสามารถให้คุณได้
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">ระยะเวลาเก็บรักษาคุกกี้</h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        คุกกี้ส่วนใหญ่ของเราจะหมดอายุภายใน <strong>{{ \App\Models\CookieSetting::get('cookie_expiry_days', 365) }} วัน</strong>
                        นับจากการเยี่ยมชมครั้งล่าสุดของคุณ คุกกี้บางประเภทจะหมดอายุเมื่อคุณปิดเบราว์เซอร์
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">การเปลี่ยนแปลงนโยบาย</h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        เราอาจปรับปรุงนโยบายคุกกี้นี้เป็นครั้งคราว การเปลี่ยนแปลงใดๆ จะมีผลทันทีเมื่อเผยแพร่บนเว็บไซต์
                        เราขอแนะนำให้คุณตรวจสอบนโยบายนี้เป็นระยะๆ
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">การปฏิบัติตามกฎหมาย PDPA</h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">
                        เว็บไซต์ของเราปฏิบัติตาม <strong>พระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)</strong> อย่างเคร่งครัด:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2 ml-4">
                        <li>เราขอความยินยอมจากคุณก่อนเก็บรวบรวมข้อมูลส่วนบุคคล</li>
                        <li>คุณมีสิทธิ์เข้าถึง แก้ไข และลบข้อมูลส่วนบุคคลของคุณ</li>
                        <li>เราเก็บข้อมูลเท่าที่จำเป็นและตามระยะเวลาที่กฎหมายกำหนด</li>
                        <li>ข้อมูลของคุณได้รับการปกป้องด้วยมาตรการรักษาความปลอดภัยที่เหมาะสม</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">ติดต่อเรา</h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">
                        หากคุณมีคำถามเกี่ยวกับนโยบายคุกกี้หรือการใช้ข้อมูลของคุณ กรุณาติดต่อเราได้ที่:
                    </p>
                    <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300">
                            <strong>Email:</strong> privacy@{{ request()->getHost() }}<br>
                            <strong>หรือผ่านหน้าติดต่อเรา</strong>
                        </p>
                    </div>
                </section>
            </div>

            <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button onclick="window.history.back()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    กลับหน้าก่อนหน้า
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
