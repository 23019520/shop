<?php
session_start();
require 'db.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Get stats for dashboard
$orders = $pdo->query("SELECT COUNT(*) FROM order_history")->fetchColumn();
$customers = $pdo->query("SELECT COUNT(*) FROM people")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(price * quantity) FROM order_history WHERE delivery_status = 'delivered'")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        .dashboard-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            flex: 1;
            text-align: center;
        }
        .card h3 {
            margin-top: 0;
        }
        .admin-menu {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Admin Dashboard</h1>
    
    <div class="admin-menu">
        <a href="admin_dashboard.php">Dashboard</a> |
        <a href="manage_products.php">Manage Products</a> |
        <a href="manage_orders.php">Manage Orders</a> |
        <a href="manage_users.php">Manage Users</a> |
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="dashboard-cards">
        <div class="card">
            <h3>Total Orders</h3>
            <p><?php echo $orders; ?></p>
        </div>
        <div class="card">
            <h3>Total Customers</h3>
            <p><?php echo $customers; ?></p>
        </div>
        <div class="card">
            <h3>Total Revenue</h3>
            <p>$<?php echo number_format($revenue, 2); ?></p>
        </div>
    </div>
    
    <h2>Recent Orders</h2>
    <?php
    $recentOrders = $pdo->query("SELECT * FROM order_history ORDER BY processed_date DESC LIMIT 5")->fetchAll();
    if ($recentOrders): ?>
        <table border="1">
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <?php foreach ($recentOrders as $order): 
                $customer = $pdo->prepare("SELECT username FROM people WHERE id = ?");
                $customer->execute([$order['user_id']]);
                $customerName = $customer->fetchColumn();
            ?>
            <tr>
                <td><?php echo $order['history_id']; ?></td>
                <td><?php echo htmlspecialchars($customerName); ?></td>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                <td><?php echo ucfirst($order['delivery_status']); ?></td>
                <td><?php echo date('M j, Y', strtotime($order['processed_date'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>
</body>
</html>