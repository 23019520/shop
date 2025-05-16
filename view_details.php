<?php
session_start();
require 'db.php';

// Admin check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Get order ID
if (!isset($_GET['id'])) {
    echo "No order ID provided.";
    exit();
}

$order_id = $_GET['id'];

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, p.username, p.email
    FROM order_history o
    JOIN people p ON o.user_id = p.id
    WHERE o.history_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    echo "Order not found.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .admin-menu {
            margin-bottom: 20px;
        }
        .admin-menu a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }
        h1 {
            color: #333;
        }
        table {
            width: 60%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            text-align: left;
            width: 30%;
        }
        .status-processing { color: #2196F3; }
        .status-shipped { color: #673AB7; }
        .status-in_transit { color: #FF9800; }
        .status-delivered { color: #4CAF50; }
        .status-cancelled { color: #f44336; }
    </style>
</head>
<body>

    <div class="admin-menu">
        <a href="manage_orders.php">← Back to Orders</a>
    </div>

    <h1>Order Details</h1>

    <table>
        <tr>
            <th>Order ID</th>
            <td><?php echo $order['history_id']; ?></td>
        </tr>
        <tr>
            <th>Customer</th>
            <td><?php echo htmlspecialchars($order['username']); ?> (<?php echo htmlspecialchars($order['email']); ?>)</td>
        </tr>
        <tr>
            <th>Product</th>
            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
        </tr>
        <tr>
            <th>Quantity</th>
            <td><?php echo $order['quantity']; ?></td>
        </tr>
        <tr>
            <th>Price</th>
            <td>$<?php echo number_format($order['price'], 2); ?></td>
        </tr>
        <tr>
            <th>Order Date</th>
            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
        </tr>
        <tr>
            <th>Processed Date</th>
            <td><?php echo $order['processed_date'] ? date('M j, Y', strtotime($order['processed_date'])) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Delivery Status</th>
            <td class="status-<?php echo str_replace(' ', '_', strtolower($order['delivery_status'])); ?>">
                <?php echo ucfirst($order['delivery_status']); ?>
            </td>
        </tr>
    </table>

</body>
</html>
