<?php
/**
 * Enhanced Orders API Endpoint
 * Handles all order-related operations with proper validation and error handling
 */

require_once '../config/config.php';

header('Content-Type: application/json');

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
rateLimit($clientIP);

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetOrders($db);
            break;
        case 'POST':
            handleCreateOrder($db);
            break;
        case 'PUT':
            handleUpdateOrder($db);
            break;
        case 'DELETE':
            handleDeleteOrder($db);
            break;
        default:
            Response::error('Method not allowed', null, 405);
    }
} catch (Exception $e) {
    logError('Orders API Error: ' . $e->getMessage(), [
        'method' => $method,
        'request' => $_REQUEST,
        'trace' => $e->getTraceAsString()
    ]);
    Response::serverError('An error occurred while processing your request');
}

function handleGetOrders($db) {
    $orderId = $_GET['id'] ?? null;
    $status = $_GET['status'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $orderType = $_GET['order_type'] ?? null;
    
    // Validate limit
    if ($limit > 200) $limit = 200;
    if ($limit < 1) $limit = 50;
    
    if ($orderId) {
        getSingleOrder($db, $orderId);
    } else {
        getAllOrders($db, $status, $startDate, $endDate, $limit, $offset, $orderType);
    }
}

function getSingleOrder($db, $orderId) {
    if (!Validator::integer($orderId, 1)) {
        Response::error('Invalid order ID');
    }
    
    // Get order details
    $stmt = $db->prepare("
        SELECT o.*, 
               COALESCE(of.title, 'No Offer') as offer_name,
               COALESCE(of.discount_value, 0) as offer_discount
        FROM orders o
        LEFT JOIN offers of ON o.offer_id = of.id
        WHERE o.id = ?
    ");
    
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        Response::notFound('Order not found');
    }
    
    // Get order items
    $stmt = $db->prepare("
        SELECT oi.*, 
               COALESCE(p.name, c.name) as item_name,
               CASE 
                   WHEN oi.product_id IS NOT NULL THEN 'product'
                   ELSE 'combo'
               END as item_type
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN combos c ON oi.combo_id = c.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    
    $stmt->execute([$orderId]);
    $order['items'] = $stmt->fetchAll();
    
    // Calculate totals
    $order['items_count'] = count($order['items']);
    $order['formatted_total'] = formatCurrency($order['total_amount']);
    $order['formatted_subtotal'] = formatCurrency($order['subtotal']);
    $order['formatted_tax'] = formatCurrency($order['tax_amount']);
    $order['formatted_discount'] = formatCurrency($order['discount_amount']);
    
    Response::success($order, 'Order retrieved successfully');
}

function getAllOrders($db, $status, $startDate, $endDate, $limit, $offset, $orderType) {
    $where = ['1=1'];
    $params = [];
    
    // Build WHERE clause
    if ($status && Validator::orderStatus($status)) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }
    
    if ($startDate && Validator::date($startDate)) {
        $where[] = 'DATE(o.created_at) >= ?';
        $params[] = $startDate;
    }
    
    if ($endDate && Validator::date($endDate)) {
        $where[] = 'DATE(o.created_at) <= ?';
        $params[] = $endDate;
    }
    
    if ($orderType && in_array($orderType, ['dine_in', 'takeaway', 'delivery', 'online'])) {
        $where[] = 'o.order_type = ?';
        $params[] = $orderType;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM orders o 
        WHERE $whereClause
    ");
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    
    // Get orders with pagination
    $stmt = $db->prepare("
        SELECT o.*, 
               COUNT(oi.id) as items_count,
               COALESCE(of.title, 'No Offer') as offer_name
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN offers of ON o.offer_id = of.id
        WHERE $whereClause
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Format currency for display
    foreach ($orders as &$order) {
        $order['formatted_total'] = formatCurrency($order['total_amount']);
        $order['time_ago'] = timeAgo($order['created_at']);
    }
    
    $response = [
        'orders' => $orders,
        'pagination' => [
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ]
    ];
    
    Response::success($response, 'Orders retrieved successfully');
}

function handleCreateOrder($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $requiredFields = ['items'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            Response::error("Field '$field' is required");
        }
    }
    
    // Validate items
    if (!is_array($input['items']) || count($input['items']) === 0) {
        Response::error('Order must contain at least one item');
    }
    
    // Sanitize input
    $customerName = sanitizeInput($input['customer_name'] ?? '');
    $customerPhone = sanitizeInput($input['customer_phone'] ?? '');
    $customerEmail = sanitizeInput($input['customer_email'] ?? '');
    $orderType = sanitizeInput($input['order_type'] ?? 'dine_in');
    $tableNumber = sanitizeInput($input['table_number'] ?? '');
    $specialInstructions = sanitizeInput($input['special_instructions'] ?? '');
    $couponCode = sanitizeInput($input['coupon_code'] ?? '');
    $paymentMethod = sanitizeInput($input['payment_method'] ?? 'cash');
    
    // Validate order type and payment method
    if (!in_array($orderType, ['dine_in', 'takeaway', 'delivery', 'online'])) {
        Response::error('Invalid order type');
    }
    
    if (!Validator::paymentMethod($paymentMethod)) {
        Response::error('Invalid payment method');
    }
    
    // Validate phone if provided
    if ($customerPhone && !Validator::phone($customerPhone)) {
        Response::error('Invalid phone number format');
    }
    
    // Validate email if provided
    if ($customerEmail && !Validator::email($customerEmail)) {
        Response::error('Invalid email format');
    }
    
    try {
        $db->beginTransaction();
        
        // Calculate order totals
        $subtotal = 0;
        $validatedItems = [];
        
        foreach ($input['items'] as $item) {
            if (!isset($item['quantity']) || !Validator::integer($item['quantity'], 1)) {
                throw new Exception('Invalid item quantity');
            }
            
            $quantity = (int)$item['quantity'];
            $unitPrice = 0;
            $itemName = '';
            
            if (isset($item['product_id'])) {
                // Product item
                $stmt = $db->prepare("SELECT id, name, price, is_available FROM products WHERE id = ? AND is_available = TRUE");
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    throw new Exception("Product with ID {$item['product_id']} not found or unavailable");
                }
                
                $unitPrice = $product['price'];
                $itemName = $product['name'];
                $validatedItems[] = [
                    'type' => 'product',
                    'id' => $product['id'],
                    'name' => $itemName,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity
                ];
                
            } elseif (isset($item['combo_id'])) {
                // Combo item
                $stmt = $db->prepare("SELECT id, name, price, is_available FROM combos WHERE id = ? AND is_available = TRUE");
                $stmt->execute([$item['combo_id']]);
                $combo = $stmt->fetch();
                
                if (!$combo) {
                    throw new Exception("Combo with ID {$item['combo_id']} not found or unavailable");
                }
                
                $unitPrice = $combo['price'];
                $itemName = $combo['name'];
                $validatedItems[] = [
                    'type' => 'combo',
                    'id' => $combo['id'],
                    'name' => $itemName,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity
                ];
            } else {
                throw new Exception('Each item must have either product_id or combo_id');
            }
            
            $subtotal += $unitPrice * $quantity;
        }
        
        // Apply coupon/offer if provided
        $discountAmount = 0;
        $offerId = null;
        
        if ($couponCode) {
            $stmt = $db->prepare("
                SELECT id, discount_value, offer_type, min_order_amount, max_discount_amount,
                       usage_limit, usage_count, valid_from, valid_until
                FROM offers 
                WHERE coupon_code = ? AND is_active = TRUE 
                AND NOW() BETWEEN valid_from AND valid_until
            ");
            $stmt->execute([$couponCode]);
            $offer = $stmt->fetch();
            
            if ($offer) {
                if ($offer['usage_limit'] && $offer['usage_count'] >= $offer['usage_limit']) {
                    throw new Exception('Coupon usage limit exceeded');
                }
                
                if ($subtotal < $offer['min_order_amount']) {
                    throw new Exception("Minimum order amount for this coupon is " . formatCurrency($offer['min_order_amount']));
                }
                
                // Calculate discount
                if ($offer['offer_type'] === 'percentage') {
                    $discountAmount = ($subtotal * $offer['discount_value']) / 100;
                    if ($offer['max_discount_amount'] && $discountAmount > $offer['max_discount_amount']) {
                        $discountAmount = $offer['max_discount_amount'];
                    }
                } elseif ($offer['offer_type'] === 'fixed_amount') {
                    $discountAmount = $offer['discount_value'];
                }
                
                $offerId = $offer['id'];
            } else {
                throw new Exception('Invalid or expired coupon code');
            }
        }
        
        // Calculate tax and total
        $taxRate = getSystemSettings('tax_rate') ?? 18.0;
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = ($taxableAmount * $taxRate) / 100;
        $totalAmount = $taxableAmount + $taxAmount;
        
        // Generate order number
        $orderNumber = generateOrderNumber(getSystemSettings('order_prefix') ?? 'TC');
        
        // Insert order
        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number, customer_name, customer_phone, customer_email,
                order_type, table_number, subtotal, tax_amount, discount_amount,
                total_amount, offer_id, coupon_code, special_instructions,
                payment_method, status, payment_status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW()
            )
        ");
        
        $stmt->execute([
            $orderNumber, $customerName, $customerPhone, $customerEmail,
            $orderType, $tableNumber, $subtotal, $taxAmount, $discountAmount,
            $totalAmount, $offerId, $couponCode, $specialInstructions, $paymentMethod
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Insert order items
        $stmt = $db->prepare("
            INSERT INTO order_items (order_id, product_id, combo_id, quantity, unit_price, total_price)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($validatedItems as $item) {
            $productId = ($item['type'] === 'product') ? $item['id'] : null;
            $comboId = ($item['type'] === 'combo') ? $item['id'] : null;
            
            $stmt->execute([
                $orderId, $productId, $comboId, $item['quantity'],
                $item['unit_price'], $item['total_price']
            ]);
        }
        
        $db->commit();
        
        // Log activity
        logActivity(null, 'order_created', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount
        ]);
        
        // Get the created order
        getSingleOrder($db, $orderId);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function handleUpdateOrder($db) {
    $orderId = $_GET['id'] ?? null;
    
    if (!$orderId || !Validator::integer($orderId, 1)) {
        Response::error('Invalid order ID');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if order exists
    $stmt = $db->prepare("SELECT id, status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        Response::notFound('Order not found');
    }
    
    // Prevent updating completed/cancelled orders
    if (in_array($order['status'], ['completed', 'cancelled'])) {
        Response::error('Cannot update completed or cancelled orders');
    }
    
    $allowedUpdates = ['status', 'payment_status', 'special_instructions', 'table_number'];
    $updates = [];
    $values = [];
    
    foreach ($allowedUpdates as $field) {
        if (isset($input[$field])) {
            $value = sanitizeInput($input[$field]);
            
            // Validate specific fields
            if ($field === 'status' && !Validator::orderStatus($value)) {
                Response::error('Invalid order status');
            }
            
            if ($field === 'payment_status' && !in_array($value, ['pending', 'paid', 'refunded', 'failed'])) {
                Response::error('Invalid payment status');
            }
            
            $updates[] = "$field = ?";
            $values[] = $value;
        }
    }
    
    if (empty($updates)) {
        Response::error('No valid fields to update');
    }
    
    $values[] = $orderId;
    
    $stmt = $db->prepare("
        UPDATE orders 
        SET " . implode(', ', $updates) . ", updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute($values);
    
    // Log activity
    logActivity(null, 'order_updated', [
        'order_id' => $orderId,
        'updates' => $input
    ]);
    
    getSingleOrder($db, $orderId);
}

function handleDeleteOrder($db) {
    $orderId = $_GET['id'] ?? null;
    
    if (!$orderId || !Validator::integer($orderId, 1)) {
        Response::error('Invalid order ID');
    }
    
    // Check if order exists and can be deleted
    $stmt = $db->prepare("SELECT id, status, order_number FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        Response::notFound('Order not found');
    }
    
    // Only allow deletion of pending orders
    if ($order['status'] !== 'pending') {
        Response::error('Only pending orders can be deleted');
    }
    
    try {
        $db->beginTransaction();
        
        // Delete order items first (due to foreign key constraint)
        $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        // Delete order
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        
        $db->commit();
        
        // Log activity
        logActivity(null, 'order_deleted', [
            'order_id' => $orderId,
            'order_number' => $order['order_number']
        ]);
        
        Response::success(null, 'Order deleted successfully');
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hr ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}
?>
