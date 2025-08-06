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
        case '30days':
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');
            break;
        case 'year':
            $startDate = date('Y-01-01');
            $endDate = date('Y-m-d');
            break;
        default:
            $startDate = $endDate = date('Y-m-d');
    }

    try {
        $dashboard = [
            'stats' => getDashboardStats($db, $startDate, $endDate),
            'recent_orders' => getRecentOrders($db),
            'top_products' => getTopProducts($db, $startDate, $endDate),
            'low_stock' => getLowStock($db),
            'sales_chart' => getSalesChart($db, $filter, $startDate, $endDate)
        ];

        Response::success($dashboard);
    } catch (Exception $e) {
        logError('Dashboard data error: ' . $e->getMessage());
        Response::error('Failed to load dashboard data');
    }
}

function getDashboardStats($db, $startDate, $endDate) {
    try {
        // Get total revenue from sales_orders
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(total_amount), 0) AS total_revenue 
            FROM sales_orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $totalRevenue = $stmt->fetchColumn();
        
        // Get total orders count
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total_orders 
            FROM sales_orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $totalOrders = $stmt->fetchColumn();
        
        // Calculate average order value
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        // For products sold, we'll use order_items if available, otherwise estimate from orders
        $productsSold = 0;
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'order_items'
            ");
            $stmt->execute();
            $orderItemsExists = $stmt->fetchColumn() > 0;
            
            if ($orderItemsExists) {
                $stmt = $db->prepare("
                    SELECT COALESCE(SUM(oi.quantity), 0) AS products_sold
                    FROM order_items oi
                    JOIN sales_orders so ON oi.order_id = so.id
                    WHERE DATE(so.created_at) BETWEEN ? AND ?
                ");
                $stmt->execute([$startDate, $endDate]);
                $productsSold = $stmt->fetchColumn();
            } else {
                // Estimate based on order count if order_items doesn't exist
                $productsSold = $totalOrders;
            }
        } catch (Exception $e) {
            $productsSold = $totalOrders; // Fallback
        }
        
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
        $stmt = $db->prepare("
            SELECT 
                so.id,
                so.order_number,
                so.total_amount as final_amount,
                so.payment_method,
                'completed' as order_status,
                so.created_at,
                c.name AS customer_name,
                c.email AS customer_email
            FROM sales_orders so
            LEFT JOIN customers c ON so.customer_id = c.id
            ORDER BY so.created_at DESC
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
        // Check if order_items table exists
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'order_items'
        ");
        $stmt->execute();
        $orderItemsExists = $stmt->fetchColumn() > 0;
        
        if ($orderItemsExists) {
            $stmt = $db->prepare("
                SELECT 
                    p.id,
                    p.name,
                    SUM(oi.quantity) AS total_sold,
                    SUM(oi.total_price) AS total_revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN sales_orders so ON oi.order_id = so.id
                WHERE DATE(so.created_at) BETWEEN ? AND ?
                GROUP BY p.id, p.name
                ORDER BY total_sold DESC
                LIMIT 10
            ");
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll();
        } else {
            // Fallback: return most popular products based on stock levels
            $stmt = $db->prepare("
                SELECT 
                    id,
                    name,
                    (100 - stock_quantity) AS total_sold,
                    price * (100 - stock_quantity) AS total_revenue
                FROM products
                WHERE is_active = 1
                ORDER BY total_sold DESC
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        }
        
    } catch (Exception $e) {
        logError('Top products error: ' . $e->getMessage());
        return [];
    }
}

function getLowStock($db) {
    try {
        $stmt = $db->prepare("
            SELECT 
                id,
                name,
                stock_quantity,
                COALESCE(min_stock_level, 10) as min_stock_level,
                price
            FROM products
            WHERE stock_quantity <= COALESCE(min_stock_level, 10)
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

function getSalesChart($db, $filter, $startDate, $endDate) {
    try {
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
            case '30days':
                $groupBy = 'DATE(created_at)';
                $dateFormat = '%Y-%m-%d';
                break;
            case 'year':
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
                SUM(total_amount) AS revenue
            FROM sales_orders
            WHERE DATE(created_at) BETWEEN ? AND ?
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
