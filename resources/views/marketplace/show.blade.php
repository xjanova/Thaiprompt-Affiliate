@extends('layouts.app')

@section('title', $bot->name . ' - ตลาดบอท')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('marketplace.index') }}">ตลาดบอท</a></li>
            <li class="breadcrumb-item active">{{ $bot->name }}</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Left Column: Bot Details -->
        <div class="col-lg-8">
            <!-- Main Info Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <!-- Avatar -->
                        <div class="col-md-3 text-center mb-3">
                            @if($bot->avatar_url)
                                <img src="{{ $bot->avatar_url }}"
                                     alt="{{ $bot->name }}"
                                     class="rounded-circle img-fluid"
                                     style="max-width: 150px;">
                            @else
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white mx-auto"
                                     style="width: 150px; height: 150px; font-size: 4rem;">
                                    🤖
                                </div>
                            @endif
                        </div>

                        <!-- Details -->
                        <div class="col-md-9">
                            <h2 class="mb-3">{{ $bot->name }}</h2>

                            <p class="text-muted mb-3">{{ $bot->description }}</p>

                            <!-- Meta Info -->
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <strong>เจ้าของ:</strong> {{ $bot->owner->name }}
                                </div>
                                <div class="col-sm-6">
                                    <strong>AI Provider:</strong> {{ $bot->provider->name }}
                                </div>
                                @if($bot->category)
                                    <div class="col-sm-6 mt-2">
                                        <strong>หมวดหมู่:</strong>
                                        <span class="badge bg-secondary">{{ $bot->category }}</span>
                                    </div>
                                @endif
                                <div class="col-sm-6 mt-2">
                                    <strong>Model:</strong> {{ $bot->model_name }}
                                </div>
                            </div>

                            <!-- Features -->
                            <div class="mb-3">
                                <strong>ฟีเจอร์:</strong><br>
                                @if($bot->enable_knowledge_base)
                                    <span class="badge bg-info me-1">📚 RAG Knowledge Base</span>
                                @endif
                                @if($bot->max_context_messages > 0)
                                    <span class="badge bg-success me-1">💬 Context: {{ $bot->max_context_messages }} messages</span>
                                @endif
                                @if($bot->enable_web_search)
                                    <span class="badge bg-warning me-1">🔍 Web Search</span>
                                @endif
                            </div>

                            <!-- Stats -->
                            <div class="row text-center bg-light rounded p-3">
                                <div class="col-3">
                                    <div class="fw-bold text-primary">{{ $rentalStats['active_rentals'] }}</div>
                                    <div class="small text-muted">ผู้เช่าปัจจุบัน</div>
                                </div>
                                <div class="col-3">
                                    <div class="fw-bold text-success">{{ $rentalStats['total_rentals'] }}</div>
                                    <div class="small text-muted">เช่าทั้งหมด</div>
                                </div>
                                <div class="col-3">
                                    <div class="fw-bold text-info">{{ number_format($rentalStats['total_messages']) }}</div>
                                    <div class="small text-muted">ข้อความ</div>
                                </div>
                                <div class="col-3">
                                    <div class="fw-bold text-warning">⭐ 0.0</div>
                                    <div class="small text-muted">คะแนน</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personality & Instructions -->
            @if($bot->personality || $bot->instructions)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">📝 บุคลิกและคำสั่ง</h5>
                    </div>
                    <div class="card-body">
                        @if($bot->personality)
                            <div class="mb-3">
                                <strong>บุคลิก:</strong>
                                <p class="mb-0">{{ $bot->personality }}</p>
                            </div>
                        @endif
                        @if($bot->instructions)
                            <div>
                                <strong>คำสั่งพิเศษ:</strong>
                                <pre class="bg-light p-3 rounded">{{ $bot->instructions }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Knowledge Bases -->
            @if($bot->enable_knowledge_base && $bot->knowledgeBases->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">📚 ฐานความรู้</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            @foreach($bot->knowledgeBases as $kb)
                                <li class="mb-2">
                                    <i class="fas fa-book text-info"></i>
                                    <strong>{{ $kb->name }}</strong>
                                    @if($kb->status === 'completed')
                                        <span class="badge bg-success">✓ พร้อมใช้</span>
                                    @endif
                                    <div class="small text-muted ms-3">
                                        {{ $kb->total_chunks }} chunks • {{ number_format($kb->total_tokens) }} tokens
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Rental Options -->
        <div class="col-lg-4">
            @if($existingRental)
                <!-- Already Rented -->
                <div class="card mb-3 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">✓ คุณกำลังเช่าบอทนี้อยู่</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>ประเภท:</strong> {{ $existingRental->rental_type === 'monthly' ? 'รายเดือน' : 'ต่อข้อความ' }}</p>
                        <p><strong>ราคา:</strong> ฿{{ number_format($existingRental->price, 2) }}</p>
                        @if($existingRental->rental_type === 'monthly')
                            <p><strong>วันหมดอายุ:</strong> {{ $existingRental->end_date->format('d/m/Y') }}</p>
                            <p><strong>คงเหลือ:</strong> {{ $existingRental->days_remaining }} วัน</p>
                        @else
                            <p><strong>ข้อความทั้งหมด:</strong> {{ number_format($existingRental->total_messages) }}</p>
                            <p><strong>ยอดรวม:</strong> ฿{{ number_format($existingRental->total_amount, 2) }}</p>
                        @endif

                        <a href="{{ route('my-rentals.show', $existingRental->id) }}" class="btn btn-primary w-100">
                            จัดการการเช่า
                        </a>
                    </div>
                </div>
            @else
                <!-- Rental Options -->
                @auth
                    @if($bot->rental_price_per_month)
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">💳 เช่ารายเดือน</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="display-6 fw-bold text-primary">
                                        ฿{{ number_format($bot->rental_price_per_month, 2) }}
                                    </div>
                                    <div class="text-muted">ต่อเดือน</div>
                                </div>

                                <ul class="list-unstyled mb-3">
                                    <li><i class="fas fa-check text-success"></i> ใช้ได้ไม่จำกัด</li>
                                    <li><i class="fas fa-check text-success"></i> ต่ออายุอัตโนมัติ</li>
                                    <li><i class="fas fa-check text-success"></i> ยกเลิกได้ทุกเวลา</li>
                                </ul>

                                <form method="POST" action="{{ route('marketplace.rent', $bot->id) }}">
                                    @csrf
                                    <input type="hidden" name="rental_type" value="monthly">

                                    <div class="mb-3">
                                        <label class="form-label">วิธีชำระเงิน</label>
                                        <select name="payment_method" class="form-select" required>
                                            <option value="wallet">Wallet</option>
                                            <option value="credit_card">Credit Card</option>
                                        </select>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="accept_terms_monthly" name="accept_terms" required>
                                        <label class="form-check-label small" for="accept_terms_monthly">
                                            ฉันยอมรับ <a href="#">เงื่อนไขการให้บริการ</a>
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        เช่ารายเดือน
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if($bot->rental_price_per_message)
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">💬 เช่าต่อข้อความ</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="display-6 fw-bold text-success">
                                        ฿{{ number_format($bot->rental_price_per_message, 2) }}
                                    </div>
                                    <div class="text-muted">ต่อข้อความ</div>
                                </div>

                                <ul class="list-unstyled mb-3">
                                    <li><i class="fas fa-check text-success"></i> จ่ายเมื่อใช้งาน</li>
                                    <li><i class="fas fa-check text-success"></i> ไม่มีค่าเช่ารายเดือน</li>
                                    <li><i class="fas fa-check text-success"></i> เหมาะสำหรับใช้เป็นครั้งคราว</li>
                                </ul>

                                <form method="POST" action="{{ route('marketplace.rent', $bot->id) }}">
                                    @csrf
                                    <input type="hidden" name="rental_type" value="per_message">
                                    <input type="hidden" name="payment_method" value="wallet">

                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="accept_terms_message" name="accept_terms" required>
                                        <label class="form-check-label small" for="accept_terms_message">
                                            ฉันยอมรับ <a href="#">เงื่อนไขการให้บริการ</a>
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100">
                                        เช่าต่อข้อความ
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="card">
                        <div class="card-body text-center">
                            <p class="mb-3">กรุณาเข้าสู่ระบบเพื่อเช่าบอท</p>
                            <a href="{{ route('login') }}" class="btn btn-primary">เข้าสู่ระบบ</a>
                        </div>
                    </div>
                @endauth
            @endif

            <!-- Contact Owner -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">👤 ติดต่อเจ้าของ</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>{{ $bot->owner->name }}</strong></p>
                    <p class="text-muted small mb-3">{{ $bot->owner->email }}</p>
                    <a href="mailto:{{ $bot->owner->email }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-envelope"></i> ส่งข้อความ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
