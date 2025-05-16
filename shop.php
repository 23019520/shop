<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get products from database
$products = $pdo->query("SELECT * FROM products WHERE stock > 0")->fetchAll();

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
<html>
<head>
    <title>Shop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .products { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
            margin-top: 20px;
        }
        .product { 
            border: 1px solid #ddd; 
            padding: 15px; 
            width: 250px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .product h4 {
            margin-top: 0;
            color: #2c3e50;
        }
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            color: #4CAF50;
            text-decoration: none;
            margin-right: 15px;
            font-weight: bold;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        .success {
            color: green;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }
        .error {
            color: red;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
        }
        button[type="submit"] {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background: #3e8e41;
        }
    </style>
</head>
<body>
    <div class="nav-links">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <a href="order_tracking.php">Track Orders</a> | 
        <a href="cart.php">View Cart (<?php 
            // FIXED: Added table name to the query
            $cartCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'");
            $cartCount->execute([$_SESSION['user_id']]);
            echo $cartCount->fetchColumn();
        ?>)</a> | 
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
            <a href="admin_dashboard.php">Admin Panel</a> |
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>
    
    <?php if (isset($message)): ?>
        <p class="success"><?php echo $message; ?></p>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <h3>Available Products</h3>
    <div class="products">
        <?php foreach ($products as $product): ?>
        <div class="product">
            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
            <p><?php echo htmlspecialchars($product['description']); ?></p>
            <p>Price: $<?php echo number_format($product['price'], 2); ?></p>
            <p>In Stock: <?php echo $product['stock']; ?></p>
            <form method="post">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <button type="submit" name="add_to_cart">Add to Cart</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>