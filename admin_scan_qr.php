<?php
// admin_scan_qr.php - Admin's QR Scan and Attendance Marking Interface
require('header.php');
require('conn.php');

if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'ADMIN') {
    $_SESSION['msg'] = 'Access denied. You must be an admin.';
    header("location: login.php");
    exit();
}

$message = '';
$scanned_enrollment_no = '';
$current_date = date('Y-m-d');
$session_id = null;

// Fetch unique semesters and batches for the form
$semesters = [];
$semesters_sql = "SELECT DISTINCT `semester` FROM `students` ORDER BY `semester`";
$semesters_result = mysqli_query($conn, $semesters_sql);
if ($semesters_result) {
    while ($row = mysqli_fetch_assoc($semesters_result)) {
        $semesters[] = $row['semester'];
    }
}

$batches = [];
$batches_sql = "SELECT DISTINCT `batch` FROM `students` ORDER BY `batch`";
$batches_result = mysqli_query($conn, $batches_sql);
if ($batches_result) {
    while ($row = mysqli_fetch_assoc($batches_result)) {
        $batches[] = $row['batch'];
    }
}

// Function to automatically create a new session if one does not exist for the day
function getOrCreateSession($conn, $semester, $batch, $admin_id) {
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');

    // STEP 1: Check if today's session exists
    $check_sql = "SELECT `session_id` 
                     FROM `daily_attendance_sessions` 
                     WHERE `session_date` = ? 
                       AND `semester` = ? 
                       AND `batch` = ? 
                       AND `status` = 'active'";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, 'sss', $current_date, $semester, $batch);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $existing_session_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($existing_session_id) {
        return $existing_session_id;
    }

    // STEP 2: Create a new session
    $insert_sql = "INSERT INTO `daily_attendance_sessions`
                     (`session_date`, `start_time`, `end_time`, `semester`, `batch`, `admin_id`, `status`)
                     VALUES (?, ?, '18:00:00', ?, ?, ?, 'active')";
    $stmt_insert = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt_insert, 'ssssi', $current_date, $current_time, $semester, $batch, $admin_id);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        $new_session_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_insert);
        return $new_session_id;
    } else {
        mysqli_stmt_close($stmt_insert);
        return null;
    }
}

// Get semester and batch from GET request, if available
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : null;
$selected_batch = isset($_GET['batch']) ? $_GET['batch'] : null;

// If both are set, get or create a session and store its ID
if ($selected_semester && $selected_batch) {
    $session_id = getOrCreateSession($conn, $selected_semester, $selected_batch, $_SESSION['user_id']);
    if (!$session_id) {
        $message = '<div class="alert alert-danger">Error: Could not start or find an active session.</div>';
    }
}

// --- CORRECTED LOGIC FOR MARKING ATTENDANCE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $scanned_enrollment_no = trim($_POST['enrollment_no']);
    $post_semester = $_POST['semester'];
    $post_batch = $_POST['batch'];

    if (empty($scanned_enrollment_no) || empty($post_semester) || empty($post_batch)) {
        $_SESSION['msg'] = '<div class="alert alert-danger">Invalid form data. Please rescan.</div>';
    } else {
        // Step 1: Check if the student exists and retrieve their actual class details
        $student_sql = "SELECT `semester`, `batch` FROM `students` WHERE `enrollment_no` = ?";
        $stmt_student = mysqli_prepare($conn, $student_sql);
        mysqli_stmt_bind_param($stmt_student, 's', $scanned_enrollment_no);
        mysqli_stmt_execute($stmt_student);
        mysqli_stmt_bind_result($stmt_student, $student_semester, $student_batch);
        mysqli_stmt_fetch($stmt_student);
        mysqli_stmt_close($stmt_student);

        if (empty($student_semester) || empty($student_batch)) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Invalid student enrollment number.</div>';
        } elseif ($student_semester !== $post_semester || $student_batch !== $post_batch) {
            // Step 2: VULNERABILITY FIXED - Validate student's class against the selected class
            $_SESSION['msg'] = '<div class="alert alert-danger">Student ' . htmlspecialchars($scanned_enrollment_no) . ' does not belong to this class (' . htmlspecialchars($post_semester) . ', ' . htmlspecialchars($post_batch) . ').</div>';
        } else {
            // Step 3: Student is valid for this session, get or create session
            $current_session_id = getOrCreateSession($conn, $post_semester, $post_batch, $_SESSION['user_id']);

            if (!$current_session_id) {
                $_SESSION['msg'] = '<div class="alert alert-danger">Error: No active session found.</div>';
            } else {
                // Step 4: Check if attendance already exists
                $check_sql = "SELECT 1 FROM attendance WHERE session_id = ? AND enrollment_no = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("is", $current_session_id, $scanned_enrollment_no);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    $_SESSION['msg'] = '<div class="alert alert-warning">Attendance already marked for Enrollment No: ' . htmlspecialchars($scanned_enrollment_no) . ' today.</div>';
                } else {
                    // Step 5: Insert attendance
                    $insert_sql = "INSERT INTO attendance (session_id, enrollment_no, attendance_time) VALUES (?, ?, NOW())";
                    $stmt_insert = $conn->prepare($insert_sql);
                    $stmt_insert->bind_param("is", $current_session_id, $scanned_enrollment_no);

                    if ($stmt_insert->execute()) {
                        $_SESSION['msg'] = '<div class="alert alert-success">Attendance marked for Enrollment No: ' . htmlspecialchars($scanned_enrollment_no) . '</div>';
                    } else {
                        $_SESSION['msg'] = '<div class="alert alert-danger">Error marking attendance: ' . $conn->error . '</div>';
                    }
                    $stmt_insert->close();
                }
                $stmt_check->close();
            }
        }
    }
    // Redirect to prevent form resubmission
    header("location: admin_scan_qr.php?semester=" . urlencode($post_semester) . "&batch=" . urlencode($post_batch));
    exit();
}

// Fetch session details from GET parameter
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>

<div class="container-fluid pt-4 px-4">
    <div class="row bg-light rounded mx-0">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h4 class="mb-4">Scan Student QR & Mark Attendance</h4>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                Select Class to Scan
                            </div>
                            <div class="card-body">
                                <form id="session-select-form">
                                    <div class="mb-3">
                                        <label for="semester_select" class="form-label">Semester</label>
                                        <select class="form-select" id="semester_select" name="semester" required onchange="this.form.submit()">
                                            <option value="">Select Semester</option>
                                            <?php foreach ($semesters as $s) : ?>
                                                <option value="<?php echo htmlspecialchars($s); ?>" <?php if(isset($_GET['semester']) && $_GET['semester'] == $s) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($s); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="batch_select" class="form-label">Batch</label>
                                        <select class="form-select" id="batch_select" name="batch" required onchange="this.form.submit()">
                                            <option value="">Select Batch</option>
                                            <?php foreach ($batches as $b) : ?>
                                                <option value="<?php echo htmlspecialchars($b); ?>" <?php if(isset($_GET['batch']) && $_GET['batch'] == $b) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($b); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>
                                <hr>
                                <div class="text-center">
                                    <video id="preview" class="mb-3 w-100" style="max-height: 300px;"></video>
                                    <div class="my-3">
                                        <label for="qr-image-upload" class="btn btn-secondary w-100">
                                            <i class="fas fa-upload me-2"></i>Upload QR Code Image
                                        </label>
                                        <input type="file" id="qr-image-upload" accept="image/*" style="display: none;">
                                    </div>
                                    <div id="scan-feedback" class="mt-3">
                                        <?php if ($selected_semester && $selected_batch) : ?>
                                            <div class="alert alert-info">Scanning for Semester: <strong><?php echo htmlspecialchars($selected_semester); ?></strong>, Batch: <strong><?php echo htmlspecialchars($selected_batch); ?></strong>.</div>
                                        <?php else : ?>
                                            <div class="alert alert-warning">Please select a class to begin scanning.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                Mark Attendance
                            </div>
                            <div class="card-body">
                                <?php echo $message; ?>
                                <form id="mark-attendance-form" action="admin_scan_qr.php" method="POST">
                                    <div class="mb-3">
                                        <label for="enrollment_no" class="form-label">Scanned Enrollment Number</label>
                                        <input type="text" class="form-control" id="enrollment_no" name="enrollment_no" readonly required value="<?php echo htmlspecialchars($scanned_enrollment_no); ?>">
                                    </div>
                                    <input type="hidden" name="semester" id="hidden_semester" value="<?php echo htmlspecialchars($selected_semester); ?>">
                                    <input type="hidden" name="batch" id="hidden_batch" value="<?php echo htmlspecialchars($selected_batch); ?>">
                                    <button type="submit" name="mark_attendance" class="btn btn-success w-100" id="mark-btn" <?php echo ($selected_semester && $selected_batch) ? '' : 'disabled'; ?>>
                                        <i class="fas fa-check-circle me-2"></i>Mark Present
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://rawgit.com/schmich/instascan-js/master/instascan.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.0.0/dist/jsQR.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const preview = document.getElementById('preview');
    const scanFeedback = document.getElementById('scan-feedback');
    const enrollmentNoInput = document.getElementById('enrollment_no');
    const hiddenSemesterInput = document.getElementById('hidden_semester');
    const hiddenBatchInput = document.getElementById('hidden_batch');
    const markButton = document.getElementById('mark-btn');
    const semesterSelect = document.getElementById('semester_select');
    const batchSelect = document.getElementById('batch_select');
    const fileInput = document.getElementById('qr-image-upload');
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    let scanner = null;
    let scannerActive = false;

    function handleScan(content) {
        if (!content) return;

        if (!hiddenSemesterInput.value || !hiddenBatchInput.value) {
            scanFeedback.innerHTML = `<div class="alert alert-warning">Please select a class first.</div>`;
            return;
        }

        enrollmentNoInput.value = content;
        scanFeedback.innerHTML = `<div class="alert alert-success">Scanned: <strong>${content}</strong>. Click 'Mark Present' to confirm.</div>`;
        markButton.disabled = false;
        
        if (scanner) {
            scanner.stop();
            scannerActive = false;
        }
    }

    function initWebcamScanner() {
        // Only start if a class is selected
        if (!semesterSelect.value || !batchSelect.value) {
            scanFeedback.innerHTML = `<div class="alert alert-warning">Please select a class to begin scanning.</div>`;
            return;
        }

        if (scannerActive) return;

        scanner = new Instascan.Scanner({ video: preview, mirror: false });
        
        scanner.addListener('scan', function (content) {
            handleScan(content);
        });

        Instascan.Camera.getCameras().then(function (cameras) {
            if (cameras.length > 0) {
                scanner.start(cameras[0]);
                scannerActive = true;
            } else {
                console.error('No cameras found.');
                scanFeedback.innerHTML = '<div class="alert alert-danger">No camera found. Please try on a device with a camera.</div>';
            }
        }).catch(function (e) {
            console.error(e);
            scanFeedback.innerHTML = '<div class="alert alert-danger">Error accessing camera: ' + e + '. Please grant camera permissions.</div>';
        });
    }

    function stopWebcamScanner() {
        if (scanner && scannerActive) {
            scanner.stop();
            scannerActive = false;
        }
    }

    // Handle file upload
    fileInput.addEventListener('change', function(e) {
        if (!hiddenSemesterInput.value || !hiddenBatchInput.value) {
            scanFeedback.innerHTML = `<div class="alert alert-warning">Please select a class first.</div>`;
            return;
        }

        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            const reader = new FileReader();
            
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    canvas.width = img.width;
                    canvas.height = img.height;
                    context.drawImage(img, 0, 0, img.width, img.height);
                    const imageData = context.getImageData(0, 0, img.width, img.height);
                    
                    const code = jsQR(imageData.data, imageData.width, imageData.height);
                    
                    if (code) {
                        handleScan(code.data);
                    } else {
                        scanFeedback.innerHTML = `<div class="alert alert-danger">No QR code found in the uploaded image.</div>`;
                    }
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Initial check to start or stop the webcam based on URL parameters
    if (hiddenSemesterInput.value && hiddenBatchInput.value) {
        initWebcamScanner();
    } else {
        stopWebcamScanner();
    }
});
</script>