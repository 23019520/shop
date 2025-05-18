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
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Checkout</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap');
    :root{
      --bg:#111;--card:#1a1a1a;--accent:#d6a56a;--accent-light:#efcfa6;--text:#ececec;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:"Inter",sans-serif;background:var(--bg);color:var(--text);line-height:1.4}
    a{color:inherit;text-decoration:none}
    /* ---------- HEADER ----------*/
    header{width:100%;padding:1rem 3rem;display:flex;align-items:center;justify-content:space-between;background:#0003;backdrop-filter:blur(6px);position:sticky;top:0;z-index:50;border-bottom:1px solid #ffffff0d}
    .brand{display:flex;align-items:center;gap:.75rem;font-size:1.4rem;font-weight:600}
    .brand img{width:32px;filter:invert(1)}
    nav{display:flex;align-items:center;gap:1.5rem;font-weight:500}
    nav img{width:18px;filter:invert(1);margin-right:.35rem}
    nav a{display:flex;align-items:center;gap:.35rem;opacity:.8;transition:.25s}
    nav a:hover{opacity:1;color:var(--accent)}
    .cart-count{background:#e74c3c;color:#fff;border-radius:50%;width:20px;height:20px;font-size:.75rem;display:grid;place-items:center}
    /* ---------- CONTAINER ---------- */
    .container{max-width:800px;margin:2rem auto;padding:0 6vw}
    h1,h2,h3{font-family:"Playfair Display",serif;color:var(--accent-light);margin-bottom:1.5rem}
    h1{font-size:2rem;border-bottom:1px solid var(--accent);padding-bottom:.5rem}
    h2{font-size:1.5rem}
    h3{font-size:1.25rem}
    /* ---------- NAVIGATION ---------- */
    .nav-links{display:flex;gap:1.5rem;margin-bottom:2rem;font-weight:500}
    .nav-links a{display:flex;align-items:center;gap:.35rem;opacity:.8;transition:.25s}
    .nav-links a:hover{opacity:1;color:var(--accent)}
    /* ---------- TABLE ---------- */
    table{width:100%;border-collapse:collapse;margin:1.5rem 0}
    th,td{padding:1rem;border-bottom:1px solid #ffffff0d;text-align:left}
    th{background:#0003;color:var(--accent-light);font-weight:600}
    tr:hover{background:#ffffff08}
    .total-row{font-weight:600;background:#0002}
    /* ---------- FORM ---------- */
    form{margin-top:2rem;background:var(--card);padding:1.5rem;border-radius:8px;border:1px solid #ffffff0d}
    label{display:block;margin-bottom:.5rem;opacity:.8}
    input{width:100%;padding:.75rem;margin-bottom:1rem;background:#0002;border:1px solid #ffffff1a;border-radius:4px;color:var(--text);transition:.25s}
    input:focus{outline:none;border-color:var(--accent);background:#0004}
    button{width:100%;padding:.75rem;background:var(--accent);border:none;border-radius:4px;font-weight:600;cursor:pointer;transition:.3s;margin-top:1rem}
    button:hover{background:var(--accent-light);color:#000}
    /* ---------- ERROR ---------- */
    .error{padding:.75rem;background:#2a1a1a;border-radius:4px;color:#e74c3c;margin-bottom:1.5rem}
    /* ---------- FOOTER ---------- */
    footer{padding:2rem 0;text-align:center;font-size:.8rem;opacity:.6;margin-top:3rem}
/* ... (keep all your existing CSS above) ... */

/* ---------- IMAGE STYLING ---------- */
/* Header & Nav Icons */
header img,
nav img,
.nav-links img {
    width: 18px;
    height: 18px;
    filter: invert(1);
    opacity: 0.8;
    transition: opacity 0.25s ease;
}

header img:hover,
nav img:hover,
.nav-links img:hover {
    opacity: 1;
}

/* Brand Logo */
.brand img {
    width: 32px;
    height: 32px;
    filter: invert(1);
    opacity: 1; /* Logo should always be fully visible */
}

/* Product Images in Tables */
table img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 12px;
    vertical-align: middle;
}

/* Form Icons (if any) */
form img {
    width: 20px;
    height: 20px;
    filter: invert(1);
    margin-right: 8px;
    vertical-align: middle;
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

/* Footer Icons */
footer img {
    width: 16px;
    height: 16px;
    filter: invert(1);
    opacity: 0.6;
    margin: 0 4px;
    vertical-align: middle;
}

/* Responsive Images */
img {
    max-width: 100%;
    height: auto;
    display: inline-block;
}
</style>
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
  </div><nav>
    <a href="shop.php"><img src="https://www.freeiconspng.com/uploads/retail-store-icon-24.png" alt="shop">Shop</a>
    <a href="cart.php"><img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" alt="cart">Cart</a>
    <a href="logout.php"><img src="https://cdn-icons-png.flaticon.com/512/126/126467.png" alt="logout">Logout</a>
  </nav>
</header>

<div class="container">
    <h1>Checkout</h1>
    
    <div class="nav-links">
        <a href="cart.php"><img src="https://cdn-icons-png.flaticon.com/512/507/507205.png" alt="back">Back to Cart</a>
        <a href="shop.php"><img src="https://www.freeiconspng.com/uploads/retail-store-icon-24.png" alt="shop">Continue Shopping</a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (empty($cartItems)): ?>
        <p style="text-align:center;opacity:.7;padding:3rem 0">Your cart is empty.</p>
    <?php else: ?>
        <h2>Order Summary</h2>
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
            </tbody>
        </table>
        
        <h2>Payment Information</h2>
        <form method="post">
            <!-- Simple payment form - in a real app, use a payment processor like Stripe -->
            <div>
                <label>Card Number</label>
                <input type="text" placeholder="1234 5678 9012 3456" required>
            </div>
            <div>
                <label>Expiration Date</label>
                <input type="text" placeholder="MM/YY" required>
            </div>
            <div>
                <label>CVV</label>
                <input type="text" placeholder="123" required>
            </div>
            
            <button type="submit" name="process_payment">Process Payment</button>
        </form>
    <?php endif; ?>
</div>

<footer>© 2025 GrocerX. All rights reserved.</footer>
</body>
</html>