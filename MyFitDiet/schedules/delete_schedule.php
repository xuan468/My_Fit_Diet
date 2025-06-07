<?php
session_start();
include '../general/dbconn.php';

$data = json_decode(file_get_contents("php://input"), true);
$workout_id = $data['workout_id'];

$checkQuery = "SELECT workout_name FROM workoutstbl WHERE workout_id=?";
$checkStmt = $connection->prepare($checkQuery);
$checkStmt->bind_param("i", $workout_id);
$checkStmt->execute();
$result = $checkStmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Workout not found"]);
    exit;
}
$row = $result->fetch_assoc();
$workout_name = $row['workout_name'];

$deleteQuery = "DELETE FROM workoutstbl WHERE workout_id=?";
$deleteStmt = $connection->prepare($deleteQuery);
$deleteStmt->bind_param("i", $workout_id);
$deleteStmt->execute();

$checkRemainingQuery = "SELECT COUNT(*) as count FROM workoutstbl WHERE workout_name=?";
$checkRemainingStmt = $connection->prepare($checkRemainingQuery);
$checkRemainingStmt->bind_param("s", $workout_name);
$checkRemainingStmt->execute();
$remainingResult = $checkRemainingStmt->get_result();
$remainingRow = $remainingResult->fetch_assoc();

if ($remainingRow['count'] == 0) {
    $updateCategoryQuery = "UPDATE workout_categoriestbl SET category_description='No registered workouts' WHERE category_name=?";
    $updateCategoryStmt = $connection->prepare($updateCategoryQuery);
    $updateCategoryStmt->bind_param("s", $workout_name);
    $updateCategoryStmt->execute();
}

$deleteStmt->close();
$checkStmt->close();
$checkRemainingStmt->close();
$connection->close();

echo json_encode(["status" => "success", "message" => "Schedule deleted successfully!", "workout_name" => $workout_name]);
?>
