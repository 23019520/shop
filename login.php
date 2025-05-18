<?php
session_start();
require 'db.php';

$error = null; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM people WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin']; 
        
        if ($user['is_admin']) {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: shop.php");
        }
        exit();
    } else {
        $error = "Invalid username or password"; // Set error message
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Login - GrocerX</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap');
    :root {
      --bg: #111;
      --card: #1a1a1a;
      --accent: #d6a56a;
      --accent-light: #efcfa6;
      --text: #ececec;
    }
    * { box-sizing: border-box; margin: 0; padding: 0 }
    body {
      font-family: "Inter", sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    a { color: inherit; text-decoration: none }
    
    /* Header */
    header {
      width: 100%;
      padding: 1rem 3rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #0003;
      backdrop-filter: blur(6px);
      position: sticky;
      top: 0;
      z-index: 50;
      border-bottom: 1px solid #ffffff0d;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: .75rem;
      font-size: 1.4rem;
      font-weight: 600;
    }
    .brand img {
      width: 32px;
      filter: invert(1);
    }
    
    /* Main Content */
    main {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .auth-container {
      background: var(--card);
      padding: 2.5rem;
      border-radius: 10px;
      width: 100%;
      max-width: 450px;
      box-shadow: 0 10px 30px #0005;
    }
    .auth-title {
      font-family: "Playfair Display", serif;
      color: var(--accent-light);
      font-size: 2rem;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    /* Form Styles */
    .auth-form {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    .form-group label {
      font-weight: 500;
      font-size: 0.9rem;
    }
    .form-control {
      background: #222;
      border: 1px solid #333;
      border-radius: 4px;
      padding: 0.8rem 1rem;
      color: var(--text);
      font-family: inherit;
      font-size: 1rem;
      transition: border-color 0.3s;
    }
    .form-control:focus {
      outline: none;
      border-color: var(--accent);
    }
    .btn {
      background: var(--accent);
      color: #000;
      border: none;
      border-radius: 4px;
      padding: 0.8rem;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: 0.3s;
      margin-top: 0.5rem;
    }
    .btn:hover {
      background: var(--accent-light);
    }
    
    /* Messages */
    .error {
      color: #e74c3c;
      padding: 10px;
      background: #ffebee;
      border-radius: 4px;
      margin-bottom: 20px;
      text-align: center;
    }
    .success {
      color: #4CAF50;
      padding: 10px;
      background: #e8f5e9;
      border-radius: 4px;
      margin-bottom: 20px;
      text-align: center;
    }
    
    /* Footer */
    footer {
      padding: 2rem 0;
      text-align: center;
      font-size: .8rem;
      opacity: .6;
    }
    
    /* Links */
    .auth-link {
      text-align: center;
      margin-top: 1.5rem;
      color: var(--accent-light);
    }
    .auth-link a {
      color: var(--accent);
      text-decoration: underline;
      transition: 0.3s;
    }
    .auth-link a:hover {
      color: var(--accent-light);
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
   <div class="brand">
    <img src="logo.png" alt="logo">
    <div class="brand-text">
      <span class="brand-name">LasLas24</span>
      <span class="brand-slogan">Home of Premium Chicken</span>
    </div>
  </div>
  </header>

  <!-- Main Content -->
  <main>
    <div class="auth-container">
      <h1 class="auth-title">Welcome Back</h1>
      
      <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>
      
      <form method="post" class="auth-form">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
        </div>
        
        <button type="submit" class="btn">Login</button>
      </form>
      
      <p class="auth-link">
        Don't have an account? <a href="signup.php">Sign Up</a>
      </p>
    </div>
  </main>

  <footer>
    © 2025 GrocerX. All rights reserved.
  </footer>
</body>
</html>