{{--
    หน้าค้นหาโรงแรม (V3 Theme)

    ฟีเจอร์:
    - ค้นหาตามจังหวัด/ภูมิภาค
    - ค้นหาจาก GPS (ใกล้เคียง)
    - Dark/Light mode support
    - Glassmorphism & 3D effects
    - Alpine.js interactivity
--}}
@extends('layouts.app')

@section('title', isset($selectedProvince) ? 'โรงแรมใน ' . $selectedProvince->name_th : (isset($regionName) ? 'โรงแรมภาค' . $regionName : 'ค้นหาโรงแรมและที่พัก'))

@section('content')
<div x-data="hotelSearch()" class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-cyan-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
    {{-- Hero Section with Search --}}
    <div class="relative overflow-hidden">
        {{-- Background Gradient with Animated Shapes --}}
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-cyan-600 to-teal-600 dark:from-blue-900 dark:via-cyan-900 dark:to-teal-900">
            <div class="absolute inset-0 opacity-30">
                <div class="absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-10 right-10 w-96 h-96 bg-cyan-400/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
            </div>
        </div>

        <div class="relative container mx-auto px-4 py-12 md:py-20">
            <div class="max-w-5xl mx-auto text-center">
                {{-- Title --}}
                <h1 class="text-3xl md:text-5xl font-bold text-white mb-4 animate-fade-in">
                    @if(isset($selectedProvince))
                        โรงแรมใน {{ $selectedProvince->name_th }}
                    @elseif(isset($regionName))
                        โรงแรมภาค{{ $regionName }}
                    @elseif(isset($city))
                        โรงแรมใน {{ $city }}
                    @else
                        ค้นหาโรงแรมและที่พักทั่วไทย
                    @endif
                </h1>
                <p class="text-lg text-white/80 mb-8">
                    @if(isset($selectedProvince))
                        พบ {{ number_format($hotels->total()) }} โรงแรมใน{{ $selectedProvince->name_th }}
                    @else
                        กว่า 10,000 โรงแรมทั่วประเทศ พร้อมราคาพิเศษสำหรับคุณ
                    @endif
                </p>

                {{-- Search Form (Glassmorphism) --}}
                <form action="{{ route('hotels.search') }}" method="GET"
                      class="backdrop-blur-xl bg-white/20 dark:bg-slate-800/40 rounded-2xl md:rounded-3xl shadow-2xl p-4 md:p-8 border border-white/30">

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        {{-- Location Input with GPS --}}
                        <div class="relative">
                            <label class="block text-sm font-semibold text-white mb-2">สถานที่ / จังหวัด</label>
                            <div class="relative">
                                <input type="text"
                                       name="city"
                                       x-model="searchQuery"
                                       @input.debounce.300ms="autocomplete()"
                                       @focus="showSuggestions = true"
                                       placeholder="ค้นหาจังหวัด, โรงแรม..."
                                       value="{{ request('city') }}"
                                       class="w-full pl-10 pr-12 py-3 rounded-xl bg-white/90 dark:bg-slate-700/90 border-0 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-cyan-400 transition-all">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>

                                {{-- GPS Button --}}
                                <button type="button"
                                        @click="getLocation()"
                                        :class="{'animate-pulse': loadingLocation}"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 transition-colors"
                                        title="ใช้ตำแหน่งปัจจุบัน">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Hidden GPS fields --}}
                            <input type="hidden" name="latitude" x-model="latitude">
                            <input type="hidden" name="longitude" x-model="longitude">
                            <input type="hidden" name="province_id" x-model="selectedProvinceId">

                            {{-- Autocomplete Suggestions --}}
                            <div x-show="showSuggestions && (suggestions.provinces.length > 0 || suggestions.hotels.length > 0)"
                                 x-transition
                                 @click.outside="showSuggestions = false"
                                 class="absolute z-50 w-full mt-2 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden max-h-80 overflow-y-auto">

                                {{-- Provinces --}}
                                <template x-if="suggestions.provinces.length > 0">
                                    <div>
                                        <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-slate-700/50">
                                            จังหวัด
                                        </div>
                                        <template x-for="province in suggestions.provinces" :key="'p-' + province.id">
                                            <button type="button"
                                                    @click="selectProvince(province)"
                                                    class="w-full px-4 py-3 text-left hover:bg-cyan-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center text-white">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-800 dark:text-white" x-text="province.name"></div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        <span x-text="province.region"></span>
                                                        <span class="mx-1">•</span>
                                                        <span x-text="province.hotel_count + ' โรงแรม'"></span>
                                                    </div>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                {{-- Hotels --}}
                                <template x-if="suggestions.hotels.length > 0">
                                    <div>
                                        <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-slate-700/50">
                                            โรงแรม
                                        </div>
                                        <template x-for="hotel in suggestions.hotels" :key="'h-' + hotel.id">
                                            <a :href="'/hotels/' + hotel.id"
                                               class="w-full px-4 py-3 text-left hover:bg-cyan-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-3 block">
                                                <img :src="hotel.image" :alt="hotel.name" class="w-10 h-10 rounded-lg object-cover">
                                                <div>
                                                    <div class="font-medium text-gray-800 dark:text-white" x-text="hotel.name"></div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="hotel.city + (hotel.province ? ', ' + hotel.province : '')"></div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Check-in Date --}}
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">วันเช็คอิน</label>
                            <input type="date"
                                   name="check_in"
                                   value="{{ request('check_in', date('Y-m-d')) }}"
                                   min="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-slate-700/90 border-0 text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-400 transition-all">
                        </div>

                        {{-- Check-out Date --}}
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">วันเช็คเอาท์</label>
                            <input type="date"
                                   name="check_out"
                                   value="{{ request('check_out', date('Y-m-d', strtotime('+1 day'))) }}"
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                   class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-slate-700/90 border-0 text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-400 transition-all">
                        </div>

                        {{-- Guests --}}
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">ผู้เข้าพัก</label>
                            <select name="adults"
                                    class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-slate-700/90 border-0 text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-400 transition-all">
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ request('adults', 2) == $i ? 'selected' : '' }}>
                                        {{ $i }} ผู้ใหญ่
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    {{-- Province/Region Quick Filter --}}
                    <div class="mb-4" x-data="{ showFilters: false }">
                        <button type="button" @click="showFilters = !showFilters"
                                class="flex items-center gap-2 text-white/80 hover:text-white text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': showFilters}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span x-text="showFilters ? 'ซ่อนตัวกรองเพิ่มเติม' : 'แสดงตัวกรองเพิ่มเติม'"></span>
                        </button>

                        <div x-show="showFilters" x-collapse class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                            {{-- Province Select --}}
                            <div>
                                <label class="block text-sm font-semibold text-white/80 mb-2">จังหวัด</label>
                                <select name="province_id"
                                        class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-slate-700/90 border-0 text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-400 transition-all">
                                    <option value="">ทุกจังหวัด</option>
                                    @isset($provinces)
                                        @foreach($provinces as $province)
                                            <option value="{{ $province->id }}" {{ request('province_id') == $province->id ? 'selected' : '' }}>
                                                {{ $province->name_th }}
                                            </option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>

                            {{-- Price Range --}}
                            <div>
                                <label class="block text-sm font-semibold text-white/80 mb-2">ช่วงราคา (บาท/คืน)</label>
                                <div class="flex gap-2">
                                    <input type="number" name="min_price" value="{{ request('min_price') }}"
                                           placeholder="ต่ำสุด" class="w-full px-3 py-3 rounded-xl bg-white/90 dark:bg-slate-700/90 border-0 text-gray-800 dark:text-white text-sm">
                                    <input type="number" name="max_price" value="{{ request('max_price') }}"
                                           placeholder="สูงสุด" class="w-full px-3 py-3 rounded-xl bg-white/90 dark:bg-slate-700/90 border-0 text-gray-800 dark:text-white text-sm">
                                </div>
                            </div>

                            {{-- Hotel Type --}}
                            <div>
                                <label class="block text-sm font-semibold text-white/80 mb-2">ประเภท</label>
                                <select name="type" class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-slate-700/90 border-0 text-gray-800 dark:text-white">
                                    <option value="">ทั้งหมด</option>
                                    <option value="hotel" {{ request('type') == 'hotel' ? 'selected' : '' }}>โรงแรม</option>
                                    <option value="resort" {{ request('type') == 'resort' ? 'selected' : '' }}>รีสอร์ท</option>
                                    <option value="hostel" {{ request('type') == 'hostel' ? 'selected' : '' }}>โฮสเทล</option>
                                    <option value="villa" {{ request('type') == 'villa' ? 'selected' : '' }}>วิลล่า</option>
                                    <option value="apartment" {{ request('type') == 'apartment' ? 'selected' : '' }}>อพาร์ทเมนท์</option>
                                    <option value="guesthouse" {{ request('type') == 'guesthouse' ? 'selected' : '' }}>เกสต์เฮาส์</option>
                                </select>
                            </div>

                            {{-- Star Rating --}}
                            <div>
                                <label class="block text-sm font-semibold text-white/80 mb-2">ระดับดาว</label>
                                <select name="star_rating" class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-slate-700/90 border-0 text-gray-800 dark:text-white">
                                    <option value="">ทั้งหมด</option>
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" {{ request('star_rating') == $i ? 'selected' : '' }}>
                                            {{ $i }} ดาวขึ้นไป
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Search Button --}}
                    <button type="submit"
                            class="w-full bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white font-bold py-4 px-8 rounded-xl shadow-lg shadow-cyan-500/30 hover:shadow-cyan-500/50 transition-all duration-300 transform hover:scale-[1.02]">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            ค้นหาโรงแรม
                        </span>
                    </button>
                </form>

                {{-- GPS Status --}}
                <div x-show="locationStatus" x-transition
                     class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/20 text-white text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <span x-text="locationStatus"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Region Quick Links --}}
    @isset($regions)
    <div class="container mx-auto px-4 -mt-6 mb-8 relative z-10">
        <div class="backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-xl p-4 border border-white/50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3">ค้นหาตามภูมิภาค</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($regions as $regionData)
                    <a href="{{ route('hotels.by-region', $regionData['code']) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all
                              {{ isset($region) && $region == $regionData['code']
                                 ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-md'
                                 : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600' }}">
                        {{ $regionData['name'] }}
                        <span class="text-xs opacity-70">({{ number_format($regionData['hotel_count']) }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @endisset

    {{-- Results Section --}}
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">
                    @if($hotels->total() > 0)
                        พบ {{ number_format($hotels->total()) }} โรงแรม
                    @else
                        โรงแรมแนะนำ
                    @endif
                </h2>
                @if(isset($selectedProvince))
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        ใน{{ $selectedProvince->name_th }} ({{ $selectedProvince->region_name }})
                    </p>
                @endif
            </div>

            {{-- Sort Options --}}
            <form method="GET" action="{{ route('hotels.index') }}" class="flex items-center gap-3">
                @foreach(request()->except('sort_by') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                <label class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">เรียงตาม:</label>
                <select name="sort_by" onchange="this.form.submit()"
                        class="px-4 py-2 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-gray-300 text-sm focus:ring-2 focus:ring-cyan-400 focus:border-transparent">
                    <option value="popular" {{ request('sort_by') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                    <option value="price_low" {{ request('sort_by') == 'price_low' ? 'selected' : '' }}>ราคา: ต่ำ-สูง</option>
                    <option value="price_high" {{ request('sort_by') == 'price_high' ? 'selected' : '' }}>ราคา: สูง-ต่ำ</option>
                    <option value="rating" {{ request('sort_by') == 'rating' ? 'selected' : '' }}>คะแนนรีวิว</option>
                    <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>ชื่อ A-Z</option>
                </select>
            </form>
        </div>

        {{-- Hotel Grid --}}
        @if($hotels->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($hotels as $hotel)
                    <a href="{{ route('hotels.show', $hotel->slug) }}"
                       class="group bg-white dark:bg-slate-800 rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 dark:border-slate-700">
                        {{-- Image --}}
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <img src="{{ $hotel->main_image_url }}"
                                 alt="{{ $hotel->name }}"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">

                            {{-- Badges --}}
                            <div class="absolute top-3 left-3 flex flex-col gap-2">
                                @if($hotel->is_featured)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gradient-to-r from-amber-400 to-orange-500 text-white text-xs font-bold shadow-lg">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        แนะนำ
                                    </span>
                                @endif
                            </div>

                            {{-- Star Rating --}}
                            @if($hotel->star_rating)
                                <div class="absolute top-3 right-3 flex items-center gap-1 px-2 py-1 rounded-full bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm text-xs font-bold text-amber-500">
                                    @for($i = 0; $i < $hotel->star_rating; $i++)
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                            @endif

                            {{-- Distance Badge (if GPS search) --}}
                            @if(isset($hotel->distance))
                                <div class="absolute bottom-3 left-3 px-2 py-1 rounded-full bg-cyan-500 text-white text-xs font-semibold">
                                    {{ number_format($hotel->distance, 1) }} กม.
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="p-4">
                            <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-1 line-clamp-1 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                {{ $hotel->name }}
                            </h3>

                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-3">
                                <svg class="w-4 h-4 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                @if($hotel->province)
                                    {{ $hotel->province->name_th }}
                                @else
                                    {{ $hotel->city }}
                                @endif
                            </div>

                            {{-- Rating --}}
                            @if($hotel->rating > 0)
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-500 text-white text-sm font-bold">
                                        {{ number_format($hotel->rating, 1) }}
                                    </span>
                                    <div>
                                        <div class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            @if($hotel->rating >= 4.5) ยอดเยี่ยม
                                            @elseif($hotel->rating >= 4) ดีมาก
                                            @elseif($hotel->rating >= 3.5) ดี
                                            @else พอใช้
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ number_format($hotel->review_count) }} รีวิว
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Price --}}
                            <div class="pt-3 border-t border-gray-100 dark:border-slate-700 flex items-end justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">เริ่มต้น</p>
                                    <p class="text-2xl font-bold bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">
                                        ฿{{ number_format($hotel->lowest_price) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">ต่อคืน</p>
                                </div>
                                <span class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 text-sm font-medium group-hover:bg-cyan-500 group-hover:text-white transition-colors">
                                    ดูรายละเอียด
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-12 flex justify-center">
                {{ $hotels->appends(request()->query())->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="text-center py-16">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 dark:bg-slate-800 flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">ไม่พบโรงแรม</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">ลองเปลี่ยนเงื่อนไขการค้นหาหรือขยายรัศมีการค้นหา</p>
                <a href="{{ route('hotels.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 text-white font-semibold hover:from-cyan-600 hover:to-blue-600 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    ล้างตัวกรอง
                </a>
            </div>
        @endif
    </div>

    {{-- Popular Destinations --}}
    @if(isset($popularDestinations) && $popularDestinations->count() > 0)
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800 dark:to-slate-900 py-12">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">จุดหมายยอดนิยม</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($popularDestinations as $destination)
                        <a href="{{ route('hotels.by-city', $destination->city) }}"
                           class="group bg-white dark:bg-slate-800 rounded-xl p-4 text-center shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 dark:border-slate-700">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gradient-to-br from-cyan-100 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/30 flex items-center justify-center group-hover:from-cyan-500 group-hover:to-blue-500 transition-all">
                                <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-800 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                {{ $destination->city }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $destination->hotel_count }} โรงแรม</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
/**
 * Alpine.js component สำหรับค้นหาโรงแรม
 */
function hotelSearch() {
    return {
        // ข้อมูลการค้นหา
        searchQuery: '{{ request("city", "") }}',
        latitude: '{{ request("latitude", "") }}',
        longitude: '{{ request("longitude", "") }}',
        selectedProvinceId: '{{ request("province_id", "") }}',

        // UI state
        showSuggestions: false,
        loadingLocation: false,
        locationStatus: null,

        // ผลลัพธ์ autocomplete
        suggestions: {
            provinces: [],
            hotels: []
        },

        /**
         * ค้นหา autocomplete
         */
        async autocomplete() {
            if (this.searchQuery.length < 2) {
                this.suggestions = { provinces: [], hotels: [] };
                return;
            }

            try {
                const response = await fetch(`{{ route('hotels.autocomplete') }}?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();
                this.suggestions = data;
                this.showSuggestions = true;
            } catch (error) {
                console.error('Autocomplete error:', error);
            }
        },

        /**
         * เลือกจังหวัด
         */
        selectProvince(province) {
            this.searchQuery = province.name;
            this.selectedProvinceId = province.id;
            this.latitude = province.latitude;
            this.longitude = province.longitude;
            this.showSuggestions = false;
        },

        /**
         * ดึงตำแหน่ง GPS
         */
        async getLocation() {
            if (!navigator.geolocation) {
                this.locationStatus = 'เบราว์เซอร์ไม่รองรับ GPS';
                return;
            }

            this.loadingLocation = true;
            this.locationStatus = 'กำลังค้นหาตำแหน่ง...';

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    this.latitude = position.coords.latitude;
                    this.longitude = position.coords.longitude;

                    // ค้นหาจังหวัดใกล้ที่สุด
                    try {
                        const response = await fetch('{{ route("hotels.nearby") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                latitude: this.latitude,
                                longitude: this.longitude,
                                radius: 100
                            })
                        });

                        const data = await response.json();

                        if (data.nearest_province) {
                            this.searchQuery = data.nearest_province.name;
                            this.locationStatus = `พบ: ${data.nearest_province.name}`;
                        } else {
                            this.locationStatus = 'พร้อมค้นหาใกล้เคียง';
                        }
                    } catch (error) {
                        this.locationStatus = 'พร้อมค้นหาใกล้เคียง';
                    }

                    this.loadingLocation = false;
                },
                (error) => {
                    this.loadingLocation = false;
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            this.locationStatus = 'ไม่ได้รับอนุญาตเข้าถึงตำแหน่ง';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            this.locationStatus = 'ไม่สามารถระบุตำแหน่งได้';
                            break;
                        case error.TIMEOUT:
                            this.locationStatus = 'หมดเวลาค้นหาตำแหน่ง';
                            break;
                        default:
                            this.locationStatus = 'เกิดข้อผิดพลาด';
                    }

                    // ซ่อนข้อความหลัง 3 วินาที
                    setTimeout(() => {
                        this.locationStatus = null;
                    }, 3000);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
    };
}
</script>
@endpush

@push('styles')
<style>
/* Animation classes */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}

/* Custom scrollbar for suggestions */
.suggestions-scroll::-webkit-scrollbar {
    width: 4px;
}

.suggestions-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.suggestions-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.dark .suggestions-scroll::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
@endpush
@endsection
