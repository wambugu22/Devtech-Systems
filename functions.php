<?php
/**
 * DevTech Systems - Global Helper Functions
 */

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data; // Only basic sanitation, real escaping happens in execute_query params
}

/**
 * Format number as currency (KES)
 */
function format_currency($amount) {
    return 'KES ' . number_format((float)$amount, 2);
}

/**
 * Format Date
 */
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format DateTime
 */
function format_datetime($date) {
    return date('M d, Y H:i', strtotime($date));
}

/**
 * Clean data for DB insertion (escapes string) - Legacy helper
 */
function clean_data($data) {
    global $conn; 
    return $conn->real_escape_string($data);
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: " . SITE_URL . "/" . $url);
    exit();
}

/**
 * Set Flash Message
 * Types: success, danger, warning, info
 */
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Display Flash Message
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'success';
        $message = $_SESSION['flash_message'];
        
        // Map types to FontAwesome icons
        $icons = [
            'success' => 'check-circle',
            'danger' => 'exclamation-circle',
            'warning' => 'exclamation-triangle',
            'info' => 'info-circle'
        ];
        $icon = $icons[$type] ?? 'info-circle';

        echo '<div class="alert alert-' . $type . '">
            <i class="fas fa-' . $icon . '"></i>
            <span>' . $message . '</span>
        </div>';

        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Check if user has permission
 */
function user_has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Calculate Profit Margin
 */
function calculate_profit_margin($selling_price, $cost_price) {
    if ($selling_price == 0) return 0;
    return (($selling_price - $cost_price) / $selling_price) * 100;
}

// ---------------- DASHBOARD WIDGET FUNCTIONS ---------------- //

/**
 * Get Dashboard Stats
 */
function get_dashboard_stats() {
    $stats = [
        'total_products' => 0,
        'today_sales' => 0,
        'today_profit' => 0,
        'low_stock' => 0,
        'month_sales' => 0,
        'month_profit' => 0,
        'month_expenses' => 0,
        'net_profit' => 0,
        'stock_value' => 0
    ];

    // Total Products & Stock Value
    $products = fetch_all("SELECT quantity, cost_price, reorder_level FROM products");
    $stats['total_products'] = count($products);
    foreach ($products as $p) {
        if ($p['quantity'] <= $p['reorder_level']) {
            $stats['low_stock']++;
        }
        $stats['stock_value'] += ($p['quantity'] * $p['cost_price']);
    }

    // Today's Sales
    $today = date('Y-m-d');
    $sales_today = fetch_all("SELECT total_amount, profit FROM sales WHERE DATE(sale_date) = ?", [$today], "s");
    foreach ($sales_today as $sale) {
        $stats['today_sales'] += $sale['total_amount'];
        $stats['today_profit'] += $sale['profit'];
    }

    // Month Sales
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');
    $sales_month = fetch_all("SELECT total_amount, profit FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?", [$month_start, $month_end], "ss");
    foreach ($sales_month as $sale) {
        $stats['month_sales'] += $sale['total_amount'];
        $stats['month_profit'] += $sale['profit'];
    }

    // Month Expenses
    $expenses = fetch_all("SELECT amount FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?", [$month_start, $month_end], "ss");
    foreach ($expenses as $e) {
        $stats['month_expenses'] += $e['amount'];
    }

    // Net Profit
    $stats['net_profit'] = $stats['month_profit'] - $stats['month_expenses'];

    return $stats;
}

/**
 * Get Low Stock Products
 */
function get_low_stock_products() {
    return fetch_all("SELECT product_id, product_name, product_code, quantity, reorder_level FROM products WHERE quantity <= reorder_level LIMIT 5");
}

/**
 * Get Recent Sales
 */
function get_recent_sales($limit = 5) {
    // Corrected query using 'sales' table directly (no sale_items)
    $sql = "SELECT s.sale_date, s.total_amount, s.profit, p.product_name, s.quantity 
            FROM sales s 
            JOIN products p ON s.product_id = p.product_id
            ORDER BY s.sale_date DESC LIMIT ?";
    return fetch_all($sql, [$limit], "i");
}

/**
 * Get Top Products
 */
function get_top_products($limit = 5, $period = 'month') {
    // Corrected query using 'sales' table directly
    $month_start = date('Y-m-01');
    $sql = "SELECT p.product_name, p.product_code, SUM(s.quantity) as total_sold, SUM(s.total_amount) as total_revenue, SUM(s.profit) as total_profit
            FROM sales s
            JOIN products p ON s.product_id = p.product_id
            WHERE s.sale_date >= ?
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT ?";
    return fetch_all($sql, [$month_start, $limit], "si");
}

// ---------------- INVENTORY HELPER FUNCTIONS ---------------- //

/**
 * Update Product Stock
 */
function update_product_stock($product_id, $quantity, $type = 'subtract') {
    if ($type === 'subtract') {
        $sql = "UPDATE products SET quantity = quantity - ? WHERE product_id = ?";
    } else {
        $sql = "UPDATE products SET quantity = quantity + ? WHERE product_id = ?";
    }
    return execute_query($sql, [$quantity, $product_id], "ii");
}

/**
 * Send Notification (Mock implementation for now)
 */
function send_notification($user_id, $type, $message) {
    // In a real app, this might insert into a notifications table
    // For now, we'll just log it
    // error_log("Notification for User $user_id [$type]: $message");
    
    // Attempt to insert if 'notifications' table exists (optional)
    /*
    $sql = "INSERT INTO notifications (user_id, type, message, created_at) VALUES (?, ?, ?, NOW())";
    execute_query($sql, [$user_id, $type, $message], "iss");
    */
    return true;
}

?>
