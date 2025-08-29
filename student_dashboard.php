<?php
// student_dashboard.php - Student's main dashboard with profile and options
require('header.php');
require('conn.php');

// Ensure student is logged in
if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'STUDENT') {
    $_SESSION['msg'] = 'Please log in as a student to access this page.';
    header("location: student_login.php");
    exit();
}

$enrollment_no = $_SESSION['student_enrollment_no'];
$student_name = $_SESSION['student_name'];
$student_details = null;

// Fetch full student details
$sql = "SELECT `semester`, `batch` FROM `students` WHERE `enrollment_no` = '$enrollment_no'";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $student_details = mysqli_fetch_assoc($result);
}
?>

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4 text-center">
                <h4 class="mb-4">Student Dashboard</h4>
                <?php if (isset($_SESSION['msg']) && !empty($_SESSION['msg'])) : ?>
                    <div class="alert alert-info" role="alert">
                        <?php echo $_SESSION['msg']; ?>
                    </div>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>

                <?php if ($student_details) : ?>
                    <div class="card my-4 mx-auto" style="max-width: 500px;">
                        <div class="card-header bg-primary text-white">
                            Student Profile
                        </div>
                        <div class="card-body text-start">
                            <p class="card-text"><strong>Name:</strong> <?php echo htmlspecialchars($student_name); ?></p>
                            <p class="card-text"><strong>Enrollment No:</strong> <?php echo htmlspecialchars($enrollment_no); ?></p>
                            <p class="card-text"><strong>Semester:</strong> <?php echo htmlspecialchars($student_details['semester']); ?></p>
                            <p class="card-text"><strong>Batch:</strong> <?php echo htmlspecialchars($student_details['batch']); ?></p>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center my-4">
                        
                        <a href="student_view_qr.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-id-card me-2"></i>My QR
                        </a>
                        <a href="student_report.php" class="btn btn-info">
                            <i class="fas fa-chart-bar me-2"></i>View Attendance Report
                        </a>
                    </div>
                <?php else : ?>
                    <div class="alert alert-danger" role="alert">
                        Student details not found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>