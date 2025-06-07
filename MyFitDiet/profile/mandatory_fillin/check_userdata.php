<?php
ob_start(); 
include '../general/dbconn.php';

// Ensure the session contains UserID
$UserID = $_SESSION['userid'] ?? null;

if (!$UserID) {
    echo "<script>alert('Session expired. Please log in again.');</script>";
    echo "<script>window.location.href='/MyFitDiet/login/login.php';</script>";
    exit();
}

// Prevent re-running the script on the homepage
if (!isset($_SESSION['profile_check_done'])) {

    // Get all column names from `user` table
    $sqlColumns = "SHOW COLUMNS FROM user";
    $resultColumns = mysqli_query($connection, $sqlColumns);

    if (!$resultColumns) {
        die("Error fetching column names: " . mysqli_error($connection));
    }

    $columns = [];
    while ($row = mysqli_fetch_assoc($resultColumns)) {
        $columns[] = $row['Field'];
    }

    // Fetch user details securely
    $sqlUser = "SELECT * FROM user WHERE userID = ?";
    $stmtUser = $connection->prepare($sqlUser);
    $stmtUser->bind_param("i", $UserID);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $userData = $resultUser->fetch_assoc();
    $stmtUser->close();

    // If user data is not found, redirect to login
    if (!$userData) {
        echo "<script>alert('Error: User not found!');</script>";
        echo "<script>window.location.href='/MyFitDiet/login/login.php';</script>";
        exit();
    }

    // Check for NULL or empty values in user data
    foreach ($columns as $column) {
        if ($column !== 'userID' && (!isset($userData[$column]) || trim($userData[$column]) === '')) {
            // Redirect to mandatory fill-in page
            echo "<script>window.location.href='/MyFitDiet/profile/mandatory_fillin/member_fillin.php';</script>";
            exit();
        }
    }

    // Check height and weight
    $sqlHeightWeight = "SELECT height, weight FROM height_weight WHERE userID = ? ORDER BY update_time DESC LIMIT 1";
    $stmtHeightWeight = $connection->prepare($sqlHeightWeight);
    $stmtHeightWeight->bind_param("i", $UserID);
    $stmtHeightWeight->execute();
    $result = $stmtHeightWeight->get_result();
    $heightWeightData = $result->fetch_assoc();
    $stmtHeightWeight->close();

    if (!$heightWeightData || empty($heightWeightData['height']) || empty($heightWeightData['weight']) || $heightWeightData['height'] == 0 || $heightWeightData['weight'] == 0) {
        echo "<script>window.location.href='/MyFitDiet/profile/mandatory_fillin/member_fillin.php';</script>";
        exit();
    }

    // Mark that the script has run once
    $_SESSION['profile_check_done'] = true;
}
ob_end_flush();
?>
