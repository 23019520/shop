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

// Calculate order total
$orderTotal = array_reduce($orders, function($sum, $order) {
    return $sum + ($order['price'] * $order['quantity']);
}, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Order Confirmation | GrocerX</title>
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
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem 2rem;
      text-align: center;
    }
    .confirmation-container {
      max-width: 800px;
      width: 100%;
      background: var(--card);
      padding: 3rem;
      border-radius: 10px;
      border: 1px solid var(--border);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    /* Confirmation Message */
    .confirmation-icon {
      width: 80px;
      height: 80px;
      margin: 0 auto 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(76, 175, 80, 0.1);
      border-radius: 50%;
      border: 2px solid var(--success);
    }
    .confirmation-icon svg {
      width: 40px;
      height: 40px;
      fill: var(--success);
    }
    .confirmation-title {
      font-family: "Playfair Display", serif;
      color: var(--accent-light);
      font-size: 2.5rem;
      margin-bottom: 1rem;
      letter-spacing: 0.5px;
    }
    .confirmation-message {
      font-size: 1.1rem;
      margin-bottom: 2.5rem;
      opacity: 0.9;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    
    /* Orders Summary */
    .order-summary {
      margin: 2rem 0;
      text-align: left;
    }
    .summary-title {
      font-family: "Playfair Display", serif;
      color: var(--accent-light);
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid var(--border);
    }
    
    /* Orders Table */
    .orders-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1.5rem;
      background: var(--card);
      border-radius: 8px;
      overflow: hidden;
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
    .orders-table tr:last-child td {
      border-bottom: none;
    }
    .orders-table tr:hover {
      background: rgba(255, 255, 255, 0.03);
    }
    .orders-table td:last-child {
      font-weight: 500;
    }
    
    /* Order Total */
    .order-total {
      text-align: right;
      margin-top: 1.5rem;
      font-size: 1.1rem;
    }
    .total-label {
      color: var(--accent-light);
      margin-right: 1rem;
    }
    .total-value {
      font-size: 1.4rem;
      font-weight: 600;
      color: var(--text);
    }
    
    /* Action Links */
    .action-links {
      display: flex;
      justify-content: center;
      gap: 1.5rem;
      margin-top: 3rem;
    }
    .action-link {
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.9rem 2rem;
      border-radius: 6px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .continue-shopping {
      background: var(--accent);
      color: #000;
    }
    .continue-shopping:hover {
      background: var(--accent-light);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(214, 165, 106, 0.3);
    }
    .view-history {
      border: 1px solid var(--accent);
    }
    .view-history:hover {
      background: rgba(214, 165, 106, 0.1);
      transform: translateY(-2px);
    }
    .action-link img {
      width: 18px;
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
      .confirmation-container {
        padding: 2rem 1.5rem;
      }
      .confirmation-title {
        font-size: 2rem;
      }
      .orders-table {
        display: block;
        overflow-x: auto;
      }
      .action-links {
        flex-direction: column;
        gap: 1rem;
      }
      .action-link {
        justify-content: center;
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
      <a href="shop.php">
        <img src="https://www.freeiconspng.com/uploads/retail-store-icon-24.png" alt="shop">
        Shop
      </a>
      <a href="order_history.php">
        <img src="https://www.shutterstock.com/image-vector/order-icon-trendy-design-style-260nw-1417643735.jpg" alt="orders">
        Orders
      </a>
    </nav>
  </header>

  <!-- Main Content -->
  <main>
    <div class="confirmation-container">
      <div class="confirmation-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
        </svg>
      </div>
      <h1 class="confirmation-title">Order Confirmed!</h1>
      <p class="confirmation-message">Thank you for your purchase. Your order has been successfully processed and will be prepared for shipment. A confirmation email has been sent to your registered address.</p>
      
      <div class="order-summary">
        <h2 class="summary-title">Order Summary</h2>
        
        <?php if (!empty($orders)): ?>
          <table class="orders-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
              <tr>
                <td>#<?php echo htmlspecialchars($order['history_id']); ?></td>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                <td>$<?php echo number_format($order['price'], 2); ?></td>
                <td><?php echo date('M j, Y', strtotime($order['processed_date'])); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          
          <div class="order-total">
            <span class="total-label">Total:</span>
            <span class="total-value">$<?php echo number_format($orderTotal, 2); ?></span>
          </div>
        <?php endif; ?>
      </div>
      
      <div class="action-links">
        <a href="shop.php" class="action-link continue-shopping">
          <img src="https://www.freeiconspng.com/uploads/retail-store-icon-24.png" alt="shop">
          Continue Shopping
        </a>
        <a href="order_history.php" class="action-link view-history">
          <img src="https://www.shutterstock.com/image-vector/order-icon-trendy-design-style-260nw-1417643735.jpg" alt="history">
          View Order History
        </a>
      </div>
    </div>
  </main>

  <footer>
    © 2025 GrocerX. All rights reserved.
  </footer>
</body>
</html>