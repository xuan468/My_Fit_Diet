<?php
session_start();
include '../general/dbconn.php'; 

if (!isset($_SESSION['userid'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['userid'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['recipe_id']) || !isset($_POST['action'])) {
        die("Invalid request.");
    }

    $recipe_id = intval($_POST['recipe_id']);
    $action = $_POST['action'];

    if ($action === "add") {
        $query = "INSERT IGNORE INTO fav_recipe (userID, recipe_id) VALUES (?, ?)";
    } elseif ($action === "remove") {
        $query = "DELETE FROM fav_recipe WHERE userID = ? AND recipe_id = ?";
    } else {
        die("Invalid action.");
    }

    $stmt = $connection->prepare($query);
    if (!$stmt) {
        die("Error preparing statement: " . $connection->error);
    }

    $stmt->bind_param("ii", $user_id, $recipe_id);
    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error executing query: " . $stmt->error;
    }

    $stmt->close();
    $connection->close();
    exit;
}
?>