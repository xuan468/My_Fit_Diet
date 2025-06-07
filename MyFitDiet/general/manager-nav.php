<?php
include("dbconn.php"); // Ensure you have your DB connection

$staff_id = $_SESSION['userroleid']; // Assuming you store the user ID in session

$sql = "SELECT Profile_pic FROM staff WHERE userroleID = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$stmt->bind_result($profilePic);
$stmt->fetch();
$stmt->close();

$imageData = base64_encode($profilePic);
$imageSrc = 'data:image/jpeg;base64,' . $imageData; // Change image type if needed

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/MyFitDiet/general/top-nav.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="navbar-container">
    <nav class="navbar">
        <img src="/MyFitDiet/general/logo.png?v=<?php echo time(); ?>" alt="Logo" class="logo" id="logo">        <div class="nav-links">
        <div class="nav-links">
            <a href="/MyFitDiet/homepage/manager_homepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manager_homepage.php' ? 'active' : ''; ?>">Homepage</a>
            <a href="/MyFitDiet/report/manager_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manager_report.php' ? 'active' : ''; ?>">Report</a>
            <div class="dropdown">
                <a href="#" id="dietBtn" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['Recipe_Admin.php', 'Food_Knowledge_Admin.php']) ? 'active' : ''; ?>">Diet Plan</a>
                <div class="dropdown-menu" id="dietDropdown">
                    <a href="/MyFitDiet/Food_Information/Recipe_Admin.php">Recipe</a>
                    <a href="/MyFitDiet/Food_Information/Food_Knowledge_Admin.php">Food Knowledge</a>
                </div>
            </div>
            <a href="/MyFitDiet/challenges/adminchallenges.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adminchallenges.php' ? 'active' : ''; ?>">Challenges</a>
            <a href="/MyFitDiet/schedules/schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : ''; ?>">Schedule</a>
            <a href="/MyFitDiet/feedback/feedbacks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedbacks.php' ? 'active' : ''; ?>">Feedback</a>
            <a href="/MyFitDiet/manage_staff/manage_staff.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_staff.php' ? 'active' : ''; ?>">Manage Staff</a>

            <!-- Challenges Dropdown -->
            <div class="dropdown">
                <a href="#" id="reviewBtn" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['reviewchallengecomment.php', 'reviewcommunity.php', 'reviewfeedback.php']) ? 'active' : ''; ?>">Review</a>
                <div class="dropdown-menu" id="reviewDropdown">
                    <a href="/MyFitDiet/profile/reviewer/reviewchallengecomment.php">Challenges</a>
                    <a href="/MyFitDiet/profile/reviewer/reviewcommunity.php">Community</a>
                    <a href="/MyFitDiet/profile/reviewer/reviewfeedback.php">Feedback</a>
                </div>
            </div>
        </div>

        <!-- Profile Dropdown -->
        <div class="profile-container">
            <img src="<?php echo $imageSrc; ?>" alt="Profile" class="profile-pic" id="profilePic">
            <div class="dropdown-menu" id="profileDropdown">
                <a href="/MyFitDiet/profile/staff.php?userid=<?php echo $staff_id; ?>">Profile</a>
                <a href="/MyFitDiet/login/logout.php">Log Out</a>
            </div>
        </div>
    </nav>
</div>

    <script src="script.js"></script>
</body>
</html>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const profilePic = document.getElementById("profilePic");
    const profileDropdown = document.getElementById("profileDropdown");
    const challengesBtn = document.getElementById("reviewBtn");
    const challengesDropdown = document.getElementById("reviewDropdown");
    const dietBtn = document.getElementById("dietBtn");
    const dietDropdown = document.getElementById("dietDropdown");

    // Toggle Profile dropdown
    profilePic.addEventListener("click", function (event) {
        event.stopPropagation();
        profileDropdown.style.display = profileDropdown.style.display === "block" ? "none" : "block";
    });

    // Toggle Challenges dropdown
    challengesBtn.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        reviewDropdown.style.display = reviewDropdown.style.display === "block" ? "none" : "block";
    });

    // Toggle Diet dropdown
    dietBtn.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        dietDropdown.style.display = dietDropdown.style.display === "block" ? "none" : "block";
    });

    // Hide dropdowns when clicking outside
    document.addEventListener("click", function () {
        profileDropdown.style.display = "none";
        reviewDropdown.style.display = "none";
        dietDropdown.style.display = "none";
    });

    // Prevent dropdowns from closing when clicking inside them
    profileDropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });

    reviewDropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });

    dietDropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });
});
</script>

<style>
    body{
        padding-top: 65px !important;
    }
</style>