<?php
// admin_delete_student.php - Deletes a student and their attendance history
session_start();
require('conn.php');

// Security check: Ensure only admins can access this page
if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'ADMIN') {
    header("location: login.php");
    exit();
}

$enrollment_no = isset($_GET['enrollment_no']) ? $_GET['enrollment_no'] : null;

if ($enrollment_no) {
    // --- Anti-Foreign Key Algorithm ---
    // First, delete all attendance records associated with the student.
    // This must be done before deleting the student record itself.
    $delete_attendance_sql = "DELETE FROM `attendance` WHERE `enrollment_no` = ?";
    $stmt_attendance = mysqli_prepare($conn, $delete_attendance_sql);
    mysqli_stmt_bind_param($stmt_attendance, 's', $enrollment_no);
    mysqli_stmt_execute($stmt_attendance);
    mysqli_stmt_close($stmt_attendance);
    
    // Now, delete the student from the students table
    $delete_student_sql = "DELETE FROM `students` WHERE `enrollment_no` = ?";
    $stmt_student = mysqli_prepare($conn, $delete_student_sql);
    mysqli_stmt_bind_param($stmt_student, 's', $enrollment_no);
    
    if (mysqli_stmt_execute($stmt_student)) {
        $_SESSION['msg'] = "Student " . htmlspecialchars($enrollment_no) . " and all associated records deleted successfully.";
    } else {
        $_SESSION['msg'] = "Error deleting student: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt_student);
} else {
    $_SESSION['msg'] = "Invalid request: No enrollment number specified.";
}

header("location: admin_student_list.php"); // Redirect to the student list page
exit();
?>