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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $workout_name = ucfirst(strtolower(trim(mysqli_real_escape_string($connection, $_POST['workout_name']))));
    $workout_type = ucfirst(strtolower(trim(mysqli_real_escape_string($connection, $_POST['workout_type']))));
    $workout_description = isset($_POST['workout_description']) ? mysqli_real_escape_string($connection, $_POST['workout_description']) : 'No registered workouts';

    if (isset($_FILES['workout_image']) && $_FILES['workout_image']['error'] == 0) {
        $image_tmp = $_FILES['workout_image']['tmp_name'];
        $image_data = file_get_contents($image_tmp);
    } else {
        $image_data = NULL;
    }

    $workout_location = "📍" . mysqli_real_escape_string($connection, $_POST['workout_location']);

    if ($userrole === "admin" || $userrole === "manager") {
        $is_system = 1;
    } else {
        $is_system = 0;
    }

    $query = "INSERT INTO workout_categoriestbl (userID, category_name, category_type, category_image, category_description, is_system) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "issbsi", $userid, $workout_name, $workout_type, $null, $workout_description, $is_system);
    
    if ($image_data) {
        mysqli_stmt_send_long_data($stmt, 3, $image_data);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: submit_workout.php?success=true");
        exit();
    } else {
        echo "Database Error: " . mysqli_error($connection);
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($connection);
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Workout</title>
    <link rel="stylesheet" href="workout.css?v=<?php echo time(); ?>">
    <script src="workout.js" defer></script>
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
        <h1>Add New Workout</h1>
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
                    <li><button class="shortcut-btn2" onclick="goToAddSchedule()">Add New Schedule</button></li>
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
                <h2>Workout Information</h2>

                <!-- Form to Add New Workout -->
                <form id="workoutForm" action="submit_workout.php" method="POST" class="add-workout-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="workout-name">Workout Name:</label>
                        <input type="text" id="workout-name" name="workout_name" required>
                    </div>

                    <div class="form-group">
                        <label for="workout-type">Workout Type:</label>
                        <input type="text" id="workout-type" name="workout_type" required>
                    </div>

                    <div class="form-group">
                        <label for="workout-image">Workout Image:</label>
                        <input type="file" id="workout-image" name="workout_image" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label for="workout-description">Description:</label>
                        <input type="text" id="workout-description" name="workout_description" value="No registered workouts" readonly>
                    </div>

                    <button type="submit" class="submit-btn">Add Workout</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>