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
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Manage Orders | GrocerX Admin</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap');
    :root {
      --bg: #111;
      --card: #1a1a1a;
      --accent: #d6a56a;
      --accent-light: #efcfa6;
      --text: #ececec;
      --border: #ffffff0d;
      --success: #4CAF50;
      --processing: #2196F3;
      --shipped: #673AB7;
      --in-transit: #FF9800;
      --delivered: #4CAF50;
      --cancelled: #e74c3c;
    }
    * { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0;
    }
    body {
      font-family: "Inter", sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      min-height: 100vh;
    }
    a { 
      color: var(--accent);
      text-decoration: none;
      transition: all 0.25s ease;
    }
    a:hover {
      color: var(--accent-light);
    }
    
    /* Header */
    header {
      width: 100%;
      padding: 1rem 3rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(6px);
      position: sticky;
      top: 0;
      z-index: 50;
      border-bottom: 1px solid var(--border);
    }
    .brand {
      display: flex;
      align-items: center;
      gap: .75rem;
      font-size: 1.4rem;
      font-weight: 600;
      color: var(--accent-light);
    }
    .brand img {
      width: 32px;
      filter: invert(1) brightness(0.9) sepia(1) hue-rotate(330deg) saturate(5);
    }
    nav {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }
    nav a {
      display: flex;
      align-items: center;
      gap: .5rem;
      font-weight: 500;
      opacity: 0.9;
    }
    nav a:hover {
      opacity: 1;
    }
    nav img {
      width: 18px;
      filter: invert(1) brightness(0.8);
    }
    
    /* Main Content */
    main {
      padding: 2rem 3rem;
    }
    .admin-menu {
      margin-bottom: 2rem;
    }
    .admin-menu a {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      background: rgba(214, 165, 106, 0.1);
    }
    .admin-menu a:hover {
      background: rgba(214, 165, 106, 0.2);
    }
    .admin-menu img {
      width: 16px;
      filter: invert(1) brightness(0.8);
    }
    
    /* Page Title */
    .page-title {
      font-family: "Playfair Display", serif;
      color: var(--accent-light);
      font-size: 2rem;
      margin-bottom: 1.5rem;
      position: relative;
      padding-bottom: 0.75rem;
    }
    .page-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 80px;
      height: 3px;
      background: var(--accent);
      border-radius: 3px;
    }
    
    /* Messages */
    .message {
      padding: 1rem;
      margin-bottom: 1.5rem;
      border-radius: 4px;
      font-weight: 500;
    }
    .success {
      background: rgba(76, 175, 80, 0.15);
      color: var(--success);
      border-left: 4px solid var(--success);
    }
    .error {
      background: rgba(231, 76, 60, 0.15);
      color: var(--cancelled);
      border-left: 4px solid var(--cancelled);
    }
    
    /* Orders Table */
    .table-container {
      background: var(--card);
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--border);
    }
    .orders-table {
      width: 100%;
      border-collapse: collapse;
    }
    .orders-table th, 
    .orders-table td {
      padding: 1.25rem 1.5rem;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }
    .orders-table th {
      background: rgba(0, 0, 0, 0.2);
      font-weight: 600;
      color: var(--accent-light);
      font-size: 0.9rem;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    .orders-table tr:hover {
      background: rgba(255, 255, 255, 0.03);
    }
    .orders-table td {
      vertical-align: middle;
    }
    
    /* Status Badges */
    .status {
      display: inline-block;
      padding: 0.35rem 1rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
      letter-spacing: 0.3px;
    }
    .status-processing {
      background: rgba(33, 150, 243, 0.15);
      color: var(--processing);
      border: 1px solid rgba(33, 150, 243, 0.3);
    }
    .status-shipped {
      background: rgba(103, 58, 183, 0.15);
      color: var(--shipped);
      border: 1px solid rgba(103, 58, 183, 0.3);
    }
    .status-in_transit {
      background: rgba(255, 152, 0, 0.15);
      color: var(--in-transit);
      border: 1px solid rgba(255, 152, 0, 0.3);
    }
    .status-delivered {
      background: rgba(76, 175, 80, 0.15);
      color: var(--delivered);
      border: 1px solid rgba(76, 175, 80, 0.3);
    }
    .status-cancelled {
      background: rgba(231, 76, 60, 0.15);
      color: var(--cancelled);
      border: 1px solid rgba(231, 76, 60, 0.3);
    }
    
    /* Action Buttons */
    .action-group {
      display: flex;
      gap: 0.75rem;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.6rem 1.25rem;
      border-radius: 4px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      font-family: inherit;
      font-size: 0.9rem;
    }
    .btn-processing {
      background: var(--processing);
      color: white;
    }
    .btn-shipped {
      background: var(--shipped);
      color: white;
    }
    .btn-in_transit {
      background: var(--in-transit);
      color: white;
    }
    .btn-delivered {
      background: var(--delivered);
      color: white;
    }
    .btn-disabled {
      background: #333;
      color: #777;
      cursor: not-allowed;
    }
    .btn:hover:not(.btn-disabled) {
      opacity: 0.9;
      transform: translateY(-2px);
    }
    .details-link {
      color: var(--accent);
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
    }
    .details-link:hover {
      color: var(--accent-light);
      text-decoration: underline;
    }
    .details-link img {
      width: 14px;
      filter: invert(1) brightness(0.8);
    }
    
    /* Footer */
    footer {
      padding: 2rem 0;
      text-align: center;
      font-size: 0.85rem;
      opacity: 0.7;
      border-top: 1px solid var(--border);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      header {
        padding: 1rem;
      }
      main {
        padding: 1.5rem;
      }
      .orders-table {
        display: block;
        overflow-x: auto;
      }
      .action-group {
        flex-direction: column;
      }
    }
    .brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.brand-text {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.brand-name {
  font-size: 1.4rem;
  font-weight: 600;
}

.brand-slogan {
  font-size: 0.9rem;
  opacity: 0.8;
  font-style: italic;
}
  </style>
</head>
<body>
  <!-- Header -->
  <header>
     <div class="brand">
    <img src="logo.png" alt="logo">
    <div class="brand-text">
      <span class="brand-name">LasLas24</span>
      <span class="brand-slogan">Home of Premium Chicken</span>
    </div>
  </div>
    <nav>
      <a href="admin_dashboard.php">
        <img src="https://previews.123rf.com/images/urfandadashov/urfandadashov1808/urfandadashov180823306/108988792-dashboard-images-vector-icon-isolated-on-transparent-background-dashboard-images-logo-concept.jpg" alt="dashboard">
        Dashboard
      </a>
      <a href="logout.php">
        <img src="https://toppng.com/uploads/preview/logout-11551056293ans77of4wy.png" alt="logout">
        Logout
      </a>
    </nav>
  </header>

  <!-- Main Content -->
  <main>
    <div class="admin-menu">
      <a href="admin_dashboard.php">
        <img src="https://www.shutterstock.com/image-vector/left-arrow-icon-vector-illustration-260nw-2222751293.jpg" alt="back">
        Back to Dashboard
      </a>
    </div>

    <h1 class="page-title">Manage Orders</h1>
    
    <?php if (isset($success)): ?>
      <div class="message success">
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
      <div class="message error">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <div class="table-container">
      <table class="orders-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Order Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): 
            $current_status = $order['delivery_status'];
            $next_status = $status_flow[$current_status] ?? 'processing';
            $is_final_status = ($current_status === 'delivered' || $current_status === 'cancelled');
          ?>
          <tr>
            <td>#<?php echo htmlspecialchars($order['history_id']); ?></td>
            <td><?php echo htmlspecialchars($order['username']); ?></td>
            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
            <td><?php echo htmlspecialchars($order['quantity']); ?></td>
            <td>$<?php echo number_format($order['price'], 2); ?></td>
            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
            <td>
              <span class="status status-<?php echo str_replace(' ', '_', strtolower($current_status)); ?>">
                <?php echo ucfirst($current_status); ?>
              </span>
            </td>
            <td>
              <div class="action-group">
                <form method="post">
                  <input type="hidden" name="order_id" value="<?php echo $order['history_id']; ?>">
                  <input type="hidden" name="current_status" value="<?php echo $current_status; ?>">
                  <?php if (!$is_final_status): ?>
                    <button type="submit" name="update_status" 
                      class="btn btn-<?php echo str_replace(' ', '_', strtolower($current_status)); ?>">
                      Mark as <?php echo ucfirst($next_status); ?>
                    </button>
                  <?php else: ?>
                    <button class="btn btn-disabled" disabled>
                      <?php echo ucfirst($current_status); ?>
                    </button>
                  <?php endif; ?>
                </form>
                <a href="full_order_history.php?id=<?php echo $order['history_id']; ?>" class="details-link">
                  <img src="https://cdn-icons-png.flaticon.com/512/709/709612.png" alt="details">
                  Details
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <footer>
    © 2025 GrocerX. All rights reserved.
  </footer>
</body>
</html>