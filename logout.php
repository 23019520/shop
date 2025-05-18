<?php
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Logging Out | GrocerX</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&display=swap');
    
    :root {
      --bg: #111;
      --accent: #d6a56a;
      --accent-light: #efcfa6;
    }
    
    body {
      margin: 0;
      padding: 0;
      background: var(--bg);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: 'Playfair Display', serif;
      overflow: hidden;
    }
    
    .goodbye-container {
      text-align: center;
    }
    
    .goodbye-message {
      color: var(--accent-light);
      font-size: 4rem;
      font-weight: 600;
      margin-bottom: 1rem;
      opacity: 0;
      transform: translateY(20px);
      animation: fadeIn 0.5s ease-out 0.3s forwards;
    }
    
    .brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      opacity: 0;
      animation: fadeIn 0.5s ease-out 0.6s forwards;
    }
    
    .brand img {
      width: 32px;
      filter: invert(1) brightness(0.9) sepia(1) hue-rotate(330deg) saturate(5);
    }
    
    .brand span {
      color: var(--accent-light);
      font-size: 1.4rem;
      font-weight: 600;
    }
    
    @keyframes fadeIn {
      to {
        opacity: 1;
        transform: translateY(0);
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
  <div class="goodbye-container">
    <div class="goodbye-message">Bye</div>
     <div class="brand">
    <img src="logo.png" alt="logo">
    <div class="brand-text">
      <span class="brand-name">LasLas24</span>
      <span class="brand-slogan">Home of Premium Chicken</span>
    </div>
  </div>
  </div>

  <script>
    // Redirect after 2 seconds
    setTimeout(() => {
      window.location.href = 'login.php';
    }, 3000);
  </script>
</body>
</html>