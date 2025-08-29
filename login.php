<?php
// login.php
session_start();
require('conn.php');

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT `id`, `username`, `password`, `usertype` FROM `users` WHERE `username` = '$username'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Assuming the password is 'adminpass' in plain text as per your previous code
        if ($password === 'adminpass') {
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['usertype'] = $user['usertype'];
            
            // Correct redirection for an ADMIN user
            if ($user['usertype'] == 'ADMIN') {
                header("location: admin_attendance_control.php");
                exit();
            }

            // Fallback for other user types
            header("location: index.php");
            exit();

        } else {
            $error_message = 'Invalid username or password.';
        }
    } else {
        $error_message = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
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
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4">Admin Login</h2>
        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg']) && !empty($_SESSION['msg'])) : ?>
            <div class="alert alert-info" role="alert">
                <?php echo $_SESSION['msg']; ?>
            </div>
            <?php unset($_SESSION['msg']); // Clear message after display ?>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <div class="text-center mt-3">
            <a href="student_login.php">Student Login</a>
        </div>
    </div>
</body>
</html>