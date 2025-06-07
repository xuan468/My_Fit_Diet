<?php
session_start();
include '../general/dbconn.php';

header('Content-Type: application/json'); 
if (isset($_SESSION['userrole'])) {
    $userrole = strtolower($_SESSION['userrole']);
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Your'; 

    if ($userrole === "manager" || $userrole === "admin") {
        $userid = $_SESSION['userroleid']; 
    } elseif ($userrole === "member") {
        $userid = $_SESSION['userid']; 
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $workoutName = trim($_POST["workoutName"]);
    $workoutType = trim($_POST["workoutType"]);
    $workoutDescription = trim($_POST["workoutDescription"]);
    $isSystem = ($userrole === 'admin' || $userrole === 'manager') ? 1 : 0;
    $imageData = NULL;

    $checkQuery = "SELECT category_id, userID FROM workout_categoriestbl WHERE category_name = ?";
    $stmt = $connection->prepare($checkQuery);
    $stmt->bind_param("s", $workoutName);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingUsers = [];

    while ($row = $result->fetch_assoc()) {
        $existingUsers[] = $row['userID'];
    }
    $stmt->close();

    if (!empty($existingUsers) && $isSystem) {
        echo json_encode([
            "success" => false,
            "message" => "This workout category already exists, added by another user. Admins and managers cannot add duplicates."
        ]);
        exit;
    }

    if (isset($_FILES["workoutImage"]) && $_FILES["workoutImage"]["error"] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES["workoutImage"]["tmp_name"]);
    }

    $query = "INSERT INTO workout_categoriestbl 
              (category_name, category_type, userID, UserRole, category_description, category_image, is_system) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssissbi", $workoutName, $workoutType, $userid, $userrole, $workoutDescription, $null, $isSystem);

    if ($imageData !== NULL) {
        $stmt->send_long_data(5, $imageData);
    }

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
    $connection->close();
}
?>
