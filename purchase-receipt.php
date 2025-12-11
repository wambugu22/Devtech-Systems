<?php
/**
 * DevTech Systems - Purchase Receipt
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Get purchase ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: purchase-history.php');
    exit();
}

$purchase_id = intval($_GET['id']);

// Get purchase details
$sql = "SELECT pu.*, p.product_name, p.product_code, s.supplier_name, s.phone as supplier_phone, s.email as supplier_email 
        FROM purchases pu
        JOIN products p ON pu.product_id = p.product_id 
        LEFT JOIN suppliers s ON pu.supplier_id = s.supplier_id 
        WHERE pu.purchase_id = ?";
$purchase = fetch_one($sql, [$purchase_id], "i");

if (!$purchase) {
    header('Location: purchase-history.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Receipt #<?php echo $purchase_id; ?> - DevTech Systems</title>
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
            <p style="margin-top: 15px; font-size: 18px; font-weight: bold;">PURCHASE ORDER</p>
        </div>
        
        <!-- Receipt Info -->
        <div class="section">
            <div class="info-row">
                <span class="info-label">Purchase Order No:</span>
                <span>#PO-<?php echo str_pad($purchase_id, 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span><?php echo format_datetime($purchase['purchase_date']); ?></span>
            </div>
            <?php if ($purchase['supplier_name']): ?>
            <div class="section-title" style="margin-top: 20px;">SUPPLIER INFORMATION</div>
            <div class="info-row">
                <span class="info-label">Supplier:</span>
                <span><?php echo htmlspecialchars($purchase['supplier_name']); ?></span>
            </div>
            <?php if ($purchase['supplier_phone']): ?>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span><?php echo htmlspecialchars($purchase['supplier_phone']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($purchase['supplier_email']): ?>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span><?php echo htmlspecialchars($purchase['supplier_email']); ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Cost</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($purchase['product_name']); ?></strong>
                        <br>
                        <small>Code: <?php echo htmlspecialchars($purchase['product_code']); ?></small>
                    </td>
                    <td style="text-align: center;"><?php echo $purchase['quantity']; ?></td>
                    <td style="text-align: right;"><?php echo format_currency($purchase['unit_cost']); ?></td>
                    <td style="text-align: right;"><strong><?php echo format_currency($purchase['total_cost']); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="total-section">
            <div class="total-row grand-total">
                <span>TOTAL AMOUNT:</span>
                <span><?php echo format_currency($purchase['total_cost']); ?></span>
            </div>
            <div class="info-row" style="margin-top: 15px;">
                <span class="info-label">Payment Status:</span>
                <span><?php echo strtoupper($purchase['payment_status']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span><?php echo $purchase['payment_method'] ? strtoupper($purchase['payment_method']) : 'N/A'; ?></span>
            </div>
            <?php if ($purchase['notes']): ?>
            <div style="margin-top: 20px;">
                <div class="info-label">Notes:</div>
                <p style="margin-top: 5px;"><?php echo htmlspecialchars($purchase['notes']); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p style="font-weight: bold; margin-bottom: 10px;">Thank you for your business!</p>
            <p style="margin-top: 15px; font-size: 12px;">This is a computer-generated document</p>
        </div>
    </div>
    
    <!-- Print Buttons -->
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <button class="back-btn" onclick="window.location.href='purchase-history.php'">
            Back to History
        </button>
    </div>
</body>
</html>