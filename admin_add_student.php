<?php
require('header.php');
require('conn.php');

if ($_SESSION['usertype'] != 'ADMIN') {
    $_SESSION['msg'] = 'Access denied. You must be an admin to add student details.';
    header("location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $enrollment_no = mysqli_real_escape_string($conn, $_POST['enrollment_no']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $batch = mysqli_real_escape_string($conn, $_POST['batch']);

    if (empty($enrollment_no) || empty($name) || empty($semester) || empty($batch)) {
        $_SESSION['msg'] = '<div class="alert alert-danger">All fields are required.</div>';
    } else {
        $check_duplicate_sql = "SELECT `enrollment_no` FROM `students` WHERE `enrollment_no` = '$enrollment_no'";
        $check_duplicate_result = mysqli_query($conn, $check_duplicate_sql);

        if (mysqli_num_rows($check_duplicate_result) > 0) {
            $_SESSION['msg'] = '<div class="alert alert-warning">Student with Enrollment Number ' . htmlspecialchars($enrollment_no) . ' already exists.</div>';
        } else {
            // Generate a unique QR token for the student
            $qr_token = bin2hex(random_bytes(32)); 
            
            $insert_sql = "INSERT INTO `students` (`enrollment_no`, `name`, `semester`, `batch`, `qr_token`) VALUES ('$enrollment_no', '$name', '$semester', '$batch', '$qr_token')";

            if (mysqli_query($conn, $insert_sql)) {
                $_SESSION['msg'] = '<div class="alert alert-success">Student ' . htmlspecialchars($name) . ' (' . htmlspecialchars($enrollment_no) . ') added successfully!</div>';
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger">Error adding student: ' . mysqli_error($conn) . '</div>';
            }
        }
    }
    header("Location: admin_add_student.php");
    exit();
}

$semesters = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth'];
$batches = ['BBS', 'BBM', 'BCA'];
?>

<div class="row">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_attendance_control.php">Admin Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Student</li>
            </ol>
        </nav>
        <h2 class="mb-4">Add New Student Details</h2>

        <?php
        if (isset($_SESSION['msg'])) {
            echo $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        ?>

        <div class="card mb-4">
            <div class="card-header">
                Student Information Form
            </div>
            <div class="card-body">
                <form action="admin_add_student.php" method="post">
                    <div class="mb-3">
                        <label for="enrollment_no" class="form-label">Enrollment Number</label>
                        <input type="text" class="form-control" id="enrollment_no" name="enrollment_no" required maxlength="50" placeholder="e.g., BBS001">
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="name" name="name" required maxlength="255" placeholder="e.g., John Doe">
                    </div>
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $s) : ?>
                                <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="batch" class="form-label">Batch</label>
                        <select class="form-select" id="batch" name="batch" required>
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $b) : ?>
                                <option value="<?php echo htmlspecialchars($b); ?>"><?php echo htmlspecialchars($b); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_student" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Add Student
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>