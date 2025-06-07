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
    <title>Workout Planner</title>
    <link rel="stylesheet" href="schedules.css?v=<?php echo time(); ?>">
    <script>
        let userrole = "<?php echo $_SESSION['userrole'] ?? ''; ?>";
        document.addEventListener("DOMContentLoaded", function() {
            checkNewWorkouts();
        });
    </script>

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
            <div id="uploadForm" class="modals">
                <div class="modal-contents">
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
        <h1>Workout Schedule</h1>
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

            <?php if (strtolower($userrole) === "admin" || strtolower($userrole) === "manager"): ?>
                <div class="search-container">
                    <input type="text" id="searchUser" placeholder="Enter UserID or Username">
                    <button id=search-button onclick="searchUserWorkouts()">Search</button>
                </div>
            <?php endif; ?>

            <?php if ($userrole === "member") : ?>
                <nav class="navigations">
                    <h3>Navigation</h3>
                    <ul>
                        <li><a href="weeklys.php">Fitness Program</a></li>
                    </ul>
                </nav>
            <?php endif; ?>
        </aside>

        <!-- Main Content Section -->
        <main class="main-content">
            <!-- Workout Categories -->
            <section class="workout-categories-section">
                <h2>Workout Categories</h2>
                <div class="category-tabs">
                    <div class="tab all" onclick="handleTabClick('all')">All Categories</div>
                    <?php if (strtolower($userrole) === "member") : ?>
                        <div class="tab registered" onclick="handleTabClick('registered')">Registered</div>
                    <?php endif; ?>
                </div>
                <?php if (strtolower($userrole) === "admin" || strtolower($userrole) === "manager"): ?>
                    <div class="workout-card add-workout-card" onclick="openWorkoutModal()">
                        <div class="add-icon">+</div>
                        <p>Add New Workout</p>
                    </div>
                <?php endif; ?>
                <div class="workout-categories"></div>
                <div id="workoutModal" class="modals">
                    <div class="modal-contents">
                        <span class="close" onclick="closeWorkoutModal()">&times;</span>
                        <h2>Add Workout</h2>
                        <form id="workoutForm">
                            <label>Workout Name:</label>
                            <input type="text" id="workoutName" required>
                            <br><br>
                            <label>Workout Type:</label>
                            <input type="text" id="workoutType" required>
                            <br><br>
                            <label>Workout Image:</label>
                            <input type="file" id="workoutImage" accept="image/*">
                            <br><br>
                            <label>Workout Description:</label>
                            <input type="text" id="workoutDescription" value="No registered workouts" readonly>
                            <div class="button-group">
                                <button type="button" onclick="submitWorkout()">Confirm</button>
                                <button type="button" onclick="closeWorkoutModal()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div id="editCategoryModal" class="modals">
                    <div class="modal-contents">
                        <span class="close" onclick = "closeEditModal()" >&times;</span>
                        <h2>Edit Workout Category</h2>
                        <form id="editCategoryForm">
                            <div class="category-group">
                                <input type="hidden" id="editCategoryID">
                                <label>Category Name:</label>
                                <input type="text" id="editCategoryName" required>
                            </div>
                            <div class="category-group">
                                <label>Category Type:</label>
                                <input type="text" id="editCategoryType" required>
                            </div>
                            <div class="category-group">
                                <label>Category Image:</label>
                                <input type="file" id="editCategoryImage">
                            </div>
                            <div class="button-group">
                                <button type="button" onclick="submitCategoryEdit()">Confirm</button>
                                <button type="button" onclick="closeEditModal()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
            <section class="fitness-planner-section">
                <h2>Fitness Planner</h2>
                <div class="fitness-tabs">
                    <span class="fitness active">📆 Calendar</span>
                </div>
                
                <div id="calendar-header">
                    <button onclick="prevMonth()">◀</button>
                    <span id="month-year"></span>
                    <button onclick="nextMonth()">▶</button>
                </div>
                
                <div class="fitness-planner">
                    <table class="planner-table">
                        <thead>
                            <tr>
                                <th>Mon</th>
                                <th>Tue</th>
                                <th>Wed</th>
                                <th>Thu</th>
                                <th>Fri</th>
                                <th>Sat</th>
                                <th>Sun</th>
                            </tr>
                        </thead>
                        <tbody id="planner-body"></tbody>
                        <div id="editWorkoutModal" class="modals">
                            <div class="modal-contents">
                                <span class="close" onclick="closeEditModals()">&times;</span>
                                <h2>Edit Workout</h2>
                                <form>
                                    <div class="workout-group">
                                        <label>Workout Name:</label>
                                        <select id="editWorkoutName" required>
                                            <?php
                                                include '../general/dbconn.php';
                                                $query = "SELECT DISTINCT category_name FROM workout_categoriestbl WHERE userID = ? OR is_system = 1";
                                                $stmt = mysqli_prepare($connection, $query);
                                                mysqli_stmt_bind_param($stmt, "i", $userid);
                                                mysqli_stmt_execute($stmt);
                                                $result = mysqli_stmt_get_result($stmt);
                                                
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='" . htmlspecialchars($row['category_name']) . "'>" . 
                                                    htmlspecialchars($row['category_name']) . "</option>";
                                                }

                                                mysqli_stmt_close($stmt);
                                            ?>
                                        </select>
                                    </div>
                                    <div class="workout-group">
                                        <label>Workout Type:</label>
                                        <select id="editWorkoutType" required></select>
                                    </div>
                                    <div class="workout-group">
                                        <label>Time From:</label>
                                        <input type="time" id="editWorkoutTimeFrom">
                                    </div>
                                    <div class="workout-group">
                                        <label>Time To:</label>
                                        <input type="time" id="editWorkoutTimeTo">
                                    </div>
                                    <div class="workout-group">
                                        <label>Duration:</label>
                                        <input type="text" id="editWorkoutDuration">
                                    </div>
                                    <div class="workout-group">
                                        <label>Location:</label>
                                        <input type="text" id="editWorkoutLocation" oninput="addLocationPrefix()" required>
                                    </div>
                                    <div class="workout-group">
                                        <label>Notes:</label>
                                        <input type="text" id="editWorkoutNotes">
                                    </div>
                                        <div class="button-group">
                                            <button type="button" onclick="updateWorkout()">Confirm</button>
                                            <button type="button" onclick="closeEditModals()">Cancel</button>
                                        </div>
                                </form>
                            </div>
                        </div>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="schedules.js" defer></script>
</body>
</html>

<?php
include '../general/footer.php';
?>