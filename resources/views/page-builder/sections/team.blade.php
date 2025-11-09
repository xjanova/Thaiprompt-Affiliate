@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$layout = $settings['layout'] ?? 'grid'; // grid, carousel
$columns = $settings['columns'] ?? 4;
$members = $content['members'] ?? [];
$showSocial = $settings['show_social'] ?? true;
@endphp

<section class="py-20 bg-white">
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

        <div class="grid grid-cols-1 md:grid-cols-{{ $columns }} gap-8 max-w-7xl mx-auto">
            @foreach($members as $member)
            <div class="group relative bg-gradient-to-br from-gray-50 to-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                @if(!empty($member['image']))
                <div class="relative overflow-hidden aspect-square">
                    <img src="{{ $member['image'] }}" alt="{{ $member['name'] ?? '' }}"
                         class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">

                    <!-- Social Links Overlay -->
                    @if($showSocial && !empty($member['social']))
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-6 flex justify-center gap-4">
                            @if(!empty($member['social']['twitter']))
                            <a href="{{ $member['social']['twitter'] }}" target="_blank"
                               class="p-3 bg-white/20 backdrop-blur-sm rounded-full hover:bg-white/30 transition-colors">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                                </svg>
                            </a>
                            @endif
                            @if(!empty($member['social']['linkedin']))
                            <a href="{{ $member['social']['linkedin'] }}" target="_blank"
                               class="p-3 bg-white/20 backdrop-blur-sm rounded-full hover:bg-white/30 transition-colors">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"></path>
                                </svg>
                            </a>
                            @endif
                            @if(!empty($member['social']['github']))
                            <a href="{{ $member['social']['github'] }}" target="_blank"
                               class="p-3 bg-white/20 backdrop-blur-sm rounded-full hover:bg-white/30 transition-colors">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path>
                                </svg>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @else
                <div class="aspect-square bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                    <span class="text-6xl text-white font-bold">{{ substr($member['name'] ?? 'T', 0, 1) }}</span>
                </div>
                @endif

                <div class="p-6 text-center">
                    @if(!empty($member['name']))
                    <h3 class="text-xl font-bold text-gray-900 mb-1">
                        {{ $member['name'] }}
                    </h3>
                    @endif

                    @if(!empty($member['position']))
                    <p class="text-blue-600 font-semibold mb-3">
                        {{ $member['position'] }}
                    </p>
                    @endif

                    @if(!empty($member['bio']))
                    <p class="text-gray-600 text-sm mb-4">
                        {{ $member['bio'] }}
                    </p>
                    @endif

                    @if(!empty($member['skills']))
                    <div class="flex flex-wrap gap-2 justify-center">
                        @foreach($member['skills'] as $skill)
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                            {{ $skill }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        @if(!empty($content['cta_text']))
        <div class="text-center mt-12">
            <a href="{{ $content['cta_url'] ?? '#' }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                {{ $content['cta_text'] }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
        </div>
        @endif
    </div>
</section>
