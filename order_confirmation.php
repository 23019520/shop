<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's recent orders
$stmt = $pdo->prepare("SELECT * FROM order_history WHERE user_id = ? ORDER BY processed_date DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
</head>
<body>
    <h2>Thank You for Your Order!</h2>
    <p>Your payment has been processed successfully.</p>
    
    <h3>Order Details</h3>
    <?php if (!empty($orders)): ?>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Date</th>
            </tr>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo $order['history_id']; ?></td>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                <td><?php echo $order['quantity']; ?></td>
                <td>$<?php echo number_format($order['price'], 2); ?></td>
                <td><?php echo date('M j, Y g:i a', strtotime($order['processed_date'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    
    <a href="shop.php">Continue Shopping</a> | 
    <a href="order_history.php">View Full Order History</a>
</body>
</html>