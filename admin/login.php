<?php
/**
 * GlowCart Cosmetics - Admin Login
 */
require_once __DIR__ . '/../config/db.php';

// If already logged in as admin, redirect to dashboard
if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both admin email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];

                $_SESSION['admin_flash_success'] = "Welcome back, {$admin['username']}!";
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid administrative credentials.';
            }
        } catch (PDOException $e) {
            $error = 'Database connection error.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | GlowCart Cosmetics</title>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👑</text></svg>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body style="background: #1e1e2d; min-height: 100vh; display: flex; align-items: center; justify-content: center;">

    <div class="admin-auth-box">
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="font-size: 36px; margin-bottom: 6px;">👑</div>
            <h2 style="font-size: 22px; color: var(--admin-text); margin-bottom: 4px;">GlowCart Admin Portal</h2>
            <p style="font-size: 13px; color: var(--admin-muted);">Enter your administrator credentials</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px 14px; border-radius: 6px; margin-bottom: 18px; font-size: 13px;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="admin-form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" class="admin-form-control" placeholder="admin@glowcart.com" value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>

            <div class="admin-form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="admin-form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
                Sign In to Dashboard &rarr;
            </button>
        </form>

        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--admin-border); text-align: center; font-size: 12px; color: var(--admin-muted);">
            Default Credentials:<br>
            <strong>Email:</strong> <code>admin@glowcart.com</code> | <strong>Pass:</strong> <code>admin123</code>
        </div>

        <div style="text-align: center; margin-top: 15px;">
            <a href="../index.php" style="font-size: 12px; color: var(--admin-primary);">&larr; Back to Main Website</a>
        </div>
    </div>

</body>
</html>
