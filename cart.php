<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($orders as $order) {
    $total += $order['price'] * $order['quantity'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>
    
</head>
<body>
    <h2>Your Cart</h2>
    <a href="order_tracking.php">Track Orders</a>
    <a href="shop.php">Continue Shopping</a> | <a href="logout.php">Logout</a>
    
    <?php if (empty($orders)): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                <td>$<?php echo $order['price']; ?></td>
                <td><?php echo $order['quantity']; ?></td>
                <td>$<?php echo $order['price'] * $order['quantity']; ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td><strong>$<?php echo $total; ?></strong></td>
            </tr>
        </table>
    <?php endif; ?>
    <!-- Add this to your existing cart.php, before the closing </body> tag -->
<?php if (!empty($orders)): ?>
    <div style="margin-top: 20px;">
        <a href="checkout.php" style="padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none;">Proceed to Checkout</a>
    </div>
<?php endif; ?>
</body>
</html>