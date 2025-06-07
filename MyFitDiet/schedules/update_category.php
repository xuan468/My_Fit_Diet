<?php
session_start();
include '../general/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['userrole'])) {
        $userrole = strtolower($_SESSION['userrole']);
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Your'; 
    
        if ($userrole === "manager" || $userrole === "admin") {
            $userid = $_SESSION['userroleid']; 
        } elseif ($userrole === "member") {
            $userid = $_SESSION['userid']; 
        }
    }    

    if ($userid && in_array($userrole, ['admin', 'member', 'manager'])) {
        $categoryID = $_POST['category_id'];
        $categoryName = $_POST['category_name'];
        $categoryType = $_POST['category_type'];

        if (isset($_FILES['category_image']) && $_FILES['category_image']['size'] > 0) {
            $imageData = file_get_contents($_FILES['category_image']['tmp_name']);
            $query = "UPDATE workout_categoriestbl 
                      SET category_name = ?, category_type = ?, category_image = ? 
                      WHERE category_id = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "sssi", $categoryName, $categoryType, $imageData, $categoryID);
        } else {
            $query = "UPDATE workout_categoriestbl 
                      SET category_name = ?, category_type = ? 
                      WHERE category_id = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "ssi", $categoryName, $categoryType, $categoryID);
        }

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database update failed."]);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["status" => "error", "message" => "Unauthorized request."]);
    }
}

mysqli_close($connection);
?>
