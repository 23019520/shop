<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all orders with product details
$stmt = $pdo->prepare("
    SELECT o.*, p.description, p.image_url 
    FROM order_history o
    JOIN products p ON o.product_id = p.product_id
    WHERE o.user_id = ? 
    ORDER BY o.processed_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Calculate order statistics
$totalSpent = 0;
$orderStats = [
    'processing' => 0,
    'shipped' => 0,
    'in_transit' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

foreach ($orders as $order) {
    $totalSpent += $order['price'] * $order['quantity'];
    if (isset($orderStats[$order['status']])) {
        $orderStats[$order['status']]++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order History - LasLas24</title>
    <style>
        :root {
            --bg-dark: #111;
            --card-bg: #1a1a1a;
            --text-light: #ececec;
            --accent: #d6a56a;
            --accent-light: #efcfa6;
            --border: #333;
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #F44336;
            --info: #1E90FF;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-dark);
            color: var(--text-light);
            line-height: 1.6;
        }
        
        /* ===== HEADER ===== */
        header {
            width: 100%;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(6px);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border);
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .brand img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            filter: invert(1) brightness(0.9) sepia(1) hue-rotate(330deg) saturate(5);
        }
        
        .brand-text {
            display: flex;
            flex-direction: column;
        }
        
        .brand-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--accent-light);
        }
        
        .brand-slogan {
            font-size: 0.85rem;
            font-style: italic;
            color: var(--accent);
            opacity: 0.9;
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-light);
        }
        
        .nav-links img {
            width: 18px;
            height: 18px;
            filter: invert(1) brightness(0.8);
        }
        
        /* ===== MAIN CONTENT ===== */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        h2 {
            color: var(--accent-light);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
            margin: 2rem 0 1.5rem;
            font-family: 'Playfair Display', serif;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--border);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: var(--accent-light);
            font-size: 1rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: bold;
        }
        
        /* Status colors */
        .status-processing { color: var(--info); }
        .status-shipped { color: #9932CC; }
        .status-delivered { color: var(--success); }
        .status-cancelled { color: var(--danger); }
        
        /* ===== TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
        }
        
        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        th {
            background: rgba(0, 0, 0, 0.3);
            color: var(--accent-light);
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
            border: 1px solid var(--border);
        }
        
        .tracking-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .tracking-link:hover {
            color: var(--accent-light);
            text-decoration: underline;
        }
        
        .no-orders {
            text-align: center;
            padding: 3rem;
            color: var(--accent-light);
            background: var(--card-bg);
            border-radius: 8px;
            margin: 2rem 0;
        }
        
        .total-amount {
            font-size: 1.1rem;
            text-align: right;
            margin-top: 1.5rem;
            color: var(--accent-light);
        }
        
        .amount-value {
            font-weight: bold;
            font-size: 1.3rem;
            color: var(--accent);
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }
            
            .nav-links {
                width: 100%;
                justify-content: space-around;
            }
            
            .stats {
                grid-template-columns: 1fr 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header>
        <div class="brand">
            <img src="logo.png" alt="LasLas24 Logo">
            <div class="brand-text">
                <span class="brand-name">LasLas24</span>
                <span class="brand-slogan">Home of Premium Chicken</span>
            </div>
        </div>
        <div class="nav-links">
            <a href="shop.php">
                <img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" alt="Shop">
                Shop
            </a>
            <a href="cart.php">
                <img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" alt="Cart">
                Cart
            </a>
            <a href="order_history.php">
                <img src="https://cdn-icons-png.flaticon.com/512/2917/2917995.png" alt="Orders">
                Orders
            </a>
            <a href="logout.php">
                <img src="https://cdn-icons-png.flaticon.com/512/126/126467.png" alt="Logout">
                Logout
            </a>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container">
        <h2>Your Order History</h2>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="stat-value"><?php echo count($orders); ?></div>
            </div>
            <div class="stat-card">
                <h3>Processing</h3>
                <div class="stat-value status-processing"><?php echo $orderStats['processing']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Shipped</h3>
                <div class="stat-value status-shipped"><?php echo $orderStats['shipped']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Delivered</h3>
                <div class="stat-value status-delivered"><?php echo $orderStats['delivered']; ?></div>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <p>You haven't placed any orders yet.</p>
                <p><a href="shop.php" style="color: var(--accent);">Start shopping now!</a></p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
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
                        <td><?php echo $order['history_id']; ?></td>
                        <td>
                            <?php if (!empty($order['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($order['product_name']); ?>" 
                                     class="product-image">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($order['product_name']); ?>
                        </td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td>$<?php echo number_format($order['price'], 2); ?></td>
                        <td><?php echo date('M j, Y g:i a', strtotime($order['order_date'])); ?></td>
                        <td class="status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </td>
                        <td>
                            <?php if (!empty($order['tracking_number'])): ?>
                                <?php
                                // Determine carrier from tracking number format
                                $carrier = "shipper"; // Default
                                if (preg_match('/^1Z/', $order['tracking_number'])) $carrier = "ups";
                                elseif (preg_match('/^\d{12}$/', $order['tracking_number'])) $carrier = "fedex";
                                elseif (preg_match('/^9[0-9]{15,21}$/', $order['tracking_number'])) $carrier = "usps";
                                
                                $tracking_urls = [
                                    'ups' => "https://www.ups.com/track?loc=en_US&tracknum={$order['tracking_number']}",
                                    'fedex' => "https://www.fedex.com/fedextrack/?trknbr={$order['tracking_number']}",
                                    'usps' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$order['tracking_number']}",
                                    'shipper' => "#"
                                ];
                                ?>
                                <a href="<?php echo $tracking_urls[$carrier]; ?>" 
                                   class="tracking-link" 
                                   target="_blank">
                                    Track Package
                                </a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="total-amount">
                Total amount spent: <span class="amount-value">$<?php echo number_format($totalSpent, 2); ?></span>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>