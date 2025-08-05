<?php
require_once '../config/config.php';

$db = Database::getInstance()->getConnection();

echo "Initializing Thrive Cafe Database with sample data...\n\n";

try {
    // Sample Products
    $products = [
        ['Espresso', 45.00, 15.00],
        ['Cappuccino', 65.00, 20.00],
        ['Latte', 75.00, 25.00],
        ['Americano', 55.00, 18.00],
        ['Mocha', 85.00, 30.00],
        ['Cold Coffee', 70.00, 22.00],
        ['Hot Chocolate', 60.00, 20.00],
        ['Green Tea', 40.00, 12.00],
        ['English Breakfast Tea', 35.00, 10.00],
        ['Masala Chai', 30.00, 8.00],
        ['Croissant', 50.00, 20.00],
        ['Muffin', 45.00, 18.00],
        ['Sandwich', 120.00, 50.00],
        ['Pasta', 150.00, 60.00],
        ['Burger', 180.00, 70.00],
        ['Pizza Slice', 90.00, 35.00],
        ['Cookies', 25.00, 8.00],
        ['Cake Slice', 80.00, 30.00],
        ['Brownie', 60.00, 22.00],
        ['Cheesecake', 100.00, 40.00]
    ];
    
    echo "Adding sample products...\n";
    $stmt = $db->prepare("INSERT INTO products (name, price, making_cost) VALUES (?, ?, ?)");
    foreach ($products as $product) {
        $stmt->execute($product);
        echo "Added: {$product[0]}\n";
    }
    
    // Sample Combos
    $combos = [
        [
            'name' => 'Coffee & Croissant Combo',
            'products' => [1, 11], // Espresso + Croissant
            'price' => 85.00
        ],
        [
            'name' => 'Latte & Muffin Combo',
            'products' => [3, 12], // Latte + Muffin
            'price' => 110.00
        ],
        [
            'name' => 'Tea & Cookies Combo',
            'products' => [8, 17], // Green Tea + Cookies
            'price' => 55.00
        ],
        [
            'name' => 'Breakfast Special',
            'products' => [2, 13, 17], // Cappuccino + Sandwich + Cookies
            'price' => 220.00
        ],
        [
            'name' => 'Sweet Treat Combo',
            'products' => [6, 19], // Cold Coffee + Brownie
            'price' => 120.00
        ]
    ];
    
    echo "\nAdding sample combos...\n";
    foreach ($combos as $combo) {
        // Calculate making cost
        $placeholders = str_repeat('?,', count($combo['products']) - 1) . '?';
        $costStmt = $db->prepare("SELECT SUM(making_cost) FROM products WHERE id IN ($placeholders)");
        $costStmt->execute($combo['products']);
        $makingCost = $costStmt->fetchColumn();
        
        // Insert combo
        $stmt = $db->prepare("INSERT INTO combos (name, price, making_cost) VALUES (?, ?, ?)");
        $stmt->execute([$combo['name'], $combo['price'], $makingCost]);
        $comboId = $db->lastInsertId();
        
        // Insert combo items
        $itemStmt = $db->prepare("INSERT INTO combo_items (combo_id, product_id) VALUES (?, ?)");
        foreach ($combo['products'] as $productId) {
            $itemStmt->execute([$comboId, $productId]);
        }
        
        echo "Added: {$combo['name']}\n";
    }
    
    // Sample Offers
    $offers = [
        [
            'name' => 'Happy Hour - 20% Off All Items',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '15:00',
            'end_time' => '17:00',
            'discount_percent' => 20.00,
            'apply_to_all' => true
        ],
        [
            'name' => 'Morning Special - Coffee 15% Off',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '11:00',
            'discount_percent' => 15.00,
            'apply_to_all' => false
        ],
        [
            'name' => 'Weekend Combo Deal - 10% Off',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '00:00',
            'end_time' => '23:59',
            'discount_percent' => 10.00,
            'apply_to_all' => false
        ]
    ];
    
    echo "\nAdding sample offers...\n";
    foreach ($offers as $offer) {
        $stmt = $db->prepare("
            INSERT INTO offers (name, start_date, end_date, start_time, end_time, discount_percent, apply_to_all) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $offer['name'],
            $offer['start_date'],
            $offer['end_date'],
            $offer['start_time'],
            $offer['end_time'],
            $offer['discount_percent'],
            $offer['apply_to_all']
        ]);
        
        $offerId = $db->lastInsertId();
        
        // Add specific items for non-global offers
        if (!$offer['apply_to_all']) {
            if (strpos($offer['name'], 'Coffee') !== false) {
                // Add coffee products
                $coffeeProducts = [1, 2, 3, 4, 5, 6]; // First 6 coffee products
                $itemStmt = $db->prepare("INSERT INTO offer_items (offer_id, item_id, item_type) VALUES (?, ?, 'product')");
                foreach ($coffeeProducts as $productId) {
                    $itemStmt->execute([$offerId, $productId]);
                }
            } elseif (strpos($offer['name'], 'Combo') !== false) {
                // Add all combos
                $comboStmt = $db->prepare("SELECT id FROM combos");
                $comboStmt->execute();
                $comboIds = $comboStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $itemStmt = $db->prepare("INSERT INTO offer_items (offer_id, item_id, item_type) VALUES (?, ?, 'combo')");
                foreach ($comboIds as $comboId) {
                    $itemStmt->execute([$offerId, $comboId]);
                }
            }
        }
        
        echo "Added: {$offer['name']}\n";
    }
    
    // Sample Customers
    $customers = [
        ['Rajesh Kumar', '9876543210'],
        ['Priya Sharma', '9765432109'],
        ['Amit Patel', '9654321098'],
        ['Sneha Gupta', '9543210987'],
        ['Vikram Singh', '9432109876']
    ];
    
    echo "\nAdding sample customers...\n";
    $stmt = $db->prepare("INSERT INTO customers (name, mobile) VALUES (?, ?)");
    foreach ($customers as $customer) {
        $stmt->execute($customer);
        echo "Added: {$customer[0]} - {$customer[1]}\n";
    }
    
    // Sample Sales Orders (for demo purposes)
    echo "\nAdding sample sales orders...\n";
    
    $sampleOrders = [
        [
            'customer_id' => 1,
            'items' => [
                ['id' => 1, 'type' => 'product', 'quantity' => 2, 'price' => 45.00],
                ['id' => 11, 'type' => 'product', 'quantity' => 1, 'price' => 50.00]
            ],
            'subtotal' => 140.00,
            'discount' => 0.00,
            'payment_type' => 'Cash'
        ],
        [
            'customer_id' => 2,
            'items' => [
                ['id' => 1, 'type' => 'combo', 'quantity' => 1, 'price' => 85.00],
                ['id' => 6, 'type' => 'product', 'quantity' => 1, 'price' => 70.00]
            ],
            'subtotal' => 155.00,
            'discount' => 15.50,
            'payment_type' => 'UPI'
        ],
        [
            'customer_id' => null, // Walk-in customer
            'items' => [
                ['id' => 3, 'type' => 'product', 'quantity' => 1, 'price' => 75.00],
                ['id' => 18, 'type' => 'product', 'quantity' => 1, 'price' => 80.00]
            ],
            'subtotal' => 155.00,
            'discount' => 0.00,
            'payment_type' => 'Card'
        ]
    ];
    
    foreach ($sampleOrders as $order) {
        $finalAmount = $order['subtotal'] - $order['discount'];
        
        // Insert order
        $stmt = $db->prepare("
            INSERT INTO sales_orders (customer_id, subtotal, discount_amount, final_amount, payment_type, datetime_paid) 
            VALUES (?, ?, ?, ?, ?, NOW() - INTERVAL FLOOR(RAND() * 7) DAY - INTERVAL FLOOR(RAND() * 24) HOUR)
        ");
        $stmt->execute([
            $order['customer_id'],
            $order['subtotal'],
            $order['discount'],
            $finalAmount,
            $order['payment_type']
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Insert order items
        $itemStmt = $db->prepare("
            INSERT INTO sales_order_items (order_id, item_id, item_type, quantity, price_per_item) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($order['items'] as $item) {
            $itemStmt->execute([
                $orderId,
                $item['id'],
                $item['type'],
                $item['quantity'],
                $item['price']
            ]);
        }
        
        echo "Added sample order #$orderId\n";
    }
    
    echo "\n✅ Database initialization completed successfully!\n";
    echo "You can now access the application at: http://localhost/Thrive/\n\n";
    
    echo "Sample data added:\n";
    echo "- " . count($products) . " products\n";
    echo "- " . count($combos) . " combos\n";
    echo "- " . count($offers) . " offers\n";
    echo "- " . count($customers) . " customers\n";
    echo "- " . count($sampleOrders) . " sample orders\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    logError('Database initialization error: ' . $e->getMessage());
}
?>
