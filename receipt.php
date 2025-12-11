<?php
/**
 * DevTech Systems - Sales Receipt
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Get sale ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: sales-history.php');
    exit();
}

$sale_id = intval($_GET['id']);

// Get sale details
$sql = "SELECT s.*, p.product_name, p.product_code, u.full_name as sold_by 
        FROM sales s 
        JOIN products p ON s.product_id = p.product_id 
        LEFT JOIN users u ON s.user_id = u.user_id 
        WHERE s.sale_id = ?";
$sale = fetch_one($sql, [$sale_id], "i");

if (!$sale) {
    header('Location: sales-history.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $sale_id; ?> - DevTech Systems</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .receipt {
            border: 2px solid #000;
            padding: 30px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            margin: 3px 0;
        }
        
        .section {
            margin: 20px 0;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            text-decoration: underline;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }
        
        .items-table th {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        .total-section {
            border-top: 2px dashed #000;
            padding-top: 20px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 18px;
        }
        
        .grand-total {
            font-size: 24px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            border-top: 2px dashed #000;
            padding-top: 20px;
        }
        
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        
        .no-print button {
            padding: 12px 30px;
            font-size: 16px;
            margin: 0 10px;
            cursor: pointer;
        }
        
        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
        }
        
        .back-btn {
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 5px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                padding: 0;
            }
            
            .receipt {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt" id="receipt">
        <!-- Header -->
        <div class="header">
            <h1>DEVTECH SYSTEMS</h1>
            <p>Inventory Management Solutions</p>
            <p>Tel: +254 XXX XXX XXX | Email: info@devtech.com</p>
            <p style="margin-top: 15px; font-size: 18px; font-weight: bold;">SALES RECEIPT</p>
        </div>
        
        <!-- Receipt Info -->
        <div class="section">
            <div class="info-row">
                <span class="info-label">Receipt No:</span>
                <span>#<?php echo str_pad($sale_id, 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span><?php echo format_datetime($sale['sale_date']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Served By:</span>
                <span><?php echo htmlspecialchars($sale['sold_by'] ?? 'N/A'); ?></span>
            </div>
            <?php if ($sale['customer_name']): ?>
            <div class="info-row">
                <span class="info-label">Customer:</span>
                <span><?php echo htmlspecialchars($sale['customer_name']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($sale['customer_phone']): ?>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span><?php echo htmlspecialchars($sale['customer_phone']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($sale['product_name']); ?></strong>
                        <br>
                        <small>Code: <?php echo htmlspecialchars($sale['product_code']); ?></small>
                    </td>
                    <td style="text-align: center;"><?php echo $sale['quantity']; ?></td>
                    <td style="text-align: right;"><?php echo format_currency($sale['unit_price']); ?></td>
                    <td style="text-align: right;"><strong><?php echo format_currency($sale['total_amount']); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span><?php echo format_currency($sale['total_amount']); ?></span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span><?php echo format_currency($sale['total_amount']); ?></span>
            </div>
            <div class="info-row" style="margin-top: 15px;">
                <span class="info-label">Payment Method:</span>
                <span><?php echo strtoupper($sale['payment_method']); ?></span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p style="font-weight: bold; margin-bottom: 10px;">Thank you for your business!</p>
            <p>For inquiries, please contact us at support@devtech.com</p>
            <p style="margin-top: 15px; font-size: 12px;">This is a computer-generated receipt</p>
        </div>
    </div>
    
    <!-- Print Buttons -->
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <button class="back-btn" onclick="window.location.href='sales-history.php'">
            Back to Sales History
        </button>
    </div>
</body>
</html>