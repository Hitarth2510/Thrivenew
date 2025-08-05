<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGetDashboard($db);
            break;
        default:
            Response::error('Method not allowed', null, 405);
    }
} catch (Exception $e) {
    logError('Dashboard API Error: ' . $e->getMessage());
    
    // Return default structure to prevent undefined errors
    $defaultDashboard = [
        'stats' => [
            'total_revenue' => 0,
            'total_orders' => 0,
            'avg_order_value' => 0,
            'products_sold' => 0
        ],
        'recent_orders' => [],
        'top_products' => [],
        'low_stock' => [],
        'sales_chart' => []
    ];
    
    Response::success($defaultDashboard);
}

function handleGetDashboard($db) {
    $filter = $_GET['filter'] ?? 'today';
    $startDate = null;
    $endDate = null;

    // Determine date range based on filter
    switch ($filter) {
        case 'today':
            $startDate = $endDate = date('Y-m-d');
            break;
        case '7days':
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate = date('Y-m-d');
            break;
        case '28days':
            $startDate = date('Y-m-d', strtotime('-28 days'));
            $endDate = date('Y-m-d');
            break;
        case 'custom':
            $startDate = $_GET['start_date'] ?? date('Y-m-d');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            if (!Validator::date($startDate) || !Validator::date($endDate)) {
                Response::error('Invalid date format');
            }
            break;
        default:
            Response::error('Invalid filter');
    }

    try {
        $dashboard = [
            'stats' => getDashboardStats($db, $startDate, $endDate),
            'recent_orders' => getRecentOrders($db),
            'top_products' => getTopProducts($db, $startDate, $endDate),
            'low_stock' => getLowStockProducts($db),
            'sales_chart' => getSalesChart($db, $startDate, $endDate, $filter)
        ];

        Response::success($dashboard);
    } catch (Exception $e) {
        logError('Dashboard data error: ' . $e->getMessage());
        
        // Return default structure to prevent undefined errors
        $defaultDashboard = [
            'stats' => [
                'total_revenue' => 0,
                'total_orders' => 0,
                'avg_order_value' => 0,
                'products_sold' => 0
            ],
            'recent_orders' => [],
            'top_products' => [],
            'low_stock' => [],
            'sales_chart' => []
        ];
        
        Response::success($defaultDashboard);
    }
}

function getDashboardStats($db, $startDate, $endDate) {
    try {
        // Check if orders table exists
        $stmt = $db->query("SHOW TABLES LIKE 'orders'");
        $ordersTableExists = $stmt->fetch();
        
        if (!$ordersTableExists) {
            return [
                'total_revenue' => 0,
                'total_orders' => 0,
                'avg_order_value' => 0,
                'products_sold' => 0
            ];
        }
        
        // Get total revenue
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(final_amount), 0) AS total_revenue 
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND order_status NOT IN ('cancelled')
        ");
        $stmt->execute([$startDate, $endDate]);
        $totalRevenue = $stmt->fetchColumn();
        
        // Get total orders
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total_orders 
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND order_status NOT IN ('cancelled')
        ");
        $stmt->execute([$startDate, $endDate]);
        $totalOrders = $stmt->fetchColumn();
        
        // Get total products sold
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(oi.quantity), 0) AS products_sold
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            AND o.order_status NOT IN ('cancelled')
        ");
        $stmt->execute([$startDate, $endDate]);
        $productsSold = $stmt->fetchColumn();
        
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        return [
            'total_revenue' => (float)$totalRevenue,
            'total_orders' => (int)$totalOrders,
            'avg_order_value' => (float)$avgOrderValue,
            'products_sold' => (int)$productsSold
        ];
        
    } catch (Exception $e) {
        logError('Dashboard stats error: ' . $e->getMessage());
        return [
            'total_revenue' => 0,
            'total_orders' => 0,
            'avg_order_value' => 0,
            'products_sold' => 0
        ];
    }
}

function getRecentOrders($db) {
    try {
        // Check if orders table exists
        $stmt = $db->query("SHOW TABLES LIKE 'orders'");
        $ordersTableExists = $stmt->fetch();
        
        if (!$ordersTableExists) {
            return [];
        }
        
        $stmt = $db->prepare("
            SELECT 
                o.id,
                o.order_number,
                o.final_amount,
                o.payment_method,
                o.order_status,
                o.created_at,
                c.name AS customer_name
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            ORDER BY o.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        logError('Recent orders error: ' . $e->getMessage());
        return [];
    }
}

function getTopProducts($db, $startDate, $endDate) {
    try {
        // Check if required tables exist
        $stmt = $db->query("SHOW TABLES LIKE 'order_items'");
        $orderItemsExists = $stmt->fetch();
        
        if (!$orderItemsExists) {
            return [];
        }
        
        $stmt = $db->prepare("
            SELECT 
                p.id,
                p.name,
                SUM(oi.quantity) AS total_sold,
                SUM(oi.total_price) AS total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            AND o.order_status NOT IN ('cancelled')
            GROUP BY p.id, p.name
            ORDER BY total_sold DESC
            LIMIT 10
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        logError('Top products error: ' . $e->getMessage());
        return [];
    }
}

function getLowStockProducts($db) {
    try {
        // Check if products table exists
        $stmt = $db->query("SHOW TABLES LIKE 'products'");
        $productsExists = $stmt->fetch();
        
        if (!$productsExists) {
            return [];
        }
        
        $stmt = $db->prepare("
            SELECT 
                id,
                name,
                stock_quantity,
                min_stock_level
            FROM products
            WHERE stock_quantity <= min_stock_level
            AND is_active = 1
            ORDER BY stock_quantity ASC
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        logError('Low stock products error: ' . $e->getMessage());
        return [];
    }
}

function getSalesChart($db, $startDate, $endDate, $filter) {
    try {
        // Check if orders table exists
        $stmt = $db->query("SHOW TABLES LIKE 'orders'");
        $ordersExists = $stmt->fetch();
        
        if (!$ordersExists) {
            return [];
        }
        
        $groupBy = '';
        $dateFormat = '';
        
        switch ($filter) {
            case 'today':
                $groupBy = 'HOUR(created_at)';
                $dateFormat = '%H:00';
                break;
            case '7days':
                $groupBy = 'DATE(created_at)';
                $dateFormat = '%Y-%m-%d';
                break;
            case '28days':
                $groupBy = 'DATE(created_at)';
                $dateFormat = '%Y-%m-%d';
                break;
            default:
                $groupBy = 'DATE(created_at)';
                $dateFormat = '%Y-%m-%d';
        }
        
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(created_at, ?) AS period,
                COUNT(*) AS order_count,
                SUM(final_amount) AS revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND order_status NOT IN ('cancelled')
            GROUP BY {$groupBy}
            ORDER BY created_at ASC
        ");
        $stmt->execute([$dateFormat, $startDate, $endDate]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        logError('Sales chart error: ' . $e->getMessage());
        return [];
    }
}
?>
