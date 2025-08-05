<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

try {
    $query = $_GET['query'] ?? '';
    
    if (strlen($query) < 2) {
        Response::success([]);
    }
    
    $searchTerm = '%' . $query . '%';
    
    // Search products
    $stmt = $db->prepare("
        SELECT id, name, price, 'product' as type 
        FROM products 
        WHERE is_active = 1 AND name LIKE ? 
        ORDER BY name ASC 
        LIMIT 10
    ");
    $stmt->execute([$searchTerm]);
    $products = $stmt->fetchAll();
    
    // Search combos
    $stmt = $db->prepare("
        SELECT id, name, price, 'combo' as type 
        FROM combos 
        WHERE is_active = 1 AND name LIKE ? 
        ORDER BY name ASC 
        LIMIT 10
    ");
    $stmt->execute([$searchTerm]);
    $combos = $stmt->fetchAll();
    
    // Combine and limit results
    $results = array_merge($products, $combos);
    
    // Sort by relevance (exact matches first, then alphabetical)
    usort($results, function($a, $b) use ($query) {
        $aExact = (stripos($a['name'], $query) === 0) ? 0 : 1;
        $bExact = (stripos($b['name'], $query) === 0) ? 0 : 1;
        
        if ($aExact !== $bExact) {
            return $aExact - $bExact;
        }
        
        return strcasecmp($a['name'], $b['name']);
    });
    
    // Limit to 15 results
    $results = array_slice($results, 0, 15);
    
    Response::success($results);
    
} catch (Exception $e) {
    logError('Search API Error: ' . $e->getMessage());
    Response::error('Search failed');
}
?>
