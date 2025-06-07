<?php
session_start();
include '../general/dbconn.php';

if (isset($_SESSION['userrole'])) {
    $userrole = strtolower($_SESSION['userrole']);
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Your'; 

    if ($userrole === "manager" || $userrole === "admin") {
        $userid = $_SESSION['userroleid']; // 对于 admin 和 manager，使用 userroleid
    } elseif ($userrole === "member") {
        $userid = $_SESSION['userid']; // 对于 member，使用 userid
    }
}

// **获取 categories**
if ($userrole === "admin" || $userrole === "manager") {
    $category_query = "SELECT category_id, category_name, category_type, category_image, category_description, is_system 
                       FROM workout_categoriestbl 
                       WHERE is_system = 1";
    $stmt = mysqli_prepare($connection, $category_query);
} else {
    $category_query = "SELECT category_id, category_name, category_type, category_image, category_description, is_system, userID 
                       FROM workout_categoriestbl 
                       WHERE is_system = 1 OR userID = ?";
    $stmt = mysqli_prepare($connection, $category_query);
    mysqli_stmt_bind_param($stmt, "i", $userid);
}

mysqli_stmt_execute($stmt);
$category_result = mysqli_stmt_get_result($stmt);

$categories = [];
while ($category_row = mysqli_fetch_assoc($category_result)) {
    $imageData = base64_encode($category_row['category_image']);
    $imageSrc = "data:image/jpeg;base64," . $imageData; 
    $category_name = $category_row['category_name'];
    $category_type = $category_row['category_type']; 
    $is_system = $category_row['is_system'];

    if ($userrole === "admin" || $userrole === "manager") {
        $category_description = "No registered workouts";
    } else {
        $count_query = "SELECT COUNT(*) AS count FROM workoutstbl 
                        WHERE workout_name = ? AND workout_type = ? AND userID = ?";
        $stmt_count = mysqli_prepare($connection, $count_query);
        mysqli_stmt_bind_param($stmt_count, "ssi", $category_name, $category_type, $userid);
        mysqli_stmt_execute($stmt_count);
        $count_result = mysqli_stmt_get_result($stmt_count);
        $count_row = mysqli_fetch_assoc($count_result);
        $registered_count = $count_row['count'] ?? 0;

        $category_description = ($registered_count > 0) 
            ? "✅ Registered: $registered_count times" 
            : "No registered workouts";
    }

    $categories[] = [
        'category_id' => $category_row['category_id'],
        'category_name' => $category_name,
        'category_type' => $category_type, 
        'category_image' => $imageSrc,
        'category_description' => $category_description,
        'is_system' => $category_row['is_system']
    ];
}

mysqli_close($connection);

echo json_encode([
    'categories' => $categories,
    'userrole' => $userrole
]);
?>
