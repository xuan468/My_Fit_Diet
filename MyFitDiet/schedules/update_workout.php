<?php
session_start();
include '../general/dbconn.php';

$data = json_decode(file_get_contents("php://input"), true);

$time_from = strtotime($data['time_from']);
$time_to = strtotime($data['time_to']);
$totalMinutes = max(($time_to - $time_from) / 60, 0);
$hours = floor($totalMinutes / 60);
$minutes = $totalMinutes % 60;
$duration = "{$hours} hr {$minutes} min"; 

$query = "UPDATE workoutstbl 
          SET workout_name = ?, workout_type = ?, workout_time_from = ?, workout_time_to = ?, duration = ?, workout_location = ?, workout_notes = ? 
          WHERE workout_id = ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("sssssssi", 
    $data['activity_name'], 
    $data['category'], 
    $data['time_from'], 
    $data['time_to'], 
    $duration,  
    $data['location'], 
    $data['notes'], 
    $data['workout_id']
);

if ($stmt->execute()) {
    echo "Workout updated successfully.";
} else {
    echo "Error updating workout.";
}
?>
