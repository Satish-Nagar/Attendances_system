<?php
$page_title = 'Bulk Register Faculties';
require_once '../includes/header_admin.php';
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
require_once '../config/mail.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['faculties_file']) && $_FILES['faculties_file']['tmp_name']) {
    $file = $_FILES['faculties_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $faculties = [];
    try {
        if ($ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, $row);
                if (!empty($data['name']) && !empty($data['designation']) && !empty($data['email']) && !empty($data['contact']) && !empty($data['college']) && !empty($data['password'])) {
                    $faculties[] = $data;
                }
            }
            fclose($handle);
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $headers = array_map('strtolower', array_map('trim', $rows[1]));
            for ($i = 2; $i <= count($rows); $i++) {
                $row = $rows[$i];
                $data = array_combine($headers, $row);
                if (!empty($data['name']) && !empty($data['designation']) && !empty($data['email']) && !empty($data['contact']) && !empty($data['college']) && !empty($data['password'])) {
                    $faculties[] = $data;
                }
            }
        } else {
            $error = 'Invalid file type. Only CSV, XLS, XLSX allowed.';
        }
    } catch (Exception $e) {
        $error = 'Error reading file: ' . $e->getMessage();
    }
    // Insert Faculties
    if ($faculties && !$error) {
        $stmt = $db->prepare("INSERT INTO faculties (name, designation, email, contact, college, password) VALUES (?, ?, ?, ?, ?, ?)");
        $count = 0;
        $emails_sent = 0;
        foreach ($faculties as $faculty) {
            $hashed = password_hash($faculty['password'], PASSWORD_DEFAULT);
            if ($stmt->execute([$faculty['name'], $faculty['designation'], $faculty['email'], $faculty['contact'], $faculty['college'], $hashed])) {
                // Send email with credentials
                $subject = "Your Faculty Account - QuickMark";
                $login_url = 'http://quickmark.kesug.com/login.php';
                $message = getWelcomeEmailHTML($faculty['name'], 'faculty', $faculty['email'], $faculty['password'], $login_url);
                $embeddedImages = ['app_logo' => __DIR__ . '/images/download.png'];
                $sent = sendSMTPMail($faculty['email'], $subject, $message, $embeddedImages);
                if ($sent) $emails_sent++;
                $count++;
            }
        }
        if ($count > 0) {
            if ($emails_sent == $count) {
                $success = "Successfully imported $count Faculties. Credentials sent to all emails.";
            } else if ($emails_sent == 0) {
                $success = "Imported $count Faculties, but email delivery failed.";
            } else {
                $success = "Imported $count Faculties. Credentials sent to $emails_sent emails.";
            }
        }
    } elseif (!$error) {
        $error = 'No valid faculty records found in the file.';
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="text-navy fw-bold mb-1">Bulk Register Faculties</h2>
                <p class="text-muted">Import multiple faculty accounts via CSV or Excel.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-navy btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="custom-card p-4">
            <div class="mb-4">
                <h5 class="fw-bold text-navy"><i class="fas fa-file-import me-2 text-gold"></i>Upload File</h5>
                <p class="small text-muted">Please ensure your file follows the required format for a smooth import.</p>
            </div>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="faculties_file" class="form-label fw-bold small text-navy">Choose File (CSV, XLS, XLSX)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-file-excel text-muted"></i></span>
                        <input type="file" class="form-control border-start-0" id="faculties_file" name="faculties_file" accept=".csv,.xls,.xlsx" required>
                    </div>
                    <div class="form-text mt-3 p-3 bg-light rounded-3 border">
                        <i class="fas fa-info-circle text-navy me-1"></i> <strong>Required Columns:</strong> 
                        <code class="ms-1">name, designation, email, contact, college, password</code>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-navy bg-navy text-white btn-lg py-3 shadow-sm">
                        <i class="fas fa-upload me-2"></i>Start Import
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-4 p-4 border border-dashed rounded-4 bg-white shadow-sm">
            <h6 class="fw-bold text-navy mb-3"><i class="fas fa-lightbulb me-2 text-gold"></i>Pro Tips:</h6>
            <ul class="small text-muted mb-0 ps-3">
                <li class="mb-2">Download a sample template to ensure your data matches our system requirements.</li>
                <li class="mb-2">Make sure emails are unique; duplicate emails will cause the import for that row to fail.</li>
                <li>Passwords should be at least 8 characters for security.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 
