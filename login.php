<?php
/**
 * GlowCart Cosmetics - Customer Login
 */
$page_title = 'Customer Login | GlowCart Cosmetics';
require_once __DIR__ . '/config/db.php';

// If already logged in, redirect to home
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please provide both your email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_phone'] = $user['phone'];

                $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['name']) . "!";

                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: {$redirect}");
                exit;
            } else {
                $error = 'Invalid email address or password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <span style="font-size: 36px; display: block; margin-bottom: 8px;">✨</span>
            <h2>Welcome Back</h2>
            <p>Log in to access your cart, orders, and saved addresses.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: var(--danger-bg); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid rgba(198,40,40,0.2); font-size: 13px;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="subhasis@example.com" value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; font-size: 13px;">
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="checkbox" name="remember_me" value="1" checked style="accent-color: var(--primary);">
                    <span>Remember Me</span>
                </label>
                <a href="#" style="color: var(--text-muted);">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Log In &rarr;
            </button>
        </form>

        <div style="text-align: center; margin-top: 25px; font-size: 14px;">
            Don't have an account? <a href="signup.php" style="font-weight: 600;">Create Account</a>
        </div>

        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center; font-size: 12px; color: var(--text-muted);">
            Demo Customer Credentials:<br>
            <strong>Email:</strong> <code>subhasis@example.com</code> | <strong>Pass:</strong> <code>password123</code>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
