<?php
require_once '../config/config.php';

$db = Database::getInstance()->getConnection();

try {
    $reportType = $_GET['report_type'] ?? '';
    $filter = $_GET['filter'] ?? 'today';
    
    // Determine date range
    $startDate = null;
    $endDate = null;
    
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
            break;
        default:
            $startDate = $endDate = date('Y-m-d');
    }
    
    switch ($reportType) {
        case 'sales':
            exportSalesReport($db, $startDate, $endDate);
            break;
        case 'products':
            exportProductsReport($db);
            break;
        case 'combos':
            exportCombosReport($db);
            break;
        case 'customers':
            exportCustomersReport($db);
            break;
        default:
            http_response_code(400);
            die('Invalid report type');
    }
    
} catch (Exception $e) {
    logError('Export API Error: ' . $e->getMessage());
    http_response_code(500);
    die('Export failed');
}

function exportSalesReport($db, $startDate, $endDate) {
    $stmt = $db->prepare("
        SELECT 
            so.id as order_id,
            so.datetime_paid,
            COALESCE(c.name, 'Walk-in') as customer_name,
            c.mobile as customer_mobile,
            so.subtotal,
            so.discount_amount,
            so.final_amount,
            so.payment_type,
            soi.item_type,
            CASE 
                WHEN soi.item_type = 'product' THEN p.name
                WHEN soi.item_type = 'combo' THEN cb.name
            END as item_name,
            soi.quantity,
            soi.price_per_item,
            (soi.quantity * soi.price_per_item) as item_total,
            CASE 
                WHEN soi.item_type = 'product' THEN (soi.price_per_item - p.making_cost) * soi.quantity
                WHEN soi.item_type = 'combo' THEN (soi.price_per_item - cb.making_cost) * soi.quantity
            END as item_profit
        FROM sales_orders so
        LEFT JOIN customers c ON so.customer_id = c.id
        JOIN sales_order_items soi ON so.id = soi.order_id
        LEFT JOIN products p ON soi.item_id = p.id AND soi.item_type = 'product'
        LEFT JOIN combos cb ON soi.item_id = cb.id AND soi.item_type = 'combo'
        WHERE DATE(so.datetime_paid) BETWEEN ? AND ?
        ORDER BY so.datetime_paid DESC, so.id ASC
    ");
    $stmt->execute([$startDate, $endDate]);
    $data = $stmt->fetchAll();
    
    $filename = "sales_report_{$startDate}_to_{$endDate}.csv";
    $headers = [
        'Order ID', 'Date', 'Time', 'Customer Name', 'Customer Mobile',
        'Item Type', 'Item Name', 'Quantity', 'Price Per Item', 'Item Total', 'Item Profit',
        'Order Subtotal', 'Order Discount', 'Order Total', 'Payment Method'
    ];
    
    outputCSV($filename, $headers, $data, function($row) {
        $datetime = new DateTime($row['datetime_paid']);
        return [
            $row['order_id'],
            $datetime->format('Y-m-d'),
            $datetime->format('H:i:s'),
            $row['customer_name'] ?: '',
            $row['customer_mobile'] ?: '',
            ucfirst($row['item_type']),
            $row['item_name'],
            $row['quantity'],
            '₹' . number_format($row['price_per_item'], 2),
            '₹' . number_format($row['quantity'] * $row['price_per_item'], 2),
            '₹' . number_format($row['item_profit'], 2),
            '₹' . number_format($row['subtotal'], 2),
            '₹' . number_format($row['discount_amount'], 2),
            '₹' . number_format($row['final_amount'], 2),
            $row['payment_type']
        ];
    });
}

function exportProductsReport($db) {
    $stmt = $db->prepare("
        SELECT 
            p.*,
            (p.price - p.making_cost) as profit_per_unit,
            COALESCE(SUM(soi.quantity), 0) as total_sold,
            COALESCE(SUM(soi.quantity * soi.price_per_item), 0) as total_revenue,
            COALESCE(SUM((soi.price_per_item - p.making_cost) * soi.quantity), 0) as total_profit
        FROM products p
        LEFT JOIN sales_order_items soi ON p.id = soi.item_id AND soi.item_type = 'product'
        GROUP BY p.id
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();
    
    $filename = "products_report_" . date('Y-m-d') . ".csv";
    $headers = [
        'ID', 'Name', 'Selling Price', 'Making Cost', 'Profit Per Unit', 
        'Status', 'Total Sold', 'Total Revenue', 'Total Profit', 'Created At'
    ];
    
    outputCSV($filename, $headers, $data, function($row) {
        return [
            $row['id'],
            $row['name'],
            '₹' . number_format($row['price'], 2),
            '₹' . number_format($row['making_cost'], 2),
            '₹' . number_format($row['profit_per_unit'], 2),
            $row['is_active'] ? 'Active' : 'Inactive',
            $row['total_sold'],
            '₹' . number_format($row['total_revenue'], 2),
            '₹' . number_format($row['total_profit'], 2),
            $row['created_at']
        ];
    });
}

function exportCombosReport($db) {
    $stmt = $db->prepare("
        SELECT 
            c.*,
            (c.price - c.making_cost) as profit_per_unit,
            COALESCE(SUM(soi.quantity), 0) as total_sold,
            COALESCE(SUM(soi.quantity * soi.price_per_item), 0) as total_revenue,
            COALESCE(SUM((soi.price_per_item - c.making_cost) * soi.quantity), 0) as total_profit,
            GROUP_CONCAT(p.name SEPARATOR ', ') as products
        FROM combos c
        LEFT JOIN sales_order_items soi ON c.id = soi.item_id AND soi.item_type = 'combo'
        LEFT JOIN combo_items ci ON c.id = ci.combo_id
        LEFT JOIN products p ON ci.product_id = p.id
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();
    
    $filename = "combos_report_" . date('Y-m-d') . ".csv";
    $headers = [
        'ID', 'Name', 'Products', 'Selling Price', 'Making Cost', 'Profit Per Unit',
        'Status', 'Total Sold', 'Total Revenue', 'Total Profit', 'Created At'
    ];
    
    outputCSV($filename, $headers, $data, function($row) {
        return [
            $row['id'],
            $row['name'],
            $row['products'] ?: '',
            '₹' . number_format($row['price'], 2),
            '₹' . number_format($row['making_cost'], 2),
            '₹' . number_format($row['profit_per_unit'], 2),
            $row['is_active'] ? 'Active' : 'Inactive',
            $row['total_sold'],
            '₹' . number_format($row['total_revenue'], 2),
            '₹' . number_format($row['total_profit'], 2),
            $row['created_at']
        ];
    });
}

function exportCustomersReport($db) {
    $stmt = $db->prepare("
        SELECT 
            c.*,
            COUNT(so.id) as total_orders,
            COALESCE(SUM(so.final_amount), 0) as total_spent,
            COALESCE(AVG(so.final_amount), 0) as avg_order_value,
            MAX(so.datetime_paid) as last_order_date
        FROM customers c
        LEFT JOIN sales_orders so ON c.id = so.customer_id
        GROUP BY c.id
        ORDER BY total_spent DESC, c.name ASC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();
    
    $filename = "customers_report_" . date('Y-m-d') . ".csv";
    $headers = [
        'ID', 'Name', 'Mobile', 'Total Orders', 'Total Spent', 
        'Average Order Value', 'Last Order Date', 'Customer Since'
    ];
    
    outputCSV($filename, $headers, $data, function($row) {
        return [
            $row['id'],
            $row['name'] ?: '',
            $row['mobile'] ?: '',
            $row['total_orders'],
            '₹' . number_format($row['total_spent'], 2),
            '₹' . number_format($row['avg_order_value'], 2),
            $row['last_order_date'] ? date('Y-m-d H:i', strtotime($row['last_order_date'])) : '',
            date('Y-m-d', strtotime($row['created_at']))
        ];
    });
}

function outputCSV($filename, $headers, $data, $rowMapper) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fputs($output, "\xEF\xBB\xBF");
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $rowMapper($row));
    }
    
    fclose($output);
    exit;
}
?>
