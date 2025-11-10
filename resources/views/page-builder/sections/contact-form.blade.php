@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$layout = $settings['layout'] ?? 'split'; // split, centered, side-by-side
$formAction = $content['form_action'] ?? route('contact.submit');
$fields = $content['fields'] ?? ['name', 'email', 'subject', 'message'];
$showContactInfo = $settings['show_contact_info'] ?? true;
@endphp

<section class="py-20 bg-gradient-to-br from-gray-50 to-white">
    <div class="container mx-auto px-4">
        @if(!empty($content['title']))
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                {{ $content['title'] }}
            </h2>
            @if(!empty($content['subtitle']))
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                {{ $content['subtitle'] }}
            </p>
            @endif
        </div>
        @endif

        <div class="{{ $layout === 'centered' ? 'max-w-2xl mx-auto' : 'max-w-7xl mx-auto grid md:grid-cols-2 gap-12 items-start' }}">
            <!-- Contact Form -->
            <div class="{{ $layout === 'centered' ? '' : 'order-2' }}">
                <form action="{{ $formAction }}" method="POST" class="bg-white rounded-2xl shadow-xl p-8" x-data="contactForm()">
                    @csrf

                    @if(in_array('name', $fields))
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                            ชื่อของคุณ <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               required
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                               placeholder="กรอกชื่อของคุณ">
                    </div>
                    @endif

                    @if(in_array('email', $fields))
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            อีเมล <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               required
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                               placeholder="your@email.com">
                    </div>
                    @endif

                    @if(in_array('phone', $fields))
                    <div class="mb-6">
                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                            เบอร์โทรศัพท์
                        </label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                               placeholder="0XX-XXX-XXXX">
                    </div>
                    @endif

                    @if(in_array('subject', $fields))
                    <div class="mb-6">
                        <label for="subject" class="block text-sm font-semibold text-gray-700 mb-2">
                            หัวข้อ <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="subject"
                               name="subject"
                               required
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                               placeholder="เรื่องที่ต้องการติดต่อ">
                    </div>
                    @endif

                    @if(in_array('message', $fields))
                    <div class="mb-6">
                        <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">
                            ข้อความ <span class="text-red-500">*</span>
                        </label>
                        <textarea id="message"
                                  name="message"
                                  required
                                  rows="6"
                                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors resize-none"
                                  placeholder="พิมพ์ข้อความของคุณที่นี่..."></textarea>
                    </div>
                    @endif

                    @if($settings['enable_privacy_checkbox'] ?? true)
                    <div class="mb-6">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" required class="mt-1 w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-200">
                            <span class="text-sm text-gray-600">
                                ฉันยอมรับ <a href="{{ $content['privacy_policy_url'] ?? '#' }}" class="text-blue-600 hover:underline">นโยบายความเป็นส่วนตัว</a> และยินยอมให้ประมวลผลข้อมูลของฉัน
                            </span>
                        </label>
                    </div>
                    @endif

                    <button type="submit"
                            class="w-full px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
                        {{ $content['submit_text'] ?? 'ส่งข้อความ' }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>

                    <p class="text-sm text-gray-500 text-center mt-4">
                        {{ $content['response_time'] ?? 'เราจะตอบกลับภายใน 24 ชั่วโมง' }}
                    </p>
                </form>
            </div>

            <!-- Contact Info -->
            @if($showContactInfo && $layout !== 'centered')
            <div class="order-1">
                <div class="sticky top-8">
                    @if(!empty($content['contact_description']))
                    <p class="text-gray-600 mb-8 text-lg leading-relaxed">
                        {{ $content['contact_description'] }}
                    </p>
                    @endif

                    <div class="space-y-6">
                        @if(!empty($content['contact_email']))
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">อีเมล</h3>
                                <a href="mailto:{{ $content['contact_email'] }}" class="text-blue-600 hover:underline">
                                    {{ $content['contact_email'] }}
                                </a>
                            </div>
                        </div>
                        @endif

                        @if(!empty($content['contact_phone']))
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">โทรศัพท์</h3>
                                <a href="tel:{{ $content['contact_phone'] }}" class="text-blue-600 hover:underline">
                                    {{ $content['contact_phone'] }}
                                </a>
                            </div>
                        </div>
                        @endif

                        @if(!empty($content['contact_address']))
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">ที่อยู่</h3>
                                <p class="text-gray-600">{{ $content['contact_address'] }}</p>
                            </div>
                        </div>
                        @endif

                        @if(!empty($content['business_hours']))
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">เวลาทำการ</h3>
                                <p class="text-gray-600">{{ $content['business_hours'] }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

@push('scripts')
<script>
function contactForm() {
    return {
        // Add any form validation or submission logic here
    };
}
</script>
@endpush
