<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ticket System Configuration
    |--------------------------------------------------------------------------
    */

    // File upload settings
    'attachments' => [
        'enabled' => true,
        'max_size' => 10 * 1024, // 10MB in KB
        'max_files_per_ticket' => 10,
        'max_files_per_reply' => 5,
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx',
            'xls', 'xlsx', 'txt', 'zip', 'rar'
        ],
        'storage_disk' => 'public',
        'storage_path' => 'tickets/attachments',
    ],

    // SLA settings
    'sla' => [
        'enabled' => true,
        'business_hours_only' => true,
        'default_business_hours' => [
            'start' => '09:00',
            'end' => '18:00',
            'days' => [1, 2, 3, 4, 5], // Monday to Friday
            'timezone' => 'Asia/Bangkok',
        ],
        'default_first_response_time' => 60, // minutes
        'default_resolution_time' => 1440, // minutes (24 hours)
        'warning_threshold' => 80, // % of SLA time remaining to send warning
    ],

    // Auto-assignment settings
    'auto_assignment' => [
        'enabled' => true,
        'strategy' => 'round_robin', // round_robin, least_active, random
        'consider_workload' => true,
        'max_tickets_per_agent' => 50,
    ],

    // Notification settings
    'notifications' => [
        'enabled' => true,
        'channels' => [
            'email' => true,
            'in_app' => true,
            'sms' => false,
        ],
        'queue' => true,
        'queue_name' => 'notifications',
    ],

    // Knowledge Base integration
    'knowledge_base' => [
        'enabled' => true,
        'auto_suggest' => true,
        'max_suggestions' => 5,
        'require_agent_approval' => false,
    ],

    // Rating/Satisfaction survey
    'ratings' => [
        'enabled' => true,
        'request_on_close' => true,
        'required' => false,
        'detailed_ratings' => true, // response_speed, solution_quality, staff_friendliness
    ],

    // Ticket priorities
    'priorities' => [
        'low' => [
            'label' => 'ต่ำ',
            'color' => 'gray',
            'sla_multiplier' => 2.0,
        ],
        'medium' => [
            'label' => 'ปานกลาง',
            'color' => 'blue',
            'sla_multiplier' => 1.0,
        ],
        'high' => [
            'label' => 'สูง',
            'color' => 'orange',
            'sla_multiplier' => 0.5,
        ],
        'critical' => [
            'label' => 'วิกฤต',
            'color' => 'red',
            'sla_multiplier' => 0.25,
        ],
    ],

    // Ticket statuses
    'statuses' => [
        'open' => [
            'label' => 'เปิด',
            'color' => 'blue',
            'is_closed' => false,
        ],
        'in_progress' => [
            'label' => 'กำลังดำเนินการ',
            'color' => 'yellow',
            'is_closed' => false,
        ],
        'waiting_customer' => [
            'label' => 'รอลูกค้า',
            'color' => 'purple',
            'is_closed' => false,
        ],
        'resolved' => [
            'label' => 'แก้ไขแล้ว',
            'color' => 'green',
            'is_closed' => true,
        ],
        'closed' => [
            'label' => 'ปิด',
            'color' => 'gray',
            'is_closed' => true,
        ],
    ],

    // Canned responses
    'canned_responses' => [
        'enabled' => true,
        'allow_variables' => true,
        'variables' => [
            '{user_name}' => 'ชื่อผู้ใช้',
            '{ticket_number}' => 'หมายเลข Ticket',
            '{current_date}' => 'วันที่ปัจจุบัน',
            '{current_time}' => 'เวลาปัจจุบัน',
            '{agent_name}' => 'ชื่อเจ้าหน้าที่',
        ],
    ],

    // Advanced features
    'features' => [
        'ticket_merging' => true,
        'ticket_linking' => true,
        'tags' => true,
        'internal_notes' => true,
        'full_text_search' => true,
        'advanced_analytics' => true,
        'export_reports' => true,
    ],

    // Pagination
    'pagination' => [
        'admin_per_page' => 20,
        'user_per_page' => 15,
        'replies_per_page' => 50,
    ],
];
