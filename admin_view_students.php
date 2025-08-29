<?php
// admin_view_students.php - Admin interface to view all student details
require('header.php');
require('conn.php');

// Ensure only admins can access this page
if ($_SESSION['usertype'] != 'ADMIN') {
    $_SESSION['msg'] = 'Access denied. You must be an admin to view this page.';
    header("location: login.php");
    exit();
}

// Fetch all students from the database
$sql = "SELECT `id`, `enrollment_no`, `name`, `semester`, `batch`, `created_at` FROM `students` ORDER BY `name` ASC";
$result = mysqli_query($conn, $sql);
$students = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
} else {
    // Handle query error
    $_SESSION['msg'] = '<div class="alert alert-danger">Error fetching students: ' . mysqli_error($conn) . '</div>';
}
?>

<div class="row">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_attendance_control.php">Admin Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Students</li>
            </ol>
        </nav>
        <h2 class="mb-4">View All Student Details (Total: <?php echo count($students); ?>)</h2>

        <?php
        // Display session messages (success/error from add/edit/delete operations)
        if (isset($_SESSION['msg'])) {
            echo $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        ?>

        <div class="card mb-4">
            <div class="card-header">
                Student List
            </div>
            <div class="card-body">
                <?php if (empty($students)) : ?>
                    <p class="text-muted">No student records found in the database.</p>
                    <a href="admin_add_student.php" class="btn btn-success"><i class="fas fa-user-plus me-2"></i>Add First Student</a>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Enrollment No.</th>
                                    <th>Name</th>
                                    <th>Semester</th>
                                    <th>Batch</th>
                                    <th>Added On</th>
                                    <th style="width: 250px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['enrollment_no']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($student['batch']); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($student['created_at'])); ?></td>
                                        <td>
                                            <a href="admin_edit_student.php?enrollment_no=<?php echo urlencode($student['enrollment_no']); ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="admin_delete_student.php?enrollment_no=<?php echo urlencode($student['enrollment_no']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                                            <a href="admin_student_report.php?enrollment_no=<?php echo urlencode($student['enrollment_no']); ?>" class="btn btn-sm btn-info">Report</a>
                                            <a href="admin_generate_qr_student.php?enrollment_no=<?php echo urlencode($student['enrollment_no']); ?>" class="btn btn-sm btn-success">Generate QR</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>