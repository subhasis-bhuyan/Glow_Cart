<?php
/**
 * GlowCart Cosmetics - Administrator Settings & Security
 */
require_once __DIR__ . '/auth_check.php';

$admin_page_title = 'Admin Settings & Security | GlowCart Admin';
$admin_header_title = 'Account & Security';
$active_tab = 'settings';

$admin_id = (int)$_SESSION['admin_id'];
$errors = [];
$success_msg = '';

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $new_username = clean_input($_POST['username'] ?? '');
    $new_email = clean_input($_POST['email'] ?? '');

    if (empty($new_username) || empty($new_email)) {
        $errors[] = "Username and email cannot be empty.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    } else {
        try {
            // Check if email already taken by another admin
            $chk = $pdo->prepare("SELECT id FROM admins WHERE email = :email AND id != :id");
            $chk->execute([':email' => $new_email, ':id' => $admin_id]);
            if ($chk->fetch()) {
                $errors[] = "This email is already in use by another admin account.";
            } else {
                $upd = $pdo->prepare("UPDATE admins SET username = :user, email = :email WHERE id = :id");
                $upd->execute([':user' => $new_username, ':email' => $new_email, ':id' => $admin_id]);

                $_SESSION['admin_username'] = $new_username;
                $_SESSION['admin_email'] = $new_email;
                $_SESSION['flash_success'] = "Admin profile information updated successfully.";
                header('Location: settings.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Password Change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $errors[] = "All password fields are required.";
    } elseif (strlen($new_pass) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    } elseif ($new_pass !== $confirm_pass) {
        $errors[] = "New password and confirmation do not match.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $admin_id]);
            $admin_hash = $stmt->fetchColumn();

            if ($admin_hash && password_verify($current_pass, $admin_hash)) {
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("UPDATE admins SET password = :pass WHERE id = :id");
                $upd->execute([':pass' => $new_hash, ':id' => $admin_id]);

                $_SESSION['flash_success'] = "Administrator password was changed successfully!";
                header('Location: settings.php');
                exit;
            } else {
                $errors[] = "Current password is incorrect.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch current admin info
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $admin_id]);
$admin_profile = $stmt->fetch();

$db_driver = $pdo ? $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) : 'Unknown';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Account & Security</h1>
        <p style="color: var(--admin-muted); font-size: 13px; margin-top: 4px;">Update administrator profile details and change password credentials.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-danger">
        <div>
            <strong>Error:</strong>
            <ul style="margin-left: 20px; margin-top: 4px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="order-details-grid">
    <!-- Profile & Password Forms -->
    <div>
        <!-- Profile Form -->
        <div class="admin-summary-card">
            <div class="admin-summary-title">
                <span>👤 Administrator Profile</span>
            </div>

            <form action="settings.php" method="POST">
                <input type="hidden" name="action" value="update_profile">

                <div class="admin-form-group">
                    <label for="username">Admin Display Name</label>
                    <input type="text" id="username" name="username" class="admin-form-control" value="<?= htmlspecialchars($admin_profile['username'] ?? '') ?>" required>
                </div>

                <div class="admin-form-group">
                    <label for="email">Admin Email Address</label>
                    <input type="email" id="email" name="email" class="admin-form-control" value="<?= htmlspecialchars($admin_profile['email'] ?? '') ?>" required>
                </div>

                <button type="submit" class="admin-btn admin-btn-primary">
                    Update Profile
                </button>
            </form>
        </div>

        <!-- Password Change Form -->
        <div class="admin-summary-card">
            <div class="admin-summary-title">
                <span>🔒 Change Admin Password</span>
            </div>

            <form action="settings.php" method="POST">
                <input type="hidden" name="action" value="change_password">

                <div class="admin-form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="admin-form-control" placeholder="••••••••" required>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="new_password">New Password (min 6 chars)</label>
                        <input type="password" id="new_password" name="new_password" class="admin-form-control" placeholder="••••••••" required minlength="6">
                    </div>

                    <div class="admin-form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="admin-form-control" placeholder="••••••••" required minlength="6">
                    </div>
                </div>

                <button type="submit" class="admin-btn admin-btn-primary">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <!-- System Diagnostics Card -->
    <div>
        <div class="admin-summary-card">
            <div class="admin-summary-title">
                <span>⚙️ System Diagnostics</span>
            </div>

            <ul class="info-list">
                <li>
                    <span class="label">Admin ID</span>
                    <span class="value">#<?= (int)$admin_profile['id'] ?></span>
                </li>
                <li>
                    <span class="label">Database Engine</span>
                    <span class="value"><span class="admin-badge admin-badge-info"><?= strtoupper($db_driver) ?></span></span>
                </li>
                <li>
                    <span class="label">PHP Version</span>
                    <span class="value"><?= PHP_VERSION ?></span>
                </li>
                <li>
                    <span class="label">Account Created</span>
                    <span class="value"><?= date('M d, Y', strtotime($admin_profile['created_at'] ?? 'now')) ?></span>
                </li>
                <li>
                    <span class="label">Session Status</span>
                    <span class="value"><span class="admin-badge admin-badge-active">Active</span></span>
                </li>
            </ul>

            <div style="margin-top: 20px; padding: 12px; background: #f8fafc; border-radius: 6px; border: 1px solid var(--admin-border); font-size: 12px; color: var(--admin-muted); line-height: 1.5;">
                ℹ️ GlowCart uses native PHP 8.2 password hashing (Bcrypt) and parameterized PDO statements across all queries.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
