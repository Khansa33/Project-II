<?php
require('header.php');
require('conn.php');

if ($_SESSION['usertype'] != 'ADMIN') {
    $_SESSION['msg'] = 'Access denied. You must be an admin to view student reports.';
    header("location: login.php");
    exit();
}

$student_enrollment_no = isset($_GET['enrollment_no']) ? $_GET['enrollment_no'] : null;

// The code block below is now wrapped in a condition
if ($student_enrollment_no) {
    // Fetch student data
    $student_sql = "SELECT * FROM `students` WHERE `enrollment_no` = '$student_enrollment_no'";
    $student_result = mysqli_query($conn, $student_sql);
    $student = mysqli_fetch_assoc($student_result);

    if (!$student) {
        $_SESSION['msg'] = '<div class="alert alert-danger">Student not found.</div>';
        header("location: admin_view_students.php");
        exit();
    }

    $student_semester = $student['semester'];
    $student_batch = $student['batch'];

    // Total possible sessions
    $total_sessions_sql = "SELECT COUNT(DISTINCT `session_date`) as total_possible_sessions
                          FROM `daily_attendance_sessions`
                          WHERE `semester` = '$student_semester' AND `batch` = '$student_batch'";
    $total_sessions_result = mysqli_query($conn, $total_sessions_sql);
    $total_days = mysqli_fetch_assoc($total_sessions_result)['total_possible_sessions'];

    // Present days
    $present_sql = "SELECT COUNT(DISTINCT DATE(`attendance_time`)) as present_count
                    FROM `attendance`
                    WHERE `enrollment_no` = '$student_enrollment_no'";
    $present_result = mysqli_query($conn, $present_sql);
    $present_days = mysqli_fetch_assoc($present_result)['present_count'];

    $absent_days = $total_days - $present_days;
    if ($absent_days < 0) $absent_days = 0;

    // Monthly attendance data for chart
    $monthly_attendance_sql = "SELECT
        DATE_FORMAT(das.session_date, '%Y-%m') AS month,
        COUNT(DISTINCT das.session_date) AS total_sessions_in_month,
        COUNT(DISTINCT a.attendance_time) AS present_days_in_month
    FROM daily_attendance_sessions das
    LEFT JOIN attendance a ON das.session_id = a.session_id AND a.enrollment_no = '$student_enrollment_no'
    WHERE das.semester = '$student_semester' AND das.batch = '$student_batch'
    GROUP BY month
    ORDER BY month";

    $monthly_attendance_result = mysqli_query($conn, $monthly_attendance_sql);
    
    $months = [];
    $present_data = [];
    $absent_data = [];

    if ($monthly_attendance_result) {
        while ($row = mysqli_fetch_assoc($monthly_attendance_result)) {
            $months[] = date('F Y', strtotime($row['month']));
            $present_data[] = (int)$row['present_days_in_month'];
            $absent_data[] = (int)$row['total_sessions_in_month'] - (int)$row['present_days_in_month'];
        }
    }

    // Recent attendance records
    $recent_sql = "SELECT
        das.session_date AS date,
        DAYNAME(das.session_date) AS day_of_week,
        a.attendance_time AS scanned_at,
        CASE WHEN a.attendance_time IS NOT NULL THEN 'Present' ELSE 'Absent' END AS status_text
    FROM `daily_attendance_sessions` das
    LEFT JOIN `attendance` a ON das.session_id = a.session_id AND a.enrollment_no = '$student_enrollment_no'
    WHERE das.semester = '$student_semester' AND das.batch = '$student_batch'
    ORDER BY das.session_date DESC
    LIMIT 10";

    $recent_result = mysqli_query($conn, $recent_sql);
}
?>

<div class="container pt-3 px-4 m-0">
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-1 rounded-4" style="background: #eee;">
            <li class="breadcrumb-item"><a href="admin_attendance_control.php">Home</a></li>
            <li class="breadcrumb-item"><a href="admin_view_students.php">View Students</a></li>
            <li class="breadcrumb-item active">Attendance Report</li>
        </ol>
    </nav>
</div>

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <?php if (!$student_enrollment_no) : ?>
                    <div class="alert alert-danger" role="alert">
                        No student enrollment number provided for report. Please select a student from the <a href="admin_view_students.php">View Students</a> page.
                    </div>
                <?php else : ?>
                    <h4 class="mb-4">Attendance Report for <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student_enrollment_no); ?>)</h4>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Total Possible Sessions</h5>
                                    <p class="card-text display-6">
                                        <?php echo $total_days; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Present Days</h5>
                                    <p class="card-text display-6">
                                        <?php echo $present_days; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-danger mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Absent Days</h5>
                                    <p class="card-text display-6">
                                        <?php echo $absent_days; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="bg-white p-4 rounded">
                                <h5 class="mb-4">Monthly Attendance Overview</h5>
                                <canvas id="attendanceChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-4">Recent Attendance Records</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Status</th>
                                            <th>Scanned At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($recent_result) > 0) :
                                            while($record = mysqli_fetch_assoc($recent_result)):
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['date']); ?></td>
                                            <td><?php echo htmlspecialchars($record['day_of_week']); ?></td>
                                            <td>
                                                <?php if($record['status_text'] == 'Present'): ?>
                                                    <span class="badge bg-success">Present</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Absent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $record['scanned_at'] ? date('h:i:s A', strtotime($record['scanned_at'])) : 'N/A'; ?></td>
                                        </tr>
                                        <?php
                                            endwhile;
                                        else :
                                        ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No recent attendance records found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: 'Present Days',
                    data: <?php echo json_encode($present_data); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Absent Days',
                    data: <?php echo json_encode($absent_data); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<?php
require('footer.php');
?>