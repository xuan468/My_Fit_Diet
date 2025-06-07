<?php
session_start();

include "../../general/dbconn.php";
include '../../general/member-nav.php';

$loggedInUserID = $_SESSION['userid'];

$profileUserID = $_GET['profileUserID'];

// Fetch user details
$sqlUser = "SELECT * FROM user WHERE userID = ?";
$stmtUser = $connection->prepare($sqlUser);
$stmtUser->bind_param("i", $profileUserID);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

// Fetch latest height and weight
$sqlHeightWeight = "
    SELECT height, weight FROM height_weight 
    WHERE userID = ? 
    ORDER BY update_time DESC 
    LIMIT 1";
$stmtHW = $connection->prepare($sqlHeightWeight);
$stmtHW->bind_param("i", $profileUserID);
$stmtHW->execute();
$hwData = $stmtHW->get_result()->fetch_assoc();
$stmtHW->close();

// Fetch follower and following count
$sqlFollowers = "
    SELECT 
        (SELECT COUNT(*) FROM following_user WHERE followedID = ?) AS num_followers,
        (SELECT COUNT(*) FROM following_user WHERE followID = ?) AS num_following";
$stmtFollow = $connection->prepare($sqlFollowers);
$stmtFollow->bind_param("ii", $profileUserID, $profileUserID);
$stmtFollow->execute();
$followData = $stmtFollow->get_result()->fetch_assoc();
$stmtFollow->close();

// Check if the logged-in user is following the profile user
$isFollowing = false;
if ($profileUserID != $loggedInUserID) {
    $sqlIsFollowing = "SELECT * FROM following_user WHERE followID = ? AND followedID = ?";
    $stmtIsFollowing = $connection->prepare($sqlIsFollowing);
    $stmtIsFollowing->bind_param("ii", $loggedInUserID, $profileUserID);
    $stmtIsFollowing->execute();
    $isFollowing = $stmtIsFollowing->get_result()->num_rows > 0;
    $stmtIsFollowing->close();
}



// Profile Picture
$imageSrc = (!empty($userData['Profile_pic']))
    ? "data:image/jpeg;base64," . base64_encode($userData['Profile_pic'])
    : "icons/default_profile.png";
    
// Assign values from database
$currentGender = $userData['Gender'] ?? '';
$currentCountry = $userData['Country'] ?? '';
$currentPrimaryGoal = $userData['Primary_goal'] ?? '';
$currentSecondaryGoal = $userData['Secondary_goal'] ?? '';
$currentDesiredWorkout = $userData['Desired_workout'] ?? '';
$currentTargetFitnessLevel = $userData['Target_fitness_lvl'] ?? '';
$currentFavouriteExercise = $userData['Fav_exercise'] ?? '';
$currentWorkoutDuration = $userData['Workout_duration_pref'] ?? '';
$currentPrefTOD = $userData['Pref_timeOfDay'] ?? '';
$currentWorkoutIntensity = $userData['Workout_intensity_pref'] ?? '';
$currentDietaryGoal = $userData['Dietary_goal'] ?? '';  

$currentFoodAllergies = $userData['Food_allergies'] ?? '';
$currentFoodAllergiesArray = explode(',', $currentFoodAllergies);

$currentMealTiming = $userData['Meal_timing_pref'] ?? '';
$currentDietStyle = $userData['Pref_diet_style'] ?? '';

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="view-member.css?v=<?php echo time(); ?>">

</head>
<body>

    <div class="profile-page">
        <div class="general-info">
            <div class="username-follow">
                <div class="top-rectangle"></div>
                <p class="username"><?php echo htmlspecialchars($userData['Username']); ?></p>
                <p class="follower">Followers <?php echo htmlspecialchars($followData['num_followers']); ?></p>
                <p class="following">Following <?php echo htmlspecialchars($followData['num_following']); ?></p>
                <div class="main-profilepic" id="main-profilepic">
                    <img src="<?php echo $imageSrc; ?>" alt="Main Profile Picture" class="main-profile-pic">
                </div>
                <button class="follow-btn <?php echo ($isFollowing ? 'unfollow-btn' : ''); ?>" 
                        data-profileid="<?php echo $profileUserID; ?>">
                    <?php echo ($isFollowing ? 'Unfollow' : 'Follow'); ?>
                </button>
            </div>

            <div class="separate">
                <div class="gen-info">
                    <p class="About">𝘼𝘽𝙊𝙐𝙏</p>
                    <div class="info-item">
                        <span class="label">Gender</span> 
                        <span class="data"><?php echo htmlspecialchars($userData['Gender']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Height</span> 
                        <span class="data"><?php echo htmlspecialchars($hwData['height']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Weight</span> 
                        <span class="data"><?php echo htmlspecialchars($hwData['weight']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Age</span> 
                        <span class="data"><?php echo htmlspecialchars($userData['Age']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Country</span> 
                        <span class="data"><?php echo htmlspecialchars($userData['Country']); ?></span>
                    </div>
                </div>

                <div class="others-info">
                    <p>Health and Fitness Goal</p>
                    <div class="info-item2">
                        <span class="label">Primary Goal</span>
                        <span class="data"><?php echo htmlspecialchars($currentPrimaryGoal); ?></span>
                    </div>
                    <div class="info-item2">
                        <span class="label">Secondary Goal</span>
                        <span class="data"><?php echo htmlspecialchars($currentSecondaryGoal); ?></span>
                    </div>
                    <div class="info-item2">
                        <span class="label">Desired Workout</span>
                        <span class="data"><?php echo htmlspecialchars($currentDesiredWorkout); ?></span>
                    </div>
                    <div class="info-item2">
                        <span class="label">Target Fitness Level</span>
                        <span class="data"><?php echo htmlspecialchars($currentTargetFitnessLevel); ?></span>
                    </div>
                    <p>Activity Workout Preference</p>
                    <div class="info-item2">
                        <span class="label">Favourite Exercise</span>
                        <span class="data"><?php echo htmlspecialchars($currentFavouriteExercise); ?></span>
                    </div>
                    <div class="info-item2">
                        <span class="label">Workout Duration Preference</span>
                        <span class="data"><?php echo htmlspecialchars($currentWorkoutDuration); ?></span>
                    </div>
                    <div class="info-item2">
                        <span class="label">Preference Time of Day for Workout</span>
                        <span class="data"><?php echo htmlspecialchars($currentPrefTOD); ?></span>
                    </div>
                    <div class="info-item2">
                        <span class="label">Workout Intensity Preference</span>
                        <span class="data"><?php echo htmlspecialchars($currentWorkoutIntensity); ?></span>
                    </div>
                    <p>Diet Nutrition Preference</p>
                    <div class="info-item2">
                        <span class="label">Dietary Goal</span>
                        <span class="data"><?php echo htmlspecialchars($currentDietaryGoal); ?></span>
                    </div>
                    <div class="info-item2">
                        <span class="label">Food Allergies</span>
                        <span class="data"><?php echo htmlspecialchars($currentFoodAllergies); ?></span>
                    </div>
                    <div class="info-item2">
                        <span class="label">Meal Timing Preference</span>
                        <span class="data"><?php echo htmlspecialchars($currentMealTiming); ?></span>
                    </div>
                    <div class="info-item2">
                        <span class="label">Preference Diet Style</span>
                        <span class="data"><?php echo htmlspecialchars($currentDietStyle); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>   
</body>
</html>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".follow-btn").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            let profileUserID = this.getAttribute("data-profileid"); 
            let button = this;

            fetch("follow_action.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "profileUserID=" + encodeURIComponent(profileUserID)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.textContent = button.textContent.trim() === "Follow" ? "Unfollow" : "Follow";
                    button.classList.toggle("unfollow-btn");
                }
            })
            .catch(error => console.error("Error:", error));
        });
    });
});
</script>

<?php
include '../../general/footer.php';
?>