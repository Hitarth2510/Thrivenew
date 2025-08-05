<?php
/**
 * Database Installation Script for Thrive Cafe POS System
 * This script will create the database and populate it with initial data
 */

require_once '../config/config.php';

set_time_limit(300); // 5 minutes for large database operations

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Thrive Cafe - Database Installation</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .install-log { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
<div class='container mt-4'>
    <h1 class='text-center mb-4'>Thrive Cafe - Database Installation</h1>
    <div class='card'>
        <div class='card-body'>
            <div class='install-log' id='installLog'>";

function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : 'info');
    echo "<span class='$class'>[$timestamp] $message</span>\n";
    flush();
    ob_flush();
}

try {
    logMessage("Starting database installation...", 'info');
    
    // Create database connection without specifying database name first
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    logMessage("Connected to MySQL server successfully", 'success');
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    logMessage("Database '" . DB_NAME . "' created successfully", 'success');
    
    // Use the database
    $pdo->exec("USE " . DB_NAME);
    logMessage("Selected database '" . DB_NAME . "'", 'success');
    
    // Read and execute the schema SQL file
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (file_exists($schemaFile)) {
        logMessage("Reading schema file...", 'info');
        $sql = file_get_contents($schemaFile);
        
        // Split by semicolon but be careful with stored procedures
        $statements = preg_split('/;(?=(?:[^\'"]|[\'"][^\'"]*[\'"])*$)/', $sql);
        
        $successCount = 0;
        $totalStatements = count($statements);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    logMessage("Warning: " . $e->getMessage(), 'error');
                }
            }
        }
        
        logMessage("Executed $successCount SQL statements successfully", 'success');
    } else {
        logMessage("Schema file not found, creating basic tables...", 'info');
        
        // Basic table creation if schema file doesn't exist
        $basicTables = [
            "CREATE TABLE IF NOT EXISTS categories (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS products (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                category_id INT,
                price DECIMAL(10,2) NOT NULL,
                making_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                is_available BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS orders (
                id INT PRIMARY KEY AUTO_INCREMENT,
                order_number VARCHAR(20) NOT NULL UNIQUE,
                total_amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS order_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )"
        ];
        
        foreach ($basicTables as $sql) {
            $pdo->exec($sql);
        }
        
        logMessage("Basic tables created successfully", 'success');
    }
    
    // Insert sample data
    logMessage("Inserting sample data...", 'info');
    
    // Sample categories
    $categories = [
        ['Hot Coffee', 'Freshly brewed hot coffee varieties'],
        ['Cold Coffee', 'Iced and cold coffee beverages'],
        ['Tea', 'Traditional and herbal tea varieties'],
        ['Food', 'Meals and snacks'],
        ['Desserts', 'Sweet treats']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    logMessage("Categories inserted successfully", 'success');
    
    // Sample products
    $products = [
        ['Espresso', 1, 45.00, 15.00],
        ['Cappuccino', 1, 65.00, 20.00],
        ['Latte', 1, 75.00, 25.00],
        ['Americano', 1, 55.00, 18.00],
        ['Iced Latte', 2, 80.00, 25.00],
        ['Cold Brew', 2, 70.00, 22.00],
        ['Green Tea', 3, 40.00, 12.00],
        ['Masala Chai', 3, 30.00, 8.00],
        ['Club Sandwich', 4, 150.00, 60.00],
        ['Chocolate Cake', 5, 80.00, 30.00]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO products (name, category_id, price, making_cost) VALUES (?, ?, ?, ?)");
    foreach ($products as $product) {
        $stmt->execute($product);
    }
    logMessage("Sample products inserted successfully", 'success');
    
    // Create admin user if users table exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        
        // Insert default admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@thrivecafe.com', $adminPassword, 'System Administrator', 'admin']);
        logMessage("Default admin user created (username: admin, password: admin123)", 'success');
    } catch (PDOException $e) {
        logMessage("Users table not available, skipping user creation", 'info');
    }
    
    // Create system settings if table exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_settings");
        $stmt->execute();
        
        $settings = [
            ['cafe_name', 'Thrive Cafe', 'string', 'Name of the cafe', 1],
            ['tax_rate', '18.00', 'number', 'Default tax rate percentage', 0],
            ['currency_symbol', '₹', 'string', 'Currency symbol', 1],
            ['order_prefix', 'TC', 'string', 'Prefix for order numbers', 0]
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES (?, ?, ?, ?, ?)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        logMessage("System settings configured successfully", 'success');
    } catch (PDOException $e) {
        logMessage("System settings table not available, skipping settings configuration", 'info');
    }
    
    logMessage("Database installation completed successfully!", 'success');
    logMessage("You can now access the application dashboard.", 'info');
    
    echo "</div>
        <div class='mt-3 text-center'>
            <a href='../index.php' class='btn btn-primary'>Go to Dashboard</a>
            <a href='../system-check.php' class='btn btn-secondary'>System Check</a>
        </div>";
    
} catch (Exception $e) {
    logMessage("Installation failed: " . $e->getMessage(), 'error');
    logMessage("Please check your database configuration and try again.", 'error');
    
    echo "</div>
        <div class='mt-3 text-center'>
            <button class='btn btn-danger' onclick='location.reload()'>Retry Installation</button>
        </div>";
}

echo "        </div>
    </div>
</div>
</body>
</html>";
?>
