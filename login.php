<?php
require_once 'includes/functions.php';
require_once 'includes/password_reset.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } elseif (isOM()) {
        redirect('faculty/dashboard.php');
    }
}

$login_error = '';
$reset_error = '';
$reset_success = '';
$show_forgot = isset($_GET['forgot']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = sanitizeInput(strtolower($_POST['email']));
        $password = $_POST['password']; // Don't sanitize password to allow special characters
        
        if (empty($email) || empty($password)) {
            $login_error = 'Please enter both email and password.';
        } else {
            $db = getDB();
            
            // Check in admins table first
            $stmt = $db->prepare("SELECT id, username, email, password FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && verifyPassword($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['username'] = $admin['username'];
                $_SESSION['email'] = $admin['email'];
                redirect('admin/dashboard.php');
            } else {
                // Check in faculties table
                $stmt = $db->prepare("SELECT id, name, email, password, college FROM faculties WHERE email = ? AND is_active = TRUE");
                $stmt->execute([$email]);
                $faculty = $stmt->fetch();
                
                if ($faculty && verifyPassword($password, $faculty['password'])) {
                    $_SESSION['user_id'] = $faculty['id'];
                    $_SESSION['user_type'] = 'faculty';
                    $_SESSION['username'] = $faculty['name'];
                    $_SESSION['email'] = $faculty['email'];
                    $_SESSION['college'] = $faculty['college'];
                    redirect('faculty/dashboard.php');
                } else {
                    $login_error = 'Invalid email or password.';
                }
            }
        }
    } elseif (isset($_POST['reset'])) {
        $reset_email = sanitizeInput(strtolower($_POST['reset_email']));
        
        if (empty($reset_email)) {
            $reset_error = 'Please enter your email address.';
        } else {
            $passwordReset = new PasswordReset();
            
            if ($passwordReset->checkEmailExists($reset_email)) {
                if ($passwordReset->sendResetEmail($reset_email)) {
                    $reset_success = 'Password reset link has been sent to your email address.';
                } else {
                    $reset_error = 'Failed to send reset email. Please try again later.';
                }
            } else {
                // Security: don't reveal if email exists
                $reset_success = 'If your email is registered, you will receive a password reset link shortly.';
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
    <title>QuickMark - Login</title>
    
    <link rel="icon" type="image/x-icon" href="download.png">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0B1C3A">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-navy) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="none"/><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></svg>');
            animation: float 20s linear infinite;
        }

        @keyframes float {
            from { transform: translateY(0); }
            to { transform: translateY(-100px); }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            z-index: 1;
        }

        .logo-box {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-box img {
            width: 90px;
            height: 90px;
            border-radius: 18px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-login {
            background: linear-gradient(45deg, var(--primary-navy), var(--primary-gold));
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: scale(1.02);
            color: white;
        }

        .reset-form { display: none; }
        .reset-form.show { display: block; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-box">
            <img src="download.png" alt="Logo">
            <h2 class="mt-3 fw-bold text-navy">QuickMark</h2>
            <p class="text-muted">Smart Attendance Automation</p>
        </div>

        <?php if ($login_error): ?>
            <div class="alert alert-danger py-2"><?php echo $login_error; ?></div>
        <?php endif; ?>

        <?php if ($reset_success): ?>
            <div class="alert alert-success py-2"><?php echo $reset_success; ?></div>
        <?php endif; ?>

        <?php if ($reset_error): ?>
            <div class="alert alert-danger py-2"><?php echo $reset_error; ?></div>
        <?php endif; ?>

        <!-- Login Section -->
        <div id="loginSection" class="<?php echo $show_forgot ? 'reset-form' : ''; ?>">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-navy fw-semibold small">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control bg-light border-0" placeholder="name@example.com" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-navy fw-semibold small">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control bg-light border-0" placeholder="••••••••" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-login mb-3">Login to Dashboard</button>
                <div class="text-center">
                    <a href="?forgot=1" class="text-navy text-decoration-none small fw-bold" id="toForgot">Forgot Password?</a>
                </div>
            </form>
        </div>

        <!-- Forgot Section -->
        <div id="forgotSection" class="reset-form <?php echo $show_forgot ? 'show' : ''; ?>">
            <p class="text-muted small mb-4">Enter your email and we'll send you a link to reset your password.</p>
            <form method="POST">
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" name="reset_email" class="form-control bg-light border-0" placeholder="Your account email" required>
                    </div>
                </div>
                <button type="submit" name="reset" class="btn btn-login mb-3">Send Reset Link</button>
                <div class="text-center">
                    <a href="?" class="text-navy text-decoration-none small fw-bold" id="toLogin">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('toForgot').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('loginSection').classList.add('reset-form');
            document.getElementById('forgotSection').classList.add('show');
        });
        document.getElementById('toLogin').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('forgotSection').classList.remove('show');
            document.getElementById('loginSection').classList.remove('reset-form');
        });
    </script>
</body>
</html> 