<?php
// admin_attendance_control.php - Admin's main panel for attendance sessions
require('header.php');
require('conn.php');

if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'ADMIN') {
    $_SESSION['msg'] = 'Access denied. You must be an admin.';
    header("location: login.php");
    exit();
}

$session_message = '';
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Function to check if a session for a specific semester/batch is already active for today using prepared statements
function isSessionActive($conn, $semester, $batch) {
    $check_sql = "SELECT `session_id` FROM `daily_attendance_sessions` WHERE DATE(`start_time`) = CURDATE() AND `semester` = ? AND `batch` = ? AND `status` = 'active'";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, 'ss', $semester, $batch);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $is_active = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $is_active;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_session'])) {
    $semester = $_POST['semester'];
    $batch = $_POST['batch'];
    $admin_id = $_SESSION['user_id'];
    $start_time = date('Y-m-d H:i:s');

    if (isSessionActive($conn, $semester, $batch)) {
        $session_message = '<div class="alert alert-warning">An attendance session is already active for ' . htmlspecialchars($semester) . ' Semester, ' . htmlspecialchars($batch) . ' today.</div>';
    } else {
        $sql = "INSERT INTO `daily_attendance_sessions` (`admin_id`, `semester`, `batch`, `start_time`, `status`)
                VALUES (?, ?, ?, ?, 'active')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'isss', $admin_id, $semester, $batch, $start_time);
        
        if (mysqli_stmt_execute($stmt)) {
            $session_message = '<div class="alert alert-success">New attendance session started successfully!</div>';
        } else {
            $session_message = '<div class="alert alert-danger">Error starting session: ' . mysqli_error($conn) . '</div>';
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all active and past sessions for today
$sessions_sql = "SELECT das.*, u.username FROM `daily_attendance_sessions` das JOIN `users` u ON das.admin_id = u.id WHERE DATE(`start_time`) = CURDATE() ORDER BY `start_time` DESC";
$sessions_result = mysqli_query($conn, $sessions_sql);

$semesters = ['First', 'Second', 'Third', 'Fouth', 'Fifth', 'Sixth', 'Seventh', 'Eighth'];
$batches = ['BBS', 'BBM', 'BCA'];
?>

<h4 class="mb-4">Attendance Analysis Dashboard</h4>
<div class="mb-3">
    <a href="admin_analytics.php" class="btn btn-primary">Analysis</a>
</div>
<div class="row">

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h4 class="mb-4">Admin Attendance Control</h4>
                <?php echo $session_message; ?>
                <?php
                if (isset($_SESSION['msg'])) {
                    echo $_SESSION['msg'];
                    unset($_SESSION['msg']);
                }
                ?>
                <div class="card mb-4">
                    <div class="card-header">
                        Start a New Attendance Session
                    </div>
                    <div class="card-body">
                        <form action="admin_attendance_control.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <select class="form-select" name="semester" required>
                                        <option value="">Select Semester</option>
                                        <?php foreach ($semesters as $s) : ?>
                                            <option value="<?php echo htmlspecialchars($s); ?>">Semester <?php echo htmlspecialchars($s); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <select class="form-select" name="batch" required>
                                        <option value="">Select Batch</option>
                                        <?php foreach ($batches as $b) : ?>
                                            <option value="<?php echo htmlspecialchars($b); ?>"><?php echo htmlspecialchars($b); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="start_session" class="btn btn-primary w-100">
                                        <i class="fas fa-play-circle me-1"></i>Start Session
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Today's Attendance Sessions
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($sessions_result) == 0) : ?>
                            <p class="text-muted">No sessions have been started today.</p>
                            <a href="admin_scan_qr.php" class="btn btn-primary"><i class="fas fa-camera me-1"></i>Go to QR Scanner</a>
                        <?php else : ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Session ID</th>
                                            <th>Semester</th>
                                            <th>Batch</th>
                                            <th>Start Time</th>
                                            <th>Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($session = mysqli_fetch_assoc($sessions_result)) : ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($session['session_id']); ?></td>
                                                <td><?php echo htmlspecialchars($session['semester']); ?></td>
                                                <td><?php echo htmlspecialchars($session['batch']); ?></td>
                                                <td><?php echo date('h:i:s A', strtotime($session['start_time'])); ?></td>
                                                <td>
                                                    <?php
                                                    if ($session['status'] == 'active') {
                                                        echo '<span class="badge bg-success">Active</span>';
                                                    } else {
                                                        echo '<span class="badge bg-danger">Closed</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="admin_scan_qr.php?session_id=<?php echo urlencode($session['session_id']); ?>" class="btn btn-sm btn-success me-1" title="Scan Attendance">
                                                        <i class="fas fa-camera me-1"></i>Scan
                                                    </a>
                                                    <a href="admin_attendance_report.php?session_id=<?php echo urlencode($session['session_id']); ?>" class="btn btn-sm btn-info" title="Analyze Attendance">
                                                        <i class="fas fa-chart-bar me-1"></i>Analyze
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>