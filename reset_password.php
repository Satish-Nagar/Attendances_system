<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/password_reset.php';

// If user is already logged in, send them to the right dashboard.
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    }
    if (isOM()) {
        redirect('faculty/dashboard.php');
    }
}

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$error = '';
$success = '';

if ($token === '') {
    $error = 'Invalid or missing reset token.';
} else {
    $passwordReset = new PasswordReset();
    $tokenData = $passwordReset->validateToken($token);
    if (!$tokenData) {
        $error = 'This reset link is invalid, expired, or already used.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($newPassword === '' || $confirmPassword === '') {
        $error = 'Please enter and confirm your new password.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $passwordReset = new PasswordReset();
        if ($passwordReset->updatePassword($token, $newPassword)) {
            $success = 'Your password has been updated. You can now log in.';
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - QuickMark</title>

    <link rel="icon" type="image/x-icon" href="download.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-navy) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card-box {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 460px;
        }
        .btn-primary-wide {
            background: linear-gradient(45deg, var(--primary-navy), var(--primary-gold));
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
        }
        .logo {
            width: 72px;
            height: 72px;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="card-box">
        <div class="text-center mb-4">
            <img class="logo" src="download.png" alt="Logo">
            <h3 class="mt-3 fw-bold text-navy mb-1">QuickMark</h3>
            <p class="text-muted mb-0">Reset your password</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success py-2"><?php echo htmlspecialchars($success); ?></div>
            <a href="login.php" class="btn btn-primary-wide mt-2">Go to Login</a>
        <?php else: ?>
            <form method="POST" class="mt-3">
                <div class="mb-3">
                    <label class="form-label text-navy fw-semibold small">New Password</label>
                    <input type="password" name="new_password" class="form-control bg-light border-0" placeholder="Enter new password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-navy fw-semibold small">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control bg-light border-0" placeholder="Confirm new password" required>
                </div>
                <button type="submit" class="btn btn-primary-wide">Update Password</button>
                <div class="text-center mt-3">
                    <a href="login.php" class="text-navy text-decoration-none small fw-bold">Back to Login</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

