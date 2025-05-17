<!DOCTYPE html>
<html>
<head>
    <title>Order History</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-card {
            flex: 1;
            min-width: 150px;
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            margin-top: 0;
            color: #4CAF50;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            color: #4CAF50;
            text-decoration: none;
            margin-right: 15px;
            font-weight: bold;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        /* Status colors */
        .status-processing { color: #1E90FF; font-weight: bold; }
        .status-shipped { color: #9932CC; font-weight: bold; }
        .status-delivered { color: #4CAF50; font-weight: bold; }
        .status-cancelled { color: #FF0000; font-weight: bold; }
        .tracking-link {
            color: #1E90FF;
            text-decoration: none;
        }
        .tracking-link:hover {
            text-decoration: underline;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
            vertical-align: middle;
        }
        .no-orders {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        .total-amount {
            font-size: 18px;
            text-align: right;
            margin-top: 20px;
            color: #2c3e50;
        }
        .amount-value {
            font-weight: bold;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="shop.php">Continue Shopping</a>
            <a href="cart.php">View Cart</a>
            <a href="logout.php">Logout</a>
        </div>

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
                <p><a href="shop.php">Start shopping now!</a></p>
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