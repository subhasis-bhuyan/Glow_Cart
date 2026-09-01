<?php
/**
 * GlowCart Cosmetics - Administrator Login Portal
 */
$admin_page_title = 'Administrator Login | GlowCart Cosmetics';
require_once __DIR__ . '/../config/db.php';

// If already authenticated as admin, redirect to admin dashboard
if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

// Check for redirect flash error
if (isset($_SESSION['admin_flash_error'])) {
    $error = $_SESSION['admin_flash_error'];
    unset($_SESSION['admin_flash_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your administrator email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);

                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];

                $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($admin['username']) . "! You are logged into the Admin Panel.";
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid administrator credentials. Please check your email and password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($admin_page_title) ?></title>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💄</text></svg>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body style="background: linear-gradient(135deg, #1e1e2d 0%, #11111b 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

    <div class="admin-auth-box" style="margin: 0; width: 100%;">
        <div class="admin-auth-header">
            <div style="font-size: 38px; margin-bottom: 8px;">💄</div>
            <h2 style="font-size: 22px; font-weight: 700; color: #1e1e2d;">GLOWCART <span style="color: var(--admin-primary);">ADMIN</span></h2>
            <p>Authorized access only. Enter administrative credentials.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="admin-alert admin-alert-danger">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="admin-alert admin-alert-success">
                <span>✅</span>
                <span><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <div class="admin-form-group">
                <label for="adminEmail">Admin Email</label>
                <input type="email" id="adminEmail" name="email" class="admin-form-control" placeholder="admin@glowcart.com" value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>

            <div class="admin-form-group">
                <label for="adminPassword">Admin Password</label>
                <input type="password" id="adminPassword" name="password" class="admin-form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 14px; margin-top: 10px;">
                Secure Admin Log In &rarr;
            </button>
        </form>

        <!-- Demo Credentials Card with 1-click Auto-fill -->
        <div style="margin-top: 25px; padding: 15px; background: #f8fafc; border: 1px dashed var(--admin-border); border-radius: 8px; font-size: 12px;">
            <div style="font-weight: 600; color: var(--admin-text); margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
                <span>🔑 Demo Admin Account:</span>
                <button type="button" id="fillDemoBtn" style="background: none; border: none; color: var(--admin-primary); cursor: pointer; font-weight: 600; font-size: 12px; text-decoration: underline;">
                    Auto-Fill
                </button>
            </div>
            <div style="color: var(--admin-muted); line-height: 1.6;">
                <strong>Email:</strong> <code>admin@glowcart.com</code><br>
                <strong>Password:</strong> <code>admin123</code>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 13px;">
            <a href="../index.php" style="color: var(--admin-muted); text-decoration: none;">&larr; Back to Storefront</a>
        </div>
    </div>

<script>
document.getElementById('fillDemoBtn')?.addEventListener('click', () => {
    document.getElementById('adminEmail').value = 'admin@glowcart.com';
    document.getElementById('adminPassword').value = 'admin123';
});
</script>
</body>
</html>
