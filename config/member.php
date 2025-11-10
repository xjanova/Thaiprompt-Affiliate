<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Member Number Settings
    |--------------------------------------------------------------------------
    |
    | Configure the member number generation settings for your application.
    |
    */

    // Enable automatic member number generation
    'auto_generate' => env('MEMBER_AUTO_GENERATE', true),

    // Prefix for member numbers (e.g., "MEM" results in MEM00001)
    'prefix' => env('MEMBER_NUMBER_PREFIX', 'MEM'),

    // Starting number (default: 1)
    'starting_number' => env('MEMBER_STARTING_NUMBER', 1),

    // Number padding (e.g., 5 means 00001, 6 means 000001)
    'padding' => env('MEMBER_NUMBER_PADDING', 5),

    // Format: {prefix}{padded_number}
    // Example: MEM00001, MEM00002, etc.
];
