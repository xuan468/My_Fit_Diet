<?php
session_start();
include '../general/dbconn.php';


$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

$query = "
    SELECT avg_calories FROM weekly_nutrition_summary
    WHERE start_date = '$start_date' AND end_date = '$end_date' AND user_id = '$userID'";
$result = mysqli_query($connection, $query);
$row = mysqli_fetch_assoc($result);

echo json_encode(['avg_calories' => $row ? $row['avg_calories'] : 0]);
?>
