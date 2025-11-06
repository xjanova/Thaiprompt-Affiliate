-- Add PaySolutions Payment Gateway
-- Run this SQL if you want to add PaySolutions gateway manually

INSERT INTO payment_gateways (
    code,
    name,
    slug,
    description,
    instructions,
    is_active,
    is_available,
    is_coming_soon,
    supports_deposit,
    supports_withdrawal,
    config,
    credentials,
    fees,
    limits,
    icon,
    logo_url,
    color,
    help_url,
    test_mode,
    test_credentials,
    sort_order,
    category,
    metadata,
    created_at,
    updated_at
) VALUES (
    'paysolutions',
    'PaySolutions',
    'paysolutions',
    'PaySolutions Payment Gateway - รองรับการชำระเงินหลากหลายรูปแบบ',
    'ชำระเงินผ่าน PaySolutions ด้วย QR Code, บัตรเครดิต, e-Wallet และอื่นๆ',
    0, -- is_active (false - admin needs to configure first)
    1, -- is_available (true)
    0, -- is_coming_soon (false)
    1, -- supports_deposit (true)
    0, -- supports_withdrawal (false - can be enabled later)
    -- config
    JSON_OBJECT(
        'api_version', 'v1',
        'timeout', 30,
        'supported_methods', JSON_OBJECT(
            'qr', 'QR Code Payment',
            'card', 'Credit/Debit Card',
            'bank_transfer', 'Bank Transfer',
            'ewallet', 'E-Wallet',
            'installment', 'Installment'
        )
    ),
    -- credentials (will be filled by admin)
    JSON_OBJECT(
        'merchant_id', '',
        'api_key', '',
        'secret_key', '',
        'webhook_secret', ''
    ),
    -- fees
    JSON_OBJECT(
        'type', 'percentage',
        'deposit_fee', 2.5,
        'fixed_fee', 0,
        'min_fee', 5,
        'withdrawal_fee', 0
    ),
    -- limits
    JSON_OBJECT(
        'min_amount', 10,
        'max_amount', 1000000,
        'daily_limit', 5000000
    ),
    '💳', -- icon
    'https://paysolutions.asia/images/logo.png', -- logo_url
    '#4F46E5', -- color
    'https://api-docs.paysolutions.asia/', -- help_url
    1, -- test_mode (true)
    -- test_credentials
    JSON_OBJECT(
        'merchant_id', 'TEST_MERCHANT',
        'api_key', 'test_api_key',
        'secret_key', 'test_secret_key',
        'webhook_secret', 'test_webhook_secret'
    ),
    10, -- sort_order
    'thai', -- category
    -- metadata
    JSON_OBJECT(
        'provider', 'paysolutions',
        'country', 'TH',
        'documentation', 'https://api-docs.paysolutions.asia/docs/api/overviews',
        'support_email', 'support@paysolutions.asia',
        'features', JSON_OBJECT(
            'qr_payment', true,
            'card_payment', true,
            'bank_transfer', true,
            'ewallet', true,
            'installment', true,
            'refund', true,
            'webhook', true
        )
    ),
    NOW(), -- created_at
    NOW()  -- updated_at
)
ON DUPLICATE KEY UPDATE
    updated_at = NOW();

-- Verify insertion
SELECT
    id,
    code,
    name,
    is_active,
    is_available,
    test_mode,
    created_at
FROM payment_gateways
WHERE code = 'paysolutions';
