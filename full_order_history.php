<?php
/* ---------- 1. Session / DB ---------- */
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* ---------- 2. Pull every order ever ---------- */
$query = "
    SELECT
        oh.history_id,
        oh.order_id,
        MIN(oh.processed_date) AS ordered_at,
        MAX(CASE WHEN oh.delivery_status = 'delivered' THEN oh.processed_date END) AS delivered_at,
        SUM(oh.quantity) AS total_items,
        SUM(oh.price * oh.quantity) AS total_cost,
        GROUP_CONCAT(oh.product_name SEPARATOR ', ') AS products,
        MAX(oh.delivery_status) AS last_known_status
    FROM order_history oh
    WHERE oh.user_id = ?
    GROUP BY oh.order_id
    ORDER BY ordered_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- 3. Quick stats ---------- */
$stats = [
    'total_orders' => count($orders),
    'delivered' => 0,
    'in_flight' => 0,
];

foreach ($orders as $o) {
    if ($o['last_known_status'] === 'delivered') $stats['delivered']++;
    else $stats['in_flight']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Order History</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap');
    :root{
      --bg:#111;--card:#1a1a1a;--accent:#d6a56a;--accent-light:#efcfa6;--text:#ececec;
      --success:#4CAF50;--warning:#FFC107;--danger:#F44336;
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
    .container{max-width:1200px;margin:2rem auto;padding:0 6vw}
    h1{font-family:"Playfair Display",serif;font-size:2rem;margin-bottom:1.5rem;color:var(--accent-light);border-bottom:1px solid var(--accent);padding-bottom:.5rem}
    /* ---------- STATS CARDS ---------- */
    .stats{display:flex;gap:1rem;margin:2rem 0;flex-wrap:wrap}
    .card{flex:1;min-width:150px;background:var(--card);padding:1.5rem;border-radius:8px;border:1px solid #ffffff0d;transition:.3s}
    .card:hover{transform:translateY(-4px);box-shadow:0 4px 15px #0005}
    .card h3{margin:0 0 .5rem;font-weight:500;font-size:.9rem;opacity:.8}
    .val{font-size:1.8rem;font-weight:600}
    .delivered{color:var(--success)}
    .in_flight{color:var(--warning)}
    .pending{color:var(--danger)}
    /* ---------- TABLE ---------- */
    table{width:100%;border-collapse:collapse;margin-top:1.5rem}
    th,td{padding:1rem;border-bottom:1px solid #ffffff0d;text-align:left}
    th{background:#0003;color:var(--accent-light);font-weight:600;position:sticky;top:0;backdrop-filter:blur(6px)}
    tr:hover{background:#ffffff08}
    /* ---------- NAVIGATION ---------- */
    .nav{display:flex;gap:1.5rem;margin-bottom:2rem;font-weight:500}
    .nav a{display:flex;align-items:center;gap:.35rem;opacity:.8;transition:.25s}
    .nav a:hover{opacity:1;color:var(--accent)}
    /* ---------- FOOTER ---------- */
    footer{padding:2rem 0;text-align:center;font-size:.8rem;opacity:.6;margin-top:3rem}

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
  </div><nav>
    <a href="shop.php"><img src="https://www.freeiconspng.com/uploads/retail-store-icon-24.png" alt="shop">Shop</a>
    <a href="cart.php"><img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" alt="cart">Cart</a>
    <a href="logout.php"><img src="https://cdn-icons-png.flaticon.com/512/126/126467.png" alt="logout">Logout</a>
  </nav>
</header>

<div class="container">
    <!-- Navigation -->
    <div class="nav">
        <a href="shop.php">Continue Shopping</a>
        <a href="cart.php">View Cart</a>
        <a href="logout.php">Logout</a>
    </div>

    <h1>Your Full Order History</h1>

    <!-- Quick statistics -->
    <div class="stats">
        <div class="card">
            <h3>Total Orders</h3>
            <div class="val"><?= $stats['total_orders'] ?></div>
        </div>
        <div class="card">
            <h3>Delivered</h3>
            <div class="val delivered"><?= $stats['delivered'] ?></div>
        </div>
        <div class="card">
            <h3>In‑Flight / Other</h3>
            <div class="val in_flight"><?= $stats['in_flight'] ?></div>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <p style="text-align:center;opacity:.7;padding:3rem 0">No orders on record.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Products</th>
                <th>Items</th>
                <th>Total&nbsp;Cost</th>
                <th>Ordered&nbsp;On</th>
                <th>Delivered&nbsp;On</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['order_id']) ?></td>
                <td><?= htmlspecialchars($row['products']) ?></td>
                <td><?= $row['total_items'] ?></td>
                <td>$<?= number_format($row['total_cost'], 2) ?></td>
                <td><?= date('M j, Y g:i a', strtotime($row['ordered_at'])) ?></td>
                <td>
                    <?= $row['delivered_at'] 
                        ? date('M j, Y g:i a', strtotime($row['delivered_at'])) 
                        : '—'; ?>
                </td>
                <td class="<?= strtolower($row['last_known_status']) ?>">
                    <?= ucfirst($row['last_known_status']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<footer>© 2025 GrocerX. All rights reserved.</footer>
</body>
</html>