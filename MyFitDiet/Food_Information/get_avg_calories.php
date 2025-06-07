<?php
include '../general/dbconn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $userID = $_SESSION['userid'];
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];

    $query = "
        SELECT avg_calories FROM weekly_nutrition_summary 
        WHERE user_id = '$userID' 
        AND start_date = '$startDate' 
        AND end_date = '$endDate'
        LIMIT 1";
    $result = mysqli_query($connection, $query);
    $avgCalories = 0;

    if ($row = mysqli_fetch_assoc($result)) {
        $avgCalories = $row['avg_calories'];
    }

    $goalQuery = "
        SELECT target_calories FROM weekly_goals 
        WHERE user_id = '$userID' 
        AND start_date = '$startDate'
        AND end_date = '$endDate'
        LIMIT 1";
    $goalResult = mysqli_query($connection, $goalQuery);

    if ($goalRow = mysqli_fetch_assoc($goalResult)) {
        $targetCalories = $goalRow['target_calories'];
        $remainingCalories = round($targetCalories - $avgCalories, 2);
    } else {
        $targetCalories = null;
        $remainingCalories = null;
    }

    echo json_encode([
        "success" => true,
        "avg_calories" => $avgCalories,
        "remaining_calories" => $remainingCalories,
        "goal_set" => isset($targetCalories) // true if goal exists, false otherwise
    ]);
}
?>
