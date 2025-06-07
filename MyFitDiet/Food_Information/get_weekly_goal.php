<?php
session_start();
include '../general/dbconn.php';

$userID = $_SESSION['userid'];
$startDate = $_GET['start_date'];

function getCurrentWeekRange($date) {
    $date = new DateTime($date);
    $date->modify('Monday this week');
    $startOfWeek = clone $date;
    $endOfWeek = clone $date;
    $endOfWeek->modify('Sunday this week');
    
    return [
        'start' => $startOfWeek->format('Y-m-d'),
        'end' => $endOfWeek->format('Y-m-d')
    ];
}

$weekRange = getCurrentWeekRange($startDate);

$query = "SELECT target_calories FROM weekly_goals WHERE user_id = '$userID' AND '{$weekRange['start']}' BETWEEN start_date AND end_date";

$result = mysqli_query($connection, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['target_calories' => $row['target_calories']]);
} else {
    echo json_encode(['target_calories' => 2000]); 
}
?>