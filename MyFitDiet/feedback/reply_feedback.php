<?php
session_start();
include '../general/dbconn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['userrole'])) {
    $userrole = strtolower($_SESSION['userrole']);

    if ($userrole === 'admin' || $userrole === 'manager') {
        $admin = $_SESSION['userroleid'];
        $feedback_id = mysqli_real_escape_string($connection, $_POST['feedback_id']);
        $reply_text = mysqli_real_escape_string($connection, $_POST['reply_text']);

        $stmt = mysqli_prepare($connection, "INSERT INTO feedback_replies (feedback_id, userID, reply_text) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iis', $feedback_id, $admin, $reply_text);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: feedbacks.php?reply_success=1");
            exit();
        } else {
            echo "Error: " . mysqli_error($connection);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Unauthorized access.";
    }
}

mysqli_close($connection);
?>
