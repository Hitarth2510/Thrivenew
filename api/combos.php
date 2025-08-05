<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetCombos($db);
            break;
        case 'POST':
            handleCreateCombo($db);
            break;
        case 'PUT':
            handleUpdateCombo($db);
            break;
        case 'DELETE':
            handleDeleteCombo($db);
            break;
        default:
            Response::error('Method not allowed');
    }
} catch (Exception $e) {
    logError('Combos API Error: ' . $e->getMessage());
    Response::error('Internal server error');
}

function handleGetCombos($db) {
    $activeOnly = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : false;
    $includeProducts = isset($_GET['include_products']) ? (bool)$_GET['include_products'] : false;
    
    $sql = "SELECT * FROM combos";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $combos = $stmt->fetchAll();
    
    if ($includeProducts) {
        foreach ($combos as &$combo) {
            $combo['products'] = getComboProducts($db, $combo['id']);
        }
    }
    
    Response::success($combos);
}

function handleCreateCombo($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['name']) || !isset($input['price']) || !isset($input['products'])) {
        Response::error('Missing required fields: name, price, products');
    }
    
    $name = sanitizeInput($input['name']);
    $price = $input['price'];
    $cost = isset($input['cost']) ? $input['cost'] : 0;
    $description = isset($input['description']) ? sanitizeInput($input['description']) : '';
    $products = $input['products'];
    $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
    
    // Validate data
    if (!Validator::required($name)) {
        Response::error('Combo name is required');
    }
    
    if (!Validator::decimal($price) || $price <= 0) {
        Response::error('Invalid price');
    }
    
    if (!is_array($products) || empty($products)) {
        Response::error('At least one product must be selected');
    }
    
    // Calculate making cost
    $makingCost = calculateComboMakingCost($db, $products);
    
    $db->beginTransaction();
    
    try {
        // Create combo
        $stmt = $db->prepare("
            INSERT INTO combos (name, price, cost, description, is_active) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $price, $cost, $description, $isActive]);
        
        $comboId = $db->lastInsertId();
        
        // Add combo products
        $stmt = $db->prepare("INSERT INTO combo_items (combo_id, product_id) VALUES (?, ?)");
        foreach ($products as $productId) {
            $stmt->execute([$comboId, $productId]);
        }
        
        $db->commit();
        
        Response::success(['id' => $comboId], 'Combo created successfully');
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->getCode() == 23000) { // Duplicate entry
            Response::error('Combo name already exists');
        }
        throw $e;
    }
}

function handleUpdateCombo($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        Response::error('Combo ID is required');
    }
    
    $id = (int)$input['id'];
    $name = sanitizeInput($input['name']);
    $price = $input['price'];
    $products = $input['products'];
    $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
    
    // Validate data
    if (!Validator::required($name)) {
        Response::error('Combo name is required');
    }
    
    if (!Validator::decimal($price) || $price <= 0) {
        Response::error('Invalid price');
    }
    
    if (!is_array($products) || empty($products)) {
        Response::error('At least one product must be selected');
    }
    
    // Calculate making cost
    $makingCost = calculateComboMakingCost($db, $products);
    
    $db->beginTransaction();
    
    try {
        // Update combo
        $stmt = $db->prepare("
            UPDATE combos 
            SET name = ?, price = ?, cost = ?, description = ?, is_active = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$name, $price, $makingCost, $isActive, $id]);
        
        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            Response::error('Combo not found');
        }
        
        // Remove existing combo products
        $stmt = $db->prepare("DELETE FROM combo_items WHERE combo_id = ?");
        $stmt->execute([$id]);
        
        // Add new combo products
        $stmt = $db->prepare("INSERT INTO combo_items (combo_id, product_id) VALUES (?, ?)");
        foreach ($products as $productId) {
            $stmt->execute([$id, $productId]);
        }
        
        $db->commit();
        
        Response::success(null, 'Combo updated successfully');
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->getCode() == 23000) { // Duplicate entry
            Response::error('Combo name already exists');
        }
        throw $e;
    }
}

function handleDeleteCombo($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        Response::error('Combo ID is required');
    }
    
    $id = (int)$input['id'];
    
    $stmt = $db->prepare("DELETE FROM combos WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        Response::error('Combo not found');
    }
    
    Response::success(null, 'Combo deleted successfully');
}

function calculateComboMakingCost($db, $productIds) {
    if (empty($productIds)) {
        return 0;
    }
    
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    $stmt = $db->prepare("SELECT SUM(cost) FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    
    return (float)$stmt->fetchColumn();
}

function getComboProducts($db, $comboId) {
    $stmt = $db->prepare("
        SELECT p.id, p.name, p.price, p.cost
        FROM products p
        JOIN combo_items ci ON p.id = ci.product_id
        WHERE ci.combo_id = ?
        ORDER BY p.name ASC
    ");
    $stmt->execute([$comboId]);
    
    return $stmt->fetchAll();
}
?>
