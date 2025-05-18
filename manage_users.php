<?php
session_start();
require 'db.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle user updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE people SET username = ?, email = ?, is_admin = ? WHERE id = ?");
    $stmt->execute([$username, $email, $isAdmin, $userId]);
    
    $_SESSION['message'] = "User updated successfully";
    header("Location: manage_users.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = $_GET['delete'];
    
    // Prevent deleting yourself
    if ($userId != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM people WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['message'] = "User deleted successfully";
    } else {
        $_SESSION['error'] = "You cannot delete yourself";
    }
    
    header("Location: manage_users.php");
    exit();
}

// Get all users
$users = $pdo->query("SELECT * FROM people ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Manage Users - Admin Panel</title>
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
    
    /* ---------- MESSAGES ---------- */
    .message{background:var(--success);color:#000;padding:.75rem 1rem;border-radius:4px;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center}
    .error{background:var(--danger);color:#fff}
    .close-message{cursor:pointer;font-weight:bold}
    
    /* ---------- USERS TABLE ---------- */
    table{width:100%;border-collapse:collapse;margin:1.5rem 0}
    th,td{padding:1rem;text-align:left;border-bottom:1px solid #ffffff0d}
    th{background:#0003;color:var(--accent-light);font-weight:600}
    tr:hover{background:#ffffff08}
    .admin-badge{display:inline-block;padding:.25rem .5rem;border-radius:12px;font-size:.75rem;font-weight:600;background:var(--accent);color:#000}
    
    /* ---------- BUTTONS ---------- */
    .btn{display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;border-radius:4px;cursor:pointer;transition:.25s;font-weight:500;border:none;font-family:inherit}
    .btn-sm{padding:.25rem .5rem;font-size:.85rem}
    .btn-primary{background:var(--accent);color:#000}
    .btn-primary:hover{background:var(--accent-light)}
    .btn-danger{background:var(--danger);color:#fff}
    .btn-danger:hover{background:#ff5c5c}
    .btn-edit{background:var(--warning);color:#000}
    .btn-edit:hover{background:#ffd54f}
    
    /* ---------- MODAL ---------- */
    .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:#000000cc;z-index:100;justify-content:center;align-items:center}
    .modal-content{background:var(--card);padding:2rem;border-radius:8px;width:90%;max-width:500px;border:1px solid #ffffff0d}
    .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem}
    .modal-title{font-family:"Playfair Display",serif;color:var(--accent-light);font-size:1.5rem}
    .close-modal{cursor:pointer;font-size:1.5rem}
    .form-group{margin-bottom:1rem}
    .form-group label{display:block;margin-bottom:.5rem;opacity:.8}
    .form-group input{width:100%;padding:.75rem;background:#ffffff0d;border:1px solid #ffffff0d;border-radius:4px;color:var(--text);font-family:inherit}
    .form-group input:focus{outline:none;border-color:var(--accent)}
    .form-actions{display:flex;justify-content:flex-end;gap:1rem;margin-top:1.5rem}
    
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
    <a href="logout.php"><img src="https://cdn-icons-png.flaticon.com/512/126/126467.png" alt="logout">Logout</a>
  </nav>
</header>

<div class="container">
    <h1>Manage Users</h1>
    
    <div class="admin-menu">
        <a href="admin_dashboard.php"><img src="https://cdn-icons-png.flaticon.com/512/1828/1828490.png" alt="dashboard">Dashboard</a>
        <a href="manage_products.php"><img src="https://cdn-icons-png.flaticon.com/512/3075/3075977.png" alt="products">Products</a>
        <a href="manage_orders.php"><img src="https://cdn-icons-png.flaticon.com/512/2917/2917995.png" alt="orders">Orders</a>
        <a href="manage_users.php" class="active"><img src="https://cdn-icons-png.flaticon.com/512/1077/1077114.png" alt="users">Users</a>
    </div>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message">
            <span><?php echo $_SESSION['message']; ?></span>
            <span class="close-message" onclick="this.parentElement.style.display='none'">×</span>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error">
            <span><?php echo $_SESSION['error']; ?></span>
            <span class="close-message" onclick="this.parentElement.style.display='none'">×</span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Registered</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                <td>
                    <?php if ($user['is_admin'] == 1): ?>
                        <span class="admin-badge">Admin</span>
                    <?php else: ?>
                        User
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-sm btn-edit" onclick="openEditModal(
                        '<?php echo $user['id']; ?>',
                        '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>',
                        '<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>',
                        <?php echo $user['is_admin']; ?>
                    )">Edit</button>
                    
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <a href="manage_users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- EDIT USER MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit User</h3>
            <span class="close-modal" onclick="closeEditModal()">×</span>
        </div>
        <form method="POST" action="manage_users.php">
            <input type="hidden" name="user_id" id="editUserId">
            <div class="form-group">
                <label for="editUsername">Username</label>
                <input type="text" id="editUsername" name="username" required>
            </div>
            <div class="form-group">
                <label for="editEmail">Email</label>
                <input type="email" id="editEmail" name="email" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="editIsAdmin" name="is_admin" value="1"> Admin privileges
                </label>
            </div>
            <div class="form-actions">
                <button type="button" class="btn" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" name="update_user">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<footer>© 2025 GrocerX. All rights reserved.</footer>

<script>
    // Modal functions
    function openEditModal(id, username, email, isAdmin) {
        document.getElementById('editUserId').value = id;
        document.getElementById('editUsername').value = username;
        document.getElementById('editEmail').value = email;
        document.getElementById('editIsAdmin').checked = isAdmin == 1;
        document.getElementById('editModal').style.display = 'flex';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) {
            closeEditModal();
        }
    }
    
    // Auto-close messages after 5 seconds
    setTimeout(() => {
        const messages = document.querySelectorAll('.message');
        messages.forEach(msg => {
            msg.style.display = 'none';
        });
    }, 5000);
</script>
</body>
</html>