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
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Order Details - Admin | GrocerX</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap');
    :root {
      --bg: #111;
      --card: #1a1a1a;
      --accent: #d6a56a;
      --accent-light: #efcfa6;
      --text: #ececec;
      --border: #ffffff0d;
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
      margin-bottom: 2rem;
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
    
    /* Order Details Table */
    .order-details {
      width: 100%;
      max-width: 800px;
      background: var(--card);
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--border);
    }
    .order-details tr:not(:last-child) {
      border-bottom: 1px solid var(--border);
    }
    .order-details th, 
    .order-details td {
      padding: 1.25rem 1.5rem;
      text-align: left;
    }
    .order-details th {
      background: rgba(0, 0, 0, 0.2);
      font-weight: 600;
      color: var(--accent-light);
      width: 30%;
      font-size: 0.9rem;
      letter-spacing: 0.5px;
    }
    .order-details td {
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
      background: rgba(30, 144, 255, 0.15);
      color: #1E90FF;
      border: 1px solid rgba(30, 144, 255, 0.3);
    }
    .status-shipped {
      background: rgba(153, 50, 204, 0.15);
      color: #9932CC;
      border: 1px solid rgba(153, 50, 204, 0.3);
    }
    .status-in_transit {
      background: rgba(255, 140, 0, 0.15);
      color: #FF8C00;
      border: 1px solid rgba(255, 140, 0, 0.3);
    }
    .status-delivered {
      background: rgba(76, 175, 80, 0.15);
      color: #4CAF50;
      border: 1px solid rgba(76, 175, 80, 0.3);
    }
    .status-cancelled {
      background: rgba(255, 0, 0, 0.15);
      color: #FF0000;
      border: 1px solid rgba(255, 0, 0, 0.3);
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
        flex-direction: column;
        gap: 1rem;
      }
      nav {
        width: 100%;
        justify-content: space-between;
      }
      main {
        padding: 1.5rem;
      }
      .order-details th, 
      .order-details td {
        padding: 1rem;
        display: block;
        width: 100%;
      }
      .order-details th {
        border-bottom: none;
        padding-bottom: 0.5rem;
      }
      .order-details td {
        padding-top: 0.5rem;
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
      <a href="manage_orders.php">
        <img src="https://www.shutterstock.com/image-vector/left-arrow-icon-vector-illustration-260nw-2222751293.jpg" alt="back">
        Back to Orders
      </a>
    </div>

    <h1 class="page-title">Order Details</h1>

    <table class="order-details">
      <tr>
        <th>Order ID</th>
        <td>#<?php echo htmlspecialchars($order['history_id']); ?></td>
      </tr>
      <tr>
        <th>Customer</th>
        <td>
          <?php echo htmlspecialchars($order['username']); ?><br>
          <small><?php echo htmlspecialchars($order['email']); ?></small>
        </td>
      </tr>
      <tr>
        <th>Product</th>
        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
      </tr>
      <tr>
        <th>Quantity</th>
        <td><?php echo htmlspecialchars($order['quantity']); ?></td>
      </tr>
      <tr>
        <th>Price</th>
        <td>$<?php echo number_format($order['price'], 2); ?></td>
      </tr>
      <tr>
        <th>Order Date</th>
        <td><?php echo date('M j, Y g:i a', strtotime($order['order_date'])); ?></td>
      </tr>
      <tr>
        <th>Processed Date</th>
        <td><?php echo $order['processed_date'] ? date('M j, Y g:i a', strtotime($order['processed_date'])) : 'N/A'; ?></td>
      </tr>
      <tr>
        <th>Delivery Status</th>
        <td>
          <span class="status status-<?php echo str_replace(' ', '_', strtolower($order['delivery_status'])); ?>">
            <?php echo ucfirst($order['delivery_status']); ?>
          </span>
        </td>
      </tr>
    </table>
  </main>

  <footer>
    © 2025 GrocerX. All rights reserved.
  </footer>
</body>
</html>
