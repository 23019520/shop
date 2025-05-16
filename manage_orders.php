<?php
session_start();
require 'db.php';

// Admin check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Update delivery status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['delivery_status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE order_history SET delivery_status = ? WHERE history_id = ?");
        $stmt->execute([$new_status, $order_id]);
        $success = "Order status updated successfully!";
    } catch (PDOException $e) {
        $error = "Failed to update status: " . $e->getMessage();
    }
}

// Get all orders with customer info
$orders = $pdo->query("
    SELECT o.*, p.username 
    FROM order_history o 
    JOIN people p ON o.user_id = p.id 
    ORDER BY o.processed_date DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        .admin-menu {
            margin-bottom: 20px;
        }
        .admin-menu a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }
        select, button {
            padding: 8px;
            margin-right: 5px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #45a049;
        }
        .success {
            color: green;
            padding: 10px;
            background-color: #ddffdd;
            border-left: 6px solid #04AA6D;
            margin-bottom: 15px;
        }
        .error {
            color: red;
            padding: 10px;
            background-color: #ffdddd;
            border-left: 6px solid #f44336;
            margin-bottom: 15px;
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
        <a href="admin_dashboard.php">← Back to Dashboard</a>
    </div>

    <h1>Manage Orders</h1>
    
    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Order Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo $order['history_id']; ?></td>
                <td><?php echo htmlspecialchars($order['username']); ?></td>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                <td><?php echo $order['quantity']; ?></td>
                <td>$<?php echo number_format($order['price'], 2); ?></td>
                <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                <td class="status-<?php echo str_replace(' ', '_', strtolower($order['delivery_status'])); ?>">
                    <?php
    $statusFlow = ['processing', 'shipped', 'in transit', 'delivered'];
    $currentStatus = strtolower($order['delivery_status']);
    $currentIndex = array_search($currentStatus, $statusFlow);
    $nextStatus = $currentIndex !== false && $currentIndex < count($statusFlow) - 1
                  ? $statusFlow[$currentIndex + 1]
                  : $currentStatus; // Stay at last if already 'delivered'
?>
                    <form method="post">
    <input type="hidden" name="order_id" value="<?php echo $order['history_id']; ?>">
    <input type="hidden" name="delivery_status" value="<?php echo $nextStatus; ?>">
    <span class="status-<?php echo str_replace(' ', '_', $currentStatus); ?>">
        <?php echo ucfirst($currentStatus); ?>
    </span>
    <?php if ($currentStatus !== 'delivered' && $currentStatus !== 'cancelled'): ?>
        <button type="submit" name="update_status">Advance Status</button>
    <?php endif; ?>
</form>
                </td>
                <td>
                    <a href="view_details.php?id=<?php echo $order['history_id']; ?>">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>