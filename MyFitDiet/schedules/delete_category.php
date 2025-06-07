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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_id = $_POST['category_id'];

    $check_query = "SELECT is_system FROM workout_categoriestbl WHERE category_id = ?";
    $stmt_check = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($stmt_check, "i", $category_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_bind_result($stmt_check, $is_system);
    mysqli_stmt_fetch($stmt_check);
    mysqli_stmt_close($stmt_check);

    if (!isset($is_system)) {
        echo json_encode(["success" => false, "error" => "Category not found"]);
        exit;
    }

    if ($is_system == 0) {
        $delete_query = "DELETE FROM workout_categoriestbl WHERE category_id = ? AND userID = ? AND is_system = 0";
        $stmt = mysqli_prepare($connection, $delete_query);
        mysqli_stmt_bind_param($stmt, "ii", $category_id, $userid);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(["success" => true, "message" => "Member category deleted"]);
            exit;
        } else {
            echo json_encode(["success" => false, "error" => "Unauthorized or category not found"]);
            exit;
        }
    } elseif ($is_system == 1) {
        $delete_workouts_query = "DELETE FROM workoutstbl WHERE workout_type = (SELECT category_name FROM workout_categoriestbl WHERE category_id = ?)";
        $stmt_workouts = mysqli_prepare($connection, $delete_workouts_query);
        mysqli_stmt_bind_param($stmt_workouts, "i", $category_id);
        mysqli_stmt_execute($stmt_workouts);
        mysqli_stmt_close($stmt_workouts);

        $delete_category_query = "DELETE FROM workout_categoriestbl WHERE category_id = ?";
        $stmt_category = mysqli_prepare($connection, $delete_category_query);
        mysqli_stmt_bind_param($stmt_category, "i", $category_id);
        mysqli_stmt_execute($stmt_category);
        mysqli_stmt_close($stmt_category);

        echo json_encode(["success" => true, "message" => "System category and associated workouts deleted"]);
        exit;
    }
}

mysqli_close($connection);
?>
