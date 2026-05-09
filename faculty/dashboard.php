<?php
$page_title = 'Faculty Dashboard';
require_once '../includes/header_faculty.php';

// Get Faculty's sections
$stmt = $db->prepare("SELECT s.*, 
                             (SELECT COUNT(*) FROM students WHERE section_id = s.id) as student_count,
                             (SELECT COUNT(*) FROM attendance WHERE section_id = s.id) as attendance_count
                      FROM sections s 
                      WHERE s.faculty_id = ? 
                      ORDER BY s.created_at DESC");
$stmt->execute([$faculty_id]);
$sections = $stmt->fetchAll();

// Get recent attendance records
$stmt = $db->prepare("SELECT a.*, s.name as section_name 
                      FROM attendance a 
                      JOIN sections s ON a.section_id = s.id 
                      WHERE s.faculty_id = ? 
                      ORDER BY a.date DESC 
                      LIMIT 5");
$stmt->execute([$faculty_id]);
$recent_attendance = $stmt->fetchAll();

// Get total statistics
$stmt = $db->prepare("SELECT 
                        COUNT(DISTINCT s.id) as total_sections,
                        COUNT(DISTINCT st.id) as total_students,
                        COUNT(DISTINCT a.id) as total_attendance
                      FROM sections s 
                      LEFT JOIN students st ON s.id = st.section_id 
                      LEFT JOIN attendance a ON s.id = a.section_id 
                      WHERE s.faculty_id = ?");
$stmt->execute([$faculty_id]);
$stats = $stmt->fetch();
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="text-navy fw-bold"><i class="fas fa-tachometer-alt me-2"></i>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <p class="text-muted">Overview of your classes and students.</p>
    </div>
</div>

<!-- Stats Grid -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="custom-card text-center">
            <div class="stat-icon text-primary"><i class="fas fa-layer-group"></i></div>
            <div class="h2 fw-bold text-navy"><?php echo $stats['total_sections']; ?></div>
            <div class="text-muted small text-uppercase fw-bold">Active Sections</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="custom-card text-center">
            <div class="stat-icon text-success"><i class="fas fa-user-graduate"></i></div>
            <div class="h2 fw-bold text-navy"><?php echo $stats['total_students']; ?></div>
            <div class="text-muted small text-uppercase fw-bold">Total Students</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="custom-card text-center">
            <div class="stat-icon text-info"><i class="fas fa-clipboard-check"></i></div>
            <div class="h2 fw-bold text-navy"><?php echo $stats['total_attendance']; ?></div>
            <div class="text-muted small text-uppercase fw-bold">Attendance Records</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-5">
    <div class="col-md-3">
        <a href="add_section.php" class="btn btn-primary w-100 py-2 shadow-sm">
            <i class="fas fa-plus me-2"></i>Add Section
        </a>
    </div>
    <div class="col-md-3">
        <a href="sections.php" class="btn btn-gold w-100 py-2 shadow-sm">
            <i class="fas fa-list me-2"></i>Mark Attendance
        </a>
    </div>
    <div class="col-md-3">
        <a href="attendance_history.php" class="btn btn-navy bg-navy text-white w-100 py-2 shadow-sm">
            <i class="fas fa-history me-2"></i>View History
        </a>
    </div>
    <div class="col-md-3">
        <a href="profile.php" class="btn btn-outline-primary w-100 py-2 shadow-sm">
            <i class="fas fa-user-circle me-2"></i>My Profile
        </a>
    </div>
</div>

<!-- My Sections -->
<div class="row g-4 mb-5">
    <div class="col-12">
        <h4 class="fw-bold text-navy mb-4"><i class="fas fa-th-large me-2"></i>Your Sections</h4>
    </div>
    <?php if (empty($sections)): ?>
        <div class="col-12">
            <div class="custom-card text-center py-5">
                <i class="fas fa-folder-open fa-3x text-light mb-3"></i>
                <p class="text-muted">You haven't created any sections yet. <a href="add_section.php">Create one now.</a></p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($sections as $section): ?>
            <div class="col-md-4">
                <div class="custom-card h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="fw-bold text-navy mb-0"><?php echo htmlspecialchars($section['name']); ?></h5>
                        <span class="badge bg-gold"><?php echo $section['student_count']; ?> Students</span>
                    </div>
                    <p class="text-muted small flex-grow-1"><?php echo htmlspecialchars($section['description'] ?: 'No description provided.'); ?></p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="mark_attendance.php?section_id=<?php echo $section['id']; ?>" class="btn btn-primary btn-sm">Mark Attendance</a>
                        <div class="d-flex gap-2">
                            <a href="view_section.php?id=<?php echo $section['id']; ?>" class="btn btn-outline-navy btn-sm flex-grow-1">View Students</a>
                            <a href="mark_assignment.php?section_id=<?php echo $section['id']; ?>" class="btn btn-outline-gold btn-sm flex-grow-1">Assignment</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($recent_attendance)): ?>
<div class="custom-card">
    <h5 class="fw-bold text-navy mb-4"><i class="fas fa-history me-2"></i>Recent Attendance</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Date</th>
                    <th>Present</th>
                    <th>Percentage</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_attendance as $record): ?>
                    <tr>
                        <td class="fw-bold"><?php echo htmlspecialchars($record['section_name']); ?></td>
                        <td><?php echo formatDate($record['date']); ?></td>
                        <td><?php echo $record['present_count']; ?> / <?php echo $record['total_count']; ?></td>
                        <td>
                            <?php 
                                $pct = $record['total_count'] > 0 ? round(($record['present_count'] / $record['total_count']) * 100) : 0;
                                $color = ($pct > 75) ? 'success' : (($pct > 50) ? 'warning' : 'danger');
                            ?>
                            <div class="progress" style="height: 6px; width: 100px;">
                                <div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $pct; ?>%</small>
                        </td>
                        <td>
                            <a href="attendance_history.php" class="btn btn-sm btn-link text-navy"><i class="fas fa-chevron-right"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?> 