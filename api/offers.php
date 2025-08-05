<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['active_only'])) {
                handleGetActiveOffers($db);
            } elseif (isset($_GET['checkout_offers'])) {
                handleGetCheckoutOffers($db);
            } else {
                handleGetOffers($db);
            }
            break;
        case 'POST':
            handleCreateOffer($db);
            break;
        case 'PUT':
            handleUpdateOffer($db);
            break;
        case 'DELETE':
            handleDeleteOffer($db);
            break;
        default:
            Response::error('Method not allowed');
    }
} catch (Exception $e) {
    logError('Offers API Error: ' . $e->getMessage());
    Response::error('Internal server error');
}

function handleGetOffers($db) {
    // Check if offers table exists
    $stmt = $db->query("SHOW TABLES LIKE 'offers'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        // Return empty array with proper structure to prevent undefined errors
        Response::success([]);
        return;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM offers ORDER BY created_at DESC");
        $stmt->execute();
        $offers = $stmt->fetchAll();
        
        Response::success($offers);
    } catch (Exception $e) {
        logError('Get offers error: ' . $e->getMessage());
        Response::success([]); // Return empty array instead of error
    }
}

function handleGetActiveOffers($db) {
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    $stmt = $db->prepare("
        SELECT * FROM offers 
        WHERE is_active = 1 
        AND start_date <= ? 
        AND end_date >= ?
        AND start_time <= ?
        AND end_time >= ?
        ORDER BY discount_percent DESC
    ");
    $stmt->execute([$currentDate, $currentDate, $currentTime, $currentTime]);
    $offers = $stmt->fetchAll();
    
    Response::success($offers);
}

function handleGetCheckoutOffers($db) {
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    $stmt = $db->prepare("
        SELECT id, name, discount_percent, discount_type, apply_to_all, applicable_items 
        FROM offers 
        WHERE is_active = 1 
        AND start_date <= ? 
        AND end_date >= ?
        AND start_time <= ?
        AND end_time >= ?
        ORDER BY discount_percent DESC
    ");
    $stmt->execute([$currentDate, $currentDate, $currentTime, $currentTime]);
    $offers = $stmt->fetchAll();
    
    Response::success($offers);
}

function handleCreateOffer($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $requiredFields = ['name', 'start_date', 'end_date', 'start_time', 'end_time', 'discount_percent'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            Response::error("Missing required field: $field");
        }
    }
    
    $name = sanitizeInput($input['name']);
    $startDate = $input['start_date'];
    $endDate = $input['end_date'];
    $startTime = $input['start_time'];
    $endTime = $input['end_time'];
    $discountPercent = $input['discount_percent'];
    $applyToAll = isset($input['apply_to_all']) ? (bool)$input['apply_to_all'] : true;
    $applicableItems = $input['applicable_items'] ?? [];
    $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
    
    // Validate data
    if (!Validator::required($name)) {
        Response::error('Offer name is required');
    }
    
    if (!Validator::date($startDate) || !Validator::date($endDate)) {
        Response::error('Invalid date format');
    }
    
    if (!Validator::time($startTime) || !Validator::time($endTime)) {
        Response::error('Invalid time format');
    }
    
    if (!Validator::decimal($discountPercent) || $discountPercent <= 0 || $discountPercent > 100) {
        Response::error('Discount percent must be between 0 and 100');
    }
    
    if ($endDate < $startDate) {
        Response::error('End date cannot be before start date');
    }
    
    if (!$applyToAll && empty($applicableItems)) {
        Response::error('Specific items must be selected when not applying to all items');
    }
    
    $db->beginTransaction();
    
    try {
        // Create offer
        $stmt = $db->prepare("
            INSERT INTO offers (name, start_date, end_date, start_time, end_time, discount_percent, apply_to_all, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $startDate, $endDate, $startTime, $endTime, $discountPercent, $applyToAll, $isActive]);
        
        $offerId = $db->lastInsertId();
        
        // Add specific items if not applying to all
        if (!$applyToAll && !empty($applicableItems)) {
            $stmt = $db->prepare("INSERT INTO offer_items (offer_id, item_id, item_type) VALUES (?, ?, ?)");
            foreach ($applicableItems as $item) {
                list($itemType, $itemId) = explode('-', $item, 2);
                $stmt->execute([$offerId, $itemId, $itemType]);
            }
        }
        
        $db->commit();
        
        Response::success(['id' => $offerId], 'Offer created successfully');
    } catch (PDOException $e) {
        $db->rollBack();
        throw $e;
    }
}

function handleUpdateOffer($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        Response::error('Offer ID is required');
    }
    
    $id = (int)$input['id'];
    
    // Check if it's just a toggle operation
    if (isset($input['toggle_active'])) {
        $stmt = $db->prepare("UPDATE offers SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            Response::error('Offer not found');
        }
        
        Response::success(null, 'Offer status updated successfully');
        return;
    }
    
    $name = sanitizeInput($input['name']);
    $startDate = $input['start_date'];
    $endDate = $input['end_date'];
    $startTime = $input['start_time'];
    $endTime = $input['end_time'];
    $discountPercent = $input['discount_percent'];
    $applyToAll = isset($input['apply_to_all']) ? (bool)$input['apply_to_all'] : true;
    $applicableItems = $input['applicable_items'] ?? [];
    $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
    
    // Validate data (same as create)
    if (!Validator::required($name)) {
        Response::error('Offer name is required');
    }
    
    if (!Validator::date($startDate) || !Validator::date($endDate)) {
        Response::error('Invalid date format');
    }
    
    if (!Validator::time($startTime) || !Validator::time($endTime)) {
        Response::error('Invalid time format');
    }
    
    if (!Validator::decimal($discountPercent) || $discountPercent <= 0 || $discountPercent > 100) {
        Response::error('Discount percent must be between 0 and 100');
    }
    
    if ($endDate < $startDate) {
        Response::error('End date cannot be before start date');
    }
    
    if (!$applyToAll && empty($applicableItems)) {
        Response::error('Specific items must be selected when not applying to all items');
    }
    
    $db->beginTransaction();
    
    try {
        // Update offer
        $stmt = $db->prepare("
            UPDATE offers 
            SET name = ?, start_date = ?, end_date = ?, start_time = ?, end_time = ?, 
                discount_percent = ?, apply_to_all = ?, is_active = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$name, $startDate, $endDate, $startTime, $endTime, $discountPercent, $applyToAll, $isActive, $id]);
        
        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            Response::error('Offer not found');
        }
        
        // Remove existing offer items
        $stmt = $db->prepare("DELETE FROM offer_items WHERE offer_id = ?");
        $stmt->execute([$id]);
        
        // Add new specific items if not applying to all
        if (!$applyToAll && !empty($applicableItems)) {
            $stmt = $db->prepare("INSERT INTO offer_items (offer_id, item_id, item_type) VALUES (?, ?, ?)");
            foreach ($applicableItems as $item) {
                list($itemType, $itemId) = explode('-', $item, 2);
                $stmt->execute([$id, $itemId, $itemType]);
            }
        }
        
        $db->commit();
        
        Response::success(null, 'Offer updated successfully');
    } catch (PDOException $e) {
        $db->rollBack();
        throw $e;
    }
}

function handleDeleteOffer($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        Response::error('Offer ID is required');
    }
    
    $id = (int)$input['id'];
    
    $stmt = $db->prepare("DELETE FROM offers WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        Response::error('Offer not found');
    }
    
    Response::success(null, 'Offer deleted successfully');
}
?>
