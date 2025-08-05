<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'POST') {
        Response::error('Only POST method allowed');
    }
    
    handleCreateOrder($db);
    
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
    
    if (!in_array($paymentType, ['Cash', 'Card', 'UPI', 'Other'])) {
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
?>
