<?php
require_once '../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../config/mail.php';

$admin_id = $_SESSION['user_id'];
$db = getDB();
$stmt = $db->prepare("SELECT profile_picture FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$profile_picture = $admin && $admin['profile_picture']
    ? '../uploads/' . htmlspecialchars($admin['profile_picture'])
    : 'https://via.placeholder.com/40x40.png?text=Admin';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'faculty';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Faculty specific
    $designation = trim($_POST['designation'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $college = trim($_POST['college'] ?? '');

    if (!$name || !$email || !$password) {
        $error = 'Name, Email and Password are required.';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email address.';
    } else {
        if ($role === 'admin') {
            $stmt = $db->prepare('SELECT id FROM admins WHERE email = ? OR username = ?');
            $stmt->execute([$email, $name]);
            if ($stmt->fetch()) {
                $error = 'Email or Username already exists for an Admin.';
            } else {
                $hashed_password = hashPassword($password);
                $stmt = $db->prepare('INSERT INTO admins (username, email, password) VALUES (?, ?, ?)');
                $result = $stmt->execute([$name, $email, $hashed_password]);
                if ($result) {
                    $subject = 'Welcome to Smart Attendance System';
                    $login_url = 'http://quickmark.kesug.com/login.php';
                    $body = getWelcomeEmailHTML($name, 'admin', $email, $password, $login_url);
                    $embeddedImages = ['app_logo' => __DIR__ . '/images/download.png'];
                    sendSMTPMail($email, $subject, $body, $embeddedImages);
                    $success = 'Admin registered and email sent!';
                } else {
                    $error = 'Failed to register Admin.';
                }
            }
        } else {
            // Role is faculty
            if (!$designation || !$contact || !$college) {
                $error = 'Designation, Contact, and College are required for Faculties.';
            } else {
                $stmt = $db->prepare('SELECT id FROM faculties WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already exists for a Faculty.';
                } else {
                    $hashed_password = hashPassword($password);
                    $stmt = $db->prepare('INSERT INTO faculties (name, designation, email, contact, college, password) VALUES (?, ?, ?, ?, ?, ?)');
                    $result = $stmt->execute([$name, $designation, $email, $contact, $college, $hashed_password]);
                    if ($result) {
                        $subject = 'Welcome to Smart Attendance System';
                        $login_url = 'http://quickmark.kesug.com/login.php';
                        $body = getWelcomeEmailHTML($name, 'faculty', $email, $password, $login_url);
                        $embeddedImages = ['app_logo' => __DIR__ . '/images/download.png'];
                        sendSMTPMail($email, $subject, $body, $embeddedImages);
                        $success = 'Faculty registered and email sent!';
                    } else {
                        $error = 'Failed to register Faculty.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register Faculty - Smart Attendance System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />

  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .navbar {
      background: linear-gradient(135deg, #0B1C3A 0%, #C89C5D 100%);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1050;
    }

    .sidebar {
      background: white;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
      position: fixed;
      top: 70px;
      left: 0;
      width: 250px;
      height: calc(100vh - 70px);
      padding: 16px 0;
      overflow-y: auto;
      z-index: 1000;
    }

    .sidebar .nav-link {
      color: #333;
      padding: 12px 20px;
      border-radius: 8px;
      margin: 5px 10px;
      transition: all 0.3s ease;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.08rem;
    }

    .sidebar .nav-link i {
      font-size: 1.2rem;
      margin-right: 8px;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
      background: linear-gradient(135deg, #0B1C3A 0%, #C89C5D 100%);
      color: white;
    }

    .main-content {
      margi-right: 40px;
      margin-top: 70px;
      padding: 30px 24px 30px 24px;
      min-height: calc(100vh - 70px);
      background: #f8f9fa;
    }

    .profile-dropdown {
      margin-right: auto;
      padding-left: 60px;
    }

    @media (min-width: 992px) {
      .profile-dropdown {
        margin-right: 10px;
      }
    }

    .offcanvas.offcanvas-end {
      width: 220px !important;
      height: auto !important;
      max-height: 90vh !important;
      top: 20px !important;
      bottom: auto !important;
      border-radius: 8px 0 0 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      background-color: #ffffff;
      z-index: 1049;
    }

    .offcanvas-backdrop.show {
      background-color: rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(1px);
      z-index: 1048;
    }

    .offcanvas-header {
      padding: 12px 16px;
      border-bottom: 1px solid #eee;
    }

    .offcanvas-body {
      padding: 8px 0;
      overflow-y: auto;
    }

    .offcanvas .btn-sidebar {
      padding: 8px 14px;
      font-size: 0.95rem;
      margin: 2px 0;
    }

    .sidebar .btn-sidebar i {
      font-size: 1rem;
    }

    .mobile-sidebar-toggle {
      background: none;
      border: none;
      font-size: 2rem;
      color: #fff;
      position: absolute;
      right: 1px;
      top: 15px;
      z-index: 1051;
      display: none;
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none !important;
      }

      .mobile-sidebar-toggle {
        display: block !important;
      }

      .main-content {
        margin-top: 90px;
        padding: 20px 15px;
      }

      .admin-profile-mobile {
        display: none !important;
      }

      .stat-card {
        margin-bottom: 20px;
      }
    }
  </style>
</head>

<body>
  <!-- Header/Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap">
      <div class="d-flex align-items-center">
        <!-- Hamburger for mobile -->
        <button class="mobile-sidebar-toggle btn btn-link text-white me-2" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#mobileSidebar">
          <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center" href="#">
          <img src="images/download.png" alt="Logo"
            style="height:40px;width:auto;margin-right:10px;border-radius:25%;" />
          <span class="fw-bold text-white">QuickMark</span>
        </a>
      </div>

      <!-- Profile dropdown -->
      <div class="nav-item dropdown d-flex align-items-center profile-dropdown">
        <img src="<?php echo $profile_picture; ?>" alt="Profile" class="rounded-circle me-2"
          style="width:36px;height:36px;object-fit:cover;" />

            <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">
          Admin
        </a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Offcanvas Mobile Sidebar -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">Menu</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
      <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"
        href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
      <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'register_faculty.php' ? 'active' : ''; ?>"
        href="register_faculty.php"><i class="fas fa-user-plus me-2"></i> Register Faculty</a>
      <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'bulk_register.php' ? 'active' : ''; ?>"
        href="bulk_register.php"><i class="fas fa-upload me-2"></i> Bulk Register</a>
      <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'manage_faculties.php' ? 'active' : ''; ?>"
        href="manage_faculties.php"><i class="fas fa-users-cog me-2"></i> Manage Faculties</a>
      <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'attendance_report.php' ? 'active' : ''; ?>"
        href="attendance_report.php"><i class="fas fa-chart-bar me-2"></i> Attendance Report</a>
      <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'assignment_report.php' ? 'active' : ''; ?>"
        href="assignment_report.php"><i class="fas fa-chart-bar me-2"></i> Assignment Report</a>
      <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>"
        href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a>

            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'about.php' ? 'active' : ''; ?>"
        href="about.php"><i class="fas fa-question-circle me-2"></i> About & Help</a>
    </div>
  </div>

  <!-- Desktop Sidebar -->
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-3 col-lg-2 px-0 d-none d-md-block">
        <div class="sidebar">
          <nav class="nav flex-column">
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
              <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link <?php echo $current_page == 'register_faculty.php' ? 'active' : ''; ?>"
              href="register_faculty.php">
              <i class="fas fa-user-plus"></i> Register Faculty
            </a>
            <a class="nav-link <?php echo $current_page == 'bulk_register.php' ? 'active' : ''; ?>"
              href="bulk_register.php">
              <i class="fas fa-upload"></i> Bulk Register
            </a>
            <a class="nav-link <?php echo $current_page == 'manage_faculties.php' ? 'active' : ''; ?>" href="manage_faculties.php">
              <i class="fas fa-users-cog"></i> Manage Faculties
            </a>
            <a class="nav-link <?php echo $current_page == 'attendance_report.php' ? 'active' : ''; ?>"
              href="attendance_report.php">
              <i class="fas fa-chart-bar"></i> Attendance Report
            </a>
            <a class="nav-link <?php echo $current_page == 'assignment_report.php' ? 'active' : ''; ?>"
              href="assignment_report.php">
              <i class="fas fa-chart-bar"></i> Assignment Report
            </a>
            <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
              <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php">
              <i class="fas fa-question-circle"></i> About & Help
            </a>
          </nav>
        </div>
      </div>

      <!-- Main Content -->
      <div class="col-md-9 col-lg-10">
        <div class="main-content">
          <div class="container" style="max-width: 700px">
            <a href="dashboard.php" class="btn btn-secondary btn-sm mb-3">&larr; Back to Dashboard</a>
            <h2>Register User</h2>

            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card mb-4">
              <div class="card-header">Register New User</div>
              <div class="card-body">
                <form method="POST">
                  <div class="row g-3">
                    <div class="col-md-12 mb-2">
                      <label class="form-label">Role</label>
                      <select name="role" id="roleSelect" class="form-select">
                        <option value="faculty">Faculty</option>
                        <option value="admin">Admin</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" id="nameLabel">Name</label>
                      <input type="text" name="name" class="form-control" required />
                    </div>
                    <div class="col-md-6 faculty-field">
                      <label class="form-label">Designation</label>
                      <input type="text" name="designation" class="form-control faculty-input" required />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Email</label>
                      <input type="email" name="email" class="form-control" required />
                    </div>
                    <div class="col-md-6 faculty-field">
                      <label class="form-label">Contact</label>
                      <input type="text" name="contact" class="form-control faculty-input" required />
                    </div>
                    <div class="col-md-6 faculty-field">
                      <label class="form-label">College</label>
                      <input type="text" name="college" class="form-control faculty-input" required />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Password</label>
                      <input type="text" name="password" class="form-control" required />
                    </div>
                  </div>
                  <button type="submit" class="btn btn-primary mt-3" id="submitBtn">Register Faculty</button>
                </form>
              </div>
            </div>

            <!-- <footer class="text-center mt-5 mb-3 text-muted small py-3 px-2"
              style="background-color: #f1f1f1; border-top: 1px solid #ddd; border-radius: 8px;">
              QuickMark - Presented by Satish & team...
            </footer> -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('roleSelect').addEventListener('change', function() {
      const role = this.value;
      const facultyFields = document.querySelectorAll('.faculty-field');
      const facultyInputs = document.querySelectorAll('.faculty-input');
      const submitBtn = document.getElementById('submitBtn');
      const nameLabel = document.getElementById('nameLabel');
      
      if (role === 'admin') {
        facultyFields.forEach(el => el.style.display = 'none');
        facultyInputs.forEach(el => {
            el.required = false;
            el.value = '';
        });
        submitBtn.textContent = 'Register Admin';
        nameLabel.textContent = 'Username / Full Name';
      } else {
        facultyFields.forEach(el => el.style.display = 'block');
        facultyInputs.forEach(el => el.required = true);
        submitBtn.textContent = 'Register Faculty';
        nameLabel.textContent = 'Name';
      }
    });
  </script>
</body>

</html>

