<?php
// header.php - Common header with session start and Bootstrap/FontAwesome
session_start();

// Initialize $_SESSION['msg'] if not set, or clear it after display
if (!isset($_SESSION['msg'])) {
    $_SESSION['msg'] = '';
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$user_type = $_SESSION['usertype'] ?? null;
$username = $_SESSION['username'] ?? ($_SESSION['student_enrollment_no'] ?? 'Guest');

// Redirect if not logged in and trying to access restricted page
// This header is now more general; specific pages will enforce their usertype
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'student_login.php']; // Pages accessible without login

if (!$is_logged_in && !in_array($current_page, $public_pages)) {
    $_SESSION['msg'] = 'Please log in to access this page.';
    header("location: login.php"); // Default to admin login if not specified
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <style>
        body { padding-top: 56px; } /* Adjust for fixed navbar */
        .msg-alert {
            position: fixed;
            top: 56px; /* Below navbar */
            left: 0;
            width: 100%;
            z-index: 1050;
            animation: fadeOut 5s forwards; /* Message fades out after 5 seconds */
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo ($user_type == 'ADMIN' ? 'admin_attendance_control.php' : ($is_logged_in ? 'student_scan_qr.php' : 'login.php')); ?>">
                Attendance System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($user_type == 'ADMIN') : ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'admin_attendance_control.php' ? 'active' : ''); ?>" href="admin_attendance_control.php">Admin Panel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'admin_add_student.php' ? 'active' : ''); ?>" href="admin_add_student.php">Add Student Details</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'admin_view_students.php' ? 'active' : ''); ?>" href="admin_view_students.php">View Students</a>
                        </li>
                    <?php elseif ($is_logged_in) : // Student logged in ?>
                         <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'student_dashboard.php' ? 'active' : ''); ?>" href="student_dashboard.php">Student Dashboard</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($is_logged_in) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars($username); ?>)</a>
                        </li>
                    <?php else : ?>
                         <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'login.php' ? 'active' : ''); ?>" href="login.php">Admin Login</a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'student_login.php' ? 'active' : ''); ?>" href="student_login.php">Student Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <?php if (!empty($_SESSION['msg'])) : ?>
            <div class="alert alert-info msg-alert alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['msg']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php $_SESSION['msg'] = ''; // Clear message after display ?>
        <?php endif; ?>