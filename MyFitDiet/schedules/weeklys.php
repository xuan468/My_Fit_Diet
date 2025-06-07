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

switch ($userrole) {
    case 'manager':
        include '../general/manager-nav.php';
        break;
    case 'admin':
        include '../general/admin-nav.php';
        break;
    case 'member':
        include '../general/member-nav.php';
        break;
}

if ($userrole === "admin") {
    $categoryQuery = "SELECT * FROM workout_categoriestbl";
} else {
    $categoryQuery = "SELECT * FROM workout_categoriestbl WHERE is_system = 1 OR userID = ?";
}
$categoryStmt = $connection->prepare($categoryQuery);
if ($userrole !== "admin") {
    $categoryStmt->bind_param("i", $userid);
}
$categoryStmt->execute();
$categoriesResult = $categoryStmt->get_result();
$categories = $categoriesResult->fetch_all(MYSQLI_ASSOC);

$query = "SELECT position, image_data FROM header_images ORDER BY position ASC";
$result = $connection->query($query);
$headerImages = [];
while ($row = $result->fetch_assoc()) {
    $headerImages[$row['position']] = base64_encode($row['image_data']);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Program</title>
    <link rel="stylesheet" href="weeklys.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../general/Navigation.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="collage-container">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="collage-item">
                        <img src="data:image/jpeg;base64,<?php echo $headerImages[$i] ?? ''; ?>" alt="Workout Image <?php echo $i; ?>">
                        <?php if ($userrole !== "member") : ?>
                            <img src="../images/edit.jpg" class="edit-icon" onclick="openImageUpload(<?php echo $i; ?>)">
                        <?php endif; ?> 
                    </div>
                <?php endfor; ?>
                <div id="uploadForm" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeImageUpload()">&times;</span>  
                        <h2>Upload Image</h2>
                        <input type="hidden" id="positionInput">
                        <label for="imageFile" class="file-label">Choose an image</label>  
                        <input type="file" id="imageFile" accept="image/*" class="file-input">                    
                        <div class="button-group">
                            <button class="confirm-btn" onclick="uploadImage()">Confirm</button>
                            <button class="cancel-btn" onclick="closeImageUpload()">Cancel</button>
                        </div>
                    </div>
                </div>

                <!-- Title Over Images -->
                <div class="header-title">
                    <h1>Workout Schedule</h1>
                </div>
            </div>
    </header>

    <div class="sidebar-title">
        <h1>Fitness Program</h1>
    </div>

    <div class="layout-container">
        <!-- Sidebar Section -->
        <aside class="sidebar">
            <div class="profile-section">
                <?php if ($userrole === "manager" || $userrole === "admin" || $userrole === "member"): ?>
                    <img src="show_images.php?userid=<?php echo urlencode($userid); ?>&userrole=<?php echo urlencode($userrole); ?>" alt="Profile Picture">
                <?php endif; ?>

                <?php if ($userrole === "admin"): ?>
                    <h1>Admin Panel</h1>
                <?php elseif ($userrole === "manager"): ?>
                    <h1>Manager Panel</h1>
                <?php else: ?>
                    <h1><?php echo htmlspecialchars($username); ?>'s Workout Planner</h1>
                <?php endif; ?>
            </div>
            <div class="welcome-section">
                <?php if ($userrole === "admin"): ?>
                    <h4>Welcome Admin!<br>Let's manage users and schedules efficiently.</h4>
                <?php elseif ($userrole === "manager"): ?>
                    <h4>Welcome Manager!<br>Monitor your team's progress and schedules.</h4>
                <?php else: ?>
                    <h4>Welcome!<br>Today is a fresh start to unlock your potential. Let's get moving!</h4>
                <?php endif; ?>
            </div>

            <?php if ($userrole === "member") : ?>
                <div class="shortcuts">
                    <h3>Shortcuts</h3>
                    <ul>
                        <li><button class="shortcut-btn" onclick="goToAddSchedule()">Add New Schedule</button></li>                    
                        <li><button class="shortcut-btn2" onclick="goToAddWorkout()">Add New Workout</button></li>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>

        <!-- Main Content Section -->
        <main class="main-content">
            <!-- Workout Categories -->
            <section class="workout-categories-section">
                <h2>Weekly Program</h2>
                <div class="category-tabs">
                    <span class="tab active">This Week</span>
                </div>

                <!-- Weekly Program Table -->
                <table class="weekly-program">
                    <thead>
                        <tr>
                            <th>🗓 Day</th>
                            <th>📌 Type</th>
                            <th>💪 Workout Category</th>
                            <th>⏰ Time</th>
                            <th>📝 Notes</th>
                        </tr>
                    </thead>
                    <tbody id="weeklyProgramTable">
                        
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <script src="weeklys.js?v=<?php echo time(); ?>"></script>
</body>
</html>
<?php
include '../general/footer.php';
?>