<?php
include("dbconn.php"); // Ensure you have your DB connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['userid']; // Assuming you store the user ID in session

$sql = "SELECT Profile_pic FROM user WHERE userID = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $user_id);
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
            <ul class="nav-links">
                <a href="/MyFitDiet/homepage/member_homepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'member_homepage.php' ? 'active' : ''; ?>">Homepage</a>
                <a href="/MyFitDiet/report/member_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'member_report.php' ? 'active' : ''; ?>">Summary</a>
                <a href="/MyFitDiet/Food_Information/dietplans.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dietplans.php' ? 'active' : ''; ?>">Diet Plan</a>
                <a href="/MyFitDiet/challenges/challenges.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'challenges.php' ? 'active' : ''; ?>">Challenges</a>
                <a href="/MyFitDiet/schedules/schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : ''; ?>">Workout Schedule</a>
                <a href="/MyFitDiet/community/community.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'community.php' ? 'active' : ''; ?>">Community</a>
                <a href="/MyFitDiet/feedback/feedbacks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedbacks.php' ? 'active' : ''; ?>">Feedback</a>
            </ul>
            <div class="profile-container">
            <img src="<?php echo $imageSrc; ?>" alt="Profile" class="profile-pic" id="profilePic">
            <div class="dropdown-menu" id="dropdownMenu">
                    <a href="/MyFitDiet/profile/member/member.php?userid=<?php echo $_SESSION['userid']; ?>">Profile</a>
                    <a href="/MyFitDiet/login/logout.php">Log Out</a>
                </div>
            </div>
        </nav>
        <?php include 'breadcrumb.php'; ?>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const profilePic = document.getElementById("profilePic");
        const dropdownMenu = document.getElementById("dropdownMenu");

        profilePic.addEventListener("click", function (event) {
            event.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
        });

        document.addEventListener("click", function () {
            dropdownMenu.style.display = "none";
        });

        dropdownMenu.addEventListener("click", function (event) {
            event.stopPropagation();
        });
    });
    </script>
</body>
</html>