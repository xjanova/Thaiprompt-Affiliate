{{-- Section: Custom Footer --}}
@if($layoutSettings->show_footer)
    @php $lc = $layoutSettings->layout_classes; @endphp
    <footer class="{{ $lc['footer_style'] }} mt-12" style="background-color: {{ $layoutSettings->footer_bg_color ?? '#f9fafb' }}">
        <div class="{{ $lc['container'] }} py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Store Info --}}
                <div>
                    <h3 class="font-bold text-xl mb-4" style="color: var(--store-primary)">{{ $store->store_name }}</h3>
                    <p class="text-gray-600 dark:text-gray-400">{{ $store->store_description }}</p>
                </div>

                {{-- Contact Info --}}
                @if($layoutSettings->show_contact_info)
                    <div>
                        <h3 class="font-bold text-lg mb-4 text-gray-800 dark:text-gray-200">ติดต่อเรา</h3>
                        <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                            @if($store->store_email)
                                <li class="flex items-center gap-2">
                                    <span>📧</span> {{ $store->store_email }}
                                </li>
                            @endif
                            @if($store->store_phone)
                                <li class="flex items-center gap-2">
                                    <span>📞</span> {{ $store->store_phone }}
                                </li>
                            @endif
                            @if($store->store_address)
                                <li class="flex items-center gap-2">
                                    <span>📍</span> {{ $store->store_address }}
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif

                {{-- Social Links --}}
                @if($layoutSettings->show_social_links && $layoutSettings->social_links)
                    <div>
                        <h3 class="font-bold text-lg mb-4 text-gray-800 dark:text-gray-200">ติดตามเรา</h3>
                        <div class="flex gap-3">
                            @php
                                $socialColors = [
                                    'facebook' => 'bg-blue-600',
                                    'line' => 'bg-green-500',
                                    'instagram' => 'bg-gradient-to-br from-purple-600 to-pink-500',
                                    'tiktok' => 'bg-black',
                                    'youtube' => 'bg-red-600',
                                ];
                                $socialIcons = [
                                    'facebook' => '📘', 'line' => '💚', 'instagram' => '📸',
                                    'tiktok' => '🎵', 'youtube' => '📺',
                                ];
                            @endphp
                            @foreach($socialColors as $platform => $bgClass)
                                @if(!empty($layoutSettings->social_links[$platform]))
                                    <a href="{{ $platform === 'line' ? 'https://line.me/ti/p/' . ltrim($layoutSettings->social_links[$platform], '@') : $layoutSettings->social_links[$platform] }}"
                                       target="_blank"
                                       class="w-10 h-10 {{ $bgClass }} text-white rounded-full flex items-center justify-center hover:scale-110 transition">
                                        {{ $socialIcons[$platform] }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Custom Footer Content --}}
            @if($layoutSettings->footer_content)
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                    {!! $layoutSettings->footer_content !!}
                </div>
            @endif

            {{-- Copyright --}}
            <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700 text-center text-gray-500 text-sm">
                <p>&copy; {{ date('Y') }} {{ $store->store_name }}. สงวนลิขสิทธิ์.</p>
                <p class="mt-1">Powered by <span style="color: var(--store-primary)" class="font-semibold">TP-Affiliate</span></p>
            </div>
        </div>
    </footer>
@endif
