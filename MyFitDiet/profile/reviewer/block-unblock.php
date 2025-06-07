<?php
include "../../general/dbconn.php";

function updateStatus($connection, $table, $recordColumn, $record_id, $action) {
    // Allowed tables with their respective record and status column names
    $allowed_tables = [
        'challengecomments' => ['record_column' => 'commentid', 'status_column' => 'status'],
        'communitycomment'  => ['record_column' => 'commentid', 'status_column' => 'status'],
        'community'         => ['record_column' => 'postid',    'status_column' => 'status'],
        'feedbacktbl'       => ['record_column' => 'feedback_id', 'status_column' => 'user_status'],
        'user'              => ['record_column' => 'userID',   'status_column' => 'Status']
    ];

    // Validate the table and record column
    if (!array_key_exists($table, $allowed_tables) || $allowed_tables[$table]['record_column'] !== $recordColumn) {
        die("Invalid table or column specified.");
    }
    
    // Get the proper status column for the table
    $statusColumn = $allowed_tables[$table]['status_column'];

    // Set statusbased on action
    $status = ($action === "block") ? 'block' : 'active';

    // Use prepared statements to prevent SQL injection
    $sql = "UPDATE $table SET $statusColumn = ? WHERE $recordColumn = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("si", $status, $record_id);

    if ($stmt->execute()) {
        echo ucfirst($action) . " successful.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $table = $_POST["table"];
    $recordColumn = $_POST["column"];
    $record_id = intval($_POST["record_id"]);
    $action = $_POST["action"];

    updateStatus($connectionection, $table, $recordColumn, $record_id, $action);
}

$connectionection->close();
?>

