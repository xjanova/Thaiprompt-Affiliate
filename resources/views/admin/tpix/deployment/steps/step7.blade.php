@extends('admin.tpix.deployment._wizard-layout')

@section('step-content')
<form method="POST" action="{{ route('admin.tpix.deployment.step7.save', $config->slug) }}"
      x-data="{
          submittingCMC: false,
          submittingCG: false,
          cmcResult: null,
          cgResult: null,

          async submitToCMC() {
              if (!confirm('ต้องการ Submit ไปยัง CoinMarketCap หรือไม่?')) {
                  return;
              }

              this.submittingCMC = true;
              this.cmcResult = null;

              try {
                  const response = await fetch('{{ route('admin.tpix.deployment.step7.submit-cmc', $config->slug) }}', {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'X-CSRF-TOKEN': '{{ csrf_token() }}'
                      }
                  });

                  const data = await response.json();
                  this.cmcResult = data;
              } catch (error) {
                  this.cmcResult = {
                      success: false,
                      message: 'เกิดข้อผิดพลาด: ' + error.message
                  };
              } finally {
                  this.submittingCMC = false;
              }
          },

          async submitToCG() {
              if (!confirm('ต้องการ Submit ไปยัง CoinGecko หรือไม่?')) {
                  return;
              }

              this.submittingCG = true;
              this.cgResult = null;

              try {
                  const response = await fetch('{{ route('admin.tpix.deployment.step7.submit-coingecko', $config->slug) }}', {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'X-CSRF-TOKEN': '{{ csrf_token() }}'
                      }
                  });

                  const data = await response.json();
                  this.cgResult = data;
              } catch (error) {
                  this.cgResult = {
                      success: false,
                      message: 'เกิดข้อผิดพลาด: ' + error.message
                  };
              } finally {
                  this.submittingCG = false;
              }
          }
      }" class="space-y-8">
    @csrf

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">
            Step 7: Listing & Marketing
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
            เตรียมข้อมูลสำหรับ List บน CoinMarketCap, CoinGecko และตั้งค่า Marketing
        </p>
    </div>

    {{-- Deployment Summary --}}
    <div class="p-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border-2 border-green-500">
        <h3 class="text-xl font-semibold text-green-900 dark:text-green-100 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            🎉 สรุป Deployment
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Token</div>
                <div class="font-semibold text-gray-900 dark:text-white">{{ $config->token_name }} ({{ $config->token_symbol }})</div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Supply</div>
                <div class="font-semibold text-gray-900 dark:text-white">{{ number_format($config->total_supply) }}</div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Contract Address</div>
                <div class="text-xs font-mono text-gray-900 dark:text-white truncate">{{ $config->contract_address }}</div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">DEX Pool</div>
                <div class="text-xs font-mono text-gray-900 dark:text-white truncate">{{ $config->dex_pool_address }}</div>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <a href="{{ config('tpix.explorer_url') }}/address/{{ $config->contract_address }}"
               target="_blank"
               class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-center rounded-lg transition text-sm">
                Block Explorer
            </a>
            <a href="{{ config('tpix.dex_url') }}/swap?outputCurrency={{ $config->contract_address }}"
               target="_blank"
               class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-center rounded-lg transition text-sm">
                Swap on DEX
            </a>
        </div>
    </div>

    {{-- CoinMarketCap Listing --}}
    <div class="p-6 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
            </svg>
            CoinMarketCap Listing
        </h3>

        @if($config->cmc_listing_status === 'listed')
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-xl mb-6">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-green-900 dark:text-green-100">Listed on CoinMarketCap!</h4>
                        @if($config->cmc_url)
                            <a href="{{ $config->cmc_url }}" target="_blank" class="text-sm text-green-600 hover:underline">
                                ดูหน้า CoinMarketCap →
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($config->cmc_listing_status === 'pending')
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-r-xl mb-6">
                <p class="text-yellow-800 dark:text-yellow-200">
                    ⏳ รอการอนุมัติจาก CoinMarketCap (โดยปกติใช้เวลา 3-7 วัน)
                </p>
            </div>
        @else
            <div class="space-y-4">
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">ข้อมูลที่ต้องเตรียม</h4>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Official Website URL</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Block Explorer URL (Contract Address)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Logo (200x200px PNG, transparent background)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Social Media Links (Twitter, Telegram, Discord)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Project Description (English, 200-500 words)</span>
                        </li>
                    </ul>
                </div>

                <button type="button" @click="submitToCMC()"
                        :disabled="submittingCMC"
                        class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-semibold rounded-xl transition flex items-center justify-center gap-2">
                    <svg x-show="!submittingCMC" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    <div x-show="submittingCMC" class="inline-block animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    <span x-text="submittingCMC ? 'กำลัง Submit...' : 'Submit to CoinMarketCap'"></span>
                </button>
            </div>
        @endif

        {{-- CMC Result --}}
        <div x-show="cmcResult" class="mt-4">
            <div x-show="cmcResult?.success"
                 class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-xl">
                <p class="text-sm text-green-800 dark:text-green-200" x-text="cmcResult?.message"></p>
            </div>
            <div x-show="cmcResult && !cmcResult?.success"
                 class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-r-xl">
                <p class="text-sm text-red-800 dark:text-red-200" x-text="cmcResult?.message"></p>
            </div>
        </div>
    </div>

    {{-- CoinGecko Listing --}}
    <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path>
            </svg>
            CoinGecko Listing
        </h3>

        @if($config->coingecko_listing_status === 'listed')
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-xl mb-6">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-green-900 dark:text-green-100">Listed on CoinGecko!</h4>
                        @if($config->coingecko_url)
                            <a href="{{ $config->coingecko_url }}" target="_blank" class="text-sm text-green-600 hover:underline">
                                ดูหน้า CoinGecko →
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($config->coingecko_listing_status === 'pending')
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-r-xl mb-6">
                <p class="text-yellow-800 dark:text-yellow-200">
                    ⏳ รอการอนุมัติจาก CoinGecko (โดยปกติใช้เวลา 7-14 วัน)
                </p>
            </div>
        @else
            <div class="space-y-4">
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">ข้อกำหนดของ CoinGecko</h4>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>มี Trading Volume อย่างน้อย $10,000/วัน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Active Community (Twitter, Telegram มียูสเซอร์จริง)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Contract Verified บน Block Explorer</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Legitimate Project (ไม่ใช่ Scam/Rug Pull)</span>
                        </li>
                    </ul>
                </div>

                <button type="button" @click="submitToCG()"
                        :disabled="submittingCG"
                        class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-semibold rounded-xl transition flex items-center justify-center gap-2">
                    <svg x-show="!submittingCG" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    <div x-show="submittingCG" class="inline-block animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    <span x-text="submittingCG ? 'กำลัง Submit...' : 'Submit to CoinGecko'"></span>
                </button>
            </div>
        @endif

        {{-- CG Result --}}
        <div x-show="cgResult" class="mt-4">
            <div x-show="cgResult?.success"
                 class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-xl">
                <p class="text-sm text-green-800 dark:text-green-200" x-text="cgResult?.message"></p>
            </div>
            <div x-show="cgResult && !cgResult?.success"
                 class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-r-xl">
                <p class="text-sm text-red-800 dark:text-red-200" x-text="cgResult?.message"></p>
            </div>
        </div>
    </div>

    {{-- Marketing Checklist --}}
    <div class="p-6 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"></path>
            </svg>
            Marketing & Community
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Social Media --}}
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                    </svg>
                    Social Media
                </h4>
                <div class="space-y-2 text-sm">
                    @if($config->social_links && isset($config->social_links['twitter']))
                        <a href="{{ $config->social_links['twitter'] }}" target="_blank"
                           class="flex items-center gap-2 text-blue-600 hover:underline">
                            <span>Twitter/X</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    @endif
                    @if($config->social_links && isset($config->social_links['telegram']))
                        <a href="{{ $config->social_links['telegram'] }}" target="_blank"
                           class="flex items-center gap-2 text-blue-600 hover:underline">
                            <span>Telegram</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    @endif
                    @if($config->social_links && isset($config->social_links['discord']))
                        <a href="{{ $config->social_links['discord'] }}" target="_blank"
                           class="flex items-center gap-2 text-blue-600 hover:underline">
                            <span>Discord</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Resources --}}
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                    </svg>
                    Resources
                </h4>
                <div class="space-y-2 text-sm">
                    @if($config->website_url)
                        <a href="{{ $config->website_url }}" target="_blank"
                           class="flex items-center gap-2 text-purple-600 hover:underline">
                            <span>Official Website</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    @endif
                    @if($config->whitepaper_url)
                        <a href="{{ $config->whitepaper_url }}" target="_blank"
                           class="flex items-center gap-2 text-purple-600 hover:underline">
                            <span>Whitepaper</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    @endif
                    @if($config->logo_url)
                        <a href="{{ $config->logo_url }}" target="_blank"
                           class="flex items-center gap-2 text-purple-600 hover:underline">
                            <span>Logo Download</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-white dark:bg-gray-800 rounded-xl">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Marketing Checklist</h4>
            <div class="space-y-2 text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="marketing_checklist[]" value="press_release"
                           {{ in_array('press_release', old('marketing_checklist', $config->marketing_checklist ?? [])) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span class="text-gray-700 dark:text-gray-300">เขียน Press Release/Blog Post</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="marketing_checklist[]" value="social_announcement"
                           {{ in_array('social_announcement', old('marketing_checklist', $config->marketing_checklist ?? [])) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span class="text-gray-700 dark:text-gray-300">ประกาศบน Social Media ทุก Channel</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="marketing_checklist[]" value="community_campaign"
                           {{ in_array('community_campaign', old('marketing_checklist', $config->marketing_checklist ?? [])) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span class="text-gray-700 dark:text-gray-300">จัด Community Campaign/Airdrop</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="marketing_checklist[]" value="influencer_outreach"
                           {{ in_array('influencer_outreach', old('marketing_checklist', $config->marketing_checklist ?? [])) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span class="text-gray-700 dark:text-gray-300">ติดต่อ Crypto Influencers/Reviewers</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="marketing_checklist[]" value="listing_sites"
                           {{ in_array('listing_sites', old('marketing_checklist', $config->marketing_checklist ?? [])) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span class="text-gray-700 dark:text-gray-300">List บน Token Listing Sites อื่นๆ</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Success Celebration --}}
    @if($config->status === 'listed')
        <div class="p-8 bg-gradient-to-r from-purple-100 via-pink-100 to-red-100 dark:from-purple-900/30 dark:via-pink-900/30 dark:to-red-900/30 rounded-2xl border-2 border-purple-400 text-center">
            <div class="text-6xl mb-4">🎉🚀✨</div>
            <h2 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-3">
                ยินดีด้วย! คุณสร้าง Native Coin สำเร็จแล้ว!
            </h2>
            <p class="text-lg text-gray-700 dark:text-gray-300 mb-6">
                {{ $config->token_name }} ({{ $config->token_symbol }}) พร้อมให้ชุมชนเทรดและใช้งานแล้ว
            </p>
            <div class="flex gap-4 justify-center">
                <a href="{{ route('admin.tpix.deployment.index') }}"
                   class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:shadow-xl transition">
                    กลับสู่หน้ารายการ
                </a>
                <a href="{{ config('tpix.dex_url') }}/swap?outputCurrency={{ $config->contract_address }}"
                   target="_blank"
                   class="px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 text-white font-semibold rounded-xl hover:shadow-xl transition">
                    เริ่มเทรดเลย!
                </a>
            </div>
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex gap-4 pt-6">
        <a href="{{ route('admin.tpix.deployment.step6', $config->slug) }}"
           class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            ← ย้อนกลับ
        </a>

        <button type="submit"
                class="flex-1 px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            บันทึกและเสร็จสิ้น
        </button>
    </div>
</form>
@endsection
