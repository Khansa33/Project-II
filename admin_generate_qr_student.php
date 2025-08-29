<?php
// admin_generate_qr_student.php - Generate a permanent QR for a specific student using a public service
require('header.php');
require('conn.php');

if ($_SESSION['usertype'] != 'ADMIN') {
    $_SESSION['msg'] = 'Access denied. You must be an admin.';
    header("location: login.php");
    exit();
}

$enrollment_no = isset($_GET['enrollment_no']) ? mysqli_real_escape_string($conn, $_GET['enrollment_no']) : null;
$student_details = null;
$qr_code_url = '';

if ($enrollment_no) {
    $student_sql = "SELECT `name`, `semester`, `batch` FROM `students` WHERE `enrollment_no` = '$enrollment_no'";
    $student_result = mysqli_query($conn, $student_sql);
    
    if ($student_result && mysqli_num_rows($student_result) > 0) {
        $student_details = mysqli_fetch_assoc($student_result);
        
        // Use a different, reliable public QR code generation service
        $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($enrollment_no);
    }
}
?>

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4 text-center">
                <?php if ($student_details) : ?>
                    <h4 class="mb-4">Permanent QR Code for Student</h4>
                    <p class="text-muted">
                        **<?php echo htmlspecialchars($student_details['name']); ?>** (Enrollment: <?php echo htmlspecialchars($enrollment_no); ?>)
                    </p>
                    <p class="text-muted">
                        Semester: <?php echo htmlspecialchars($student_details['semester']); ?>, Batch: <?php echo htmlspecialchars($student_details['batch']); ?>
                    </p>
                    
                    <div class="p-3 border rounded d-inline-block">
                        <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="Student QR Code" class="img-fluid" style="max-width: 300px;" />
                    </div>
                    
                    <div class="mt-4">
                        <a href="download_qr.php?enrollment_no=<?php echo urlencode($enrollment_no); ?>" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Download QR Code
                        </a>
                        <p class="mt-2 text-muted">This QR code is permanent and can be used for all attendance sessions.</p>
                    </div>
                <?php else : ?>
                    <div class="alert alert-danger" role="alert">
                        Invalid student enrollment number provided.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>