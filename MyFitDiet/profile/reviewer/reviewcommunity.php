<?php

session_start();

$userRole = strtolower($_SESSION['userrole']);; // Assume this is set when the user logs in

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

// Fetch current date or selected date
$dateFilter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Default to "POST"
$type = isset($_GET['type']) ? $_GET['type'] : "post";

// Fetch data based on selected type
if ($type === "post") {
    $sql = "SELECT * FROM community WHERE DATE(date) = '$dateFilter' ORDER BY date DESC";
} else {
    $sql = "SELECT * FROM communitycomment WHERE DATE(createdAt) = '$dateFilter' ORDER BY createdAt DESC";
}

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

        .top-buttons { 
            margin-bottom: 10px; 
        }

        .top-buttons button { 
            padding: 8px 15px; 
            font-size: 18px; 
            cursor: pointer; 
            margin-right: 5px; }

        .top-buttons .active { 
            background: green; 
            color: white; 
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
            border-collapse: collapse; 
        }

        th, td { 
            padding: 10px; 
            border: 1px solid #000; 
            text-align: left; 
        }

        th { 
            background: #eee; 
            position: sticky; 
            top: 0; z-index: 9; 
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

        .btn-block { 
            background: red; 
            color: white; 
            border: none; 
            padding: 5px 10px; 
            cursor: pointer; 
        }

        .btn-unblock { 
            background: blue; 
            color: white; 
            border: none; 
            padding: 5px 10px; 
            cursor: pointer; 
        }

        .no-community { 
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

<p class="heading">Review Community</p>

<div class="top-buttons">
    <button id="btn-post" class="active" onclick="switchType('post')">POST</button>
    <button id="btn-comment" onclick="switchType('comment')">COMMENT</button>
</div>

<div class="date-container">
    <button onclick="changeDate(-1)">&#9665;</button>
    <input type="date" id="date-picker" value="<?= $dateFilter ?>" onchange="fetchData(this.value)">
    <button onclick="changeDate(1)">&#9655;</button>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <?php if ($type === "post") { ?>
                    <th>POST ID</th>
                    <th>USER ID</th>
                    <th>IMAGE</th>
                    <th>CAPTION</th>
                    <th>ACTIONS</th>
                <?php } else { ?>
                    <th>COMMENT ID</th>
                    <th>USER ID</th>
                    <th>POST ID</th>
                    <th>COMMENT</th>
                    <th>ACTIONS</th>
                <?php } ?>
            </tr>
        </thead>
        <tbody id="comment-list">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $status = $row["status"];
                    $buttonText = ($status == "block") ? "UNBLOCK" : "BLOCK";
                    $buttonClass = ($status == "block") ? "btn-unblock" : "btn-block";

                    if ($type === "post") {
                        $imageData = base64_encode($row['img']);
                        $imageSrc = $imageData ? "data:image/jpeg;base64," . $imageData : "no-image.png";
                        
                        echo "<tr>
                                <td>{$row['postid']}</td>
                                <td>{$row['userid']}</td>
                                <td><img src='{$imageSrc}' style='width: 100px; height: 100px; object-fit: cover;' /></td>
                                <td>{$row['caption']}</td>
                                <td>
                                    <button class='$buttonClass' 
                                        onclick=\"toggleBlockComment(this, 'community', 'postid', {$row["postid"]})\">$buttonText</button>
                                </td>
                              </tr>";
                    } else {
                        echo "<tr>
                                <td>{$row['commentid']}</td>
                                <td>{$row['userid']}</td>
                                <td>{$row['postid']}</td>
                                <td>{$row['comment']}</td>
                                <td>
                                    <button class='$buttonClass' 
                                        onclick=\"toggleBlockComment(this, 'communitycomment', 'commentid', {$row['commentid']})\">$buttonText</button>
                                </td>
                              </tr>";
                    }
                    
                }
            } else {
                echo "<tr><td colspan='5' class='no-community'>No community for this day</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
function switchType(type) {
    let date = document.getElementById("date-picker").value;
    window.location.href = `?type=${type}&date=${date}`;
}

function changeDate(days) {
    let datePicker = document.getElementById("date-picker");
    let currentDate = new Date(datePicker.value);
    currentDate.setDate(currentDate.getDate() + days);
    let newDate = currentDate.toISOString().split("T")[0];
    datePicker.value = newDate;
    fetchData(newDate);
}

function fetchData(date) {
    let type = new URLSearchParams(window.location.search).get("type") || "post";
    window.location.href = `?type=${type}&date=${date}`;
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
        console.log(response); // Debugging
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


// Highlight active button
document.addEventListener("DOMContentLoaded", () => {
    let urlParams = new URLSearchParams(window.location.search);
    let type = urlParams.get("type") || "post";
    document.getElementById("btn-post").classList.remove("active");
    document.getElementById("btn-comment").classList.remove("active");
    document.getElementById(`btn-${type}`).classList.add("active");
});
</script>

</body>
</html>

<?php $connection->close(); ?>  
