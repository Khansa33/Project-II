<?php
require('header.php');
require('conn.php');

if ($_SESSION['usertype'] != 'ADMIN') {
    $_SESSION['msg'] = 'Access denied. You must be an admin to edit student details.';
    header("location: login.php");
    exit();
}

$semesters = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth'];
$batches = ['BBS', 'BBM', 'BCA'];

$enrollment_no_to_edit = isset($_GET['enrollment_no']) ? mysqli_real_escape_string($conn, $_GET['enrollment_no']) : null;
$current_student_data = null; 
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    
    $original_enrollment_no = mysqli_real_escape_string($conn, $_POST['original_enrollment_no']); 
    $new_enrollment_no = mysqli_real_escape_string($conn, $_POST['enrollment_no']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $batch = mysqli_real_escape_string($conn, $_POST['batch']);

    if (empty($new_enrollment_no) || empty($name) || empty($semester) || empty($batch)) {
        $errors[] = "All fields are required.";
    }
    
    if ($new_enrollment_no !== $original_enrollment_no) {
        $check_sql = "SELECT `enrollment_no` FROM `students` WHERE `enrollment_no` = '$new_enrollment_no'";
        $check_result = mysqli_query($conn, $check_sql);
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Error: New Enrollment Number already exists. Please choose a different one.";
        }
    }

    if (empty($errors)) {
        $update_sql = "UPDATE `students` SET
                        `enrollment_no` = '$new_enrollment_no',
                        `name` = '$name',
                        `semester` = '$semester',
                        `batch` = '$batch'
                        WHERE `enrollment_no` = '$original_enrollment_no'";

        if (mysqli_query($conn, $update_sql)) {
            $_SESSION['msg'] = '<div class="alert alert-success">Student details updated successfully!</div>';
            
            header("location: admin_view_students.php");
            exit();
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger">Error updating student details: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        $_SESSION['msg'] = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    }
    
    $current_student_data = $_POST;
}


if ($enrollment_no_to_edit && $current_student_data === null) { 
    $sql = "SELECT `enrollment_no`, `name`, `semester`, `batch` FROM `students` WHERE `enrollment_no` = '$enrollment_no_to_edit'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $current_student_data = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['msg'] = '<div class="alert alert-warning">Student not found or invalid enrollment number provided.</div>';
        header("location: admin_view_students.php");
        exit();
    }
} else if ($enrollment_no_to_edit === null && $current_student_data === null) {
    
    $_SESSION['msg'] = '<div class="alert alert-warning">No student selected for editing.</div>';
    header("location: admin_view_students.php");
    exit();
}

?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_attendance_control.php">Admin Panel</a></li>
                <li class="breadcrumb-item"><a href="admin_view_students.php">View Students</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Student Details</li>
            </ol>
        </nav>
        <h2 class="mb-4">Edit Student Details: <?php echo htmlspecialchars($current_student_data['name'] ?? ''); ?> (<?php echo htmlspecialchars($current_student_data['enrollment_no'] ?? ''); ?>)</h2>

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
                <form action="admin_edit_student.php?enrollment_no=<?php echo urlencode($enrollment_no_to_edit); ?>" method="post">
                    <input type="hidden" name="original_enrollment_no" value="<?php echo htmlspecialchars($current_student_data['enrollment_no'] ?? ''); ?>">

                    <div class="mb-3">
                        <label for="enrollment_no" class="form-label">Enrollment No.</label>
                        <input type="text" class="form-control" id="enrollment_no" name="enrollment_no"
                               value="<?php echo htmlspecialchars($current_student_data['enrollment_no'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?php echo htmlspecialchars($current_student_data['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $s_num) : ?>
                                <option value="<?php echo htmlspecialchars($s_num); ?>"
                                    <?php echo (isset($current_student_data['semester']) && $current_student_data['semester'] == $s_num) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s_num); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="batch" class="form-label">Batch</label>
                        <select class="form-select" id="batch" name="batch" required>
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $batch_option) : ?>
                                <option value="<?php echo htmlspecialchars($batch_option); ?>"
                                    <?php echo (isset($current_student_data['batch']) && $current_student_data['batch'] == $batch_option) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($batch_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="update_student" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Student</button>
                    <a href="admin_view_students.php" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>