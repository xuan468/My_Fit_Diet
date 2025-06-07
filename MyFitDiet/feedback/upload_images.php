<?php
include '../general/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image']) && isset($_POST['position'])) {
    $position = intval($_POST['position']);
    $imageData = file_get_contents($_FILES['image']['tmp_name']);

    $checkQuery = "SELECT * FROM header_images WHERE position = ?";
    $stmt = $connection->prepare($checkQuery);
    $stmt->bind_param("i", $position);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $updateQuery = "UPDATE header_images SET image_data = ? WHERE position = ?";
        $stmt = $connection->prepare($updateQuery);
        $stmt->bind_param("bi", $null, $position);
        $stmt->send_long_data(0, $imageData);
    } else {
        $insertQuery = "INSERT INTO header_images (position, image_data) VALUES (?, ?)";
        $stmt = $connection->prepare($insertQuery);
        $stmt->bind_param("ib", $position, $null);
        $stmt->send_long_data(1, $imageData);
    }

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request."]);
}
?>
