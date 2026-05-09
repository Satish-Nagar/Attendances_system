<?php
$page_title = 'Admin Dashboard';
require_once '../includes/header_admin.php';

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total_faculties FROM faculties WHERE is_active = 1");
$total_faculties = $stmt->fetch()['total_faculties'];

$stmt = $db->query("SELECT COUNT(*) as total_sections FROM sections");
$total_sections = $stmt->fetch()['total_sections'];

$stmt = $db->query("SELECT COUNT(*) as total_students FROM students");
$total_students = $stmt->fetch()['total_students'];

$stmt = $db->query("SELECT COUNT(*) as total_attendance FROM attendance");
$total_attendance = $stmt->fetch()['total_attendance'];

// Get recent Faculties
$stmt = $db->query("SELECT * FROM faculties ORDER BY created_at DESC LIMIT 5");
$recent_faculties = $stmt->fetchAll();
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="text-navy fw-bold"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
        <p class="text-muted">Welcome back, system administrator.</p>
    </div>
</div>

<!-- Stats Grid -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="custom-card text-center">
            <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
            <div class="h2 fw-bold text-navy"><?php echo $total_faculties; ?></div>
            <div class="text-muted small text-uppercase fw-bold">Active Faculties</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="custom-card text-center">
            <div class="stat-icon text-success"><i class="fas fa-layer-group"></i></div>
            <div class="h2 fw-bold text-navy"><?php echo $total_sections; ?></div>
            <div class="text-muted small text-uppercase fw-bold">Total Sections</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="custom-card text-center">
            <div class="stat-icon text-info"><i class="fas fa-user-graduate"></i></div>
            <div class="h2 fw-bold text-navy"><?php echo $total_students; ?></div>
            <div class="text-muted small text-uppercase fw-bold">Total Students</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="custom-card text-center">
            <div class="stat-icon text-warning"><i class="fas fa-clipboard-list"></i></div>
            <div class="h2 fw-bold text-navy"><?php echo $total_attendance; ?></div>
            <div class="text-muted small text-uppercase fw-bold">Attendance Records</div>
        </div>
    </div>
</div>

<!-- Action Bar -->
<div class="row g-3 mb-5">
    <div class="col-md-3">
        <a href="register_faculty.php" class="btn btn-primary w-100 py-2 shadow-sm">
            <i class="fas fa-user-plus me-2"></i>Register Faculty
        </a>
    </div>
    <div class="col-md-3">
        <a href="bulk_register.php" class="btn btn-gold w-100 py-2 shadow-sm">
            <i class="fas fa-upload me-2"></i>Bulk Register
        </a>
    </div>
    <div class="col-md-3">
        <a href="manage_faculties.php" class="btn btn-navy bg-navy text-white w-100 py-2 shadow-sm">
            <i class="fas fa-users-cog me-2"></i>Manage Users
        </a>
    </div>
    <div class="col-md-3">
        <a href="attendance_report.php" class="btn btn-outline-primary w-100 py-2 shadow-sm">
            <i class="fas fa-chart-line me-2"></i>View Reports
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="custom-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold text-navy mb-0"><i class="fas fa-history me-2"></i>Recently Registered Faculties</h5>
        <a href="manage_faculties.php" class="btn btn-link text-gold text-decoration-none fw-bold small">View All</a>
    </div>
    
    <?php if (empty($recent_faculties)): ?>
        <div class="text-center py-5">
            <i class="fas fa-user-slash fa-3x text-light mb-3"></i>
            <p class="text-muted">No faculties registered yet.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Faculty Name</th>
                        <th>Contact info</th>
                        <th>College</th>
                        <th>Status</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_faculties as $faculty): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($faculty['name']); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($faculty['designation']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($faculty['email']); ?></td>
                        <td><?php echo htmlspecialchars($faculty['college']); ?></td>
                        <td>
                            <?php if ($faculty['is_active']): ?>
                                <span class="badge bg-success-subtle text-success border border-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($faculty['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>

