<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all orders with product details
$stmt = $pdo->prepare("
    SELECT o.*, p.description, p.image_url 
    FROM order_history o
    JOIN products p ON o.product_id = p.product_id
    WHERE o.user_id = ? 
    ORDER BY o.processed_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Calculate order statistics
$totalSpent = 0;
$orderStats = [
    'processing' => 0,
    'shipped' => 0,
    'in_transit' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

foreach ($orders as $order) {
    $totalSpent += $order['price'] * $order['quantity'];
    if (isset($orderStats[$order['status']])) {
        $orderStats[$order['status']]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Order History - LasLas24</title>
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
      display: flex;
      flex-direction: column;
    }
    a { 
      color: var(--accent);
      text-decoration: none;
      transition: all 0.25s ease;
    }
    a:hover {
      color: var(--accent-light);
    }
    
    /* ===== HEADER ===== */
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
      gap: 10px;
    }
    .brand img {
      width: 32px;
      filter: invert(1) brightness(0.9) sepia(1) hue-rotate(330deg) saturate(5);
    }
    .brand-text {
      display: flex;
      flex-direction: column;
    }
    .brand-name {
      font-size: 1.4rem;
      font-weight: 600;
      color: var(--accent-light);
    }
    .brand-slogan {
      font-size: 0.8rem;
      opacity: 0.8;
      font-style: italic;
      margin-top: 2px;
      color: var(--accent);
    }
    nav {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }
    nav a {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 500;
      opacity: 0.9;
      color: var(--text);
      padding: 0.5rem 0;
      border-bottom: 2px solid transparent;
      transition: all 0.3s ease;
    }
    nav a:hover {
      opacity: 1;
      color: var(--accent-light);
      border-bottom-color: var(--accent);
    }
    nav img {
      width: 18px;
      height: 18px;
      filter: invert(1) brightness(0.8);
    }
    
    /* ===== MAIN CONTENT ===== */
    main {
      flex: 1;
      padding: 2rem 0;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }
    .page-title {
      font-family: "Playfair Display", serif;
      color: var(--accent-light);
      font-size: 2.25rem;
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
    
    /* ===== STATS CARDS ===== */
    .stats-container {
      margin-bottom: 2.5rem;
    }
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1.25rem;
    }
    .stat-card {
      background: var(--card);
      padding: 1.5rem;
      border-radius: 10px;
      text-align: center;
      transition: all 0.3s ease;
      border: 1px solid var(--border);
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }
    .stat-card h3 {
      margin-bottom: 0.75rem;
      font-size: 0.95rem;
      font-weight: 500;
      color: var(--accent-light);
      letter-spacing: 0.5px;
    }
    .stat-value {
      font-size: 1.75rem;
      font-weight: 600;
      color: var(--text);
    }
    
    /* ===== TABLE STYLES ===== */
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
    }
    .orders-table th {
      background: rgba(0, 0, 0, 0.2);
      font-weight: 600;
      position: sticky;
      top: 0;
      color: var(--accent-light);
      font-size: 0.9rem;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    .orders-table tr:not(:last-child) {
      border-bottom: 1px solid var(--border);
    }
    .orders-table tr:hover {
      background: rgba(255, 255, 255, 0.03);
    }
    .orders-table td {
      vertical-align: middle;
    }
    
    /* ===== STATUS BADGES ===== */
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
    
    /* ===== PRODUCT IMAGE ===== */
    .product-cell {
      display: flex;
      align-items: center;
      gap: 1.25rem;
    }
    .product-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid var(--border);
    }
    .product-name {
      font-weight: 500;
    }
    
    /* ===== TRACKING LINK ===== */
    .tracking-link {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--accent);
      font-weight: 500;
    }
    .tracking-link:hover {
      color: var(--accent-light);
    }
    .tracking-link img {
      width: 16px;
      filter: invert(1) brightness(0.8) sepia(1) hue-rotate(330deg) saturate(5);
    }
    
    /* ===== EMPTY STATE ===== */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: var(--card);
      border-radius: 10px;
      border: 1px solid var(--border);
    }
    .empty-state p {
      margin-bottom: 1.5rem;
      color: var(--accent-light);
      font-size: 1.1rem;
    }
    .btn {
      display: inline-block;
      padding: 0.75rem 1.75rem;
      background: var(--accent);
      color: #000;
      border-radius: 6px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .btn:hover {
      background: var(--accent-light);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(214, 165, 106, 0.3);
    }
    
    /* ===== TOTAL AMOUNT ===== */
    .total-amount {
      text-align: right;
      margin-top: 2rem;
      font-size: 1.1rem;
      color: var(--accent-light);
    }
    .amount-value {
      color: var(--text);
      font-weight: 600;
      font-size: 1.3rem;
    }
    
    /* ===== FOOTER ===== */
    footer {
      padding: 2rem 0;
      text-align: center;
      font-size: 0.85rem;
      opacity: 0.7;
      border-top: 1px solid var(--border);
    }
    
    /* ===== RESPONSIVE ===== */
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
      .container {
        padding: 0 1rem;
      }
      .stats {
        grid-template-columns: repeat(2, 1fr);
      }
      .orders-table {
        display: block;
        overflow-x: auto;
      }
    }
  </style>
</head>
<body>
  <!-- ===== HEADER ===== -->
  <header>
    <div class="brand">
      <img src="logo.png" alt="logo">
      <div class="brand-text">
        <span class="brand-name">LasLas24</span>
        <span class="brand-slogan">Home of Premium Chicken</span>
      </div>
    </div>
    <nav>
      <a href="shop.php">
        <img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" alt="shop">
        Shop
      </a>
      <a href="cart.php">
        <img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" alt="cart">
        Cart
      </a>
      <a href="order_history.php">
        <img src="https://cdn-icons-png.flaticon.com/512/2917/2917995.png" alt="orders">
        Orders
      </a>
      <a href="logout.php">
        <img src="https://cdn-icons-png.flaticon.com/512/126/126467.png" alt="logout">
        Logout
      </a>
    </nav>
  </header>

  <!-- ===== MAIN CONTENT ===== -->
  <main>
    <div class="container">
      <h1 class="page-title">Your Order History</h1>
      
      <!-- Stats Cards -->
      <div class="stats-container">
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
            <h3>In Transit</h3>
            <div class="stat-value status-in_transit"><?php echo $orderStats['in_transit']; ?></div>
          </div>
          <div class="stat-card">
            <h3>Delivered</h3>
            <div class="stat-value status-delivered"><?php echo $orderStats['delivered']; ?></div>
          </div>
        </div>
      </div>

      <?php if (empty($orders)): ?>
        <div class="empty-state">
          <p>You haven't placed any orders yet.</p>
          <a href="shop.php" class="btn">Start Shopping</a>
        </div>
      <?php else: ?>
        <div class="table-container">
          <table class="orders-table">
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
                  <td>#<?php echo htmlspecialchars($order['history_id']); ?></td>
                  <td>
                    <div class="product-cell">
                      <?php if (!empty($order['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($order['product_name']); ?>" 
                             class="product-image">
                      <?php endif; ?>
                      <span class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></span>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                  <td>$<?php echo number_format($order['price'], 2); ?></td>
                  <td><?php echo date('M j, Y g:i a', strtotime($order['order_date'])); ?></td>
                  <td>
                    <span class="status status-<?php echo $order['status']; ?>">
                      <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($order['tracking_number'])): ?>
                      <?php
                      $carrier = "shipper";
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
                        <img src="https://www.shutterstock.com/image-vector/tracking-icon-trendy-tracking-logo-260nw-1260906598.jpg" alt="track">
                        Track
                      </a>
                    <?php else: ?>
                      N/A
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="total-amount">
          Total amount spent: <span class="amount-value">$<?php echo number_format($totalSpent, 2); ?></span>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- ===== FOOTER ===== -->
  <footer>
    © 2025 LasLas24. All rights reserved.
  </footer>
</body>
</html>