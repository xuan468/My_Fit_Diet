<?php
session_start();
include '../general/dbconn.php';

if (isset($_SESSION['userrole'])) {
    $userrole = strtolower($_SESSION['userrole']);
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Your'; 

    if ($userrole === "manager" || $userrole === "admin") {
        $userid = $_SESSION['userroleid']; 
    } elseif ($userrole === "member") {
        $userid = $_SESSION['userid']; 
    }
}

if ($userrole !== "member") {
    echo json_encode([]); 
    exit;
}

$query = "SELECT workout_id, workout_name, workout_type, workout_date, workout_time_from, workout_time_to, duration, workout_location, workout_notes FROM workoutstbl WHERE userID = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$calendar = [];
while ($row = mysqli_fetch_assoc($result)) {
    $dayData = [
        'workout_id' => $row['workout_id'],
        'date' => $row['workout_date'],
        'activity_name' => $row['workout_name'],
        'activity_class' => strtolower($row['workout_type']),
        'category' => $row['workout_type'],
        'time_from' => $row['workout_time_from'],
        'time_to' => $row['workout_time_to'],
        'duration' => $row['duration'],
        'location' => $row['workout_location'],
        'notes' => $row['workout_notes']
    ];
    $calendar[] = $dayData;
}

mysqli_close($connection);
echo json_encode($calendar);
?>
