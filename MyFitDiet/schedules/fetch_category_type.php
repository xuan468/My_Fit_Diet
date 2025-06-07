<?php
include '../general/dbconn.php';

if (isset($_GET['category'])) {
    $category = $_GET['category'];

    $sql = "SELECT DISTINCT category_type FROM workout_categoriestbl WHERE category_name = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "s", $category);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $types = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $types[] = $row['category_type'];
    }

    header('Content-Type: application/json');
    echo json_encode([
        "category_types" => $types,
        "single" => count($types) === 1
    ]);

    mysqli_stmt_close($stmt);
    mysqli_close($connection);
}
?>
