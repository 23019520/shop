<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get user's order history with proper status handling
$stmt = $pdo->prepare("
    SELECT 
        o.order_id,
        o.user_id,
        o.product_id,
        o.product_name,
        o.quantity,
        o.price,
        o.order_date,
        COALESCE(o.status, 'pending') AS order_status,
        o.tracking_number,
        p.image_url,
        p.description
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.product_id
    WHERE o.user_id = ? 
    ORDER BY 
        CASE 
            WHEN COALESCE(o.status, 'pending') = 'pending' THEN 1
            WHEN COALESCE(o.status, 'pending') = 'processing' THEN 2
            WHEN COALESCE(o.status, 'pending') = 'shipped' THEN 3
            WHEN COALESCE(o.status, 'pending') = 'delivered' THEN 4
            WHEN COALESCE(o.status, 'pending') = 'cancelled' THEN 5
            ELSE 6
        END,
        o.order_date DESC
");

$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Calculate statistics
$stats = [
    'total' => 0,
    'count' => count($orders),
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0,
    'savings' => 0
];

foreach ($orders as $order) {
    $stats['total'] += $order['price'] * $order['quantity'];
    $status = strtolower($order['order_status'] ?? 'pending');
    if (isset($stats[$status])) {
        $stats[$status]++;
    }
    
    // Calculate savings if you had original prices
    if (isset($order['original_price'])) {
        $stats['savings'] += ($order['original_price'] - $order['price']) * $order['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking</title>
    <style>
        :root {
            --primary: #4CAF50;
            --secondary: #2c3e50;
            --pending: #FFA500;
            --processing: #1E90FF;
            --shipped: #9932CC;
            --delivered: var(--primary);
            --cancelled: #FF0000;
            --link: #1E90FF;
            --hover: #0d6efd;
            --shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        
        h2 {
            color: var(--secondary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            margin-top: 0;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: var(--primary);
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: var(--shadow);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--primary);
            color: white;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background-color: rgba(76, 175, 80, 0.1);
            text-decoration: underline;
        }
        
        /* Status colors */
        .status-pending { color: var(--pending); font-weight: bold; }
        .status-processing { color: var(--processing); font-weight: bold; }
        .status-shipped { color: var(--shipped); font-weight: bold; }
        .status-delivered { color: var(--delivered); font-weight: bold; }
        .status-cancelled { color: var(--cancelled); font-weight: bold; }
        
        .tracking-btn {
            background: none;
            border: none;
            color: var(--link);
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
            font: inherit;
        }
        
        .tracking-btn:hover {
            color: var(--hover);
        }
        
        .no-orders {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        
        .total-amount {
            font-size: 18px;
            color: var(--secondary);
            text-align: right;
            margin-top: 20px;
        }
        
        .amount-value {
            font-weight: bold;
            color: var(--primary);
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
        }
        
        .product-info {
            display: flex;
            flex-direction: column;
        }
        
        .product-name {
            font-weight: bold;
        }
        
        .product-desc {
            font-size: 0.9em;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="shop.php">Continue Shopping</a>
            <a href="cart.php">View Cart</a>
            <a href="order_tracking.php">Refresh Orders</a>
            <a href="logout.php">Logout</a>
        </div>

        <h2>Your Order History</h2>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="stat-value"><?php echo $stats['count']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Amount Spent</h3>
                <div class="stat-value">$<?php echo number_format($stats['total'], 2); ?></div>
            </div>
            <?php if ($stats['savings'] > 0): ?>
            <div class="stat-card">
                <h3>Total Savings</h3>
                <div class="stat-value">$<?php echo number_format($stats['savings'], 2); ?></div>
            </div>
            <?php endif; ?>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="stat-value status-pending"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Processing</h3>
                <div class="stat-value status-processing"><?php echo $stats['processing']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Shipped</h3>
                <div class="stat-value status-shipped"><?php echo $stats['shipped']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Delivered</h3>
                <div class="stat-value status-delivered"><?php echo $stats['delivered']; ?></div>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <p>You haven't placed any orders yet.</p>
                <p><a href="shop.php">Start shopping now!</a></p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Tracking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['order_id']; ?></td>
                        <td class="product-cell">
                            <?php if (!empty($order['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($order['product_name']); ?>" 
                                     class="product-img">
                            <?php endif; ?>
                            <div class="product-info">
                                <span class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></span>
                                <?php if (!empty($order['description'])): ?>
                                    <span class="product-desc"><?php echo htmlspecialchars(substr($order['description'], 0, 50)); ?>...</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td>$<?php echo number_format($order['price'], 2); ?></td>
                        <td><?php echo date('M j, Y g:i a', strtotime($order['order_date'])); ?></td>
                        <td class="status-<?php echo strtolower($order['order_status'] ?? 'pending'); ?>">
                            <?php echo ucfirst($order['order_status'] ?? 'pending'); ?>
                        </td>
                        <td>
                            <?php if (!empty($order['tracking_number'])): ?>
                                <button class="tracking-btn" onclick="showTracking('<?php echo htmlspecialchars($order['tracking_number']); ?>')">
                                    View Tracking
                                </button>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="total-amount">
                <p>Total amount spent: 
                    <span class="amount-value">$<?php echo number_format($stats['total'], 2); ?></span>
                </p>
                <?php if ($stats['savings'] > 0): ?>
                <p>Total savings: 
                    <span class="amount-value">$<?php echo number_format($stats['savings'], 2); ?></span>
                </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showTracking(trackingNumber) {
            // Create a more attractive tracking info display
            const carrier = detectCarrier(trackingNumber);
            const carrierName = getCarrierName(carrier);
            const trackingUrl = getTrackingUrl(carrier, trackingNumber);
            
            const message = `Tracking #: ${trackingNumber}\nCarrier: ${carrierName}`;
            
            if (confirm(`${message}\n\nWould you like to track this package?`)) {
                if (trackingUrl) {
                    window.open(trackingUrl, '_blank');
                } else {
                    alert('Tracking is not available for this carrier.');
                }
            }
        }
        
        function detectCarrier(trackingNumber) {
            // Improved carrier detection
            if (/^1Z[0-9A-Z]{16}$/i.test(trackingNumber)) return 'ups';
            if (/^\d{12}$/.test(trackingNumber)) return 'fedex';
            if (/^[A-Z]{2}\d{9}[A-Z]{2}$/i.test(trackingNumber)) return 'usps';
            if (/^\d{15,22}$/.test(trackingNumber)) return 'usps';
            return 'unknown';
        }
        
        function getCarrierName(carrier) {
            const names = {
                'ups': 'UPS',
                'fedex': 'FedEx',
                'usps': 'USPS',
                'unknown': 'Unknown Carrier'
            };
            return names[carrier] || carrier;
        }
        
        function getTrackingUrl(carrier, trackingNumber) {
            const urls = {
                'ups': `https://www.ups.com/track?loc=en_US&tracknum=${trackingNumber}`,
                'fedex': `https://www.fedex.com/fedextrack/?trknbr=${trackingNumber}`,
                'usps': `https://tools.usps.com/go/TrackConfirmAction?tLabels=${trackingNumber}`
            };
            return urls[carrier] || null;
        }
    </script>
</body>
</html>