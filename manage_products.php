<?php
session_start();
require 'db.php';

// Admin check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock]);
    } elseif (isset($_POST['update_product'])) {
        $id = $_POST['product_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ? WHERE product_id = ?");
        $stmt->execute([$name, $description, $price, $stock, $id]);
    } elseif (isset($_POST['delete_product'])) {
        $id = $_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
    }
}

// Get all products
$products = $pdo->query("SELECT * FROM products")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Manage Products</h1>
    <div class="admin-menu">
        <a href="admin_dashboard.php">← Back to Dashboard</a>
    </div>
    
    <h2>Add New Product</h2>
    <form method="post">
        <div class="form-group">
            <label>Product Name:</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Description:</label>
            <textarea name="description"></textarea>
        </div>
        <div class="form-group">
            <label>Price:</label>
            <input type="number" step="0.01" name="price" required>
        </div>
        <div class="form-group">
            <label>Stock:</label>
            <input type="number" name="stock" required>
        </div>
        <button type="submit" name="add_product">Add Product</button>
    </form>
    
    <h2>Product List</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?php echo $product['product_id']; ?></td>
            <td><?php echo htmlspecialchars($product['name']); ?></td>
            <td><?php echo htmlspecialchars($product['description']); ?></td>
            <td>$<?php echo number_format($product['price'], 2); ?></td>
            <td><?php echo $product['stock']; ?></td>
            <td>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    <textarea name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required>
                    <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>
                    <button type="submit" name="update_product">Update</button>
                    <button type="submit" name="delete_product" onclick="return confirm('Are you sure?')">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>