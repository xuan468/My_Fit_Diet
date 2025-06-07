<?php
ob_start();
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

$formSubmitted = false;
$successMessage = '';
$errorMessage = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $workout_name = $_POST['workout_name'] ?? '';
    $workout_type = $_POST['workout_type'] ?? '';
    $workout_date = $_POST['workout_date'] ?? '';
    $workout_time_from = $_POST['workout_time_from'] ?? '';
    $workout_time_to = $_POST['workout_time_to'] ?? '';
    $workout_location = $_POST['workout_location'] ?? '';
    if (!str_starts_with($workout_location, '📍')) {
        $workout_location = '📍' . $workout_location;
    }
    //auto add location prefix

    $workout_notes = $_POST['workout_notes'] ?? '';

    // Calculate duration
    if (!empty($workout_time_from) && !empty($workout_time_to)) {
        $start = new DateTime($workout_time_from);
        $end = new DateTime($workout_time_to);
        $interval = $start->diff($end);

        $hours = $interval->h;
        $minutes = $interval->i;
        $duration = "{$hours} hr {$minutes} min";
    } else {
        $duration = "0 hr 0 min"; // Default if times are missing
    }

    $sql = "INSERT INTO workoutstbl (workout_name, userID, workout_type, workout_date, workout_time_from, workout_time_to, duration, workout_location, workout_notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($connection, $sql);

    mysqli_stmt_bind_param($stmt, "sisssssss", $workout_name, $userid, $workout_type, $workout_date, $workout_time_from, $workout_time_to, $duration, $workout_location, $workout_notes);

    if (mysqli_stmt_execute($stmt)) {
        $formSubmitted = true; // Set to true to show success message
        header("Location: submit_schedule.php?success=1");
        exit();
    } else {
        $errorMessage = 'Error: ' . mysqli_error($connection);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($connection);
}

$showAlert = isset($_GET['success']) && $_GET['success'] == '1';
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Schedule</title>
    <link rel="stylesheet" href="submits.css?v=<?php echo time(); ?>">
    <script src="submits.js" defer></script>
    <link rel="stylesheet" href="../general/Navigation.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="collage-container">
            <div class="collage-item">
                <img src="../images/workout 1.jpg" alt="Workout Image 1">
            </div>
            <div class="collage-item">
                <img src="../images/workout 6.jpg" alt="Workout Image 2">
            </div>
            <div class="collage-item">
                <img src="../images/workout 3.jpg" alt="Workout Image 3">
            </div>
            <div class="collage-item">
                <img src="../images/workout 4.jpg" alt="Workout Image 4">
            </div>
            <div class="collage-item">
                <img src="../images/workout 5.jpg" alt="Workout Image 5">
            </div>
        </div>
    </header>

    <div class="sidebar-title">
        <h1>Add New Schedule</h1>
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

            <div class="shortcuts">
                <h3>Shortcuts</h3>
                <ul>
                    <li><button class="shortcut-btn2" onclick="goToAddWorkout()">Add New Workout</button></li>
                </ul>
            </div>

            <!-- Navigation -->
            <nav class="navigations">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="weeklys.php">Fitness Program</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content for Adding New Workout -->
        <main class="main-content">
            <section class="add-workout-section">
                <h2>Schedule Information</h2>

                <!-- Form to Add New Workout -->
                <form id="workoutForm" action="submit_schedule.php" method="POST" class="add-workout-form">
                    <div class="form-group">
                    <label for="workout-name">Workout Name:</label>
                        <select id="workout-name" name="workout_name" required>
                            <option value="">Select workout</option>
                            <?php
                            include '../general/dbconn.php';
                            $query = "SELECT DISTINCT category_name FROM workout_categoriestbl WHERE userID = ? OR is_system = 1";
                            $stmt = mysqli_prepare($connection, $query);
                            mysqli_stmt_bind_param($stmt, "i", $userid);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<option value='" . htmlspecialchars($row['category_name']) . "'>" . htmlspecialchars($row['category_name']) . "</option>";
                            }

                            mysqli_stmt_close($stmt);
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="workout-type">Workout Type:</label>
                        <select id="workout-type" name="workout_type" required>
                            <option value="">Select workout type</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="workout-date">Date:</label>
                        <input type="date" id="workout-date" name="workout_date" required>
                    </div>

                    <div class="form-group">
                        <label for="workout-time-from">Start Time:</label>
                        <input type="time" id="workout-time-from" name="workout_time_from" required>
                    </div>

                    <div class="form-group">
                        <label for="workout-time-to">End Time:</label>
                        <input type="time" id="workout-time-to" name="workout_time_to" required>
                    </div>

                    <div class="form-group">
                        <label for="workout_duration">Duration (minutes):</label>
                        <input type="text" id="workout-duration" name="duration" readonly>
                    </div>

                    <div class="form-group">
                        <label for="workout_location">Location:</label>
                        <input type="text" id="workout-location" name="workout_location" oninput="addLocationPrefix()" required>
                    </div>

                    <div class="form-group">
                        <label for="workout-notes">Notes:</label>
                        <textarea id="workout-notes" name="workout_notes" placeholder="Add any notes about your workout" rows="4"></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Add Schedule</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>

<?php
include '../general/footer.php';
?>