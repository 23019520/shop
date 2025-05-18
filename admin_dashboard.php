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
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Admin Dashboard</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap');
    :root{
      --bg:#111;--card:#1a1a1a;--accent:#d6a56a;--accent-light:#efcfa6;--text:#ececec;
      --success:#4CAF50;--warning:#FFC107;--danger:#F44336;
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
    
    /* ---------- MAIN CONTENT ---------- */
    .container{max-width:1400px;margin:2rem auto;padding:0 6vw}
    h1,h2{font-family:"Playfair Display",serif;color:var(--accent-light)}
    h1{font-size:2rem;margin-bottom:1.5rem;border-bottom:1px solid var(--accent);padding-bottom:.5rem}
    h2{font-size:1.5rem;margin:2rem 0 1rem}
    
    /* ---------- ADMIN MENU ---------- */
    .admin-menu{display:flex;gap:1.5rem;padding:1rem;background:#0002;border-radius:8px;margin-bottom:2rem;flex-wrap:wrap}
    .admin-menu a{display:flex;align-items:center;gap:.5rem;padding:.5rem 1rem;border-radius:4px;transition:.25s;opacity:.8}
    .admin-menu a:hover{opacity:1;background:#0004;color:var(--accent)}
    .admin-menu img{width:18px;filter:invert(1)}
    
    /* ---------- DASHBOARD CARDS ---------- */
    .dashboard-cards{display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:1.5rem;margin-bottom:2rem}
    .card{background:var(--card);padding:1.5rem;border-radius:8px;border:1px solid #ffffff0d;transition:.3s}
    .card:hover{transform:translateY(-4px);box-shadow:0 4px 15px #0005}
    .card h3{margin:0 0 .5rem;font-weight:500;font-size:.9rem;opacity:.8}
    .card p{font-size:1.8rem;font-weight:600;margin:0}
    .card.revenue p{color:var(--accent-light)}
    
    /* ---------- ORDERS TABLE ---------- */
    table{width:100%;border-collapse:collapse;margin:1.5rem 0}
    th,td{padding:1rem;text-align:left;border-bottom:1px solid #ffffff0d}
    th{background:#0003;color:var(--accent-light);font-weight:600}
    tr:hover{background:#ffffff08}
    .status{display:inline-block;padding:.25rem .5rem;border-radius:12px;font-size:.75rem;font-weight:600}
    .status.delivered{background:var(--success);color:#000}
    .status.shipped{background:var(--warning);color:#000}
    .status.pending{background:var(--danger);color:#fff}

    /* Brand styling */
.brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-name {
  font-size: 1.4rem;
  font-weight: 600;
}

.brand-slogan {
  font-size: 0.8rem;
  opacity: 0.8;
  font-style: italic;
  margin-top: 2px;
}
    
    /* ---------- FOOTER ---------- */
    footer{padding:2rem 0;text-align:center;font-size:.8rem;opacity:.6;margin-top:3rem}
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
    <a href="logout.php">
      <img src="https://cdn-icons-png.flaticon.com/512/126/126467.png" alt="logout">Logout
    </a>
  </nav>
</header>

<div class="container">
    <h1>Admin Dashboard</h1>
    
    <div class="admin-menu">
        <a href="admin_dashboard.php"><img src="https://cdn-icons-png.flaticon.com/512/1828/1828490.png" alt="dashboard">Dashboard</a>
        <a href="manage_products.php"><img src="https://cdn-icons-png.flaticon.com/512/3075/3075977.png" alt="products">Products</a>
        <a href="manage_orders.php"><img src="https://cdn-icons-png.flaticon.com/512/2917/2917995.png" alt="orders">Orders</a>
        <a href="manage_users.php"><img src="https://cdn-icons-png.flaticon.com/512/1077/1077114.png" alt="users">Users</a>
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
        <div class="card revenue">
            <h3>Total Revenue</h3>
            <p>$<?php echo number_format($revenue, 2); ?></p>
        </div>
    </div>
    
    <h2>Recent Orders</h2>
    <?php
    $recentOrders = $pdo->query("SELECT * FROM order_history ORDER BY processed_date DESC LIMIT 5")->fetchAll();
    if ($recentOrders): ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): 
                    $customer = $pdo->prepare("SELECT username FROM people WHERE id = ?");
                    $customer->execute([$order['user_id']]);
                    $customerName = $customer->fetchColumn();
                    $statusClass = strtolower($order['delivery_status']);
                ?>
                <tr>
                    <td><?php echo $order['history_id']; ?></td>
                    <td><?php echo htmlspecialchars($customerName); ?></td>
                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                    <td><span class="status <?php echo $statusClass; ?>"><?php echo ucfirst($order['delivery_status']); ?></span></td>
                    <td><?php echo date('M j, Y', strtotime($order['processed_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center;opacity:.7;padding:2rem 0">No orders found.</p>
    <?php endif; ?>
</div>

<footer>© 2025 GrocerX. All rights reserved.</footer>
</body>
</html>