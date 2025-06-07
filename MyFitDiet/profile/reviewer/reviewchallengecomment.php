<?php

session_start();

$userRole = strtolower($_SESSION['userrole']); 

switch ($userRole) {
    case 'admin':
        include '../../general/admin-nav.php';
        break;
    case 'manager':
        include '../../general/manager-nav.php';
        break;
    case 'reviewer':
        include '../../general/reviewer-nav.php';
        break;
}

include "../../general/dbconn.php";

// Get the selected date or default to today
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");

// Fetch comments for the selected date
$sql = "SELECT * FROM challengecomments WHERE DATE(createdAt) = '$selectedDate' ORDER BY createdAt DESC";
$result = $connection->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Challenges</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .date-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .date-container button {
            padding: 5px 10px;
            font-size: 18px;
            cursor: pointer;
        }
        #date-picker {
            border: none;
            background: none;
            font-size: 20px;
            cursor: pointer;
            text-decoration: underline;
        }
        .table-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #000;
            position: relative;
        }
        table {
            width: 100%;
            border-collapse: collapse; /* Remove gaps between cells */
        }
        th, td {
            padding: 10px;
            border: 1px solid #000;
            text-align: left;
        }
        th {
            background: #eee;
            position: sticky;
            top: 0;
            z-index: 9;
        }

        /* Adjust column widths */
        th:nth-child(1), td:nth-child(1) { 
            width: 10%; 
        }
        th:nth-child(2), td:nth-child(2) { 
            width: 10%; 
        }
        th:nth-child(3), td:nth-child(3) { 
            width: 15%; 
        }
        th:nth-child(4), td:nth-child(4) { 
            width: 40%; 
        }
        th:nth-child(5), td:nth-child(5) { 
            width: 10%; 
        }
        th:nth-child(6), td:nth-child(6) { 
            width: 15%; 
        }

        .btn-block, .btn-unblock {
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .btn-block {
            background: red;
            color: white;
        }
        .btn-unblock {
            background: blue;
            color: white;
        }
        .no-comments {
            text-align: center;
            padding: 10px;
            font-style: italic;
            color: gray;
        }

        p.heading {
            font-size: 24px; 
            font-weight: bold; 
            text-align: center; 
            color: #333; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            padding-bottom: 20px; 
            margin-top:0px;
        }
    </style>
</head>
<body>

<p class="heading">Review Challenges</p>

<div class="date-container">
    <button onclick="changeDate(-1)">&#9665;</button>
    <input type="date" id="date-picker" value="<?= $selectedDate ?>" onchange="fetchData(this.value)">
    <button onclick="changeDate(1)">&#9655;</button>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>COMMENT ID</th>
                <th>USER ID</th>
                <th>CHALLENGE ID</th>
                <th>COMMENT</th>
                <th>ACTIONS</th>
            </tr>
        </thead>
        <tbody id="comment-list">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $status = $row["status"];
                    $buttonText = ($status == "block") ? "UNBLOCK" : "BLOCK";
                    $buttonClass = ($status == "block") ? "btn-unblock" : "btn-block";

                    echo "<tr>
                            <td>{$row['commentid']}</td>
                            <td>{$row['userid']}</td>
                            <td>{$row['challengeid']}</td>
                            <td>{$row['comment']}</td>
                            <td>
                                <button class='$buttonClass' 
                                    onclick=\"toggleBlockComment(this, 'challengecomments', 'commentid', {$row['commentid']})\">
                                    $buttonText
                                </button>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='no-comments'>No comments for this day</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
function changeDate(days) {
    let datePicker = document.getElementById("date-picker");
    let currentDate = new Date(datePicker.value);
    currentDate.setDate(currentDate.getDate() + days);
    let newDate = currentDate.toISOString().split("T")[0];
    datePicker.value = newDate;
    fetchData(newDate);
}

function fetchData(date) {
    window.location.href = `?date=${date}`;
}

function toggleBlockComment(button, table, column, recordId) {
    let action = button.textContent.trim() === "BLOCK" ? "block" : "unblock";

    fetch("block-unblock.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `table=${table}&column=${column}&record_id=${recordId}&action=${action}`,
    })
    .then(response => response.text())
    .then(response => {
        alert(response);
        if (response.includes("successful")) { 
            if (action === "block") {
                button.textContent = "UNBLOCK";
                button.classList.remove("btn-block");
                button.classList.add("btn-unblock");
            } else {
                button.textContent = "BLOCK";
                button.classList.remove("btn-unblock");
                button.classList.add("btn-block");
            }
        }
    })
    .catch(error => console.error("Error:", error));
}
</script>

</body>
</html>

<?php $connection->close(); ?>
