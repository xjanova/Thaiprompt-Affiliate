<?php
/**
 * 🧪 Test Route Name to URL Conversion
 */

function routeNameToUrl($routeName) {
    if (empty($routeName) || $routeName === '#' || $routeName === null) {
        return '#';
    }

    // Remove .index, .show, .create, .edit suffixes
    $url = preg_replace('/\.(index|show|create|edit|store|update|destroy)$/', '', $routeName);

    // Replace dots with slashes
    $url = str_replace('.', '/', $url);

    // Add leading slash if not present
    if (empty($url) || $url[0] !== '/') {
        $url = '/' . $url;
    }

    return $url;
}

// Test cases
$testCases = [
    'admin.kyc.index' => '/admin/kyc',
    'admin.dashboard' => '/admin/dashboard',
    'admin.users.index' => '/admin/users',
    'admin.roles.index' => '/admin/roles',
    'seller.dashboard' => '/seller/dashboard',
    'seller.products.index' => '/seller/products',
    'admin.settings.general' => '/admin/settings/general',
    'user.profile.edit' => '/user/profile',
    'admin.reports.sales.index' => '/admin/reports/sales',
    '#' => '#',
    null => '#',
    '' => '#',
];

echo "🧪 Testing Route Name to URL Conversion\n";
echo str_repeat("=", 70) . "\n\n";

$allPassed = true;

foreach ($testCases as $input => $expected) {
    $result = routeNameToUrl($input);
    $passed = ($result === $expected);
    $status = $passed ? "✅ PASS" : "❌ FAIL";

    if (!$passed) {
        $allPassed = false;
    }

    $inputDisplay = $input === null ? 'null' : ($input === '' ? "''" : $input);

    printf("%-35s => %-25s %s\n", $inputDisplay, $result, $status);

    if (!$passed) {
        echo "  Expected: {$expected}\n";
        echo "  Got:      {$result}\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";

if ($allPassed) {
    echo "✅ All tests passed!\n";
} else {
    echo "❌ Some tests failed!\n";
}
