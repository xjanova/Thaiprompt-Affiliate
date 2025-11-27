# AI Bot System - System Design Document

## 🎯 ภาพรวมระบบ

ระบบแชทบอท AI แบบครบวงจรที่รองรับ:
- หลาย AI Providers (OpenAI, Claude, DeepSeek, Gemini, Custom)
- Knowledge Base & RAG (Retrieval-Augmented Generation)
- Rental Service สำหรับให้เช่า AI Bot
- Commission System
- DeepSeek Self-Hosted Installation
- Integration กับ LINE OA

---

## 📊 Database Schema

### 1. `ai_providers` - ผู้ให้บริการ AI

```sql
CREATE TABLE ai_providers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,              -- 'openai', 'claude', 'deepseek', 'gemini', 'custom'
    display_name VARCHAR(255) NOT NULL,      -- 'OpenAI GPT-4', 'Claude 3 Opus', etc.
    provider_type ENUM('cloud', 'self-hosted') DEFAULT 'cloud',
    api_endpoint VARCHAR(500),               -- API endpoint URL
    api_version VARCHAR(50),                 -- v1, v2, etc.
    is_active BOOLEAN DEFAULT true,
    is_available BOOLEAN DEFAULT true,       -- สามารถใช้งานได้หรือไม่
    config JSON,                             -- Provider-specific config
    pricing JSON,                            -- {input_tokens: 0.01, output_tokens: 0.03} per 1K tokens
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. `ai_models` - รายการโมเดล AI

```sql
CREATE TABLE ai_models (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    provider_id BIGINT,                      -- FK to ai_providers
    model_identifier VARCHAR(255) NOT NULL,  -- 'gpt-4', 'claude-3-opus', 'deepseek-chat'
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    context_window INT,                      -- 8192, 32768, 128000, etc.
    max_output_tokens INT,
    supports_functions BOOLEAN DEFAULT false,
    supports_vision BOOLEAN DEFAULT false,
    supports_streaming BOOLEAN DEFAULT true,
    is_active BOOLEAN DEFAULT true,
    pricing JSON,                            -- Override provider pricing if needed
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES ai_providers(id) ON DELETE CASCADE
);
```

### 3. `ai_bot_profiles` - โปรไฟล์บอท

```sql
CREATE TABLE ai_bot_profiles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    provider_id BIGINT,                      -- FK to ai_providers
    model_id BIGINT,                         -- FK to ai_models
    owner_id BIGINT,                         -- FK to users (เจ้าของบอท)

    -- System Prompt Configuration
    system_prompt TEXT,                      -- Base system prompt
    temperature DECIMAL(3,2) DEFAULT 0.7,    -- 0.0 - 2.0
    top_p DECIMAL(3,2) DEFAULT 1.0,
    max_tokens INT DEFAULT 2000,
    presence_penalty DECIMAL(3,2) DEFAULT 0,
    frequency_penalty DECIMAL(3,2) DEFAULT 0,

    -- Response Boundaries
    response_scope JSON,                     -- {topics: [], forbidden_topics: [], language: 'th'}
    allowed_domains JSON,                    -- URLs/domains allowed for knowledge
    max_conversation_length INT DEFAULT 10,  -- จำนวนข้อความย้อนหลัง

    -- Features
    enable_knowledge_base BOOLEAN DEFAULT false,
    enable_web_search BOOLEAN DEFAULT false,
    enable_function_calling BOOLEAN DEFAULT false,
    enable_memory BOOLEAN DEFAULT true,

    -- Rental Settings
    is_rentable BOOLEAN DEFAULT false,
    rental_price_per_month DECIMAL(10,2),
    rental_price_per_message DECIMAL(10,4),
    commission_rate DECIMAL(5,2),            -- % commission for platform

    -- Status
    is_active BOOLEAN DEFAULT true,
    is_public BOOLEAN DEFAULT false,         -- แสดงใน marketplace หรือไม่

    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (provider_id) REFERENCES ai_providers(id),
    FOREIGN KEY (model_id) REFERENCES ai_models(id),
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 4. `ai_knowledge_bases` - ฐานความรู้

```sql
CREATE TABLE ai_knowledge_bases (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    bot_profile_id BIGINT,                   -- FK to ai_bot_profiles
    name VARCHAR(255) NOT NULL,
    description TEXT,
    kb_type ENUM('text', 'url', 'file', 'database') DEFAULT 'text',

    -- Storage
    content LONGTEXT,                        -- For text type
    file_path VARCHAR(500),                  -- For file type
    url VARCHAR(500),                        -- For URL type
    metadata JSON,                           -- Additional metadata

    -- Vector Embeddings (for RAG)
    has_embeddings BOOLEAN DEFAULT false,
    embedding_model VARCHAR(100),            -- 'text-embedding-ada-002', etc.
    vector_dimension INT,

    -- Processing
    is_processed BOOLEAN DEFAULT false,
    processed_at TIMESTAMP NULL,
    chunk_size INT DEFAULT 1000,
    chunk_overlap INT DEFAULT 200,
    total_chunks INT DEFAULT 0,

    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (bot_profile_id) REFERENCES ai_bot_profiles(id) ON DELETE CASCADE
);
```

### 5. `ai_knowledge_chunks` - Chunks สำหรับ RAG

```sql
CREATE TABLE ai_knowledge_chunks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    knowledge_base_id BIGINT,                -- FK to ai_knowledge_bases
    chunk_index INT,
    content TEXT NOT NULL,
    embedding JSON,                          -- Vector embedding as JSON array
    metadata JSON,                           -- {page: 1, section: 'intro', etc.}
    created_at TIMESTAMP,

    FOREIGN KEY (knowledge_base_id) REFERENCES ai_knowledge_bases(id) ON DELETE CASCADE,
    INDEX idx_kb_chunk (knowledge_base_id, chunk_index)
);
```

### 6. `ai_bot_rentals` - การเช่าบอท

```sql
CREATE TABLE ai_bot_rentals (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    bot_profile_id BIGINT,                   -- FK to ai_bot_profiles
    renter_id BIGINT,                        -- FK to users (ผู้เช่า)
    owner_id BIGINT,                         -- FK to users (เจ้าของบอท)

    -- Rental Details
    rental_plan ENUM('monthly', 'per_message') DEFAULT 'monthly',
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP,
    is_active BOOLEAN DEFAULT true,

    -- Pricing
    monthly_price DECIMAL(10,2),
    price_per_message DECIMAL(10,4),

    -- Usage Tracking
    total_messages INT DEFAULT 0,
    total_tokens_used BIGINT DEFAULT 0,
    total_cost DECIMAL(10,2) DEFAULT 0,

    -- Commission
    commission_rate DECIMAL(5,2),
    platform_commission DECIMAL(10,2) DEFAULT 0,
    owner_earning DECIMAL(10,2) DEFAULT 0,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    cancelled_at TIMESTAMP NULL,

    FOREIGN KEY (bot_profile_id) REFERENCES ai_bot_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (renter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 7. `ai_conversations` - บทสนทนา

```sql
CREATE TABLE ai_conversations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    bot_profile_id BIGINT,                   -- FK to ai_bot_profiles
    rental_id BIGINT NULL,                   -- FK to ai_bot_rentals (ถ้าเป็นการใช้งานแบบเช่า)
    user_id BIGINT,                          -- FK to users
    line_user_id VARCHAR(255),               -- LINE User ID

    title VARCHAR(255),                      -- Auto-generated or user-set
    status ENUM('active', 'archived', 'deleted') DEFAULT 'active',

    -- Metadata
    total_messages INT DEFAULT 0,
    total_tokens INT DEFAULT 0,
    total_cost DECIMAL(10,4) DEFAULT 0,

    started_at TIMESTAMP,
    last_message_at TIMESTAMP,
    archived_at TIMESTAMP NULL,

    FOREIGN KEY (bot_profile_id) REFERENCES ai_bot_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (rental_id) REFERENCES ai_bot_rentals(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 8. `ai_messages` - ข้อความ

```sql
CREATE TABLE ai_messages (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    conversation_id BIGINT,                  -- FK to ai_conversations
    role ENUM('system', 'user', 'assistant', 'function') NOT NULL,
    content TEXT NOT NULL,

    -- Metadata
    tokens_used INT,
    model_used VARCHAR(255),
    provider_used VARCHAR(255),
    response_time_ms INT,                    -- Response time in milliseconds

    -- Function Calling
    function_name VARCHAR(255),
    function_arguments JSON,
    function_response JSON,

    -- Context
    context_used JSON,                       -- Retrieved knowledge chunks

    created_at TIMESTAMP,

    FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id) ON DELETE CASCADE,
    INDEX idx_conv_created (conversation_id, created_at)
);
```

### 9. `ai_usage_logs` - บันทึกการใช้งาน

```sql
CREATE TABLE ai_usage_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    bot_profile_id BIGINT,
    rental_id BIGINT NULL,
    conversation_id BIGINT,
    message_id BIGINT,

    -- Usage Details
    provider VARCHAR(255),
    model VARCHAR(255),
    prompt_tokens INT,
    completion_tokens INT,
    total_tokens INT,

    -- Cost
    cost DECIMAL(10,6),                      -- Actual cost

    -- Performance
    response_time_ms INT,
    success BOOLEAN DEFAULT true,
    error_message TEXT,

    created_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bot_profile_id) REFERENCES ai_bot_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (rental_id) REFERENCES ai_bot_rentals(id) ON DELETE SET NULL,
    FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id) ON DELETE CASCADE,
    INDEX idx_created (created_at)
);
```

### 10. `ai_installation_logs` - บันทึกการติดตั้ง DeepSeek

```sql
CREATE TABLE ai_installation_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,                          -- ผู้ที่เริ่มการติดตั้ง
    installation_type ENUM('deepseek', 'ollama', 'custom') NOT NULL,

    -- Progress
    status ENUM('pending', 'downloading', 'installing', 'configuring', 'completed', 'failed') DEFAULT 'pending',
    progress_percentage INT DEFAULT 0,
    current_step VARCHAR(255),
    total_steps INT,

    -- Installation Details
    server_ip VARCHAR(45),
    server_port INT,
    install_path VARCHAR(500),
    model_size VARCHAR(50),                  -- '7B', '13B', '70B', etc.
    gpu_enabled BOOLEAN DEFAULT false,

    -- Configuration
    config JSON,

    -- Logs
    log_output LONGTEXT,
    error_log TEXT,

    started_at TIMESTAMP,
    completed_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 🔌 API Endpoints Structure

### AI Bot Management
- `GET /api/admin/ai-bots` - รายการบอททั้งหมด
- `POST /api/admin/ai-bots` - สร้างบอทใหม่
- `PUT /api/admin/ai-bots/{id}` - แก้ไขบอท
- `DELETE /api/admin/ai-bots/{id}` - ลบบอท
- `POST /api/admin/ai-bots/{id}/test` - ทดสอบบอท

### Knowledge Base
- `GET /api/admin/ai-bots/{id}/knowledge` - รายการ knowledge base
- `POST /api/admin/ai-bots/{id}/knowledge` - เพิ่ม knowledge
- `POST /api/admin/ai-bots/{id}/knowledge/{kb_id}/process` - Process & Generate Embeddings

### Rental Marketplace
- `GET /api/marketplace/bots` - รายการบอทที่ให้เช่า
- `POST /api/marketplace/bots/{id}/rent` - เช่าบอท
- `GET /api/my-rentals` - รายการบอทที่เช่า
- `POST /api/my-rentals/{id}/cancel` - ยกเลิกการเช่า

### DeepSeek Installation
- `POST /api/admin/deepseek/install` - เริ่มติดตั้ง
- `GET /api/admin/deepseek/progress/{id}` - ตรวจสอบความคืบหน้า
- `POST /api/admin/deepseek/cancel/{id}` - ยกเลิกการติดตั้ง

### Conversations & Chat
- `GET /api/conversations` - รายการบทสนทนา
- `POST /api/conversations` - สร้างบทสนทนาใหม่
- `POST /api/chat` - ส่งข้อความ (Real-time streaming)

---

## 🎨 Features

### 1. Multi-Provider Support
- OpenAI (GPT-3.5, GPT-4, GPT-4 Turbo)
- Anthropic Claude (Opus, Sonnet, Haiku)
- DeepSeek (Self-hosted & Cloud)
- Google Gemini
- Custom Endpoints (Ollama, LM Studio, etc.)

### 2. Knowledge Base & RAG
- Upload text files, PDFs, URLs
- Automatic chunking and embedding
- Vector similarity search
- Context injection

### 3. Response Boundaries
- Topic restrictions
- Forbidden topics
- Language preferences
- Domain whitelisting

### 4. Rental System
- Monthly subscription
- Pay-per-message
- Usage tracking
- Automated billing

### 5. Commission System
- Configurable commission rates
- Owner earnings tracking
- Platform fees
- Monthly payouts

### 6. DeepSeek Self-Hosted
- One-click installation
- Progress monitoring
- GPU support detection
- Automatic configuration

---

## 🔐 Security Considerations

1. **API Key Management:**
   - Encrypted storage
   - Separate keys per provider
   - Key rotation support

2. **Access Control:**
   - Bot ownership verification
   - Rental authorization
   - Admin-only features

3. **Rate Limiting:**
   - Per-user limits
   - Per-bot limits
   - API endpoint throttling

4. **Content Moderation:**
   - Inappropriate content detection
   - Automatic filtering
   - Manual review queue

---

## 📈 Monitoring & Analytics

1. **Usage Metrics:**
   - Messages per day/month
   - Tokens consumed
   - Cost tracking
   - Response times

2. **Bot Performance:**
   - Success rate
   - Average response time
   - User satisfaction
   - Error rates

3. **Revenue Metrics:**
   - Rental income
   - Commission earned
   - Payout tracking

---

## 🚀 Implementation Phases

### Phase 1: Core Foundation (Week 1-2)
- ✅ Database migrations
- ✅ Models and relationships
- ✅ Basic CRUD operations

### Phase 2: AI Integration (Week 2-3)
- ✅ Multi-provider service layer
- ✅ Conversation management
- ✅ Basic chat functionality

### Phase 3: Knowledge Base (Week 3-4)
- ✅ File upload and processing
- ✅ Embedding generation
- ✅ RAG implementation

### Phase 4: Rental System (Week 4-5)
- ✅ Marketplace UI
- ✅ Rental management
- ✅ Billing integration

### Phase 5: DeepSeek Installation (Week 5-6)
- ✅ Installation script
- ✅ Progress monitoring
- ✅ Health checks

### Phase 6: Admin Panel & UI (Week 6-7)
- ✅ Bot management interface
- ✅ Analytics dashboard
- ✅ Settings panels

### Phase 7: LINE Integration (Week 7-8)
- ✅ LINE webhook handler
- ✅ Bot selection per conversation
- ✅ Rich message support

---

## 📚 Tech Stack

- **Backend:** Laravel 11
- **Database:** MySQL 8.0+
- **Cache:** Redis
- **Queue:** Laravel Queue (Redis)
- **AI SDKs:**
  - OpenAI PHP SDK
  - Anthropic PHP SDK
  - Custom HTTP clients
- **Vector Database:** Optional (Qdrant, Pinecone, or MySQL JSON)
- **Frontend:** Alpine.js, Tailwind CSS
- **Real-time:** Laravel Broadcasting (Pusher/Socket.io)

---

**Last Updated:** 2025-01-XX
**Version:** 1.0.0
