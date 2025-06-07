<?php
include '../general/dbconn.php';

if (isset($_GET['userid']) && isset($_GET['userrole'])) {
    $userid = $_GET['userid'];
    $userrole = strtolower($_GET['userrole']);

    if ($userrole === "member") {
        $query = "SELECT Profile_pic FROM user WHERE userID = ?";
    } else {
        $query = "SELECT profile_pic FROM staff WHERE userroleID = ?";
    }

    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $userid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        header("Content-Type: image/jpeg");
        echo $row['Profile_pic'] ?? $row['profile_pic']; // 适配两张表的字段
        exit;
    }

    header("Content-Type: image/jpeg");
    readfile('default.jpg');
} else {
    echo "No userID or UserRole provided!";
}
?>
