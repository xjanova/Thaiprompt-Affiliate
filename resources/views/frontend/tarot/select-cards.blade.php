@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 overflow-hidden">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2" id="header-title">
                กำลังสับไพ่...
            </h1>
            <p class="text-purple-200 text-lg" id="header-subtitle">
                โปรดรอสักครู่
            </p>
        </div>

        <!-- Progress Indicator -->
        <div class="max-w-md mx-auto mb-8">
            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-full p-4 border border-white border-opacity-20">
                <div class="flex items-center justify-between text-white text-sm mb-2">
                    <span>เลือกแล้ว: <span id="selected-count">0</span> ใบ</span>
                    <span>ต้องการ: <span id="required-count">{{ $spreadType->card_count }}</span> ใบ</span>
                </div>
                <div class="w-full bg-white bg-opacity-20 rounded-full h-3 overflow-hidden">
                    <div id="progress-bar" class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Tarot Table -->
        <div id="tarot-table" class="relative mx-auto" style="height: 600px; max-width: 1000px; perspective: 1500px;">
            <!-- Table Surface -->
            <div class="absolute inset-0 bg-gradient-to-br from-green-900 to-green-950 rounded-3xl shadow-2xl border-8 border-amber-900 opacity-30"></div>

            <!-- Card Stack (will be populated by JavaScript) -->
            <div id="card-deck" class="absolute inset-0 flex items-center justify-center">
                <!-- Cards will be positioned here -->
            </div>
        </div>

        <!-- Instructions -->
        <div id="instructions" class="text-center mt-8 opacity-0">
            <p class="text-white text-xl font-semibold mb-2">
                ✨ เลือกไพ่ {{ $spreadType->card_count }} ใบ
            </p>
            <p class="text-purple-200">
                ชี้เม้าส์ที่ไพ่เพื่อเลือก (คลิกเพื่อยืนยัน)
            </p>
        </div>

        <!-- Confirm Button (appears when all cards selected) -->
        <div id="confirm-button-container" class="text-center mt-8 opacity-0">
            <button id="confirm-selection" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-12 py-4 rounded-full font-bold text-xl shadow-2xl transform hover:scale-105 transition-all">
                🔮 เปิดไพ่
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
    .tarot-card-back {
        width: 120px;
        height: 180px;
        position: absolute;
        cursor: pointer;
        transform-style: preserve-3d;
        transition: all 0.3s ease;
        border-radius: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: 3px solid #fbbf24;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .tarot-card-back::before {
        content: '🌙';
        font-size: 60px;
        opacity: 0.6;
        position: absolute;
    }

    .tarot-card-back::after {
        content: '';
        position: absolute;
        inset: 10px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
    }

    /* Hover effect */
    .tarot-card-back.hovering {
        z-index: 1000 !important;
        box-shadow:
            0 0 30px rgba(139, 92, 246, 0.8),
            0 0 60px rgba(236, 72, 153, 0.6),
            0 0 90px rgba(59, 130, 246, 0.4),
            0 20px 40px rgba(0, 0, 0, 0.6);
        border-color: #fbbf24;
        animation: rgb-glow 1.5s ease-in-out infinite;
    }

    /* Selected card */
    .tarot-card-back.selected {
        opacity: 0.3;
        pointer-events: none;
        filter: grayscale(1);
    }

    /* RGB Glow Animation */
    @keyframes rgb-glow {
        0%, 100% {
            box-shadow:
                0 0 30px rgba(139, 92, 246, 1),
                0 0 60px rgba(236, 72, 153, 0.8),
                0 20px 40px rgba(0, 0, 0, 0.6);
        }
        25% {
            box-shadow:
                0 0 30px rgba(236, 72, 153, 1),
                0 0 60px rgba(59, 130, 246, 0.8),
                0 20px 40px rgba(0, 0, 0, 0.6);
        }
        50% {
            box-shadow:
                0 0 30px rgba(59, 130, 246, 1),
                0 0 60px rgba(139, 92, 246, 0.8),
                0 20px 40px rgba(0, 0, 0, 0.6);
        }
        75% {
            box-shadow:
                0 0 30px rgba(34, 197, 94, 1),
                0 0 60px rgba(236, 72, 153, 0.8),
                0 20px 40px rgba(0, 0, 0, 0.6);
        }
    }

    /* Pulse Animation */
    @keyframes pulse-scale {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.02);
        }
    }

    .tarot-card-back.hovering::before {
        animation: pulse-scale 1s ease-in-out infinite;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cardDeck = document.getElementById('card-deck');
    const headerTitle = document.getElementById('header-title');
    const headerSubtitle = document.getElementById('header-subtitle');
    const instructions = document.getElementById('instructions');
    const progressBar = document.getElementById('progress-bar');
    const selectedCountEl = document.getElementById('selected-count');
    const confirmButtonContainer = document.getElementById('confirm-button-container');
    const confirmButton = document.getElementById('confirm-selection');

    const requiredCards = {{ $spreadType->card_count }};
    const totalCards = 78; // Full tarot deck

    let selectedCards = [];
    let cards = [];
    let currentHoveredCard = null;

    // Calculate card positions in an arc/fan layout with overlap
    function calculateCardPositions(totalCards) {
        const positions = [];
        const containerWidth = 1000;
        const containerHeight = 600;
        const centerX = containerWidth / 2;
        const centerY = containerHeight / 2;

        // Create overlapping fan layout
        const totalWidth = containerWidth * 0.9;
        const cardWidth = 120;
        const overlapAmount = (totalCards * cardWidth - totalWidth) / (totalCards - 1);
        const startX = (containerWidth - totalWidth) / 2;

        // Slight arc
        const arcHeight = 100;

        for (let i = 0; i < totalCards; i++) {
            const progress = i / (totalCards - 1);
            const x = startX + (i * (cardWidth - overlapAmount));

            // Parabolic arc
            const y = centerY - 100 + (arcHeight * Math.sin(progress * Math.PI));

            // Slight rotation for natural look
            const rotation = (progress - 0.5) * 5; // -2.5 to 2.5 degrees

            positions.push({
                x: x,
                y: y,
                rotation: rotation,
                scale: 0.95 + (Math.sin(progress * Math.PI) * 0.05) // Slight scale variation
            });
        }

        return positions;
    }

    // Create cards
    function createCards() {
        const positions = calculateCardPositions(totalCards);

        for (let i = 0; i < totalCards; i++) {
            const card = document.createElement('div');
            card.className = 'tarot-card-back';
            card.dataset.cardIndex = i;

            // Set initial position (stacked in center for dealing animation)
            gsap.set(card, {
                x: 450,
                y: 200,
                rotation: 0,
                scale: 1,
                opacity: 0,
                zIndex: i
            });

            // Add event listeners
            card.addEventListener('mouseenter', handleCardHover);
            card.addEventListener('mouseleave', handleCardLeave);
            card.addEventListener('click', handleCardClick);

            cardDeck.appendChild(card);
            cards.push(card);
        }
    }

    // Deal cards animation
    function dealCards() {
        const positions = calculateCardPositions(totalCards);

        // Animate each card to its position with stagger
        cards.forEach((card, index) => {
            gsap.to(card, {
                duration: 0.8,
                delay: index * 0.03, // Stagger effect
                x: positions[index].x,
                y: positions[index].y,
                rotation: positions[index].rotation,
                scale: positions[index].scale,
                opacity: 1,
                ease: "power2.out",
                onComplete: () => {
                    if (index === cards.length - 1) {
                        // All cards dealt
                        showInstructions();
                    }
                }
            });
        });
    }

    // Show instructions
    function showInstructions() {
        gsap.to(headerTitle, {
            duration: 0.5,
            opacity: 0,
            y: -20,
            onComplete: () => {
                headerTitle.textContent = '🔮 เลือกไพ่ทาโร่ของคุณ';
                gsap.to(headerTitle, { duration: 0.5, opacity: 1, y: 0 });
            }
        });

        gsap.to(headerSubtitle, {
            duration: 0.5,
            opacity: 0,
            onComplete: () => {
                headerSubtitle.textContent = 'ใช้สัญชาตญาณของคุณในการเลือกไพ่';
                gsap.to(headerSubtitle, { duration: 0.5, opacity: 1 });
            }
        });

        gsap.to(instructions, {
            duration: 0.5,
            opacity: 1,
            y: 0,
            delay: 0.3
        });
    }

    // Handle card hover
    function handleCardHover(e) {
        const card = e.currentTarget;

        // Don't hover if already selected
        if (card.classList.contains('selected')) return;

        // Remove hover from previous card
        if (currentHoveredCard && currentHoveredCard !== card) {
            currentHoveredCard.classList.remove('hovering');
        }

        currentHoveredCard = card;
        card.classList.add('hovering');

        // GSAP animation for hover
        gsap.to(card, {
            duration: 0.3,
            y: '-=30',
            scale: 1.15,
            rotation: 0,
            zIndex: 9999,
            ease: "power2.out"
        });
    }

    // Handle card leave
    function handleCardLeave(e) {
        const card = e.currentTarget;

        if (card.classList.contains('selected')) return;

        card.classList.remove('hovering');

        const positions = calculateCardPositions(totalCards);
        const index = parseInt(card.dataset.cardIndex);

        gsap.to(card, {
            duration: 0.3,
            y: positions[index].y,
            scale: positions[index].scale,
            rotation: positions[index].rotation,
            zIndex: index,
            ease: "power2.out"
        });
    }

    // Handle card click
    function handleCardClick(e) {
        const card = e.currentTarget;

        if (card.classList.contains('selected')) return;
        if (selectedCards.length >= requiredCards) return;

        // Mark as selected
        card.classList.remove('hovering');
        card.classList.add('selected');
        selectedCards.push(parseInt(card.dataset.cardIndex));

        // Update progress
        updateProgress();

        // Animate card away
        gsap.to(card, {
            duration: 0.5,
            y: '-=100',
            opacity: 0.3,
            scale: 0.8,
            ease: "power2.in"
        });

        // Check if all cards selected
        if (selectedCards.length === requiredCards) {
            showConfirmButton();
        }
    }

    // Update progress bar
    function updateProgress() {
        const progress = (selectedCards.length / requiredCards) * 100;
        selectedCountEl.textContent = selectedCards.length;

        gsap.to(progressBar, {
            duration: 0.5,
            width: progress + '%',
            ease: "power2.out"
        });
    }

    // Show confirm button
    function showConfirmButton() {
        gsap.to(instructions, {
            duration: 0.5,
            opacity: 0,
            y: -20
        });

        gsap.to(confirmButtonContainer, {
            duration: 0.5,
            opacity: 1,
            scale: 1,
            ease: "back.out(1.7)",
            delay: 0.3
        });
    }

    // Confirm selection
    confirmButton.addEventListener('click', function() {
        // Disable button
        confirmButton.disabled = true;
        confirmButton.innerHTML = '🔮 กำลังเปิดไพ่...';

        // Send selected cards to server
        fetch('{{ route('tarot.save-selection') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                reading_id: '{{ $readingId }}',
                selected_card_indices: selectedCards
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to reading result
                window.location.href = data.redirect_url;
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
                confirmButton.disabled = false;
                confirmButton.innerHTML = '🔮 เปิดไพ่';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            confirmButton.disabled = false;
            confirmButton.innerHTML = '🔮 เปิดไพ่';
        });
    });

    // Initialize
    createCards();

    // Start dealing animation after short delay
    setTimeout(() => {
        dealCards();
    }, 500);
});
</script>
@endpush
@endsection
