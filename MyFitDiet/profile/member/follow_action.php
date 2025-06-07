<?php
session_start();

include "../../general/dbconn.php";

// Assume user is logged in (Replace with session variable)
$loggedInUserID = $_SESSION['userid'];

// Handle follow/unfollow request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profileUserID'])) {
    $profileUserID = $_POST['profileUserID'];

    if ($profileUserID == $loggedInUserID) {
        echo json_encode(['success' => false, 'message' => 'You cannot follow yourself']);
        exit;
    }

    // Check if already following
    $sql = "SELECT * FROM following_user WHERE followID = ? AND followedID = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ii", $loggedInUserID, $profileUserID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Unfollow user
        $sql = "DELETE FROM following_user WHERE followID = ? AND followedID = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ii", $loggedInUserID, $profileUserID);
        $stmt->execute();
        echo json_encode(['success' => true, 'status' => 'unfollowed']);
    } else {
        // Follow user
        $sql = "INSERT INTO following_user (followID, followedID) VALUES (?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ii", $loggedInUserID, $profileUserID);
        $stmt->execute();
        echo json_encode(['success' => true, 'status' => 'followed']);
    }
    exit;
}



