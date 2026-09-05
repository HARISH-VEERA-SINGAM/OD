<?php
// admin_register.php
session_start();
require_once 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic Validation
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Process Registration if validation passes
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username OR email = :email LIMIT 1");
        $stmt->execute(['username' => $username, 'email' => $email]);
        
        if ($stmt->fetch()) {
            $errors[] = "Username or Email already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $pdo->prepare(
                "INSERT INTO admins (username, email, password) VALUES (:username, :email, :password)"
            );

            $created = $insertStmt->execute([
                'username' => $username,
                'email'    => $email,
                'password' => $hashedPassword
            ]);

            if ($created) {
                $success = "Admin account registered successfully! You can now log in.";
                $username = $email = '';
                // Flag to clear local storage via JS upon success
                $clearStorage = true;
            } else {
                $errors[] = "An unexpected error occurred. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛡️ Admin Registration</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-sm-5">
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="display-6 mb-2">🛡️✨</div>
                        <h2 class="fw-bold text-dark mb-1">Admin Sign Up</h2>
                        <p class="text-muted small">Create an administrative account</p>
                    </div>

                    <!-- Alerts -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <div class="d-flex align-items-center mb-1">
                                <span class="me-2 fs-5">⚠️</span>
                                <strong>Please resolve the following:</strong>
                            </div>
                            <ul class="mb-0 ps-3 small">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center" role="alert">
                            <span class="me-2 fs-5">🎉</span>
                            <div class="small flex-grow-1">
                                <?= htmlspecialchars($success) ?>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Registration Form -->
                    <form action="admin_register.php" method="POST" autocomplete="off" id="registerForm">
                        
                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold small text-secondary">
                                👤 Username
                            </label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg fs-6" 
                                id="username" 
                                name="username" 
                                placeholder="e.g. admin_alex"
                                value="<?= htmlspecialchars($username ?? '') ?>" 
                                required
                            >
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-secondary">
                                ✉️ Email Address
                            </label>
                            <input 
                                type="email" 
                                class="form-control form-control-lg fs-6" 
                                id="email" 
                                name="email" 
                                placeholder="name@company.com"
                                value="<?= htmlspecialchars($email ?? '') ?>" 
                                required
                            >
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold small text-secondary">
                                🔒 Password
                            </label>
                            <input 
                                type="password" 
                                class="form-control form-control-lg fs-6" 
                                id="password" 
                                name="password" 
                                placeholder="Min. 8 characters"
                                minlength="8" 
                                required
                            >
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-semibold small text-secondary">
                                🔑 Confirm Password
                            </label>
                            <input 
                                type="password" 
                                class="form-control form-control-lg fs-6" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Repeat password"
                                minlength="8" 
                                required
                            >
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold fs-6 py-2 shadow-sm">
                            🚀 Create Admin Account
                        </button>
                    </form>

                    <!-- Footer Link -->
                    <div class="text-center mt-4 pt-2 border-top">
                        <p class="text-muted small mb-0">
                            Already have an account? <a href="index.php" class="text-decoration-none fw-semibold">Log in here 🚪</a>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Local Storage API Integration Script -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const usernameInput = document.getElementById("username");
        const emailInput = document.getElementById("email");
        const registerForm = document.getElementById("registerForm");

        <?php if (!empty($clearStorage)): ?>
            // Clear local storage upon successful registration
            localStorage.removeItem("admin_reg_username");
            localStorage.removeItem("admin_reg_email");
        <?php else: ?>
            // Restore values from localStorage if they exist and fields are empty
            if (!usernameInput.value && localStorage.getItem("admin_reg_username")) {
                usernameInput.value = localStorage.getItem("admin_reg_username");
            }
            if (!emailInput.value && localStorage.getItem("admin_reg_email")) {
                emailInput.value = localStorage.getItem("admin_reg_email");
            }
        <?php endif; ?>

        // Save values to localStorage on input change
        usernameInput.addEventListener("input", function () {
            localStorage.setItem("admin_reg_username", usernameInput.value);
        });

        emailInput.addEventListener("input", function () {
            localStorage.setItem("admin_reg_email", emailInput.value);
        });
    });
</script>
</body>
</html>
