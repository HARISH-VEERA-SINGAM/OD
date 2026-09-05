<?php
session_start();
require_once __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!empty($email) && !empty($password)) {
        // Query user record safely using prepared statements
        $stmt = $pdo->prepare('SELECT id, email, password FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verify hash created by password_hash()
        if ($user && password_verify($password, $user['password'])) {
            // Prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];

            if ($remember) {
                // Secure persistent cookie (30 days)
                setcookie('remember_user', $user['email'], [
                    'expires'  => time() + (86400 * 30),
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => true,     // Set to false only if testing locally without HTTPS
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: radial-gradient(circle at top right, #1e293b, #0f172a);
      color: #f8fafc;
      padding: 1.5rem;
    }

    .login-card {
      width: 100%;
      max-width: 420px;
      background: rgba(30, 41, 59, 0.7);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 2.5rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }

    .header {
      text-align: center;
      margin-bottom: 1.75rem;
    }

    .header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      letter-spacing: -0.5px;
      margin-bottom: 0.5rem;
    }

    .header p {
      color: #94a3b8;
      font-size: 0.9rem;
    }

    .alert-error {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #fca5a5;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-group {
      margin-bottom: 1.25rem;
    }

    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 500;
      margin-bottom: 0.5rem;
      color: #cbd5e1;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-wrapper i {
      position: absolute;
      left: 1rem;
      color: #64748b;
      font-size: 1.2rem;
      pointer-events: none;
    }

    .input-wrapper input {
      width: 100%;
      padding: 0.8rem 1rem 0.8rem 2.8rem;
      background: #0f172a;
      border: 1px solid #334155;
      border-radius: 8px;
      color: #f8fafc;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      outline: none;
    }

    .input-wrapper input:focus {
      border-color: #6366f1;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    .toggle-password {
      position: absolute;
      right: 1rem;
      cursor: pointer;
      color: #64748b;
      font-size: 1.2rem;
      transition: color 0.2s;
    }

    .toggle-password:hover {
      color: #cbd5e1;
    }

    .actions-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #94a3b8;
      cursor: pointer;
    }

    .remember-me input {
      accent-color: #6366f1;
      cursor: pointer;
    }

    .forgot-link {
      color: #818cf8;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }

    .forgot-link:hover {
      color: #a5b4fc;
      text-decoration: underline;
    }

    .btn-submit {
      width: 100%;
      padding: 0.85rem;
      background: #6366f1;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s;
    }

    .btn-submit:hover {
      background: #4f46e5;
    }

    .btn-submit:active {
      transform: scale(0.99);
    }

    .footer-text {
      text-align: center;
      margin-top: 1.75rem;
      font-size: 0.875rem;
      color: #94a3b8;
    }

    .footer-text a {
      color: #818cf8;
      font-weight: 500;
      text-decoration: none;
    }

    .footer-text a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="header">
      <h1>Welcome Back</h1>
      <p>Enter your credentials to access your account</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-error">
        <i class='bx bx-error-circle'></i>
        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" id="loginForm">
      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-wrapper">
          <i class='bx bx-envelope'></i>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="name@example.com" 
            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>" 
            required
          >
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <i class='bx bx-lock-alt'></i>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
          <i class='bx bx-show toggle-password' id="togglePassword"></i>
        </div>
      </div>

      <div class="actions-row">
        <label class="remember-me">
          <input type="checkbox" name="remember" value="1" <?= isset($_POST['remember']) ? 'checked' : '' ?>> Remember me
        </label>
        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
      </div>

      <button type="submit" class="btn-submit">Sign In</button>
    </form>

    <p class="footer-text">
      Don't have an account? <a href="register.php">Create one</a>
    </p>
  </div>

  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', () => {
      const isPassword = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
      togglePassword.classList.toggle('bx-show');
      togglePassword.classList.toggle('bx-hide');
    });

    // Local Storage API Integration for Sign-In Email
    document.addEventListener("DOMContentLoaded", function () {
        const emailInput = document.getElementById("email");

        // Restore email from localStorage if the input is currently empty
        if (!emailInput.value && localStorage.getItem("signin_email")) {
            emailInput.value = localStorage.getItem("signin_email");
        }

        // Save email to localStorage dynamically as the user types
        emailInput.addEventListener("input", function () {
            localStorage.setItem("signin_email", emailInput.value);
        });
    });
  </script>
</body>
</html>
