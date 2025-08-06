<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        handleCreateOrder($db);
    } elseif ($method === 'GET') {
        handleGetOrders($db);
    } else {
        Response::error('Only POST and GET methods allowed');
    }
    
} catch (Exception $e) {
    logError('Orders API Error: ' . $e->getMessage());
    Response::error('Order processing failed');
}

function handleCreateOrder($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['items']) || !isset($input['final_amount']) || !isset($input['payment_type'])) {
        Response::error('Missing required fields: items, final_amount, payment_type');
    }
    
    $items = $input['items'];
    $customerName = isset($input['customer_name']) ? sanitizeInput($input['customer_name']) : null;
    $customerMobile = isset($input['customer_mobile']) ? sanitizeInput($input['customer_mobile']) : null;
    $subtotal = (float)($input['subtotal'] ?? 0);
    $discountAmount = (float)($input['discount_amount'] ?? 0);
    $finalAmount = (float)$input['final_amount'];
    $paymentType = sanitizeInput($input['payment_type']);
    
    // Validate data
    if (empty($items)) {
        Response::error('No items in order');
    }
    
    if (!Validator::decimal($finalAmount) || $finalAmount <= 0) {
        Response::error('Invalid final amount');
    }
    
    // Convert payment type to lowercase for validation
    $paymentTypeForValidation = strtolower($paymentType);
    $validPaymentTypes = ['cash', 'card', 'upi', 'other', 'wallet', 'online'];
    
    if (!in_array($paymentTypeForValidation, $validPaymentTypes)) {
        Response::error('Invalid payment type');
    }
    
    if ($customerMobile && !Validator::phone($customerMobile)) {
        Response::error('Invalid mobile number format');
    }
    
    $db->beginTransaction();
    
    try {
        $customerId = null;
        
        // Handle customer data
        if ($customerName || $customerMobile) {
            $customerId = handleCustomer($db, $customerName, $customerMobile);
        }
        
        // Create sales order
        $stmt = $db->prepare("
            INSERT INTO sales_orders (customer_id, subtotal, discount_amount, final_amount, payment_type, datetime_paid) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$customerId, $subtotal, $discountAmount, $finalAmount, $paymentType]);
        
        $orderId = $db->lastInsertId();
        
        // Add order items
        $stmt = $db->prepare("
            INSERT INTO sales_order_items (order_id, item_id, item_type, quantity, price_per_item) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($items as $item) {
            if (!isset($item['id']) || !isset($item['type']) || !isset($item['quantity']) || !isset($item['price'])) {
                throw new Exception('Invalid item data');
            }
            
            $stmt->execute([
                $orderId,
                (int)$item['id'],
                $item['type'],
                (int)$item['quantity'],
                (float)$item['price']
            ]);
        }
        
        $db->commit();
        
        Response::success([
            'order_id' => $orderId,
            'customer_id' => $customerId
        ], 'Order processed successfully');
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function handleCustomer($db, $name, $mobile) {
    // If mobile is provided, check if customer exists
    if ($mobile) {
        $stmt = $db->prepare("SELECT id FROM customers WHERE mobile = ?");
        $stmt->execute([$mobile]);
        $existingCustomer = $stmt->fetch();
        
        if ($existingCustomer) {
            // Update name if provided and different
            if ($name) {
                $stmt = $db->prepare("UPDATE customers SET name = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $existingCustomer['id']]);
            }
            return $existingCustomer['id'];
        }
    }
    
    // Create new customer
    try {
        $stmt = $db->prepare("INSERT INTO customers (name, mobile) VALUES (?, ?)");
        $stmt->execute([$name, $mobile]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate mobile
            // Try to get existing customer
            $stmt = $db->prepare("SELECT id FROM customers WHERE mobile = ?");
            $stmt->execute([$mobile]);
            $customer = $stmt->fetch();
            return $customer ? $customer['id'] : null;
        }
        throw $e;
    }
}

function handleGetOrders($db) {
    try {
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        
        // Get recent orders with customer info
        $stmt = $db->prepare("
            SELECT so.*, c.name as customer_name, c.mobile as customer_mobile
            FROM sales_orders so
            LEFT JOIN customers c ON so.customer_id = c.id
            ORDER BY so.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $orders = $stmt->fetchAll();
        
        // Get order items for each order
        foreach ($orders as &$order) {
            $stmt = $db->prepare("
                SELECT soi.*, 
                       CASE 
                           WHEN soi.item_type = 'product' THEN p.name
                           WHEN soi.item_type = 'combo' THEN c.name
                       END as item_name
                FROM sales_order_items soi
                LEFT JOIN products p ON soi.item_type = 'product' AND soi.item_id = p.id
                LEFT JOIN combos c ON soi.item_type = 'combo' AND soi.item_id = c.id
                WHERE soi.order_id = ?
            ");
            $stmt->execute([$order['id']]);
            $order['items'] = $stmt->fetchAll();
        }
        
        Response::success($orders, 'Orders retrieved successfully');
        
    } catch (Exception $e) {
        logError('Get orders error: ' . $e->getMessage());
        Response::error('Failed to retrieve orders: ' . $e->getMessage());
    }
}
?>
