<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตัวอย่าง: {{ $advertisement->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .ad-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .countdown {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            font-size: 24px;
            font-weight: bold;
            z-index: 1000;
            backdrop-filter: blur(10px);
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .ad-content {
            animation: fadeInScale 0.5s ease-out;
            max-width: 90vw;
            max-height: 90vh;
        }
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .close-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 1000;
        }
        .close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="close-btn" onclick="window.close()">
        <i class="fas fa-times mr-2"></i>
        ปิด
    </div>

    <div class="countdown" id="countdown">
        <i class="fas fa-clock mr-2"></i>
        <span id="timeLeft">{{ $advertisement->duration_seconds }}</span>
    </div>

    <div class="ad-container">
        <div class="ad-content">
            @if($advertisement->type === 'image' && $advertisement->image_url)
                <img src="{{ Storage::url($advertisement->image_url) }}"
                     alt="{{ $advertisement->title }}"
                     class="rounded-2xl shadow-2xl max-w-full max-h-full object-contain"
                     @if($advertisement->link_url)
                         onclick="window.open('{{ $advertisement->link_url }}', '{{ $advertisement->link_opens_new_tab ? '_blank' : '_self' }}')"
                         style="cursor: pointer;"
                     @endif>

            @elseif($advertisement->type === 'video' && $advertisement->video_url)
                <video autoplay loop muted
                       class="rounded-2xl shadow-2xl max-w-full max-h-full"
                       @if($advertisement->link_url)
                           onclick="window.open('{{ $advertisement->link_url }}', '{{ $advertisement->link_opens_new_tab ? '_blank' : '_self' }}')"
                           style="cursor: pointer;"
                       @endif>
                    <source src="{{ Storage::url($advertisement->video_url) }}" type="video/mp4">
                </video>

            @elseif($advertisement->type === 'html' && $advertisement->html_content)
                <div class="bg-white rounded-2xl shadow-2xl p-12">
                    {!! $advertisement->html_content !!}
                </div>

            @elseif($advertisement->type === 'promotion')
                <div class="bg-gradient-to-br from-yellow-400 via-orange-500 to-red-600 text-white p-16 rounded-3xl shadow-2xl text-center max-w-2xl"
                     @if($advertisement->link_url)
                         onclick="window.open('{{ $advertisement->link_url }}', '{{ $advertisement->link_opens_new_tab ? '_blank' : '_self' }}')"
                         style="cursor: pointer;"
                     @endif>
                    <div class="animate-bounce mb-6">
                        <i class="fas fa-gift text-8xl"></i>
                    </div>
                    <h1 class="text-6xl font-bold mb-6">{{ $advertisement->title }}</h1>

                    @if($advertisement->promotion_code)
                        <div class="bg-white text-gray-900 py-4 px-8 rounded-xl text-3xl font-mono font-bold mb-6 inline-block">
                            {{ $advertisement->promotion_code }}
                        </div>
                    @endif

                    @if($advertisement->discount_percentage)
                        <div class="mb-4">
                            <p class="text-8xl font-bold mb-2">{{ $advertisement->discount_percentage }}%</p>
                            <p class="text-3xl">ส่วนลดพิเศษ</p>
                        </div>
                    @elseif($advertisement->discount_amount)
                        <div class="mb-4">
                            <p class="text-8xl font-bold mb-2">฿{{ number_format($advertisement->discount_amount) }}</p>
                            <p class="text-3xl">ส่วนลดพิเศษ</p>
                        </div>
                    @endif

                    @if($advertisement->description)
                        <p class="text-2xl mt-6 text-white/90">{{ $advertisement->description }}</p>
                    @endif

                    @if($advertisement->promotion_end)
                        <p class="text-xl mt-8 bg-white/20 py-3 px-6 rounded-full inline-block">
                            <i class="fas fa-clock mr-2"></i>
                            จนถึง {{ \Carbon\Carbon::parse($advertisement->promotion_end)->format('d/m/Y') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <script>
        let timeLeft = {{ $advertisement->duration_seconds }};
        const countdownEl = document.getElementById('timeLeft');

        const timer = setInterval(() => {
            timeLeft--;
            countdownEl.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(timer);
                window.close();
            }
        }, 1000);
    </script>
</body>
</html>
