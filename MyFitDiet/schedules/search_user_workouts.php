<?php
session_start();
include '../general/dbconn.php';

if (strtolower($_SESSION['userrole']) !== "admin" && strtolower($_SESSION['userrole']) !== "manager") {
    echo json_encode([]);
    exit;
}

$queryInput = $_GET['query'] ?? '';
if (!$queryInput) {
    echo json_encode([]);
    exit;
}

$user_query = "SELECT userID, Fav_exercise FROM user WHERE userID = ? OR Username LIKE ?";
$stmt_user = mysqli_prepare($connection, $user_query);
$likeQuery = "%$queryInput%";
mysqli_stmt_bind_param($stmt_user, "ss", $queryInput, $likeQuery);
mysqli_stmt_execute($stmt_user);
$user_result = mysqli_stmt_get_result($stmt_user);
$user_row = mysqli_fetch_assoc($user_result);

if (!$user_row) {
    echo json_encode(["error" => "User not found"]);
    exit;
}

$userid = $user_row['userID'];
$fav_exercise = explode(',', $user_row['Fav_exercise']);

$query = "SELECT * FROM workoutstbl WHERE userID = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $userid);
mysqli_stmt_execute($stmt);
$workout_result = mysqli_stmt_get_result($stmt);

$workouts = [];
while ($row = mysqli_fetch_assoc($workout_result)) {
    $workouts[] = [
        'workout_id' => $row['workout_id'],
        'workout_date' => $row['workout_date'],
        'activity_name' => $row['workout_name'],
        'activity_class' => strtolower($row['workout_type']),
        'category' => $row['workout_type'],
        'time_from' => $row['workout_time_from'],
        'time_to' => $row['workout_time_to'],
        'duration' => $row['duration'],
        'location' => $row['workout_location'],
        'notes' => $row['workout_notes']
    ];
}

$category_query = "SELECT category_id, category_name, category_type, category_image, category_description 
                   FROM workout_categoriestbl 
                   WHERE is_system = 1 OR userID = ?";
$stmt2 = mysqli_prepare($connection, $category_query);
mysqli_stmt_bind_param($stmt2, "i", $userid);
mysqli_stmt_execute($stmt2);
$category_result = mysqli_stmt_get_result($stmt2);

$categories = [];
while ($category_row = mysqli_fetch_assoc($category_result)) { 

    $count_query = "SELECT COUNT(*) AS count FROM workoutstbl WHERE workout_name = ? AND userID = ?";
    $stmt_count = mysqli_prepare($connection, $count_query);
    mysqli_stmt_bind_param($stmt_count, "si", $category_row['category_name'], $userid);
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count));
    $registered_count = $count_result['count'] ?? 0;

    $category_description = ($registered_count > 0) 
        ? "✅ Registered: $registered_count times" 
        : "No registered workouts";

    $imageData = base64_encode($category_row['category_image']);
    $imageSrc = "data:image/jpeg;base64," . $imageData;

    $is_favorite = in_array($category_row['category_name'], $fav_exercise);

    $categories[] = [
        'category_id' => $category_row['category_id'],
        'category_name' => $category_row['category_name'],
        'category_type' => $category_row['category_type'],
        'category_image' => $imageSrc,
        'category_description' => $category_description,
        'is_favorite' => $is_favorite 
    ];
}

$response = [
    'workouts' => $workouts,
    'categories' => $categories
];

mysqli_close($connection);
echo json_encode($response);
?>
