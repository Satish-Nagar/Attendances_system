<?php
// includes/header_faculty.php
require_once __DIR__ . '/functions.php';
requireOM();

$db = getDB();
$faculty_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT profile_picture FROM faculties WHERE id = ?");
$stmt->execute([$faculty_id]);
$faculty_profile = $stmt->fetch();
$profile_picture = $faculty_profile && $faculty_profile['profile_picture'] ? '../uploads/' . htmlspecialchars($faculty_profile['profile_picture']) : 'https://via.placeholder.com/40x40.png?text=Faculty';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Faculty Dashboard'; ?> - QuickMark</title>
    
    <link rel="icon" type="image/x-icon" href="../download.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#0B1C3A">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="main-navbar px-3">
        <button class="btn btn-link text-white d-lg-none me-2" id="sidebarToggle">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center text-white" href="dashboard.php">
            <img src="images/download.png" alt="Logo" style="height:35px; border-radius:8px;" class="me-2">
            <span class="fw-bold">QuickMark</span>
        </a>
        
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="<?php echo $profile_picture; ?>" alt="mdo" width="32" height="32" class="rounded-circle me-2">
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="sidebar" id="mainSidebar">
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>Dashboard
            </a>
            <a class="nav-link <?php echo $current_page == 'sections.php' || $current_page == 'view_section.php' ? 'active' : ''; ?>" href="sections.php">
                <i class="fas fa-layer-group"></i>Sections
            </a>
            <a class="nav-link <?php echo $current_page == 'attendance_history.php' ? 'active' : ''; ?>" href="attendance_history.php">
                <i class="fas fa-history"></i>Attendance History
            </a>
            <a class="nav-link <?php echo $current_page == 'assignment_history.php' ? 'active' : ''; ?>" href="assignment_history.php">
                <i class="fas fa-tasks"></i>Assignment History
            </a>
            <a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php">
                <i class="fas fa-question-circle"></i>About & Help
            </a>
        </nav>
    </div>

    <div class="mobile-overlay" id="sidebarOverlay"></div>

    <main class="main-content">
        <div class="container-fluid">
            <?php $flash = getFlashMessage(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
