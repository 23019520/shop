<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's cart items
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    try {
        $pdo->beginTransaction();
        
        // Update status of cart items to 'completed'
        $updateStmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE user_id = ? AND status = 'pending'");
        $updateStmt->execute([$_SESSION['user_id']]);
        
        // Optionally move to order_history
        foreach ($cartItems as $item) {
            $historyStmt = $pdo->prepare("INSERT INTO order_history 
                (order_id, user_id, product_id, product_name, quantity, price, order_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
            $historyStmt->execute([
                $item['order_id'],
                $item['user_id'],
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['price'],
                $item['order_date']
            ]);
        }
        
        $pdo->commit();
        header("Location: order_confirmation.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Payment processing failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .total-row { font-weight: bold; }
    </style>
</head>
<body>
    <h2>Checkout</h2>
    <a href="cart.php">Back to Cart</a> | <a href="shop.php">Continue Shopping</a>
    
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <?php if (empty($cartItems)): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <h3>Order Summary</h3>
        <table>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
            <?php foreach ($cartItems as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="3">Total</td>
                <td>$<?php echo number_format($total, 2); ?></td>
            </tr>
        </table>
        
        <h3>Payment Information</h3>
        <form method="post">
            <!-- Simple payment form - in a real app, use a payment processor like Stripe -->
            <div>
                <label>Card Number:</label>
                <input type="text" placeholder="1234 5678 9012 3456" required>
            </div>
            <div>
                <label>Expiration Date:</label>
                <input type="text" placeholder="MM/YY" required>
            </div>
            <div>
                <label>CVV:</label>
                <input type="text" placeholder="123" required>
            </div>
            
            <button type="submit" name="process_payment">Process Payment</button>
        </form>
    <?php endif; ?>
</body>
</html>