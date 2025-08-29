<?php
// student_report.php - Displays a student's attendance history
require('header.php');
require('conn.php');

// Ensure student is logged in
if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'STUDENT') {
    $_SESSION['msg'] = 'Please log in as a student to access this page.';
    header("location: student_login.php");
    exit();
}

$enrollment_no = $_SESSION['student_enrollment_no'];
$attendance_history = [];

$sql = "SELECT 
            ar.attendance_time,
            das.session_date,
            das.session_subject
        FROM `attendance` ar
        JOIN `daily_attendance_sessions` das ON ar.session_id = das.session_id
        WHERE ar.enrollment_no = '$enrollment_no'
        ORDER BY das.session_date DESC, ar.attendance_time DESC";

$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $attendance_history[] = $row;
    }
}
?>

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4 text-center">
                <h4 class="mb-4">Attendance Report for <?php echo htmlspecialchars($_SESSION['student_name']); ?></h4>
                <?php if (count($attendance_history) > 0) : ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Subject</th>
                                    <th>Attendance Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_history as $record) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['session_date']); ?></td>
                                        <td><?php echo htmlspecialchars($record['session_subject'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(date('h:i:s A', strtotime($record['attendance_time']))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="alert alert-info">
                        No attendance records found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>