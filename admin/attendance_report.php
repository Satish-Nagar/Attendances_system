<?php
$page_title = 'Attendance Report';
require_once '../includes/header_admin.php';

// Attendance filters
$attendance_colleges = $db->query("SELECT DISTINCT college FROM faculties ORDER BY college")->fetchAll();
$attendance_college = isset($_GET['attendance_college']) ? $_GET['attendance_college'] : '';
$attendance_date = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : '';
$attendance_dates = [];
if ($attendance_college) {
    $stmt = $db->prepare("SELECT DISTINCT a.date FROM attendance a JOIN sections s ON a.section_id = s.id JOIN faculties faculty ON s.faculty_id = faculty.id WHERE faculty.college = ? ORDER BY a.date DESC");
    $stmt->execute([$attendance_college]);
    $attendance_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Attendance Data
$attendance_sections = [];
$attendance_present = 0;
$attendance_total = 0;
$show_all_colleges_today = false;
$all_colleges = [];
$all_present = 0;
$all_total = 0;
$today = date('Y-m-d');
if (!$attendance_college && !$attendance_date) {
    // Default: show all colleges for today
    $show_all_colleges_today = true;
    $stmt = $db->prepare("SELECT faculty.college, SUM(a.present_count) as present, SUM(a.total_count) as total FROM attendance a JOIN sections s ON a.section_id = s.id JOIN faculties faculty ON s.faculty_id = faculty.id WHERE a.date = ? GROUP BY faculty.college");
    $stmt->execute([$today]);
    $all_colleges = $stmt->fetchAll();
    $all_present = array_sum(array_column($all_colleges, 'present'));
    $all_total = array_sum(array_column($all_colleges, 'total'));
} elseif ($attendance_college && $attendance_date) {
    // Get all sections for the selected college
    $stmt = $db->prepare("SELECT s.id, s.name FROM sections s JOIN faculties faculty ON s.faculty_id = faculty.id WHERE faculty.college = ? ORDER BY s.name");
    $stmt->execute([$attendance_college]);
    $all_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get attendance for each section
    $stmt = $db->prepare("SELECT s.id as section_id, s.name as section_name, a.present_count, a.total_count FROM sections s JOIN faculties faculty ON s.faculty_id = faculty.id LEFT JOIN attendance a ON a.section_id = s.id AND a.date = ? WHERE faculty.college = ? ORDER BY s.name");
    $stmt->execute([$attendance_date, $attendance_college]);
    $attendance_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $attendance_map = [];
    foreach ($attendance_raw as $row) {
        $attendance_map[$row['section_id']] = $row;
    }
    
    $attendance_sections = [];
    foreach ($all_sections as $section) {
        $sid = $section['id'];
        if (isset($attendance_map[$sid])) {
            $attendance_sections[] = $attendance_map[$sid];
            $attendance_present += (int)($attendance_map[$sid]['present_count'] ?? 0);
            $attendance_total += (int)($attendance_map[$sid]['total_count'] ?? 0);
        } else {
            $attendance_sections[] = [
                'section_id' => $sid,
                'section_name' => $section['name'],
                'present_count' => 0,
                'total_count' => 0
            ];
        }
    }
}

$best_section = null; $best_val = -1;
$low_section = null; $low_val = 101;
$overall_pct = 0;
if ($show_all_colleges_today) {
    $overall_pct = $all_total > 0 ? round($all_present / $all_total * 100, 2) : 0;
    foreach ($all_colleges as $row) {
        $pct = ($row['total'] ?? 0) > 0 ? ($row['present'] / $row['total']) * 100 : 0;
        if ($pct > $best_val) { $best_val = $pct; $best_section = $row['college']; }
        if ($pct < $low_val) { $low_val = $pct; $low_section = $row['college']; }
    }
} elseif ($attendance_college && $attendance_date) {
    $overall_pct = $attendance_total > 0 ? round($attendance_present / $attendance_total * 100, 2) : 0;
    foreach ($attendance_sections as $row) {
        $pct = ($row['total_count'] ?? 0) > 0 ? ($row['present_count'] / $row['total_count']) * 100 : 0;
        if ($pct > $best_val) { $best_val = $pct; $best_section = $row['section_name']; }
        if ($pct < $low_val) { $low_val = $pct; $low_section = $row['section_name']; }
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="text-navy fw-bold"><i class="fas fa-chart-bar me-2"></i>Attendance Report</h2>
        <p class="text-muted">Analyze attendance trends across colleges and sections.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="dashboard.php" class="btn btn-outline-navy btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="custom-card text-center py-4 h-100">
            <div class="small text-muted mb-1 text-uppercase fw-bold">Overall Attendance</div>
            <div class="display-6 fw-bold text-navy"><?php echo $overall_pct; ?>%</div>
            <div class="progress mt-3" style="height: 6px;">
                <div class="progress-bar bg-gold" style="width: <?php echo $overall_pct; ?>%"></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="custom-card text-center py-4 h-100">
            <div class="small text-muted mb-1 text-uppercase fw-bold">Total Present</div>
            <div class="display-6 fw-bold text-success"><?php echo $attendance_present; ?></div>
            <div class="small text-muted mt-2">Students</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="custom-card text-center py-4 h-100">
            <div class="small text-muted mb-1 text-uppercase fw-bold">Total Absent</div>
            <div class="display-6 fw-bold text-danger"><?php echo $attendance_total - $attendance_present; ?></div>
            <div class="small text-muted mt-2">Students</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="custom-card text-center py-4 h-100">
            <div class="small text-muted mb-1 text-uppercase fw-bold">Best Performer</div>
            <div class="h5 fw-bold text-navy mt-2 mb-0"><?php echo $best_section ? htmlspecialchars($best_section) : 'N/A'; ?></div>
            <div class="small text-gold fw-bold"><?php echo $best_section ? round($best_val, 1) . '%' : ''; ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="custom-card mb-4">
    <form class="row g-3" method="get" id="attendance-filter-form">
        <div class="col-md-5">
            <label class="form-label fw-bold small">Select College</label>
            <select name="attendance_college" id="attendance_college" class="form-select shadow-sm">
                <option value="">All Colleges</option>
                <?php foreach ($attendance_colleges as $c): ?>
                <option value="<?php echo htmlspecialchars($c['college']); ?>" <?php if ($attendance_college == $c['college']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($c['college']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label fw-bold small">Select Date</label>
            <select name="attendance_date" id="attendance_date" class="form-select shadow-sm" <?php if (!$attendance_college) echo 'disabled'; ?>>
                <option value="">Select Date</option>
                <?php if ($attendance_college): ?>
                    <?php foreach ($attendance_dates as $d): ?>
                    <option value="<?php echo $d; ?>" <?php if ($attendance_date == $d) echo 'selected'; ?>><?php echo date('M j, Y', strtotime($d)); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-navy bg-navy text-white w-100 shadow-sm">
                <i class="fas fa-filter me-2"></i>Filter
            </button>
        </div>
    </form>
</div>

<?php if ($show_all_colleges_today): ?>
    <?php if (!empty($all_colleges)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="custom-card">
                <h5 class="fw-bold text-navy mb-4"><i class="fas fa-chart-bar me-2 text-gold"></i>Attendance by College (Today)</h5>
                <div style="height: 350px;">
                    <canvas id="allCollegesBar"></canvas>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var allCollegesLabels = <?php echo json_encode(array_column($all_colleges, 'college')); ?>;
        var allCollegesData = <?php echo json_encode(array_map(function($row) { return $row['total'] > 0 ? round($row['present']/$row['total']*100,2) : 0; }, $all_colleges)); ?>;
        var ctxBar = document.getElementById('allCollegesBar').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: allCollegesLabels,
                datasets: [{
                    label: 'Attendance %',
                    data: allCollegesData,
                    backgroundColor: '#C89C5D',
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: value => value + '%' } },
                    x: { grid: { display: false } }
                }
            }
        });
    });
    </script>
    <?php else: ?>
        <div class="custom-card text-center py-5">
            <i class="fas fa-calendar-times fa-3x text-light mb-3"></i>
            <p class="text-muted">No attendance data recorded for today yet.</p>
        </div>
    <?php endif; ?>

<?php elseif ($attendance_college && $attendance_date): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="custom-card h-100">
                <h5 class="fw-bold text-navy mb-4">Section-wise Performance</h5>
                <div style="height: 300px;">
                    <canvas id="attendanceBar"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="custom-card h-100">
                <h5 class="fw-bold text-navy mb-4">Presence Split</h5>
                <div style="height: 300px;">
                    <canvas id="attendancePie"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var attendanceLabels = <?php echo json_encode(array_column($attendance_sections, 'section_name')); ?>;
        var attendanceData = <?php echo json_encode(array_map(function($row) { return ($row['total_count'] ?? 0) > 0 ? round(($row['present_count'] / $row['total_count']) * 100, 2) : 0; }, $attendance_sections)); ?>;
        var ctxBar = document.getElementById('attendanceBar').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: attendanceLabels,
                datasets: [{
                    label: 'Attendance %',
                    data: attendanceData,
                    backgroundColor: '#0B1C3A',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { max: 100 } }
            }
        });

        var ctxPie = document.getElementById('attendancePie').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?php echo $attendance_present; ?>, <?php echo $attendance_total - $attendance_present; ?>],
                    backgroundColor: ['#198754', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    });
    </script>

    <div class="custom-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold text-navy mb-0">Detailed Statistics</h5>
            <button class="btn btn-outline-success btn-sm" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Report
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Section Name</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Percentage</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_sections as $row): ?>
                    <?php 
                        $pct = ($row['total_count'] ?? 0) > 0 ? round(($row['present_count'] / $row['total_count']) * 100, 1) : 0;
                        $status_class = $pct >= 75 ? 'text-success' : ($pct >= 60 ? 'text-warning' : 'text-danger');
                    ?>
                    <tr>
                        <td class="fw-bold text-navy"><?= htmlspecialchars($row['section_name']) ?></td>
                        <td class="text-center"><?= (int)($row['present_count'] ?? 0) ?></td>
                        <td class="text-center"><?= (int)($row['total_count'] ?? 0) ?></td>
                        <td class="text-center">
                            <div class="fw-bold <?= $status_class ?>"><?= $pct ?>%</div>
                        </td>
                        <td class="text-end">
                            <?php if ($pct >= 75): ?>
                                <span class="badge bg-success-subtle text-success border border-success">Excellent</span>
                            <?php elseif ($pct >= 60): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning">Average</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger">Low</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
$(function() {
    $('#attendance_college').on('change', function() {
        var college = $(this).val();
        if (college) {
            $.get('get_dates.php', {
                college: college,
                type: 'attendance'
            }, function(data) {
                var options = '<option value="">Select Date</option>';
                var dates = JSON.parse(data);
                if (dates.length === 0) {
                    options += '<option value="">No data available</option>';
                } else {
                    $.each(dates, function(i, d) {
                        options += '<option value="' + d + '">' + d + '</option>';
                    });
                }
                $('#attendance_date').html(options).prop('disabled', false);
            });
        } else {
            $('#attendance_date').html('<option value="">Select Date</option>').prop('disabled', true);
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
