<?php
// register.php
session_start();
require_once 'db.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input values
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Basic Validation
    if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
        $message = '⚠️ Please fill in all required fields.';
        $message_type = 'warning';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '❌ Please enter a valid email address.';
        $message_type = 'danger';
    } elseif (strlen($password) < 6) {
        $message = '🔒 Password must be at least 6 characters long.';
        $message_type = 'warning';
    } elseif ($password !== $confirm) {
        $message = '❌ Passwords do not match.';
        $message_type = 'danger';
    } else {
        try {
            // Check if email or username is already taken
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
            $checkStmt->execute([$email, $username]);

            if ($checkStmt->fetch()) {
                $message = '⚠️ Username or Email is already registered.';
                $message_type = 'warning';
            } else {
                // Hash password securely
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user into database
                $insertStmt = $pdo->prepare(
                    "INSERT INTO users (fullname, email, username, password) VALUES (?, ?, ?, ?)"
                );
                $insertStmt->execute([$fullname, $email, $username, $hashed_password]);

                $message = '🎉 Account created successfully! You can now log in.';
                $message_type = 'success';

                // Flag to clear localStorage in JavaScript upon successful registration
                $clearStorage = true;

                // Clear post variables after successful insert
                $_POST = [];
            }
        } catch (PDOException $e) {
            // In production, log $e->getMessage() instead of showing raw error
            $message = '❌ Something went wrong. Please try again later.';
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✨ Create an Account</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0f2fe 0%, #ede9fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        .input-group-text {
            background-color: #f8fafc;
            border-right: none;
            font-size: 1.1rem;
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }
        .input-group:focus-within {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            border-radius: 0.375rem;
        }
        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: #86b7fe;
        }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card register-card bg-white p-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-6 mb-2">🚀</div>
                        <h3 class="fw-bold">Create Account</h3>
                        <p class="text-muted small">Join our community today! 👋</p>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST" autocomplete="off" id="userRegisterForm">
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">👤 Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text">📝</span>
                                <input type="text" id="fullname" name="fullname" class="form-control" placeholder="John Doe" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">📧 Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">✉️</span>
                                <input type="email" id="email" name="email" class="form-control" placeholder="sample@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">🏷️ Username</label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" id="username" name="username" class="form-control" placeholder="johndoe" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">🔑 Password</label>
                            <div class="input-group">
                                <span class="input-group-text">🔒</span>
                                <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">🔁 Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text">🛡️</span>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                Register Now ✨
                            </button>
                        </div>

                        <!-- Login Redirection -->
                        <div class="text-center">
                            <p class="small text-muted mb-0">Already have an account? <a href="index.php" class="text-decoration-none fw-bold">Sign In 🚪</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Local Storage API Script Integration -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const fullnameInput = document.getElementById("fullname");
        const emailInput = document.getElementById("email");
        const usernameInput = document.getElementById("username");

        <?php if (!empty($clearStorage)): ?>
            // Clear local storage entries upon a successful submission
            localStorage.removeItem("user_reg_fullname");
            localStorage.removeItem("user_reg_email");
            localStorage.removeItem("user_reg_username");
        <?php else: ?>
            // Restore values from localStorage if input elements are currently empty
            if (!fullnameInput.value && localStorage.getItem("user_reg_fullname")) {
                fullnameInput.value = localStorage.getItem("user_reg_fullname");
            }
            if (!emailInput.value && localStorage.getItem("user_reg_email")) {
                emailInput.value = localStorage.getItem("user_reg_email");
            }
            if (!usernameInput.value && localStorage.getItem("user_reg_username")) {
                usernameInput.value = localStorage.getItem("user_reg_username");
            }
        <?php endif; ?>

        // Listen for input changes and continuously update local storage
        fullnameInput.addEventListener("input", function () {
            localStorage.setItem("user_reg_fullname", fullnameInput.value);
        });

        emailInput.addEventListener("input", function () {
            localStorage.setItem("user_reg_email", emailInput.value);
        });

        usernameInput.addEventListener("input", function () {
            localStorage.setItem("user_reg_username", usernameInput.value);
        });
    });
</script>
</body>
</html>
