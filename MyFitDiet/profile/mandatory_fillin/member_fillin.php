<?php
session_start();
include '../../general/dbconn.php'; 

$UserID = $_SESSION['userid'];
// Fetch user details
$sqlUser = "SELECT * FROM user WHERE userID = ?";
$stmtUser = $connection->prepare($sqlUser);
$stmtUser->bind_param("i", $UserID);
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
$stmtHW->bind_param("i", $UserID);
$stmtHW->execute();
$hwData = $stmtHW->get_result()->fetch_assoc();
$stmtHW->close();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_all'])) {
        // Get form values
        $newHeight = $_POST['height'];
        $newWeight = $_POST['weight'];

        $newPrimaryGoal = $_POST['primary_goal'];
        $newSecondaryGoal = $_POST['secondary_goal'];
        $newDesiredWorkout = $_POST['desired_workout'];
        $newTargetFitnessLvl = $_POST['target_fitness_level'];
        $newFavExercise = $_POST['favourite_exercise'];
        $newWorkoutDurationPref = $_POST['workout_duration'];
        $newPrefTimeOfDay = $_POST['pref_tod'];
        $newWorkoutIntensityPref = $_POST['workout_intensity'];
        $newDietaryGoal = $_POST['dietary_goal'];
        $newFoodAllergies = isset($_POST['food_allergies']) ? implode(',', $_POST['food_allergies']) : '';
        $newMealTimingPref = $_POST['meal_timing'];
        $newPrefDietStyle = $_POST['diet_style'];

        try {
            // Update Height & Weight Table
            $sqlUpdateHW = "INSERT INTO height_weight (userID, height, weight) VALUES (?, ?, ?)";
            $stmtUpdateHW = $connection->prepare($sqlUpdateHW);
            $stmtUpdateHW->bind_param("idd", $UserID, $newHeight, $newWeight);
            $stmtUpdateHW->execute();
            $stmtUpdateHW->close();

          
            $sqlUpdateFitness = "UPDATE user SET Primary_goal = ?, Secondary_goal = ?, Desired_workout = ?, Target_fitness_lvl = ?, Fav_exercise = ?, Workout_duration_pref = ?, Pref_timeOfDay = ?, Workout_intensity_pref = ?, Dietary_goal = ?, Food_allergies = ?, Meal_timing_pref = ?, Pref_diet_style = ? WHERE userID = ?";
            $stmtUpdateFitness = $connection->prepare($sqlUpdateFitness);
            $stmtUpdateFitness->bind_param("ssssssssssssi", $newPrimaryGoal, $newSecondaryGoal, $newDesiredWorkout, $newTargetFitnessLvl, $newFavExercise, $newWorkoutDurationPref, $newPrefTimeOfDay, $newWorkoutIntensityPref, $newDietaryGoal, $newFoodAllergies, $newMealTimingPref, $newPrefDietStyle, $UserID);
            $stmtUpdateFitness->execute();
            $stmtUpdateFitness->close();

            // Redirect to member_homepage.php after successful submission
            echo "Redirecting to member_homepage.php...";
            header("Location: /MyFitDiet/homepage/member_homepage.php");            
            exit();            
        }
        catch (Exception $e) {
            $connection->rollback();
            echo "Error: " . $e->getMessage();
        }
    }
}


// Fetch all exercise categories from the database
$sql = "SELECT category_name FROM workout_categoriestbl";
$resultCategories = $connection->query($sql);

// Assign values from database
$currentPrimaryGoal = $userData['Primary_goal'] ??'';
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
    <link rel="stylesheet" href="member_fillin.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
    <p class="username"><span><?php echo htmlspecialchars($userData['Username']); ?>,</span></p>
    <p>Kindly fill in your information</p>

        <form action="" method="POST" id="profileForm" enctype="multipart/form-data">
            <div class="all">
                <div id="form" class="form">
                <div class="info">
                    <p>Height (cm) 
                        <input type="number" step="0.1" name="height" 
                               value="<?php echo isset($hwData['height']) ? htmlspecialchars($hwData['height']) : ''; ?>" 
                               placeholder="Enter height">
                    </p>
                    <p>Weight (kg) 
                        <input type="number" step="0.1" name="weight" 
                               value="<?php echo isset($hwData['weight']) ? htmlspecialchars($hwData['weight']) : ''; ?>" 
                               placeholder="Enter weight">
                    </p>
                </div>

                    <div class="other">
                        <h3>Health and Fitness Goal</h3>
                        <label>Primary Goal:</label>
                        <select name="primary_goal" required>
                            <option value="Select primary goal" hidden><?php echo empty($currentPrimaryGoal) ? 'Select primary goal' : htmlspecialchars($currentPrimaryGoal); ?></option>
                            <option value="Weight loss" <?php echo ($currentPrimaryGoal == 'Weight loss') ? 'selected' : ''; ?>>Weight loss</option>
                            <option value="Muscle gain" <?php echo ($currentPrimaryGoal == 'Muscle gain') ? 'selected' : ''; ?>>Muscle gain</option>
                            <option value="Improve endurance" <?php echo ($currentPrimaryGoal == 'Improve endurance') ? 'selected' : ''; ?>>Improve endurance</option>
                            <option value="Increase strength" <?php echo ($currentPrimaryGoal == 'Increase strength') ? 'selected' : ''; ?>>Increase strength</option>
                            <option value="Improve flexibility" <?php echo ($currentPrimaryGoal == 'Improve flexibility') ? 'selected' : ''; ?>>Improve flexibility</option>
                            <option value="Enhance athletic performance" <?php echo ($currentPrimaryGoal == 'Enhance athletic performance') ? 'selected' : ''; ?>>Enhance athletic performance</option>
                            <option value="Body toning and shaping" <?php echo ($currentPrimaryGoal == 'Body toning and shaping') ? 'selected' : ''; ?>>Body toning and shaping</option>
                            <option value="Posture improvement" <?php echo ($currentPrimaryGoal == 'Posture improvement') ? 'selected' : ''; ?>>Posture improvement</option>
                            <option value="Rehabilitation and injury recovery" <?php echo ($currentPrimaryGoal == 'Rehabilitation and injury recovery') ? 'selected' : ''; ?>>Rehabilitation and injury recovery</option>
                            <option value="Increase energy levels" <?php echo ($currentPrimaryGoal == 'Increase energy levels') ? 'selected' : ''; ?>>Increase energy levels</option>
                            <option value="Reduce stress" <?php echo ($currentPrimaryGoal == 'Reduce stress') ? 'selected' : ''; ?>>Reduce stress</option>
                            <option value="Improve cardiovascular health" <?php echo ($currentPrimaryGoal == 'Improve cardiovascular health') ? 'selected' : ''; ?>>Improve cardiovascular health</option>
                            <option value="Build core strength" <?php echo ($currentPrimaryGoal == 'Build core strength') ? 'selected' : ''; ?>>Build core strength</option>
                            <option value="Achieve body composition goals" <?php echo ($currentPrimaryGoal == 'Achieve body composition goals') ? 'selected' : ''; ?>>Achieve body composition goals</option>
                            <option value="Improve overall fitness" <?php echo ($currentPrimaryGoal == 'Improve overall fitness') ? 'selected' : ''; ?>>Improve overall fitness</option>
                            <option value="Train for a specific event (e.g., marathon)" <?php echo ($currentPrimaryGoal == 'Train for a specific event (e.g., marathon)') ? 'selected' : ''; ?>>Train for a specific event (e.g., marathon)</option>
                            <option value="Improve balance and coordination" <?php echo ($currentPrimaryGoal == 'Improve balance and coordination') ? 'selected' : ''; ?>>Improve balance and coordination</option>
                            <option value="Boost confidence and mental health" <?php echo ($currentPrimaryGoal == 'Boost confidence and mental health') ? 'selected' : ''; ?>>Boost confidence and mental health</option>
                            <option value="Improve mobility" <?php echo ($currentPrimaryGoal == 'Improve mobility') ? 'selected' : ''; ?>>Improve mobility</option>
                            <option value="Develop better eating habits" <?php echo ($currentPrimaryGoal == 'Develop better eating habits') ? 'selected' : ''; ?>>Develop better eating habits</option>
                            <option value="Learn proper exercise techniques" <?php echo ($currentPrimaryGoal == 'Learn proper exercise techniques') ? 'selected' : ''; ?>>Learn proper exercise techniques</option>
                            <option value="Increase workout consistency" <?php echo ($currentPrimaryGoal == 'Increase workout consistency') ? 'selected' : ''; ?>>Increase workout consistency</option>
                            <option value="Build sustainable lifestyle habits" <?php echo ($currentPrimaryGoal == 'Build sustainable lifestyle habits') ? 'selected' : ''; ?>>Build sustainable lifestyle habits</option>
                            <option value="Achieve healthy aging" <?php echo ($currentPrimaryGoal == 'Achieve healthy aging') ? 'selected' : ''; ?>>Achieve healthy aging</option>
                            <option value="Improve sleep quality" <?php echo ($currentPrimaryGoal == 'Improve sleep quality') ? 'selected' : ''; ?>>Improve sleep quality</option>
                            <option value="Reduce chronic pain (e.g., back pain)" <?php echo ($currentPrimaryGoal == 'Reduce chronic pain (e.g., back pain)') ? 'selected' : ''; ?>>Reduce chronic pain (e.g., back pain)</option>
                            <option value="Develop speed and agility" <?php echo ($currentPrimaryGoal == 'Develop speed and agility') ? 'selected' : ''; ?>>Develop speed and agility</option>
                            <option value="Prevent lifestyle diseases (e.g., diabetes)" <?php echo ($currentPrimaryGoal == 'Prevent lifestyle diseases (e.g., diabetes)') ? 'selected' : ''; ?>>Prevent lifestyle diseases (e.g., diabetes)</option>
                            <option value="Improve joint health" <?php echo ($currentPrimaryGoal == 'Improve joint health') ? 'selected' : ''; ?>>Improve joint health</option>
                            <option value="Maintain fitness during pregnancy" <?php echo ($currentPrimaryGoal == 'Maintain fitness during pregnancy') ? 'selected' : ''; ?>>Maintain fitness during pregnancy</option>
                        </select>
                        <label>Secondary Goal:</label>
                        <select name="secondary_goal" required>
                            <option value="Select secondary goal" hidden><?php echo empty($currentSecondaryGoal) ? 'Select secondary goal' : htmlspecialchars($currentSecondaryGoal); ?></option>
                            <option value="Weight loss" <?php echo ($currentSecondaryGoal == 'Weight loss') ? 'selected' : ''; ?>>Weight loss</option>
                            <option value="Muscle gain" <?php echo ($currentSecondaryGoal == 'Muscle gain') ? 'selected' : ''; ?>>Muscle gain</option>
                            <option value="Improve endurance" <?php echo ($currentSecondaryGoal == 'Improve endurance') ? 'selected' : ''; ?>>Improve endurance</option>
                            <option value="Increase strength" <?php echo ($currentSecondaryGoal == 'Increase strength') ? 'selected' : ''; ?>>Increase strength</option>
                            <option value="Improve flexibility" <?php echo ($currentSecondaryGoal == 'Improve flexibility') ? 'selected' : ''; ?>>Improve flexibility</option>
                            <option value="Enhance athletic performance" <?php echo ($currentSecondaryGoal == 'Enhance athletic performance') ? 'selected' : ''; ?>>Enhance athletic performance</option>
                            <option value="Body toning and shaping" <?php echo ($currentSecondaryGoal == 'Body toning and shaping') ? 'selected' : ''; ?>>Body toning and shaping</option>
                            <option value="Posture improvement" <?php echo ($currentSecondaryGoal == 'Posture improvement') ? 'selected' : ''; ?>>Posture improvement</option>
                            <option value="Rehabilitation and injury recovery" <?php echo ($currentSecondaryGoal == 'Rehabilitation and injury recovery') ? 'selected' : ''; ?>>Rehabilitation and injury recovery</option>
                            <option value="Increase energy levels" <?php echo ($currentSecondaryGoal == 'Increase energy levels') ? 'selected' : ''; ?>>Increase energy levels</option>
                            <option value="Reduce stress" <?php echo ($currentSecondaryGoal == 'Reduce stress') ? 'selected' : ''; ?>>Reduce stress</option>
                            <option value="Improve cardiovascular health" <?php echo ($currentSecondaryGoal == 'Improve cardiovascular health') ? 'selected' : ''; ?>>Improve cardiovascular health</option>
                            <option value="Build core strength" <?php echo ($currentSecondaryGoal == 'Build core strength') ? 'selected' : ''; ?>>Build core strength</option>
                            <option value="Achieve body composition goals" <?php echo ($currentSecondaryGoal == 'Achieve body composition goals') ? 'selected' : ''; ?>>Achieve body composition goals</option>
                            <option value="Improve overall fitness" <?php echo ($currentSecondaryGoal == 'Improve overall fitness') ? 'selected' : ''; ?>>Improve overall fitness</option>
                            <option value="Train for a specific event (e.g., marathon)" <?php echo ($currentSecondaryGoal == 'Train for a specific event (e.g., marathon)') ? 'selected' : ''; ?>>Train for a specific event (e.g., marathon)</option>
                            <option value="Improve balance and coordination" <?php echo ($currentSecondaryGoal == 'Improve balance and coordination') ? 'selected' : ''; ?>>Improve balance and coordination</option>
                            <option value="Boost confidence and mental health" <?php echo ($currentSecondaryGoal == 'Boost confidence and mental health') ? 'selected' : ''; ?>>Boost confidence and mental health</option>
                            <option value="Improve mobility" <?php echo ($currentSecondaryGoal == 'Improve mobility') ? 'selected' : ''; ?>>Improve mobility</option>
                            <option value="Develop better eating habits" <?php echo ($currentSecondaryGoal == 'Develop better eating habits') ? 'selected' : ''; ?>>Develop better eating habits</option>
                            <option value="Learn proper exercise techniques" <?php echo ($currentSecondaryGoal == 'Learn proper exercise techniques') ? 'selected' : ''; ?>>Learn proper exercise techniques</option>
                            <option value="Increase workout consistency" <?php echo ($currentSecondaryGoal == 'Increase workout consistency') ? 'selected' : ''; ?>>Increase workout consistency</option>
                            <option value="Build sustainable lifestyle habits" <?php echo ($currentSecondaryGoal == 'Build sustainable lifestyle habits') ? 'selected' : ''; ?>>Build sustainable lifestyle habits</option>
                            <option value="Achieve healthy aging" <?php echo ($currentSecondaryGoal == 'Achieve healthy aging') ? 'selected' : ''; ?>>Achieve healthy aging</option>
                            <option value="Improve sleep quality" <?php echo ($currentSecondaryGoal == 'Improve sleep quality') ? 'selected' : ''; ?>>Improve sleep quality</option>
                            <option value="Reduce chronic pain (e.g., back pain)" <?php echo ($currentSecondaryGoal == 'Reduce chronic pain (e.g., back pain)') ? 'selected' : ''; ?>>Reduce chronic pain (e.g., back pain)</option>
                            <option value="Develop speed and agility" <?php echo ($currentSecondaryGoal == 'Develop speed and agility') ? 'selected' : ''; ?>>Develop speed and agility</option>
                            <option value="Prevent lifestyle diseases (e.g., diabetes)" <?php echo ($currentSecondaryGoal == 'Prevent lifestyle diseases (e.g., diabetes)') ? 'selected' : ''; ?>>Prevent lifestyle diseases (e.g., diabetes)</option>
                            <option value="Improve joint health" <?php echo ($currentSecondaryGoal == 'Improve joint health') ? 'selected' : ''; ?>>Improve joint health</option>
                            <option value="Maintain fitness during pregnancy" <?php echo ($currentSecondaryGoal == 'Maintain fitness during pregnancy') ? 'selected' : ''; ?>>Maintain fitness during pregnancy</option>
                        </select>
                        <label>Desired Workout Frequency:</label>
                        <select name="desired_workout" required>
                            <option value="Select desired workout frequency" hidden><?php echo empty($currentDesiredWorkout) ? 'Select desired workout frequency' : htmlspecialchars($currentDesiredWorkout); ?></option>
                            <option value="Flexible/as needed" <?php echo ($currentDesiredWorkout == 'Flexible/as needed') ? 'selected' : ''; ?>>Flexible/as needed</option>
                            <option value="Specific days of the week" <?php echo ($currentDesiredWorkout == 'Specific days of the week') ? 'selected' : ''; ?>>Specific days of the week</option>
                            <option value="Every week 1 time" <?php echo ($currentDesiredWorkout == 'Every week 1 time') ? 'selected' : ''; ?>>Every week 1 time</option>
                            <option value="Every week 2 times" <?php echo ($currentDesiredWorkout == 'Every week 2 times') ? 'selected' : ''; ?>>Every week 2 times</option>
                            <option value="Every week 3 times" <?php echo ($currentDesiredWorkout == 'Every week 3 times') ? 'selected' : ''; ?>>Every week 3 times</option>
                            <option value="Every week 4 times" <?php echo ($currentDesiredWorkout == 'Every week 4 times') ? 'selected' : ''; ?>>Every week 4 times</option>
                            <option value="Every week 5 times" <?php echo ($currentDesiredWorkout == 'Every week 5 times') ? 'selected' : ''; ?>>Every week 5 times</option>
                            <option value="Every week 6 times" <?php echo ($currentDesiredWorkout == 'Every week 6 times') ? 'selected' : ''; ?>>Every week 6 times</option>
                            <option value="Every week 7 times" <?php echo ($currentDesiredWorkout == 'Every week 7 times') ? 'selected' : ''; ?>>Every week 7 times</option>
                            <option value="Every week 8 times" <?php echo ($currentDesiredWorkout == 'Every week 8 times') ? 'selected' : ''; ?>>Every week 8 times</option>
                            <option value="Every week 9 times" <?php echo ($currentDesiredWorkout == 'Every week 9 times') ? 'selected' : ''; ?>>Every week 9 times</option>
                            <option value="Every week 10 times" <?php echo ($currentDesiredWorkout == 'Every week 10 times') ? 'selected' : ''; ?>>Every week 10 times</option>
                            <option value="Every week 11 times" <?php echo ($currentDesiredWorkout == 'Every week 11 times') ? 'selected' : ''; ?>>Every week 11 times</option>
                            <option value="Every week 12 times" <?php echo ($currentDesiredWorkout == 'Every week 12 times') ? 'selected' : ''; ?>>Every week 12 times</option>
                            <option value="Every week 13 times" <?php echo ($currentDesiredWorkout == 'Every week 13 times') ? 'selected' : ''; ?>>Every week 13 times</option>
                            <option value="Every week 14 times" <?php echo ($currentDesiredWorkout == 'Every week 14 times') ? 'selected' : ''; ?>>Every week 14 times</option>
                            <option value="Every week 15 times" <?php echo ($currentDesiredWorkout == 'Every week 15 times') ? 'selected' : ''; ?>>Every week 15 times</option>
                            <option value="Every week 16 times" <?php echo ($currentDesiredWorkout == 'Every week 16 times') ? 'selected' : ''; ?>>Every week 16 times</option>
                            <option value="Every week 17 times" <?php echo ($currentDesiredWorkout == 'Every week 17 times') ? 'selected' : ''; ?>>Every week 17 times</option>
                            <option value="Every week 18 times" <?php echo ($currentDesiredWorkout == 'Every week 18 times') ? 'selected' : ''; ?>>Every week 18 times</option>
                            <option value="Every week 19 times" <?php echo ($currentDesiredWorkout == 'Every week 19 times') ? 'selected' : ''; ?>>Every week 19 times</option>
                            <option value="Every week 20 times" <?php echo ($currentDesiredWorkout == 'Every week 20 times') ? 'selected' : ''; ?>>Every week 20 times</option>
                            <option value="Every week 21 times" <?php echo ($currentDesiredWorkout == 'Every week 21 times') ? 'selected' : ''; ?>>Every week 21 times</option>
                        </select>                
                        <label>Target Fitness Level:</label>
                        <select name="target_fitness_level" required>
                            <option value="Select target fitness level" hidden><?php echo empty($currentTargetFitnessLevel) ? 'Select target fitness level' : htmlspecialchars($currentTargetFitnessLevel); ?></option>
                            <option value="Beginner" <?php echo ($currentTargetFitnessLevel == 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo ($currentTargetFitnessLevel == 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo ($currentTargetFitnessLevel == 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                        
                        <h3>Activity Workout Preference</h3>
                        <label>Favourite Exercise:</label>
                        <select name="favourite_exercise" required>
                            <option value="" hidden disabled <?php echo empty($currentFavouriteExercise) ? 'selected' : ''; ?>>Select favourite exercise</option>
                            <?php
                            if (isset($resultCategories) && $resultCategories->num_rows > 0) {
                                while ($row = $resultCategories->fetch_assoc()) {
                                    $category = $row['category_name'];
                                    $selected = (isset($fav_exercise) && $fav_exercise == $category) ? 'selected' : '';
                                    echo "<option value='$category' $selected>$category</option>";
                                }
                            } else {
                                echo "<option value='' disabled>No categories available</option>";
                            }
                            ?>
                        </select>
                        <label>Workout Duration Preference:</label>
                        <select name="workout_duration" required>
                            <option value="Select workout duration preference" hidden><?php echo empty($currentWorkoutDuration) ? 'Select workout duration preference' : htmlspecialchars($currentWorkoutDuration); ?></option>
                            <option value="10-20 minutes" <?php echo ($currentWorkoutDuration == '10-20 minutes') ? 'selected' : ''; ?>>10-20 minutes</option>
                            <option value="30-45 minutes" <?php echo ($currentWorkoutDuration == '30-45 minutes') ? 'selected' : ''; ?>>30-45 minutes</option>
                            <option value="60+ minutes" <?php echo ($currentWorkoutDuration == '60+ minutes') ? 'selected' : ''; ?>>60+ minutes</option>
                        </select>                
                        <label>Preference Time of Day for Workout:</label>
                        <select name="pref_tod" required>
                            <option value="Select preferred time of day for workout" hidden><?php echo empty($currentPrefTOD) ? 'Select preffered time of day for workout' : htmlspecialchars($currentPrefTOD); ?></option>
                            <option value="Early Morning (12AM – 5AM)" <?php echo ($currentPrefTOD == 'Early Morning (12AM – 5AM)') ? 'selected' : ''; ?>>Early Morning (12AM – 5AM)</option>
                            <option value="Morning (5AM - 9AM)" <?php echo ($currentPrefTOD == 'Morning (5AM - 9AM)') ? 'selected' : ''; ?>>Morning (5AM - 9AM)</option>
                            <option value="Late Morning (9AM – 12PM)" <?php echo ($currentPrefTOD == 'Late Morning (9AM – 12PM)') ? 'selected' : ''; ?>>Late Morning (9AM – 12PM)</option>
                            <option value="Afternoon (12PM – 3PM)" <?php echo ($currentPrefTOD == 'Afternoon (12PM – 3PM)') ? 'selected' : ''; ?>>Afternoon (12PM – 3PM)</option>
                            <option value="Late Afternoon (3PM – 6PM)" <?php echo ($currentPrefTOD == 'Late Afternoon (3PM – 6PM)') ? 'selected' : ''; ?>>Late Afternoon (3PM – 6PM)</option>
                            <option value="Evening (6PM – 9PM)" <?php echo ($currentPrefTOD == 'Evening (6PM – 9PM)') ? 'selected' : ''; ?>>Evening (6PM – 9PM)</option>
                            <option value="Night (9PM – 12AM)" <?php echo ($currentPrefTOD == 'Night (9PM – 12AM)') ? 'selected' : ''; ?>>Night (9PM – 12AM)</option>
                        </select>                
                        <label>Workout Intensity Preference:</label>
                        <select name="workout_intensity" required>
                            <option value="Select workout intensity preference" hidden><?php echo empty($currentWorkoutIntensity) ? 'Select workout intensity preference' : htmlspecialchars($currentWorkoutIntensity); ?></option>
                            <option value="Low intensity" <?php echo ($currentWorkoutIntensity == 'Low intensity') ? 'selected' : ''; ?>>Low intensity</option>
                            <option value="Moderate intensity" <?php echo ($currentWorkoutIntensity == 'Moderate intensity') ? 'selected' : ''; ?>>Moderate intensity</option>
                            <option value="High intensity" <?php echo ($currentWorkoutIntensity == 'High intensity') ? 'selected' : ''; ?>>High intensity</option>
                        </select>

                        <h3>Diet Nutrition Preference</h3>
                        <label>Dietary Goal:</label>
                        <select name="dietary_goal" required>
                            <option value="Select dietary goal" hidden><?php echo empty($currentDietaryGoal) ? 'Select dietary goal' : htmlspecialchars($currentDietaryGoal); ?></option>
                            <option value="Weight loss" <?php echo ($currentDietaryGoal == 'Weight loss') ? 'selected' : ''; ?>>Weight loss</option>
                            <option value="Muscle gain" <?php echo ($currentDietaryGoal == 'Muscle gain') ? 'selected' : ''; ?>>Muscle gain</option>
                            <option value="Weight Maintenance" <?php echo ($currentDietaryGoal == 'Weight Maintenance') ? 'selected' : ''; ?>>Weight Maintenance</option>
                            <option value="Performance & Endurance" <?php echo ($currentDietaryGoal == 'Performance & Endurance') ? 'selected' : ''; ?>>Performance & Endurance</option>
                            <option value="General Healthy Eating" <?php echo ($currentDietaryGoal == 'General Healthy Eating') ? 'selected' : ''; ?>>General Healthy Eating</option>
                        </select>
                        <label>Food Allergies:</label>
                        <div class="multiselect">
                            <div class="selectBox" onclick="showCheckboxes()">
                                <select id="selectedOptionsDisplay">
                                    <option value="Select food allergies"><?php echo empty($currentFoodAllergies) ? 'Select food allergies' : htmlspecialchars($currentFoodAllergies); ?></option>
                                </select>
                                <div class="overSelect"></div>
                            </div>
                            <div id="checkboxes" class="checkboxes">
                                <label for="none">
                                    <input type="checkbox" id="none" name="food_allergies[]" value="None" <?php echo (in_array('None', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />None
                                </label>
                                <label for="peanuts">
                                    <input type="checkbox" name="food_allergies[]" value="Peanuts" <?php echo (in_array('Peanuts', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Peanuts
                                </label>
                                <label for="tree-nuts">
                                    <input type="checkbox" name="food_allergies[]" value="Tree-nuts" <?php echo (in_array('Tree-nuts', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Tree-nuts (Almonds, Walnuts, Cashews, etc.)
                                </label>
                                <label for="dairy">
                                    <input type="checkbox" name="food_allergies[]" value="Dairy" <?php echo (in_array('Dairy', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Dairy (Milk, Cheese, Yogurt, etc.)
                                </label>
                                <label for="gluten">
                                    <input type="checkbox" name="food_allergies[]" value="Gluten" <?php echo (in_array('Gluten', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Gluten (Wheat, Barley, Rye)
                                </label>
                                <label for="soy">
                                    <input type="checkbox" name="food_allergies[]" value="Soy" <?php echo (in_array('Soy', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Soy (Tofu, Soy Sauce, Edamame, etc.)
                                </label>
                                <label for="eggs">
                                    <input type="checkbox" name="food_allergies[]" value="Eggs" <?php echo (in_array('Eggs', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Eggs
                                </label>
                                <label for="shellfish">
                                    <input type="checkbox" name="food_allergies[]" value="Shellfish" <?php echo (in_array('Shellfish', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Shellfish (Shrimp, Crab, Lobster, etc.)
                                </label>
                                <label for="fish">
                                    <input type="checkbox" name="food_allergies[]" value="Fish" <?php echo (in_array('Fish', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Fish (Salmon, Tuna, Cod, etc.)
                                </label>
                                <label for="sesame">
                                    <input type="checkbox" name="food_allergies[]" value="Sesame" <?php echo (in_array('Sesame', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Sesame (Seeds, Oil, Tahini, etc.)
                                </label>
                                <label for="mustard">
                                    <input type="checkbox" name="food_allergies[]" value="Mustard" <?php echo (in_array('Mustard', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Mustard
                                </label>
                                <label for="corn">
                                    <input type="checkbox" name="food_allergies[]" value="Corn" <?php echo (in_array('Corn', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Corn (Corn Syrup, Cornmeal, etc.)
                                </label>
                                <label for="celery">
                                    <input type="checkbox" name="food_allergies[]" value="Celery" <?php echo (in_array('Celery', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Celery
                                </label>
                                <label for="lupin">
                                    <input type="checkbox" name="food_allergies[]" value="Lupin" <?php echo (in_array('Lupin', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Lupin
                                </label>
                                <label for="sulfites">
                                    <input type="checkbox" name="food_allergies[]" value="Sulfites" <?php echo (in_array('Sulfites', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Sulfites (Preservatives in dried fruits, wine, etc.)
                                </label>
                                <label for="legumes">
                                    <input type="checkbox" name="food_allergies[]" value="Legumes" <?php echo (in_array('Legumes', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Legumes (Lentils, Chickpeas, beans, etc.)
                                </label>
                                <label for="fruits">
                                    <input type="checkbox" name="food_allergies[]" value="Fruits" <?php echo (in_array('Fruits', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Fruits (Citrus, Strawberries, Bananas, etc.)
                                </label>
                                <label for="vegetables">
                                    <input type="checkbox" name="food_allergies[]" value="Vegetables" <?php echo (in_array('Vegetables', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Vegetables (Carrots, Peppers, Tomatoes, etc.)
                                </label>
                                <label for="nightshades">
                                    <input type="checkbox" name="food_allergies[]" value="Nightshades" <?php echo (in_array('Nightshades', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Nightshades (Tomatoes, Eggplant, Potatoes, Peppers)
                                </label>
                                <label for="chocolate">
                                    <input type="checkbox" name="food_allergies[]" value="Chocolate" <?php echo (in_array('Chocolate', $currentFoodAllergiesArray)) ? 'checked' : ''; ?> onclick="updateSelectedOptions()" />Chocolate (Cocoa, Cacao, etc.)
                                </label>
                            </div>
                        </div>
                        <input type="hidden" id="selectedOptionsInput" name="selectedOptionsInput">

                        <label>Meal Timing Preference:</label>
                        <select name="meal_timing" required>
                            <option value="Select meal timing preference" hidden><?php echo empty($currentMealTiming) ? 'Select meal timing preference' : htmlspecialchars($currentMealTiming); ?></option>
                            <option value="Three Meals a Day (Breakfast, Lunch, Dinner)" <?php echo ($currentMealTiming == 'Three Meals a Day (Breakfast, Lunch, Dinner)') ? 'selected' : ''; ?>>Three Meals a Day (Breakfast, Lunch, Dinner)</option>
                            <option value="Small Frequent Meals (Every 2-3 Hours)" <?php echo ($currentMealTiming == 'Small Frequent Meals (Every 2-3 Hours)') ? 'selected' : ''; ?>>Small Frequent Meals (Every 2-3 Hours)</option>
                            <option value="Intermittent Fasting (Time-Restricted Eating)" <?php echo ($currentMealTiming == 'Intermittent Fasting (Time-Restricted Eating)') ? 'selected' : ''; ?>>Intermittent Fasting (Time-Restricted Eating)</option>
                            <option value="Early Dinner (Last Meal Before 6-7 PM)" <?php echo ($currentMealTiming == 'Early Dinner (Last Meal Before 6-7 PM)') ? 'selected' : ''; ?>>Early Dinner (Last Meal Before 6-7 PM)</option>
                            <option value="Late Dinner (Last Meal After 8 PM)" <?php echo ($currentMealTiming == 'Late Dinner (Last Meal After 8 PM)') ? 'selected' : ''; ?>>Late Dinner (Last Meal After 8 PM)</option>
                            <option value="Brunch Style (Skipping Breakfast, Eating Late Morning)" <?php echo ($currentMealTiming == 'Brunch Style (Skipping Breakfast, Eating Late Morning)') ? 'selected' : ''; ?>>Brunch Style (Skipping Breakfast, Eating Late Morning)</option>
                        </select>
                        <label>Preference Diet Style:</label>
                        <select name="diet_style" required>
                            <option value="Select preferred diet style" hidden><?php echo empty($currentDietStyle) ? 'Select preferred diet style' : htmlspecialchars($currentDietStyle); ?></option>
                            <option value="Balanced Diet (Mix of all food groups)" <?php echo ($currentDietStyle == 'Balanced Diet (Mix of all food groups)') ? 'selected' : ''; ?>>Balanced Diet (Mix of all food groups)</option>
                            <option value="Mediterranean Diet (Olive oil, fish, whole grains, vegetables)" <?php echo ($currentDietStyle == 'Mediterranean Diet (Olive oil, fish, whole grains, vegetables)') ? 'selected' : ''; ?>>Mediterranean Diet (Olive oil, fish, whole grains, vegetables)</option>
                            <option value="Low-Carb Diet (Reduced carbs, higher protein and fat)" <?php echo ($currentDietStyle == 'Low-Carb Diet (Reduced carbs, higher protein and fat)') ? 'selected' : ''; ?>>Low-Carb Diet (Reduced carbs, higher protein and fat)</option>
                            <option value="Ketogenic (Very low-carb, high-fat)" <?php echo ($currentDietStyle == 'Ketogenic (Very low-carb, high-fat)') ? 'selected' : ''; ?>>Ketogenic (Very low-carb, high-fat)</option>
                            <option value="Paleo (Whole foods, lean meats, nuts, no processed foods)" <?php echo ($currentDietStyle == 'Paleo (Whole foods, lean meats, nuts, no processed foods)') ? 'selected' : ''; ?>>Paleo (Whole foods, lean meats, nuts, no processed foods)</option>
                            <option value="Vegetarian (No meat, but allows dairy and eggs)" <?php echo ($currentDietStyle == 'Vegetarian (No meat, but allows dairy and eggs)') ? 'selected' : ''; ?>>Vegetarian (No meat, but allows dairy and eggs)</option>
                            <option value="Vegan (No animal products at all)" <?php echo ($currentDietStyle == 'Vegan (No animal products at all)') ? 'selected' : ''; ?>>Vegan (No animal products at all)</option>
                            <option value="High-Protein (Focus on lean meats, legumes, and dairy)" <?php echo ($currentDietStyle == 'High-Protein (Focus on lean meats, legumes, and dairy)') ? 'selected' : ''; ?>>High-Protein (Focus on lean meats, legumes, and dairy)</option>
                            <option value="Plant-Based (mostly plants, but may include some animal products)" <?php echo ($currentDietStyle == 'Plant-Based (mostly plants, but may include some animal products)') ? 'selected' : ''; ?>>Plant-Based (mostly plants, but may include some animal products)</option>
                            <option value="Gluten-Free (No wheat, barley, or rye)" <?php echo ($currentDietStyle == 'Gluten-Free (No wheat, barley, or rye)') ? 'selected' : ''; ?>>Gluten-Free (No wheat, barley, or rye)</option>
                            <option value="Dairy-Free (No milk, cheese, yogurt, etc.)" <?php echo ($currentDietStyle == 'Dairy-Free (No milk, cheese, yogurt, etc.)') ? 'selected' : ''; ?>>Dairy-Free (No milk, cheese, yogurt, etc.)</option>
                        </select>
                    </div>
                </div>
                <div class="upload-container">
                    <button type="submit" name="update_all">Update</button>
                </div>  
            </div> 
        </form>
    </div>
</body>
</html>

<script>
var expanded = false;

function showCheckboxes() {
    var checkboxes = document.getElementById("checkboxes");
    if (!expanded) {
        checkboxes.style.display = "block";
        expanded = true;
    } else {
        checkboxes.style.display = "none";
        expanded = false;
        updateSelectedOptions();
    }
}

function updateSelectedOptions() {
    var noneCheckbox = document.getElementById('none');
    var checkboxes = document.querySelectorAll('input[name="food_allergies[]"]:not(#none)');
    var selected = [];

    if (noneCheckbox.checked) {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = false;
            checkbox.disabled = true;
        });
        selected.push(noneCheckbox.value);
    } else {
        checkboxes.forEach((checkbox) => {
            checkbox.disabled = false;
            if (checkbox.checked) {
                selected.push(checkbox.value);
            }
        });
    }

    document.getElementById('selectedOptionsInput').value = selected.join(', ');
    document.getElementById('selectedOptionsDisplay').options[0].text = selected.length > 0 ? selected.join(', ') : 'Select food allergies';
}

// Initialize the selected options display on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedOptions();
});

// Function to validate the form before submission
function validateForm(event) {
    let isValid = true;

    // Get all required select elements
    const requiredSelects = document.querySelectorAll('select[required]');
    requiredSelects.forEach((select) => {
        const selectedOption = select.options[select.selectedIndex]; // Get selected option
        console.log("Selected Value:", select.value); // Debugging
        console.log("Is Hidden:", selectedOption.hasAttribute("hidden")); // Debugging

        if (!select.value || selectedOption.hasAttribute("hidden")) {
            select.style.border = "2px solid red"; // Highlight in red
            isValid = false;
        } else {
            select.style.border = ""; // Reset border if valid
        }
    });


    // Get all required input fields
    const requiredInputs = document.querySelectorAll('input[required]');
    requiredInputs.forEach((input) => {
        if (!input.value) {
            input.style.border = "2px solid red"; // Highlight in red
            isValid = false;
        } else {
            input.style.border = ""; // Reset border if valid
        }
    });

    // Prevent form submission if validation fails
    if (!isValid) {
        event.preventDefault();
        alert("Please fill in all required fields.");
    }
}

// Attach the validation function to the form's submit event
document.getElementById("profileForm").addEventListener("submit", validateForm);
</script>