<?php
$page_title = 'Manage Users';
require_once '../includes/header_admin.php';

// Handle actions
if (isset($_POST['action'])) {
    $faculty_id = (int)$_POST['faculty_id'];
    
    switch ($_POST['action']) {
        case 'delete':
            $stmt = $db->prepare("DELETE FROM faculties WHERE id = ?");
            $stmt->execute([$faculty_id]);
            setFlashMessage('success', 'Faculty deleted successfully');
            break;
            
        case 'toggle_status':
            $stmt = $db->prepare("UPDATE faculties SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$faculty_id]);
            setFlashMessage('success', 'Faculty status updated successfully');
            break;
    }
    
    redirect('manage_faculties.php');
}

// Handle Admin Actions
if (isset($_POST['admin_action'])) {
    $target_admin_id = (int)$_POST['target_admin_id'];
    
    switch ($_POST['admin_action']) {
        case 'delete_admin':
            if ($target_admin_id === $admin_id) {
                setFlashMessage('danger', 'Error: You cannot delete your own admin account.');
            } else {
                $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->execute([$target_admin_id]);
                setFlashMessage('success', 'Admin deleted successfully.');
            }
            break;
            
        case 'edit_admin':
            $new_username = trim($_POST['username']);
            $new_email = trim($_POST['email']);
            $new_password = $_POST['password'];
            
            // Check if email/username already exists for another admin
            $stmt = $db->prepare("SELECT id FROM admins WHERE (email = ? OR username = ?) AND id != ?");
            $stmt->execute([$new_email, $new_username, $target_admin_id]);
            if ($stmt->fetch()) {
                setFlashMessage('danger', 'Error: Email or Username already exists for another admin.');
            } else {
                if (!empty($new_password)) {
                    $hashed = hashPassword($new_password);
                    $stmt = $db->prepare("UPDATE admins SET username = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->execute([$new_username, $new_email, $hashed, $target_admin_id]);
                } else {
                    $stmt = $db->prepare("UPDATE admins SET username = ?, email = ? WHERE id = ?");
                    $stmt->execute([$new_username, $new_email, $target_admin_id]);
                }
                setFlashMessage('success', 'Admin updated successfully.');
            }
            break;
    }
    redirect('manage_faculties.php');
}

// Get all Admins
$stmt = $db->query("SELECT id, username, email, created_at FROM admins ORDER BY created_at DESC");
$all_admins = $stmt->fetchAll();

// Get all Faculties with their statistics
$stmt = $db->query("SELECT faculty.*, 
                           COUNT(DISTINCT s.id) as section_count,
                           COUNT(DISTINCT st.id) as student_count,
                           COUNT(DISTINCT a.id) as attendance_count
                    FROM faculties faculty
                    LEFT JOIN sections s ON faculty.id = s.faculty_id
                    LEFT JOIN students st ON s.id = st.section_id
                    LEFT JOIN attendance a ON s.id = a.section_id
                    GROUP BY faculty.id
                    ORDER BY faculty.created_at DESC");
$faculties = $stmt->fetchAll();
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="text-navy fw-bold"><i class="fas fa-users-cog me-2"></i>Manage Users</h2>
        <p class="text-muted">Manage faculty members and administrative staff.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <button type="button" class="btn btn-gold text-white me-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#manageAdminsModal">
            <i class="fas fa-user-shield me-2"></i>Admins
        </button>
        <a href="register_faculty.php" class="btn btn-navy bg-navy text-white shadow-sm">
            <i class="fas fa-user-plus me-2"></i>Add Faculty
        </a>
    </div>
</div>

<?php if (empty($faculties)): ?>
    <div class="custom-card text-center py-5">
        <i class="fas fa-user-slash fa-4x text-light mb-3"></i>
        <p class="text-muted">No faculties registered yet.</p>
        <a href="register_faculty.php" class="btn btn-primary mt-2">Register First Faculty</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($faculties as $faculty): ?>
            <div class="col-md-6 col-lg-4">
                <div class="custom-card h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold text-navy mb-0"><?php echo htmlspecialchars($faculty['name']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($faculty['designation']); ?></small>
                        </div>
                        <?php if ($faculty['is_active']): ?>
                            <span class="badge bg-success-subtle text-success border border-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger">Inactive</span>
                        <?php endif; ?>
                    </div>

                    <div class="small mb-4 flex-grow-1">
                        <div class="mb-1"><i class="fas fa-envelope text-gold me-2"></i><?php echo htmlspecialchars($faculty['email']); ?></div>
                        <div class="mb-1"><i class="fas fa-phone text-gold me-2"></i><?php echo htmlspecialchars($faculty['contact']); ?></div>
                        <div><i class="fas fa-university text-gold me-2"></i><?php echo htmlspecialchars($faculty['college']); ?></div>
                    </div>

                    <div class="row g-2 text-center mb-4">
                        <div class="col-4">
                            <div class="p-2 bg-light rounded shadow-sm">
                                <div class="fw-bold text-navy"><?php echo $faculty['section_count']; ?></div>
                                <div class="small text-muted" style="font-size: 10px;">Sections</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded shadow-sm">
                                <div class="fw-bold text-navy"><?php echo $faculty['student_count']; ?></div>
                                <div class="small text-muted" style="font-size: 10px;">Students</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded shadow-sm">
                                <div class="fw-bold text-navy"><?php echo $faculty['attendance_count']; ?></div>
                                <div class="small text-muted" style="font-size: 10px;">Records</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <div class="d-flex gap-2">
                            <a href="edit_faculty.php?id=<?php echo $faculty['id']; ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <form method="POST" class="flex-grow-1">
                                <input type="hidden" name="faculty_id" value="<?php echo $faculty['id']; ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <button type="submit" class="btn btn-outline-warning btn-sm w-100" onclick="return confirm('Change status?')">
                                    <i class="fas fa-power-off me-1"></i><?php echo $faculty['is_active'] ? 'Disable' : 'Enable'; ?>
                                </button>
                            </form>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="faculty_id" value="<?php echo $faculty['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger btn-sm w-100" onclick="return confirm('Delete permanently?')">
                                <i class="fas fa-trash me-1"></i>Delete Faculty
                            </button>
                        </form>
                    </div>
                    
                    <div class="mt-3 pt-2 border-top">
                        <small class="text-muted" style="font-size: 11px;">
                            Joined: <?php echo formatDate($faculty['created_at']); ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Manage Admins Modal -->
<div class="modal fade" id="manageAdminsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title"><i class="fas fa-user-shield me-2"></i>Administrative Access</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Admin</th>
                  <th>Contact</th>
                  <th>Added</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($all_admins as $adm): ?>
                <tr>
                  <td>
                      <div class="fw-bold"><?php echo htmlspecialchars($adm['username']); ?></div>
                      <?php if($adm['id'] == $admin_id): ?>
                        <span class="badge bg-navy-subtle text-navy" style="font-size: 10px;">Current Session</span>
                      <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($adm['email']); ?></td>
                  <td><small><?php echo date('M j, Y', strtotime($adm['created_at'])); ?></small></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-link text-primary edit-admin-btn" 
                            data-id="<?php echo $adm['id']; ?>" 
                            data-username="<?php echo htmlspecialchars($adm['username']); ?>" 
                            data-email="<?php echo htmlspecialchars($adm['email']); ?>"
                            data-bs-toggle="modal" data-bs-target="#editAdminModal" data-bs-dismiss="modal">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php if($adm['id'] != $admin_id): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove admin access?');">
                        <input type="hidden" name="admin_action" value="delete_admin">
                        <input type="hidden" name="target_admin_id" value="<?php echo $adm['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-link text-danger"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Administrator</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#manageAdminsModal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
            <input type="hidden" name="admin_action" value="edit_admin">
            <input type="hidden" name="target_admin_id" id="edit_admin_id">
            
            <div class="mb-3">
                <label class="form-label fw-bold small">Username</label>
                <input type="text" name="username" id="edit_admin_username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Email Address</label>
                <input type="email" name="email" id="edit_admin_email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">New Password (Optional)</label>
                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#manageAdminsModal">Cancel</button>
            <button type="submit" class="btn btn-navy bg-navy text-white">Update Admin</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-admin-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_admin_id').value = this.getAttribute('data-id');
            document.getElementById('edit_admin_username').value = this.getAttribute('data-username');
            document.getElementById('edit_admin_email').value = this.getAttribute('data-email');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
