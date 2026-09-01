<?php
/**
 * GlowCart Cosmetics - Customer Registration (Sign Up)
 */
$page_title = 'Create an Account | GlowCart Cosmetics';
require_once __DIR__ . '/config/db.php';

// If already logged in, redirect to home
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Full Name is required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Please enter a valid 10-digit phone number.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Duplicate email check
    if (empty($errors)) {
        try {
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $check_stmt->execute([':email' => $email]);
            if ($check_stmt->fetch()) {
                $errors[] = 'An account with this email already exists. Please log in.';
            } else {
                // Hash password securely
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                $insert_stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, password, created_at)
                    VALUES (:name, :email, :phone, :password, CURRENT_TIMESTAMP)
                ");
                $insert_stmt->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':phone'    => $phone,
                    ':password' => $hashed_password
                ]);

                $_SESSION['flash_success'] = 'Account created successfully! Please log in to continue.';
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: Could not complete registration. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <span style="font-size: 36px; display: block; margin-bottom: 8px;">💄</span>
            <h2>Join GlowCart</h2>
            <p>Create your customer account to enjoy seamless beauty shopping.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div style="background: var(--danger-bg); color: var(--danger); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid rgba(198,40,40,0.2); font-size: 13px;">
                <ul style="margin-left: 18px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="name" class="form-label">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Subhasis Nayak" value="<?= htmlspecialchars($name) ?>" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">Phone Number (10 Digits) *</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="9876543210" pattern="[0-9]{10}" value="<?= htmlspecialchars($phone) ?>" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password (Min. 8 characters) *</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" minlength="8" required>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" minlength="8" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
                Create Account &rarr;
            </button>
        </form>

        <div style="text-align: center; margin-top: 25px; font-size: 14px;">
            Already have an account? <a href="login.php" style="font-weight: 600;">Log In</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
