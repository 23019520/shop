<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

// Get selected category (if any)
$selected_category = $_GET['category'] ?? null;

// Build product query
$query = "SELECT p.*, c.category_name 
          FROM products p
          JOIN categories c ON p.category_id = c.category_id
          WHERE p.stock > 0";

$params = [];

if ($selected_category && $selected_category != 'all') {
    $query .= " AND c.category_name = ?";
    $params[] = $selected_category;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $product->execute([$product_id]);
    $product = $product->fetch();
    
    if ($product) {
        try {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, product_name, price, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $_SESSION['user_id'],
                $product['product_id'],
                $product['name'],
                $product['price']
            ]);
            $message = "Product added to cart!";
        } catch (PDOException $e) {
            $error = "Failed to add product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Shop</title>
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
    /* ---------- HERO  ----------*/
    .hero{position:relative;height:55vh;display:flex;align-items:center;padding:3rem 6vw;background:url('https://images.pexels.com/photos/10000648/pexels-photo-10000648.jpeg?auto=compress&cs=tinysrgb&w=1200') center/cover no-repeat;border-bottom:1px solid #ffffff0d}
    .hero::after{content:"";position:absolute;inset:0;background:#0008}
    .hero-content{position:relative;max-width:500px}
    .hero h1{font-family:"Playfair Display",serif;font-size:3rem;margin-bottom:1rem;color:var(--accent-light)}
    .hero p{margin-bottom:2rem;font-size:1rem}
    .btn{padding:.75rem 1.5rem;background:var(--accent);border:none;border-radius:4px;font-weight:600;cursor:pointer;transition:.3s}
    .btn:hover{background:var(--accent-light);color:#000}
    /* ---------- FILTER BAR ----------*/
    .filters{display:flex;flex-wrap:wrap;gap:.75rem;padding:2rem 6vw;background:#0002}
    .filter-btn{display:flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border:1px solid #ffffff1a;border-radius:20px;font-size:.875rem;cursor:pointer;transition:.25s;opacity:.8}
    .filter-btn img{width:16px;filter:invert(1)}
    .filter-btn.active,.filter-btn:hover{background:var(--accent);opacity:1;color:#000}
    /* ---------- PRODUCT GRID ----------*/
    .products{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.5rem;padding:3rem 6vw}
    .product{background:var(--card);border-radius:10px;overflow:hidden;transition:.3s;display:flex;flex-direction:column}
    .product:hover{transform:translateY(-6px)}
    .product img{width:100%;height:170px;object-fit:cover}
    .product-info{padding:1rem;flex:1;display:flex;flex-direction:column}
    .product-info h4{font-family:"Playfair Display",serif;font-weight:600;font-size:1.1rem;margin-bottom:.5rem}
    .meta{font-size:.75rem;letter-spacing:.5px;opacity:.7;margin-bottom:.75rem}
    .price{color:var(--accent-light);font-weight:600;margin-top:auto;font-size:1.1rem}
    .add-cart{margin-top:1rem;background:var(--accent);border:none;width:100%;padding:.6rem 0;border-radius:4px;font-weight:600;cursor:pointer;transition:.3s}
    .add-cart:hover{background:var(--accent-light);color:#000}
    /* ---------- FOOTER ----------*/
    footer{padding:2rem 0;text-align:center;font-size:.8rem;opacity:.6}
    /* ---------- MESSAGES ----------*/
    .success{color:#4CAF50;padding:10px;background:#e8f5e9;border-radius:4px;margin-bottom:20px}
    .error{color:#e74c3c;padding:10px;background:#ffebee;border-radius:4px;margin-bottom:20px}
    /* ---------- CONTAINER ----------*/
    .container{max-width:1200px;margin:0 auto}
    .page-title{font-family:"Playfair Display",serif;font-size:2rem;margin-bottom:1.5rem;color:var(--accent-light)}
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
  </div> <nav>
    <a href="order_tracking.php"><img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="orders">Track Orders</a>
    <a href="cart.php"><img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" alt="cart">View Cart <span class="cart-count"><?php 
        $cartCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'");
        $cartCount->execute([$_SESSION['user_id']]);
        echo $cartCount->fetchColumn();
    ?></span></a>
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
      <a href="admin_dashboard.php"><img src="https://cdn-icons-png.flaticon.com/512/157/157933.png" alt="admin">Admin Panel</a>
    <?php endif; ?>
    <a href="logout.php"><img src="https://cdn-icons-png.flaticon.com/512/126/126467.png" alt="logout">Logout</a>
  </nav>
</header>

<div class="container">
    <?php if (isset($message)): ?>
        <p class="success"><?php echo $message; ?></p>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <h1 class="page-title">Our Products</h1>
    
    <div class="filters">
        <a href="?category=all" class="filter-btn <?php echo (!$selected_category || $selected_category == 'all') ? 'active' : ''; ?>">
            All Products
        </a>
        <?php foreach ($categories as $category): ?>
            <a href="?category=<?php echo urlencode($category['category_name']); ?>" 
               class="filter-btn <?php echo ($selected_category == $category['category_name']) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category['category_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($products)): ?>
        <p>No products found in this category.</p>
    <?php else: ?>
        <div class="products">
            <?php foreach ($products as $product): ?>
            <div class="product">
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php endif; ?>
                <div class="product-info">
                    <span class="meta"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="price">$<?php echo number_format($product['price'], 2); ?></div>
                    <div class="meta">In Stock: <?php echo $product['stock']; ?></div>
                </div>
                <form method="post">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <button type="submit" name="add_to_cart" class="add-cart">Add to Cart</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer>© 2025 GrocerX. All rights reserved.</footer>
</body>
</html>