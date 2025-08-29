<?php
// logout.php
session_start();
session_destroy();
$_SESSION['msg'] = 'You have been logged out.';
header("location: login.php");
exit();
?>