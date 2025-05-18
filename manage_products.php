<?php
session_start();
require 'db.php';

// Admin check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Function to render category dropdown
function renderCategoryDropdown($selected = null) {
    global $pdo;
    $stmt = $pdo->query("SELECT category_id, category_name FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<select name="category_id" class="form-control" required>';
    echo '<option value="">Select Category</option>';
    foreach ($categories as $category) {
        $selectedAttr = ($selected == $category['category_id']) ? 'selected' : '';
        echo "<option value='{$category['category_id']}' $selectedAttr>{$category['category_name']}</option>";
    }
    echo '</select>';
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category_id = $_POST['category_id'];
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $category_id]);
        
    } elseif (isset($_POST['update_product'])) {
        $id = $_POST['product_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category_id = $_POST['category_id'];
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ? WHERE product_id = ?");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $id]);
        
    } elseif (isset($_POST['delete_product'])) {
        $id = $_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
    }
}

// Get all products with their categories
$products = $pdo->query("
    SELECT p.*, c.category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Manage Products | GrocerX Admin</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap');
    :root {
      --bg: #111;
      --card: #1a1a1a;
      --accent: #d6a56a;
      --accent-light: #efcfa6;
      --text: #ececec;
      --border: #ffffff0d;
      --danger: #e74c3c;
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
      margin-bottom: 1.5rem;
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
    
    /* Forms */
    .form-container {
      background: var(--card);
      padding: 2rem;
      border-radius: 10px;
      margin-bottom: 3rem;
      border: 1px solid var(--border);
    }
    .form-title {
      font-family: "Playfair Display", serif;
      color: var(--accent-light);
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
    }
    .form-group {
      margin-bottom: 1.5rem;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--accent-light);
    }
    .form-control {
      width: 100%;
      padding: 0.8rem 1rem;
      background: #222;
      border: 1px solid #333;
      border-radius: 4px;
      color: var(--text);
      font-family: inherit;
      font-size: 1rem;
      transition: border-color 0.3s;
    }
    .form-control:focus {
      outline: none;
      border-color: var(--accent);
    }
    textarea.form-control {
      min-height: 120px;
      resize: vertical;
    }
    select.form-control {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23d6a56a' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      background-size: 16px 12px;
    }
    
    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.8rem 1.5rem;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      font-family: inherit;
      font-size: 1rem;
    }
    .btn-primary {
      background: var(--accent);
      color: #000;
    }
    .btn-primary:hover {
      background: var(--accent-light);
      transform: translateY(-2px);
    }
    .btn-outline {
      background: transparent;
      color: var(--accent);
      border: 1px solid var(--accent);
    }
    .btn-outline:hover {
      background: rgba(214, 165, 106, 0.1);
    }
    .btn-danger {
      background: var(--danger);
      color: white;
    }
    .btn-danger:hover {
      background: #c0392b;
    }
    .btn-sm {
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
    }
    
    /* Product Table */
    .table-container {
      background: var(--card);
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--border);
    }
    .products-table {
      width: 100%;
      border-collapse: collapse;
    }
    .products-table th, 
    .products-table td {
      padding: 1.25rem 1.5rem;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }
    .products-table th {
      background: rgba(0, 0, 0, 0.2);
      font-weight: 600;
      color: var(--accent-light);
      font-size: 0.9rem;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    .products-table tr:hover {
      background: rgba(255, 255, 255, 0.03);
    }
    .products-table td {
      vertical-align: middle;
    }
    .product-actions {
      display: flex;
      gap: 0.5rem;
    }
    
    /* Edit Form */
    .edit-form {
      display: none;
      background: rgba(0, 0, 0, 0.2);
      padding: 1.5rem;
      border-radius: 8px;
      margin-top: 1rem;
      border: 1px solid var(--border);
    }
    .edit-form.active {
      display: block;
    }
    .edit-form .form-group {
      margin-bottom: 1rem;
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
      main {
        padding: 1.5rem;
      }
      .products-table {
        display: block;
        overflow-x: auto;
      }
      .product-actions {
        flex-direction: column;
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
      <a href="admin_dashboard.php">
        <img src="https://www.shutterstock.com/image-vector/left-arrow-icon-vector-illustration-260nw-2222751293.jpg" alt="back">
        Back to Dashboard
      </a>
    </div>

    <h1 class="page-title">Manage Products</h1>
    
    <div class="form-container">
      <h2 class="form-title">Add New Product</h2>
      <form method="post">
        <div class="form-group">
          <label for="name">Product Name</label>
          <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" class="form-control"></textarea>
        </div>
        <div class="form-group">
          <label for="price">Price</label>
          <input type="number" id="price" name="price" step="0.01" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="stock">Stock</label>
          <input type="number" id="stock" name="stock" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="category">Category</label>
          <?php renderCategoryDropdown(); ?>
        </div>
        <button type="submit" name="add_product" class="btn btn-primary">
          <img src="https://cdn-icons-png.flaticon.com/512/3524/3524388.png" width="16" alt="add">
          Add Product
        </button>
      </form>
    </div>
    
    <div class="table-container">
      <table class="products-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Category</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $product): ?>
          <tr>
            <td><?php echo $product['product_id']; ?></td>
            <td><?php echo htmlspecialchars($product['name']); ?></td>
            <td><?php echo htmlspecialchars($product['description']); ?></td>
            <td>$<?php echo number_format($product['price'], 2); ?></td>
            <td><?php echo $product['stock']; ?></td>
            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
            <td>
              <div class="product-actions">
                <button onclick="toggleEditForm(<?php echo $product['product_id']; ?>)" class="btn btn-outline btn-sm">
                  <img src="https://cdn-icons-png.flaticon.com/512/1828/1828271.png" width="14" alt="edit">
                  Edit
                </button>
                <form method="post" style="display: inline;">
                  <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                  <button type="submit" name="delete_product" onclick="return confirm('Are you sure you want to delete this product?')" class="btn btn-danger btn-sm">
                    <img src="https://cdn-icons-png.flaticon.com/512/3405/3405244.png" width="14" alt="delete">
                    Delete
                  </button>
                </form>
              </div>
              
              <div id="edit-form-<?php echo $product['product_id']; ?>" class="edit-form">
                <form method="post">
                  <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                  <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control"><?php echo htmlspecialchars($product['description']); ?></textarea>
                  </div>
                  <div class="form-group">
                    <label>Price</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" value="<?php echo $product['stock']; ?>" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label>Category</label>
                    <?php renderCategoryDropdown($product['category_id']); ?>
                  </div>
                  <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" name="update_product" class="btn btn-primary btn-sm">
                      <img src="https://cdn-icons-png.flaticon.com/512/190/190411.png" width="14" alt="update">
                      Update
                    </button>
                    <button type="button" onclick="toggleEditForm(<?php echo $product['product_id']; ?>)" class="btn btn-outline btn-sm">
                      Cancel
                    </button>
                  </div>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <footer>
    © 2025 GrocerX. All rights reserved.
  </footer>

  <script>
    function toggleEditForm(productId) {
      const form = document.getElementById(`edit-form-${productId}`);
      form.classList.toggle('active');
    }
  </script>
</body>
</html>