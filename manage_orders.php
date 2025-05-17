<?php
session_start();
require 'db.php';

// Admin check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Define status progression
$status_flow = [
    'processing' => 'shipped',
    'shipped' => 'in transit',
    'in transit' => 'delivered',
    'delivered' => 'delivered' // Final state
];

// Update delivery status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $current_status = $_POST['current_status'];
    
    // Determine next status
    $new_status = $status_flow[$current_status] ?? 'processing';
    
    try {
        $stmt = $pdo->prepare("UPDATE order_history SET delivery_status = ? WHERE history_id = ?");
        $stmt->execute([$new_status, $order_id]);
        $success = "Order status updated to " . ucfirst($new_status) . "!";
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            line-height: 1.6;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
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
        .admin-menu {
            margin-bottom: 20px;
        }
        .admin-menu a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .admin-menu a:hover {
            background-color: rgba(76, 175, 80, 0.1);
            text-decoration: underline;
        }
        .status-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .btn-processing {
            background-color: #2196F3;
            color: white;
        }
        .btn-shipped {
            background-color: #673AB7;
            color: white;
        }
        .btn-in_transit {
            background-color: #FF9800;
            color: white;
        }
        .btn-delivered {
            background-color: #4CAF50;
            color: white;
        }
        .btn-disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
        }
        .success {
            color: green;
            padding: 10px;
            background-color: #ddffdd;
            border-left: 6px solid #04AA6D;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .error {
            color: red;
            padding: 10px;
            background-color: #ffdddd;
            border-left: 6px solid #f44336;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .status-cell {
            font-weight: bold;
        }
        .status-processing { color: #2196F3; }
        .status-shipped { color: #673AB7; }
        .status-in_transit { color: #FF9800; }
        .status-delivered { color: #4CAF50; }
        .status-cancelled { color: #f44336; }
        .view-details {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }
        .view-details:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
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
                <?php foreach ($orders as $order): 
                    $current_status = $order['delivery_status'];
                    $next_status = $status_flow[$current_status] ?? 'processing';
                    $is_final_status = ($current_status === 'delivered' || $current_status === 'cancelled');
                ?>
                <tr>
                    <td><?php echo $order['history_id']; ?></td>
                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                    <td><?php echo $order['quantity']; ?></td>
                    <td>$<?php echo number_format($order['price'], 2); ?></td>
                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                    <td class="status-cell status-<?php echo str_replace(' ', '_', strtolower($current_status)); ?>">
                        <?php echo ucfirst($current_status); ?>
                    </td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="order_id" value="<?php echo $order['history_id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $current_status; ?>">
                            <?php if (!$is_final_status): ?>
                                <button type="submit" name="update_status" 
                                    class="status-btn btn-<?php echo str_replace(' ', '_', strtolower($current_status)); ?>">
                                    Mark as <?php echo ucfirst($next_status); ?>
                                </button>
                            <?php else: ?>
                                <button class="status-btn btn-disabled" disabled>
                                    <?php echo ucfirst($current_status); ?>
                                </button>
                            <?php endif; ?>
                        </form>
                        <a href="order_details.php?id=<?php echo $order['history_id']; ?>" class="view-details">Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>