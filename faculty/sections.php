<?php
$page_title = 'My Sections';
require_once '../includes/header_faculty.php';

// Handle delete section
if (isset($_POST['delete_section_id'])) {
    $section_id = (int)$_POST['delete_section_id'];
    $stmt = $db->prepare("DELETE FROM sections WHERE id = ? AND faculty_id = ?");
    if ($stmt->execute([$section_id, $faculty_id])) {
        setFlashMessage('success', 'Section deleted successfully!');
    } else {
        setFlashMessage('danger', 'Failed to delete section.');
    }
    redirect('sections.php');
}

// Handle new section submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if ($name) {
        $stmt = $db->prepare("INSERT INTO sections (faculty_id, name, description) VALUES (?, ?, ?)");
        if ($stmt->execute([$faculty_id, $name, $description])) {
            setFlashMessage('success', 'Section added successfully!');
        } else {
            setFlashMessage('danger', 'Failed to add section.');
        }
    } else {
        setFlashMessage('danger', 'Section name is required.');
    }
    redirect('sections.php');
}

// Fetch all sections for this Faculty
$stmt = $db->prepare("SELECT s.*, 
                           COUNT(st.id) as student_count 
                    FROM sections s 
                    LEFT JOIN students st ON s.id = st.section_id 
                    WHERE s.faculty_id = ? 
                    GROUP BY s.id 
                    ORDER BY s.created_at DESC");
$stmt->execute([$faculty_id]);
$sections = $stmt->fetchAll();
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="text-navy fw-bold"><i class="fas fa-layer-group me-2"></i>My Sections</h2>
        <p class="text-muted">Manage your classes and student groups.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="add_section.php" class="btn btn-gold text-white shadow-sm">
            <i class="fas fa-plus me-2"></i>Create New Section
        </a>
    </div>
</div>

<?php if (empty($sections)): ?>
    <div class="custom-card text-center py-5">
        <i class="fas fa-layer-group fa-4x text-light mb-3"></i>
        <p class="text-muted">You haven't created any sections yet.</p>
        <a href="add_section.php" class="btn btn-navy bg-navy text-white mt-2">Get Started</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($sections as $section): ?>
            <div class="col-md-6 col-lg-4">
                <div class="custom-card h-100 d-flex flex-column" style="border-left: 5px solid var(--bgi-gold);">
                    <div class="mb-3">
                        <h5 class="fw-bold text-navy mb-1"><?php echo htmlspecialchars($section['name']); ?></h5>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($section['description']); ?></p>
                    </div>

                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-light p-2 rounded-circle me-3">
                            <i class="fas fa-users text-gold"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-navy"><?php echo $section['student_count']; ?></div>
                            <div class="small text-muted">Enrolled Students</div>
                        </div>
                    </div>

                    <div class="mt-auto pt-3 border-top">
                        <div class="d-grid gap-2">
                            <div class="d-flex gap-2">
                                <a href="view_section.php?id=<?php echo $section['id']; ?>" class="btn btn-outline-navy btn-sm flex-grow-1">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                                <a href="edit_students.php?section_id=<?php echo $section['id']; ?>" class="btn btn-outline-gold btn-sm flex-grow-1">
                                    <i class="fas fa-user-edit me-1"></i>Students
                                </a>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="mark_attendance.php?section_id=<?php echo $section['id']; ?>" class="btn btn-navy bg-navy text-white btn-sm flex-grow-1">
                                    <i class="fas fa-check-circle me-1"></i>Attendance
                                </a>
                                <form method="POST" class="flex-grow-0" onsubmit="return confirm('Delete this section and all associated records?');">
                                    <input type="hidden" name="delete_section_id" value="<?php echo $section['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="far fa-clock me-1"></i>Created: <?php echo date('M j, Y', strtotime($section['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>