<?php
// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once 'config/config.php';

// Simple test to check if everything is working
echo "<!DOCTYPE html>";
echo "<html><head><title>Thrive Cafe - System Check</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:40px;background:#f5f5f5;}";
echo ".container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .info{color:#17a2b8;}";
echo "h1{color:#333;} .test{margin:15px 0;padding:10px;border-left:4px solid #ddd;}";
echo ".test.success{border-left-color:#28a745;background:#f8fff9;}";
echo ".test.error{border-left-color:#dc3545;background:#fff8f8;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🚀 Thrive Cafe System Check</h1>";

// Test 1: PHP Version
echo "<div class='test " . (version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'error') . "'>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION;
echo (version_compare(PHP_VERSION, '7.4.0', '>=') ? " ✅" : " ❌ (Requires PHP 7.4+)");
echo "</div>";

// Test 2: Database Connection
try {
    $db = Database::getInstance()->getConnection();
    echo "<div class='test success'>";
    echo "<strong>Database Connection:</strong> Connected successfully ✅";
    echo "</div>";
    
    // Test 3: Database Tables
    $tables = ['products', 'combos', 'offers', 'customers', 'sales_orders'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            $existingTables[] = $table;
        }
    }
    
    echo "<div class='test " . (count($existingTables) === count($tables) ? 'success' : 'error') . "'>";
    echo "<strong>Database Tables:</strong> " . count($existingTables) . "/" . count($tables) . " tables exist";
    if (count($existingTables) === count($tables)) {
        echo " ✅";
    } else {
        echo " ❌<br><small>Missing: " . implode(', ', array_diff($tables, $existingTables)) . "</small>";
    }
    echo "</div>";
    
    // Test 4: Sample Data
    $stmt = $db->prepare("SELECT COUNT(*) FROM products");
    $stmt->execute();
    $productCount = $stmt->fetchColumn();
    
    echo "<div class='test " . ($productCount > 0 ? 'success' : 'error') . "'>";
    echo "<strong>Sample Data:</strong> " . $productCount . " products in database";
    echo ($productCount > 0 ? " ✅" : " ❌");
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test error'>";
    echo "<strong>Database Connection:</strong> Failed ❌<br>";
    echo "<small>Error: " . htmlspecialchars($e->getMessage()) . "</small>";
    echo "</div>";
}

// Test 5: Required Extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

echo "<div class='test " . (empty($missingExtensions) ? 'success' : 'error') . "'>";
echo "<strong>PHP Extensions:</strong> ";
if (empty($missingExtensions)) {
    echo "All required extensions loaded ✅";
} else {
    echo "Missing extensions: " . implode(', ', $missingExtensions) . " ❌";
}
echo "</div>";

// Test 6: File Permissions
$writableDirs = ['logs'];
$permissionIssues = [];

foreach ($writableDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!is_writable($dir)) {
        $permissionIssues[] = $dir;
    }
}

echo "<div class='test " . (empty($permissionIssues) ? 'success' : 'error') . "'>";
echo "<strong>File Permissions:</strong> ";
if (empty($permissionIssues)) {
    echo "All directories writable ✅";
} else {
    echo "Permission issues: " . implode(', ', $permissionIssues) . " ❌";
}
echo "</div>";

// Test 7: API Endpoints
$apiEndpoints = ['dashboard.php', 'products.php', 'combos.php', 'offers.php', 'orders.php', 'search.php', 'export.php'];
$missingApis = [];

foreach ($apiEndpoints as $api) {
    if (!file_exists("api/$api")) {
        $missingApis[] = $api;
    }
}

echo "<div class='test " . (empty($missingApis) ? 'success' : 'error') . "'>";
echo "<strong>API Endpoints:</strong> ";
if (empty($missingApis)) {
    echo "All API files present ✅";
} else {
    echo "Missing API files: " . implode(', ', $missingApis) . " ❌";
}
echo "</div>";

// Summary and Next Steps
echo "<hr>";
echo "<h2>📋 Next Steps:</h2>";
echo "<ol>";

if (!empty($missingExtensions) || !empty($permissionIssues) || !empty($missingApis)) {
    echo "<li class='error'>❌ Fix the issues shown above before proceeding</li>";
} else {
    if (isset($productCount) && $productCount == 0) {
        echo "<li class='info'>🔄 Run the database initialization: <code>setup/init_data.php</code></li>";
    }
    echo "<li class='success'>✅ <a href='index.php'>Launch Thrive Cafe Application</a></li>";
}

echo "</ol>";

echo "<hr>";
echo "<p><strong>System Information:</strong></p>";
echo "<ul>";
echo "<li><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>PHP SAPI:</strong> " . php_sapi_name() . "</li>";
echo "<li><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</li>";
echo "<li><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "s</li>";
echo "<li><strong>Upload Max Size:</strong> " . ini_get('upload_max_filesize') . "</li>";
echo "</ul>";

echo "</div></body></html>";
?>
