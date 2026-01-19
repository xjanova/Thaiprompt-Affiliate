<?php

/**
 * การตั้งค่า Central AI
 *
 * ไฟล์นี้เก็บการตั้งค่าเริ่มต้นสำหรับระบบ Central AI
 * รวมถึงรายการโมเดลที่แนะนำตาม system resources
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Ollama Settings
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าเริ่มต้นสำหรับ Ollama service
    |
    */

    'ollama' => [
        'default_host' => env('OLLAMA_HOST', 'localhost'),
        'default_port' => env('OLLAMA_PORT', 11434),
        'default_model' => env('OLLAMA_DEFAULT_MODEL', 'llama3:8b'),
        'connection_timeout' => env('OLLAMA_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | PostXAgent Settings
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าเริ่มต้นสำหรับ PostXAgent
    |
    */

    'postxagent' => [
        'default_host' => env('POSTXAGENT_HOST', 'localhost'),
        'default_api_port' => env('POSTXAGENT_API_PORT', 5000),
        'default_signalr_port' => env('POSTXAGENT_SIGNALR_PORT', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG Settings
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าเริ่มต้นสำหรับ Retrieval-Augmented Generation
    |
    */

    'rag' => [
        'embedding_provider' => env('RAG_EMBEDDING_PROVIDER', 'ollama'),
        'embedding_model' => env('RAG_EMBEDDING_MODEL', 'nomic-embed-text'),
        'similarity_threshold' => env('RAG_SIMILARITY_THRESHOLD', 0.70),
        'max_context_chunks' => env('RAG_MAX_CONTEXT_CHUNKS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recommended Models
    |--------------------------------------------------------------------------
    |
    | รายการโมเดลที่แนะนำตาม RAM ที่มี
    | สามารถเพิ่ม/แก้ไขโมเดลได้ที่นี่
    |
    | โครงสร้าง:
    | - min_ram: RAM ขั้นต่ำที่ต้องการ (GB)
    | - max_ram: RAM สูงสุด (null = ไม่จำกัด)
    | - name: ชื่อโมเดล (ใช้กับ ollama pull)
    | - size: ขนาดไฟล์โดยประมาณ
    | - description: คำอธิบาย (ภาษาไทย)
    |
    */

    'recommended_models' => [
        // โมเดลขนาดเล็กมาก (RAM < 4GB)
        [
            'min_ram' => 0,
            'max_ram' => 4,
            'name' => 'llama3.2:1b',
            'size' => '1.3GB',
            'description' => 'โมเดลขนาดเล็กมาก สำหรับ RAM < 4GB',
        ],

        // โมเดลขนาดเล็ก (RAM 4-8GB)
        [
            'min_ram' => 4,
            'max_ram' => 8,
            'name' => 'llama3.2:3b',
            'size' => '2GB',
            'description' => 'โมเดลขนาดเล็ก เหมาะกับ RAM 4-8GB',
        ],

        // โมเดลขนาดกลาง (RAM 8-16GB)
        [
            'min_ram' => 8,
            'max_ram' => 16,
            'name' => 'llama3:8b',
            'size' => '4.7GB',
            'description' => 'โมเดลขนาดกลาง เหมาะกับ RAM 8-16GB',
        ],

        // โมเดลขนาดกลาง-ใหญ่ (RAM 16-32GB)
        [
            'min_ram' => 16,
            'max_ram' => 32,
            'name' => 'llama3.1:8b',
            'size' => '4.9GB',
            'description' => 'โมเดลขนาดกลาง-ใหญ่ เหมาะกับ RAM 16GB+',
        ],

        // โมเดลขนาดใหญ่ (RAM 32GB+)
        [
            'min_ram' => 32,
            'max_ram' => null,
            'name' => 'llama3.1:70b',
            'size' => '40GB',
            'description' => 'โมเดลขนาดใหญ่ เหมาะกับ RAM 32GB+',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Models (Optional)
    |--------------------------------------------------------------------------
    |
    | โมเดลเพิ่มเติมที่ผู้ใช้สามารถเลือกดาวน์โหลดได้
    |
    */

    'additional_models' => [
        [
            'name' => 'mistral:7b',
            'size' => '4.1GB',
            'description' => 'Mistral 7B - ประสิทธิภาพดีสำหรับงานทั่วไป',
            'min_ram' => 8,
        ],
        [
            'name' => 'codellama:7b',
            'size' => '3.8GB',
            'description' => 'Code Llama - เหมาะกับงานเขียนโค้ด',
            'min_ram' => 8,
        ],
        [
            'name' => 'deepseek-coder:6.7b',
            'size' => '3.8GB',
            'description' => 'DeepSeek Coder - ประสิทธิภาพสูงสำหรับงานโค้ด',
            'min_ram' => 8,
        ],
        [
            'name' => 'phi3:mini',
            'size' => '2.3GB',
            'description' => 'Microsoft Phi-3 Mini - โมเดลขนาดเล็กประสิทธิภาพสูง',
            'min_ram' => 4,
        ],
        [
            'name' => 'gemma2:9b',
            'size' => '5.4GB',
            'description' => 'Google Gemma 2 - โมเดลจาก Google',
            'min_ram' => 12,
        ],
        [
            'name' => 'qwen2:7b',
            'size' => '4.4GB',
            'description' => 'Qwen2 - รองรับภาษาไทยได้ดี',
            'min_ram' => 8,
        ],
        [
            'name' => 'nomic-embed-text',
            'size' => '274MB',
            'description' => 'Embedding model สำหรับ RAG',
            'min_ram' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Settings
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าสำหรับ Health Check
    |
    */

    'health_check' => [
        'auto_check_interval' => env('CENTRAL_AI_HEALTH_CHECK_INTERVAL', 5), // นาที
        'timeout' => env('CENTRAL_AI_HEALTH_CHECK_TIMEOUT', 10), // วินาที
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Settings
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าสำหรับการตอบกลับ
    |
    */

    'response' => [
        'max_tokens' => env('CENTRAL_AI_MAX_TOKENS', 2048),
        'default_temperature' => env('CENTRAL_AI_TEMPERATURE', 0.7),
        'fallback_message' => 'ขออภัย ระบบไม่สามารถประมวลผลได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง',
    ],

];
