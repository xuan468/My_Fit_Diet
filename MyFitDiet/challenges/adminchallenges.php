<?php
ob_start(); 
session_start();
include '../general/dbconn.php'; 

// Enable error reporting (remove after debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check user role and include appropriate navigation
$userrole = strtolower($_SESSION['userrole']);
switch ($userrole) {
    case 'manager':
        include '../general/manager-nav.php';
        break;
    case 'admin':
        include '../general/admin-nav.php';
        break;
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$difficulty = $_GET['difficulty'] ?? 'all';

// Get challenge levels data
$sql_levels = "SELECT * FROM challengelevel ORDER BY level ASC";
$result_levels = mysqli_query($connection, $sql_levels);

// Build main challenges query
$sql_challenges = "
    SELECT c.challengeid, c.challengeName, c.description, c.type, c.difficulty, c.img, c.score,
           COUNT(uc.userid) AS active_users,
           SUM(CASE WHEN uc.status = 'success' THEN 1 ELSE 0 END) AS completed_users
    FROM challenges c
    LEFT JOIN userchallenges uc ON c.challengeid = uc.challengeid
    WHERE (c.challengeName LIKE '%$search%' OR c.description LIKE '%$search%')
";

// Add difficulty filter if specified
if ($difficulty !== 'all') {
    $sql_challenges .= " AND c.difficulty = '$difficulty'";
}

$sql_challenges .= " GROUP BY c.challengeid";
$result_challenges = mysqli_query($connection, $sql_challenges);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = mysqli_real_escape_string($connection, $_POST['action']);
    
    // Handle challenge level operations
    if ($action == 'add_level') {
        $level = mysqli_real_escape_string($connection, $_POST['level']);
        $startPoint = mysqli_real_escape_string($connection, $_POST['startPoint']);
        $endPoint = mysqli_real_escape_string($connection, $_POST['endPoint']);
    
        $sql = "INSERT INTO challengelevel (level, startPoint, endPoint) VALUES ('$level', '$startPoint', '$endPoint')";
        if (mysqli_query($connection, $sql)) {
            header("Location: adminchallenges.php?status=level_added");
        } else {
            die("Error adding level: " . mysqli_error($connection));
        }
        exit();
        
    } elseif ($action == 'delete_level') {
        $levelid = mysqli_real_escape_string($connection, $_POST['levelid']);
        $sql = "DELETE FROM challengelevel WHERE levelid='$levelid'";
        if (mysqli_query($connection, $sql)) {
            header("Location: adminchallenges.php?status=level_deleted");
        } else {
            die("Error deleting level: " . mysqli_error($connection));
        }
        exit();
        
    } elseif ($action == 'edit_level') {
        $levelid = mysqli_real_escape_string($connection, $_POST['levelid']);
        $startPoint = mysqli_real_escape_string($connection, $_POST['startPoint']);
        $endPoint = mysqli_real_escape_string($connection, $_POST['endPoint']);
        $sql = "UPDATE challengelevel SET startPoint='$startPoint', endPoint='$endPoint' WHERE levelid='$levelid'";
        if (mysqli_query($connection, $sql)) {
            header("Location: adminchallenges.php?status=level_updated");
        } else {
            die("Error updating level: " . mysqli_error($connection));
        }
        exit();
    }

    // Handle challenge operations
    if ($action == 'update') {
        // Update existing challenge
        $challengeid = mysqli_real_escape_string($connection, $_POST['challengeid']);
        $challengeName = mysqli_real_escape_string($connection, $_POST['challengeName']);
        $description = mysqli_real_escape_string($connection, $_POST['description']);
        $type = mysqli_real_escape_string($connection, $_POST['type']);
        $difficulty = mysqli_real_escape_string($connection, $_POST['difficulty']);
        $score = mysqli_real_escape_string($connection, $_POST['score']);

        // Handle image upload
        if ($_FILES['img']['size'] > 0) {
            $img = file_get_contents($_FILES['img']['tmp_name']);
            $img = mysqli_real_escape_string($connection, $img);
            $sql = "UPDATE challenges SET 
                    challengeName='$challengeName', 
                    description='$description', 
                    type='$type',
                    difficulty='$difficulty', 
                    score='$score', 
                    img='$img' 
                    WHERE challengeid='$challengeid'";
        } else {
            $sql = "UPDATE challenges SET 
                    challengeName='$challengeName', 
                    description='$description',
                    type='$type', 
                    difficulty='$difficulty', 
                    score='$score' 
                    WHERE challengeid='$challengeid'";
        }

        if (mysqli_query($connection, $sql)) {
            header("Location: adminchallenges.php?status=challenge_updated");
        } else {
            die("Error updating challenge: " . mysqli_error($connection));
        }
        exit();
        
    } elseif ($action == 'delete') {
        // Delete challenge
        $challengeid = mysqli_real_escape_string($connection, $_POST['challengeid']);
        $sql = "DELETE FROM challenges WHERE challengeid='$challengeid'";
        if (mysqli_query($connection, $sql)) {
            header("Location: adminchallenges.php?status=challenge_deleted");
        } else {
            die("Error deleting challenge: " . mysqli_error($connection));
        }
        exit();
        
    } elseif ($action == 'addChallenge') {
        // Add new challenge
        $challengeName = mysqli_real_escape_string($connection, $_POST['challengeName']);
        $description = mysqli_real_escape_string($connection, $_POST['description']);
        $type = mysqli_real_escape_string($connection, $_POST['type']);
        $difficulty = mysqli_real_escape_string($connection, $_POST['difficulty']);
        $score = mysqli_real_escape_string($connection, $_POST['score']);

        // Validate required fields
        if (empty($challengeName) || empty($description) || empty($type) || empty($difficulty) || empty($score)) {
            die("All fields are required");
        }

        // Handle image upload
        if ($_FILES['img']['size'] > 0) {
            $img = file_get_contents($_FILES['img']['tmp_name']);
            $img = mysqli_real_escape_string($connection, $img);
        } else {
            die("Please upload challenge image");
        }

        // Insert new challenge
        $sql = "INSERT INTO challenges (challengeName, description, type, difficulty, img, score) 
                VALUES ('$challengeName', '$description', '$type', '$difficulty', '$img', '$score')";
        
        if (mysqli_query($connection, $sql)) {
            header("Location: adminchallenges.php?status=challenge_added");
        } else {
            die("Error adding challenge: " . mysqli_error($connection));
        }
        exit();
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Challenges</title>
    <link rel="stylesheet" href="adminchallenges.css?v=<?php echo time(); ?>">
    <script defer src="adminchallenges.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <header class="admin-header">
        <h1>Challenges Management</h1>
    </header>
    
    <!-- Challenge Levels Section -->
    <section class="challenge-levels">
        <h2>Challenge Levels</h2>
        <table>
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Start Point</th>
                    <th>End Point</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($level = mysqli_fetch_assoc($result_levels)): ?>
                    <tr>
                        <td><?= htmlspecialchars($level['level']); ?></td>
                        <td><?= htmlspecialchars($level['startPoint']); ?></td>
                        <td><?= htmlspecialchars($level['endPoint']); ?></td>
                        <td>
                            <button class="edit-level-btn" data-level-id="<?= htmlspecialchars($level['levelid']); ?>">Edit</button>
                            <button class="delete-level-btn" data-level-id="<?= htmlspecialchars($level['levelid']); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <button id="addLevelBtn">Add New Level</button>
    </section>

    <section class="challenges-list">
        <h2>All Challenges</h2>
        <!-- Search and Filter Section -->
        <div class="search-filter">
            <div class="search-bar">
                <form method="GET" action="adminchallenges.php">
                    <input type="text" name="search" placeholder="Search by name or description" value="<?= htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <div class="filter-bar">
                <form method="GET" action="adminchallenges.php">
                    <select name="difficulty">
                        <option value="all" <?= $difficulty === 'all' ? 'selected' : ''; ?>>All Difficulties</option>
                        <option value="lightly" <?= $difficulty === 'lightly' ? 'selected' : ''; ?>>Lightly</option>
                        <option value="moderately" <?= $difficulty === 'moderately' ? 'selected' : ''; ?>>Moderately</option>
                        <option value="highly" <?= $difficulty === 'highly' ? 'selected' : ''; ?>>Highly</option>
                    </select>
                    <button type="submit">Filter</button>
                </form>
            </div>
        </div>
        <div class="challenges-container">
            <!-- Add Challenge Card -->
            <div class="challenge-card add-challenge-card" id="addChallengeCard">
                <div class="add-icon">+</div>
                <p>Add New Challenge</p>
            </div>

            <!-- Existing Challenges -->
            <?php while ($row = mysqli_fetch_assoc($result_challenges)): ?>
                <div class="challenge-card" data-challenge-id="<?= htmlspecialchars($row['challengeid']); ?>">
                    <div class="challenge-image">
                        <img src="data:image/jpeg;base64,<?= base64_encode($row['img']); ?>" alt="<?= htmlspecialchars($row['challengeName']); ?>">
                    </div>
                    <div class="challenge-info">
                        <h3><?= htmlspecialchars($row['challengeName']); ?></h3>
                        <p><?= htmlspecialchars($row['description']); ?></p>
                        <p><strong>Type:</strong> <?= htmlspecialchars($row['type']); ?></p>
                        <p><strong>Difficulty:</strong> <?= htmlspecialchars($row['difficulty']); ?></p>
                        <p><strong>Score:</strong> <?= htmlspecialchars($row['score']); ?></p>
                        <p><strong>Active Users:</strong> <?= htmlspecialchars($row['active_users']); ?></p>
                        <p><strong>Completed Users:</strong> <?= htmlspecialchars($row['completed_users']); ?></p>
                    </div>
                    <div class="challenge-actions">
                        <button class="edit-btn" data-challenge-id="<?= htmlspecialchars($row['challengeid']); ?>">Edit</button>
                        <button class="delete-btn" data-challenge-id="<?= htmlspecialchars($row['challengeid']); ?>">Delete</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Challenge</h2>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="challengeid" id="editChallengeId">
                <input type="hidden" name="action" value="update">
                <label for="challengeName">Challenge Name:</label>
                <input type="text" id="challengeName" name="challengeName" required>
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
                <label for="type">Type:</label>
                <select id="type" name="type" required>
                    <option value="Diet">Diet</option>
                    <option value="Exercise">Exercise</option>
                </select>
                <label for="difficulty">Difficulty:</label>
                <select id="difficulty" name="difficulty" required>
                    <option value="lightly">Lightly</option>
                    <option value="moderately">Moderately</option>
                    <option value="highly">Highly</option>
                </select>
                <label for="score">Score:</label>
                <input type="number" id="score" name="score" required>
                <label for="img">Upload New Image:</label>
                <input type="file" id="img" name="img" accept="image/*">
                <button type="submit" class="save-changes">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Edit Level Modal -->
    <div id="editLevelModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Challenge Level</h2>
            <form id="editLevelForm" method="POST">
                <input type="hidden" name="action" value="edit_level">
                <input type="hidden" name="levelid" id="editLevelId">
                <label for="startPoint">Start Point:</label>
                <input type="number" id="startPoint" name="startPoint" required>
                <label for="endPoint">End Point:</label>
                <input type="number" id="endPoint" name="endPoint" required>
                <button type="submit" class="save-changes">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Add Level Modal -->
    <div id="addLevelModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Challenge Level</h2>
            <form id="addLevelForm" method="POST">
                <input type="hidden" name="action" value="add_level">
                <label for="level">Level:</label>
                <input type="number" id="level" name="level" required>
                <label for="startPoint">Start Point:</label>
                <input type="number" id="startPoint" name="startPoint" required>
                <label for="endPoint">End Point:</label>
                <input type="number" id="endPoint" name="endPoint" required>
                <button type="submit" class="add-button">Add Level</button>
            </form>
        </div>
    </div>

    <!-- Delete Level Modal -->
    <div id="deleteLevelModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>⚠️ Delete Level</h2>
            <p>Are you sure you want to delete this Level? <strong>This action cannot be undone.</strong></p>
            <div class="modal-buttons">
                <form id="deleteLevelForm" method="POST">
                    <input type="hidden" name="action" value="delete_level">
                    <input type="hidden" name="levelid" id="deleteLevelId">
                    <button type="submit" class="confirm-delete">Delete Level</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>⚠️ Delete Challenge</h2>
            <p>Are you sure you want to delete this challenge? <strong>This action cannot be undone.</strong></p>
            <div class="modal-buttons">
                <button class="cancel-delete">Cancel</button>
                <form id="deleteForm" method="POST" style="display:inline;">
                    <input type="hidden" name="challengeid" id="deleteChallengeId">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="confirm-delete">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Challenge Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Challenge</h2>
            <form id="addForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="addChallenge">
                <label for="addChallengeName">Challenge Name:</label>
                <input type="text" id="addChallengeName" name="challengeName" required>
                <label for="addDescription">Description:</label>
                <textarea id="addDescription" name="description" required></textarea>
                <label for="addDifficulty">Difficulty:</label>
                <select id="addDifficulty" name="difficulty" required>
                    <option value="lightly">Lightly</option>
                    <option value="moderately">Moderately</option>
                    <option value="highly">Highly</option>
                </select>
                <label for="addType">Type:</label>
                <select id="addType" name="type" required>
                    <option value="Diet">Diet</option>
                    <option value="Exercise">Exercise</option>
                </select>
                <label for="addScore">Score:</label>
                <input type="number" id="addScore" name="score" required>
                <label for="addImg">Upload Image:</label>
                <input type="file" id="addImg" name="img" accept="image/*" required>
                <button type="submit" class="add-button">Add Challenge</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
include '../general/footer.php';
ob_end_flush();
?>