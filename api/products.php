<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGetProducts($db);
            break;
        case 'POST':
            handleCreateProduct($db);
            break;
        case 'PUT':
            handleUpdateProduct($db);
            break;
        case 'DELETE':
            handleDeleteProduct($db);
            break;
        default:
            Response::error('Method not allowed', null, 405);
    }
} catch (Exception $e) {
    logError('Products API Error: ' . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage());
}

function handleGetProducts($db) {
    try {
        // Check if products table exists
        $stmt = $db->query("SHOW TABLES LIKE 'products'");
        $tableExists = $stmt->fetch();
        
        if (!$tableExists) {
            Response::success([]);
            return;
        }
        
        // Check if requesting a specific product by ID
        $productId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if ($productId) {
            // Get single product by ID
            $sql = "SELECT 
                        id, 
                        name, 
                        category_id, 
                        price, 
                        cost, 
                        stock_quantity, 
                        min_stock_level,
                        sku, 
                        description, 
                        image_url, 
                        is_active, 
                        created_at, 
                        updated_at 
                    FROM products 
                    WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if ($product) {
                Response::success([$product]);
            } else {
                Response::error('Product not found', null, 404);
            }
            return;
        }
        
        // Get all products
        $activeOnly = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : false;
        
        $sql = "SELECT 
                    id, 
                    name, 
                    category_id, 
                    price, 
                    cost, 
                    stock_quantity, 
                    min_stock_level,
                    sku, 
                    description, 
                    image_url, 
                    is_active, 
                    created_at, 
                    updated_at 
                FROM products";
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        Response::success($products);
        
    } catch (Exception $e) {
        logError('Get products error: ' . $e->getMessage());
        Response::success([]);
    }
}

function handleCreateProduct($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON data', null, 400);
            return;
        }
        
        // Validate required fields
        if (!isset($input['name']) || !isset($input['price'])) {
            Response::error('Missing required fields: name, price', null, 400);
            return;
        }
        
        $name = sanitizeInput($input['name']);
        $price = (float)$input['price'];
        $cost = isset($input['cost']) ? (float)$input['cost'] : 0;
        $categoryId = isset($input['category_id']) ? (int)$input['category_id'] : null;
        $stockQuantity = isset($input['stock_quantity']) ? (int)$input['stock_quantity'] : 0;
        $minStockLevel = isset($input['min_stock_level']) ? (int)$input['min_stock_level'] : 5;
        $sku = isset($input['sku']) ? sanitizeInput($input['sku']) : null;
        $description = isset($input['description']) ? sanitizeInput($input['description']) : null;
        $imageUrl = isset($input['image_url']) ? sanitizeInput($input['image_url']) : null;
        $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
        
        // Validate data
        if (!Validator::required($name)) {
            Response::error('Product name is required', null, 400);
            return;
        }
        
        if (!Validator::decimal($price) || $price <= 0) {
            Response::error('Price must be a positive number', null, 400);
            return;
        }
        
        if (!Validator::decimal($cost) || $cost < 0) {
            Response::error('Cost must be a non-negative number', null, 400);
            return;
        }
        
        // Generate SKU if not provided
        if (!$sku) {
            $sku = generateSKU('PROD', $name);
        }
        
        $stmt = $db->prepare("
            INSERT INTO products (name, category_id, price, cost, stock_quantity, min_stock_level, sku, description, image_url, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $name, 
            $categoryId, 
            $price, 
            $cost, 
            $stockQuantity, 
            $minStockLevel, 
            $sku, 
            $description, 
            $imageUrl, 
            $isActive
        ]);
        
        $productId = $db->lastInsertId();
        
        // Log the creation
        logActivity(1, 'product_created', ['product_id' => $productId, 'name' => $name]);
        
        Response::success([
            'id' => $productId,
            'name' => $name,
            'sku' => $sku
        ], 'Product created successfully');
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            if (strpos($e->getMessage(), 'name') !== false) {
                Response::error('Product name already exists', null, 409);
            } elseif (strpos($e->getMessage(), 'sku') !== false) {
                Response::error('SKU already exists', null, 409);
            } else {
                Response::error('Duplicate entry error', null, 409);
            }
        } else {
            logError('Create product error: ' . $e->getMessage());
            Response::error('Failed to create product: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        logError('Create product error: ' . $e->getMessage());
        Response::error('Failed to create product: ' . $e->getMessage());
    }
}

function handleUpdateProduct($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            Response::error('Invalid data or missing product ID', null, 400);
            return;
        }
        
        $id = (int)$input['id'];
        
        // Check if product exists
        $stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', null, 404);
            return;
        }
        
        // Build update query dynamically
        $updateFields = [];
        $params = [];
        
        $allowedFields = [
            'name', 'category_id', 'price', 'cost', 'stock_quantity', 
            'min_stock_level', 'sku', 'description', 'image_url', 'is_active'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'name' || $field === 'sku' || $field === 'description') {
                    $updateFields[] = "$field = ?";
                    $params[] = sanitizeInput($input[$field]);
                } else {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
        }
        
        if (empty($updateFields)) {
            Response::error('No fields to update', null, 400);
            return;
        }
        
        // Add updated_at
        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;
        
        $stmt = $db->prepare("UPDATE products SET " . implode(', ', $updateFields) . " WHERE id = ?");
        $stmt->execute($params);
        
        // Log the update
        logActivity(1, 'product_updated', ['product_id' => $id, 'fields' => array_keys($input)]);
        
        Response::success(['id' => $id], 'Product updated successfully');
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            if (strpos($e->getMessage(), 'name') !== false) {
                Response::error('Product name already exists', null, 409);
            } elseif (strpos($e->getMessage(), 'sku') !== false) {
                Response::error('SKU already exists', null, 409);
            } else {
                Response::error('Duplicate entry error', null, 409);
            }
        } else {
            logError('Update product error: ' . $e->getMessage());
            Response::error('Failed to update product: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        logError('Update product error: ' . $e->getMessage());
        Response::error('Failed to update product: ' . $e->getMessage());
    }
}

function handleDeleteProduct($db) {
    try {
        $id = null;
        
        // Handle both JSON input and query parameter
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? $_GET['id'] ?? null;
        }
        
        if (!$id) {
            Response::error('Product ID is required', null, 400);
            return;
        }
        
        $id = (int)$id;
        
        // Check if product exists
        $stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            Response::error('Product not found', null, 404);
            return;
        }
        
        // Check if product is used in any orders - commented out until order system is implemented
        /*
        $stmt = $db->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $stmt->execute([$id]);
        $orderCount = $stmt->fetchColumn();
        
        if ($orderCount > 0) {
            // Instead of preventing deletion, just mark as inactive
            $stmt = $db->prepare("UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity(1, 'product_deactivated', ['product_id' => $id, 'name' => $product['name'], 'reason' => 'used_in_orders']);
            
            Response::success(['id' => $id], 'Product deactivated (used in existing orders)');
            return;
        }
        */
        
        // Safe to delete
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            Response::error('Product not found', null, 404);
            return;
        }
        
        // Log the deletion
        logActivity(1, 'product_deleted', ['product_id' => $id, 'name' => $product['name']]);
        
        Response::success(['id' => $id], 'Product deleted successfully');
        
    } catch (Exception $e) {
        logError('Delete product error: ' . $e->getMessage());
        Response::error('Failed to delete product: ' . $e->getMessage());
    }
}
?>
