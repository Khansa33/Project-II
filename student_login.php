<?php
// student_login.php
session_start();
require('conn.php');

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enrollment_no = mysqli_real_escape_string($conn, $_POST['enrollment_no']);

    $sql = "SELECT `enrollment_no`, `name` FROM `students` WHERE `enrollment_no` = '$enrollment_no'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $student = mysqli_fetch_assoc($result);
        $_SESSION['loggedin'] = true;
        $_SESSION['usertype'] = 'STUDENT';
        $_SESSION['student_enrollment_no'] = $student['enrollment_no'];
        $_SESSION['student_name'] = $student['name'];

        $_SESSION['msg'] = 'Welcome, ' . htmlspecialchars($student['name']) . '!';
        // Redirect to the new student dashboard
        header("location: student_dashboard.php"); 
        exit();
    } else {
        $error_message = 'Invalid enrollment number.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            background: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4">Student Login</h2>
        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg']) && !empty($_SESSION['msg'])) : ?>
            <div class="alert alert-info" role="alert">
                <?php echo $_SESSION['msg']; ?>
            </div>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>
        <form action="student_login.php" method="post">
            <div class="mb-3">
                <label for="enrollment_no" class="form-label">Enrollment Number</label>
                <input type="text" class="form-control" id="enrollment_no" name="enrollment_no" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <div class="text-center mt-3">
            <a href="login.php">Admin Login</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>