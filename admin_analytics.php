<?php
// admin_analytics.php - Attendance Analysis Dashboard
require('header.php');
require('conn.php');

// Security check: Ensure only admins can access this page
if ($_SESSION['usertype'] != 'ADMIN') {
    $_SESSION['msg'] = 'Access denied. You must be an admin.';
    header("location: login.php");
    exit();
}

$low_attendance_threshold = 75; // Set the threshold for low attendance

// --- Algorithm 1: Student-wise Attendance Percentage ---
$student_percentages = [];
$total_sessions_sql = "SELECT COUNT(session_id) as total_sessions FROM daily_attendance_sessions";
$total_sessions_result = mysqli_query($conn, $total_sessions_sql);
$total_sessions_row = mysqli_fetch_assoc($total_sessions_result);
$total_sessions = $total_sessions_row['total_sessions'];

if ($total_sessions > 0) {
    $student_attendance_sql = "
        SELECT 
            s.enrollment_no, 
            s.name AS student_name, 
            COUNT(a.id) as attended_sessions
        FROM students s
        LEFT JOIN attendance a ON s.enrollment_no = a.enrollment_no
        GROUP BY s.enrollment_no
        ORDER BY attended_sessions DESC
    ";
    $student_attendance_result = mysqli_query($conn, $student_attendance_sql);

    while ($row = mysqli_fetch_assoc($student_attendance_result)) {
        $percentage = ($row['attended_sessions'] / $total_sessions) * 100;
        $student_percentages[] = [
            'enrollment_no' => $row['enrollment_no'],
            'student_name' => $row['student_name'],
            'attended_sessions' => $row['attended_sessions'],
            'total_sessions' => $total_sessions,
            'percentage' => number_format($percentage, 2)
        ];
    }
}

// --- Algorithm 2: Attendance by Day of the Week ---
$day_attendance = [];
$day_attendance_sql = "
    SELECT 
        DAYNAME(das.start_time) as day_of_week, 
        COUNT(a.id) as total_attendance,
        COUNT(DISTINCT das.session_id) as total_sessions_on_day
    FROM daily_attendance_sessions das
    LEFT JOIN attendance a ON das.session_id = a.session_id
    GROUP BY day_of_week
    ORDER BY FIELD(day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
";
$day_attendance_result = mysqli_query($conn, $day_attendance_sql);

while ($row = mysqli_fetch_assoc($day_attendance_result)) {
    $day_attendance[] = $row;
}
?>

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h4 class="mb-4">Attendance Analysis Dashboard</h4>
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Students with Low Attendance (Below <?php echo $low_attendance_threshold; ?>%)</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if (empty($student_percentages)): ?>
                                        <li class="list-group-item text-muted">No student data available.</li>
                                    <?php else: ?>
                                        <?php $low_attendance_students_exist = false; ?>
                                        <?php foreach ($student_percentages as $student): ?>
                                            <?php if ($student['percentage'] < $low_attendance_threshold): ?>
                                                <?php $low_attendance_students_exist = true; ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?php echo htmlspecialchars($student['student_name']); ?> (<?php echo htmlspecialchars($student['enrollment_no']); ?>)
                                                    <span class="badge bg-danger rounded-pill"><?php echo $student['percentage']; ?>%</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if (!$low_attendance_students_exist): ?>
                                            <li class="list-group-item text-muted">All students have good attendance.</li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Attendance by Day of the Week</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Total Sessions</th>
                                            <th>Total Attendance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($day_attendance)): ?>
                                            <tr>
                                                <td colspan="3" class="text-muted text-center">No attendance data available.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($day_attendance as $day): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($day['day_of_week']); ?></td>
                                                    <td><?php echo htmlspecialchars($day['total_sessions_on_day']); ?></td>
                                                    <td><?php echo htmlspecialchars($day['total_attendance']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>