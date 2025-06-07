<?php
session_start();
include '../general/dbconn.php';

if (!isset($_SESSION['userid'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$userid = $_SESSION['userid'];
$postid = intval($_POST['postid']);
$action = $_POST['action'];
$date_time = date('Y-m-d H:i:s'); // Correct date time format

header('Content-Type: application/json'); //Make sure you return JSON

if ($action === "like") {
    // add like
    $sql = "INSERT INTO post_likes (userid, postid, liked_at) VALUES ($userid, $postid, '$date_time')";
} elseif ($action === "unlike") {
    // cancel like
    $sql = "DELETE FROM post_likes WHERE userid = $userid AND postid = $postid";
} else {
    echo json_encode(["success" => false, "message" => "Invalid action"]);
    exit;
}

if (mysqli_query($connection, $sql)) {
    // Get the updated number of likes
    $sql_count = "SELECT COUNT(*) AS like_count FROM post_likes WHERE postid = $postid";
    $result = mysqli_query($connection, $sql_count);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode(["success" => true, "like_count" => $row['like_count']]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to get like count"]);
    }
} else {
    // Output specific SQL error information for debugging
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . mysqli_error($connection),
        "sql" => $sql // Used for debugging to view the SQL actually executed
    ]);
}
?>