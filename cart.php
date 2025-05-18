<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($orders as $order) {
    $total += $order['price'] * $order['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Your Cart</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap');
    :root{
      --bg:#111;--card:#1a1a1a;--accent:#d6a56a;--accent-light:#efcfa6;--text:#ececec;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:"Inter",sans-serif;background:var(--bg);color:var(--text);line-height:1.4}
    a{color:inherit;text-decoration:none}
    
    /* ---------- HEADER ---------- */
    header{width:100%;padding:1rem 3rem;display:flex;align-items:center;justify-content:space-between;background:#0003;backdrop-filter:blur(6px);position:sticky;top:0;z-index:50;border-bottom:1px solid #ffffff0d}
    .brand{display:flex;align-items:center;gap:.75rem;font-size:1.4rem;font-weight:600}
    .brand img{width:32px;filter:invert(1)}
    nav{display:flex;align-items:center;gap:1.5rem;font-weight:500}
    nav img{width:18px;filter:invert(1);margin-right:.35rem}
    nav a{display:flex;align-items:center;gap:.35rem;opacity:.8;transition:.25s}
    nav a:hover{opacity:1;color:var(--accent)}
    .cart-count{background:#e74c3c;color:#fff;border-radius:50%;width:20px;height:20px;font-size:.75rem;display:grid;place-items:center}
    
    /* ---------- MAIN CONTENT ---------- */
    .container{max-width:1200px;margin:2rem auto;padding:0 6vw}
    h1,h2{font-family:"Playfair Display",serif;color:var(--accent-light)}
    h1{font-size:2rem;margin-bottom:1.5rem;border-bottom:1px solid var(--accent);padding-bottom:.5rem}
    h2{font-size:1.5rem;margin-bottom:1rem}
    
    /* ---------- NAVIGATION LINKS ---------- */
    .nav-links{display:flex;gap:1.5rem;margin-bottom:2rem}
    .nav-links a{display:flex;align-items:center;gap:.35rem;opacity:.8;transition:.25s}
    .nav-links a:hover{opacity:1;color:var(--accent)}
    .nav-links img{width:18px;filter:invert(1)}
    
    /* ---------- CART TABLE ---------- */
    table{width:100%;border-collapse:collapse;margin:1.5rem 0}
    th,td{padding:1rem;text-align:left;border-bottom:1px solid #ffffff0d}
    th{background:#0003;color:var(--accent-light);font-weight:600}
    tr:hover{background:#ffffff08}
    .total-row{font-weight:600;background:#0002}
    
    /* ---------- CHECKOUT BUTTON ---------- */
    .checkout-btn{display:inline-block;margin-top:1.5rem;padding:.75rem 1.5rem;background:var(--accent);color:#000;font-weight:600;border-radius:4px;transition:.3s}
    .checkout-btn:hover{background:var(--accent-light);transform:translateY(-2px)}
    
    /* ---------- EMPTY CART MESSAGE ---------- */
    .empty-cart{text-align:center;padding:3rem 0;opacity:.7}

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
<!-- HEADER -->
 
<header>
    <div class="brand">
    <img src="logo.png" alt="logo">
    <div class="brand-text">
      <span class="brand-name">LasLas24</span>
      <span class="brand-slogan">Home of Premium Chicken</span>
    </div>
  </div>
  <nav>
    <a href="shop.php"><img src="https://www.freeiconspng.com/uploads/retail-store-icon-24.png" alt="shop">Shop</a>
    <a href="order_tracking.php"><img src="https://cdn-icons-png.flaticon.com/512/10049/10049066.png" alt="orders">Orders</a>
    <a href="logout.php"><img src="https://cdn-icons-png.flaticon.com/512/126/126467.png" alt="logout">Logout</a>
  </nav>
</header>

<div class="container">
    <h1>Your Cart</h1>
    
    <div class="nav-links">
        <a href="order_tracking.php"><img src="https://cdn-icons-png.flaticon.com/512/10049/10049066.png" alt="track">Track Orders</a>
        <a href="shop.php"><img src="https://www.freeiconspng.com/uploads/retail-store-icon-24.png" alt="shop">Continue Shopping</a>
    </div>
    
    <?php if (empty($orders)): ?>
        <p class="empty-cart">Your cart is empty.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                    <td>$<?php echo number_format($order['price'], 2); ?></td>
                    <td><?php echo $order['quantity']; ?></td>
                    <td>$<?php echo number_format($order['price'] * $order['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
    <?php endif; ?>
</div>
</body>
</html>