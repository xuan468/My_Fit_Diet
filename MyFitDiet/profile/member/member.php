<?php
session_start();

include '../../general/member-nav.php';
include '../../general/dbconn.php'; 

$UserID = $_SESSION['userid'];

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_all'])) {
        // Get form values
        $newUsername = $_POST['username'];
        $newGender = $_POST['gender'];
        $newAge = $_POST['age'];
        $newCountry = $_POST['country'];
        $newHeight = $_POST['height'];
        $newWeight = $_POST['weight'];

        try {
            // Update General User Info
            $sqlUpdateUser = "UPDATE user SET Username = ?, Gender = ?, Age = ?, Country = ? WHERE userID = ?";
            $stmtUpdateUser = $connection->prepare($sqlUpdateUser);
            $stmtUpdateUser->bind_param("ssisi", $newUsername, $newGender, $newAge, $newCountry, $UserID);
            $stmtUpdateUser->execute();
            $stmtUpdateUser->close();

            // Update height and weight table
            $sqlUpdateHW = "INSERT INTO height_weight (userID, height, weight) VALUES (?, ?, ?)";
            $stmtUpdateHW = $connection->prepare($sqlUpdateHW);
            $stmtUpdateHW->bind_param("idd", $UserID, $newHeight, $newWeight);
            $stmtUpdateHW->execute();
            $stmtUpdateHW->close();

             // Check if a file was uploaded
            if (isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] === 0) {
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                $fileTmpPath = $_FILES["profile_pic"]["tmp_name"];
                $fileType = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));

                if (in_array($fileType, $allowedTypes)) {
                    // Read file content as binary data
                    $imageData = file_get_contents($fileTmpPath);
                
                    // Store binary data in the database
                    $sql = "UPDATE user SET Profile_pic = ? WHERE userID = ?";
                    $stmt = $connection->prepare($sql);
                    $stmt->bind_param("si", $imageData, $UserID);
                    $stmt->send_long_data(0, $imageData);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    throw new Exception("Invalid file type. Only JPG, PNG, and GIF allowed.");
                }
            }
            echo "<script>
                window.location.href = window.location.href; // Refresh the page
                </script>";
            exit();
        }
        catch (Exception $e) {
            $connection->rollback();
            echo "Error: " . $e->getMessage();
        }
    }

    elseif (isset($_POST['update_others'])) {
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

        try{
            $sqlUpdateFitness = "UPDATE user SET Primary_goal = ?, Secondary_goal = ?, Desired_workout = ?, Target_fitness_lvl = ?, Fav_exercise = ?, Workout_duration_pref = ?, Pref_timeOfDay = ?, Workout_intensity_pref = ?, Dietary_goal = ?, Food_allergies = ?, Meal_timing_pref = ?, Pref_diet_style = ? WHERE userID = ?";
            $stmtUpdateFitness = $connection->prepare($sqlUpdateFitness);
            $stmtUpdateFitness->bind_param("ssssssssssssi", $newPrimaryGoal, $newSecondaryGoal, $newDesiredWorkout, $newTargetFitnessLvl, $newFavExercise, $newWorkoutDurationPref, $newPrefTimeOfDay, $newWorkoutIntensityPref, $newDietaryGoal, $newFoodAllergies, $newMealTimingPref, $newPrefDietStyle, $UserID);
            $stmtUpdateFitness->execute();
            $stmtUpdateFitness->close();
        }
        catch (Exception $e) {
            $connection->rollback();
            echo "Error: " . $e->getMessage();
        }
    }
}

// Fetch user details
$sqlUser = "SELECT * FROM user WHERE userID = ?";
$stmtUser = $connection->prepare($sqlUser);
$stmtUser->bind_param("i", $UserID);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc(); // Fetch user data
$stmtUser->close(); // Close statement

if ($userData) { // Check if user exists
    $profilePic = $userData['Profile_pic']; // Get profile picture

    if (!empty($profilePic)) {
        // Detect MIME type dynamically
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $profilePic);
        finfo_close($finfo);

        // Convert binary data to Base64
        $imageSrc = "data:$mimeType;base64," . base64_encode($profilePic);
    } else {
        $imageSrc = "default-profile.png"; // Default profile picture
    }
} else {
    echo "<script>alert('User not found.'); window.history.back();</script>";
    exit;
}



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

// Fetch follower and following count
$sqlFollowers = "
    SELECT 
        (SELECT COUNT(*) FROM following_user WHERE followedID = ?) AS num_followers,
        (SELECT COUNT(*) FROM following_user WHERE followID = ?) AS num_following";
$stmtFollow = $connection->prepare($sqlFollowers);
$stmtFollow->bind_param("ii", $UserID, $UserID);
$stmtFollow->execute();
$followData = $stmtFollow->get_result()->fetch_assoc();
$stmtFollow->close();

// Fetch all exercise categories 
$sql = "SELECT category_name FROM workout_categoriestbl";
$resultCategories = $connection->query($sql);


// Profile Picture
// $imageSrc = (!empty($userData['Profile_pic']))
//     ? "data:image/jpeg;base64," . base64_encode($userData['Profile_pic'])
//     : "icons/default_profile.png";


    
// Assign values 
$currentGender = $userData['Gender'] ?? '';
$currentCountry = $userData['Country'] ?? '';
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
    <link rel="stylesheet" href="member.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="profile-page">
        <div class="general-info"><div class="general-info">
            
            <div class="username-follow">
                <div class="top-rectangle"></div>
                <p class="username"><?php echo htmlspecialchars($userData['Username']); ?></p>
                <p class="follower">Followers <?php echo htmlspecialchars($followData['num_followers']); ?></p>
                <p class="following">Following <?php echo htmlspecialchars($followData['num_following']); ?></p>
                <div class="main-profilepic" id="main-profilepic">
                    <img src="<?php echo $imageSrc; ?>" alt="Main Profile Picture" class="main-profile-pic">
                </div>
                <div class="profile-setting">
                    <button class="settings-btn" onclick="toggleForm()">Edit General Info</button>
                    <?php include 'view-follow.php'; ?>
                </div>
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
                    <div class="profile-setting2">
                        <button class="settings-btn2" onclick="openForm()">Edit Others</button>
                    </div>
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

    <div class="modal-overlay" id="modalOverlay"></div>
        <div id="settings-form" class="settings-form">
            <form action="" method="POST" id="profileForm" enctype="multipart/form-data">
                <button class="close-btn" type="button" onclick="closeForm()">X</button>
                <div class="profilepic-placeholder" id="profilepic-placeholder">
                    <img id="profilePic" class="profile_pic" src="<?php echo $imageSrc; ?>" alt="Profile Picture">
                    <label for="fileInput" class="camera-icon">📷</label>
                    <input type="file" id="fileInput" class="file-input" name="profile_pic" accept="image/*">
                </div>

                <div class="bottom-info">
                    <div class="info">
                        <p>Username <input type="text" name="username" value="<?php echo htmlspecialchars($userData['Username']); ?>"></p>
                        <p>Gender 
                            <select name="gender">
                                <option value="instruction" hidden><?php echo empty($currentGender) ? 'Select gender' : htmlspecialchars($currentGender); ?></option>
                                <option value="Male" <?php echo ($currentGender == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($currentGender == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($currentGender == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </p>
                        <p>Age <input type="number" name="age" value="<?php echo htmlspecialchars($userData['Age']); ?>"></p>
                        <p>Country 
                            <select id="country" name="country" class="form-control">
                                <option value="instruction" hidden><?php echo empty($currentCountry) ? 'Select country' : htmlspecialchars($currentCountry); ?></option>
                                <option value="Afghanistan" <?php echo ($currentCountry == 'Afghanistan') ? 'selected' : ''; ?>>Afghanistan</option>
                                <option value="Åland Islands" <?php echo ($currentCountry == 'Åland Islands') ? 'selected' : ''; ?>>Åland Islands</option>
                                <option value="Albania" <?php echo ($currentCountry == 'Albania') ? 'selected' : ''; ?>>Albania</option>
                                <option value="Algeria" <?php echo ($currentCountry == 'Algeria') ? 'selected' : ''; ?>>Algeria</option>
                                <option value="American Samoa" <?php echo ($currentCountry == 'American Samoa') ? 'selected' : ''; ?>>American Samoa</option>
                                <option value="Andorra" <?php echo ($currentCountry == 'Andorra') ? 'selected' : ''; ?>>Andorra</option>
                                <option value="Angola" <?php echo ($currentCountry == 'Angola') ? 'selected' : ''; ?>>Angola</option>
                                <option value="Anguilla" <?php echo ($currentCountry == 'Anguilla') ? 'selected' : ''; ?>>Anguilla</option>
                                <option value="Antarctica" <?php echo ($currentCountry == 'Antarctica') ? 'selected' : ''; ?>>Antarctica</option>
                                <option value="Antigua and Barbuda" <?php echo ($currentCountry == 'Antigua and Barbuda') ? 'selected' : ''; ?>>Antigua and Barbuda</option>
                                <option value="Argentina" <?php echo ($currentCountry == 'Argentina') ? 'selected' : ''; ?>>Argentina</option>
                                <option value="Armenia" <?php echo ($currentCountry == 'Armenia') ? 'selected' : ''; ?>>Armenia</option>
                                <option value="Aruba" <?php echo ($currentCountry == 'Aruba') ? 'selected' : ''; ?>>Aruba</option>
                                <option value="Australia" <?php echo ($currentCountry == 'Australia') ? 'selected' : ''; ?>>Australia</option>
                                <option value="Austria" <?php echo ($currentCountry == 'Austria') ? 'selected' : ''; ?>>Austria</option>
                                <option value="Azerbaijan" <?php echo ($currentCountry == 'Azerbaijan') ? 'selected' : ''; ?>>Azerbaijan</option>
                                <option value="Bahamas" <?php echo ($currentCountry == 'Bahamas') ? 'selected' : ''; ?>>Bahamas</option>
                                <option value="Bahrain" <?php echo ($currentCountry == 'Bahrain') ? 'selected' : ''; ?>>Bahrain</option>
                                <option value="Bangladesh" <?php echo ($currentCountry == 'Bangladesh') ? 'selected' : ''; ?>>Bangladesh</option>
                                <option value="Barbados" <?php echo ($currentCountry == 'Barbados') ? 'selected' : ''; ?>>Barbados</option>
                                <option value="Belarus" <?php echo ($currentCountry == 'Belarus') ? 'selected' : ''; ?>>Belarus</option>
                                <option value="Belgium" <?php echo ($currentCountry == 'Belgium') ? 'selected' : ''; ?>>Belgium</option>
                                <option value="Belize" <?php echo ($currentCountry == 'Belize') ? 'selected' : ''; ?>>Belize</option>
                                <option value="Benin" <?php echo ($currentCountry == 'Benin') ? 'selected' : ''; ?>>Benin</option>
                                <option value="Bermuda" <?php echo ($currentCountry == 'Bermuda') ? 'selected' : ''; ?>>Bermuda</option>
                                <option value="Bhutan" <?php echo ($currentCountry == 'Bhutan') ? 'selected' : ''; ?>>Bhutan</option>
                                <option value="Bolivia" <?php echo ($currentCountry == 'Bolivia') ? 'selected' : ''; ?>>Bolivia</option>
                                <option value="Bosnia and Herzegovina" <?php echo ($currentCountry == 'Bosnia and Herzegovina') ? 'selected' : ''; ?>>Bosnia and Herzegovina</option>
                                <option value="Botswana" <?php echo ($currentCountry == 'Botswana') ? 'selected' : ''; ?>>Botswana</option>
                                <option value="Bouvet Island" <?php echo ($currentCountry == 'Bouvet Island') ? 'selected' : ''; ?>>Bouvet Island</option>
                                <option value="Brazil" <?php echo ($currentCountry == 'Brazil') ? 'selected' : ''; ?>>Brazil</option>
                                <option value="British Indian Ocean Territory" <?php echo ($currentCountry == 'British Indian Ocean Territory') ? 'selected' : ''; ?>>British Indian Ocean Territory</option>
                                <option value="Brunei Darussalam" <?php echo ($currentCountry == 'Brunei Darussalam') ? 'selected' : ''; ?>>Brunei Darussalam</option>
                                <option value="Bulgaria" <?php echo ($currentCountry == 'Bulgaria') ? 'selected' : ''; ?>>Bulgaria</option>
                                <option value="Burkina Faso" <?php echo ($currentCountry == 'Burkina Faso') ? 'selected' : ''; ?>>Burkina Faso</option>
                                <option value="Burundi" <?php echo ($currentCountry == 'Burundi') ? 'selected' : ''; ?>>Burundi</option>
                                <option value="Cambodia" <?php echo ($currentCountry == 'Cambodia') ? 'selected' : ''; ?>>Cambodia</option>
                                <option value="Cameroon" <?php echo ($currentCountry == 'Cameroon') ? 'selected' : ''; ?>>Cameroon</option>
                                <option value="Canada" <?php echo ($currentCountry == 'Canada') ? 'selected' : ''; ?>>Canada</option>
                                <option value="Cape Verde" <?php echo ($currentCountry == 'Cape Verde') ? 'selected' : ''; ?>>Cape Verde</option>
                                <option value="Cayman Islands" <?php echo ($currentCountry == 'Cayman Islands') ? 'selected' : ''; ?>>Cayman Islands</option>
                                <option value="Central African Republic" <?php echo ($currentCountry == 'Central African Republic') ? 'selected' : ''; ?>>Central African Republic</option>
                                <option value="Chad" <?php echo ($currentCountry == 'Chad') ? 'selected' : ''; ?>>Chad</option>
                                <option value="Chile" <?php echo ($currentCountry == 'Chile') ? 'selected' : ''; ?>>Chile</option>
                                <option value="China" <?php echo ($currentCountry == 'China') ? 'selected' : ''; ?>>China</option>
                                <option value="Christmas Island" <?php echo ($currentCountry == 'Christmas Island') ? 'selected' : ''; ?>>Christmas Island</option>
                                <option value="Cocos (Keeling) Islands" <?php echo ($currentCountry == 'Cocos (Keeling) Islands') ? 'selected' : ''; ?>>Cocos (Keeling) Islands</option>
                                <option value="Colombia" <?php echo ($currentCountry == 'Colombia') ? 'selected' : ''; ?>>Colombia</option>
                                <option value="Comoros" <?php echo ($currentCountry == 'Comoros') ? 'selected' : ''; ?>>Comoros</option>
                                <option value="Congo" <?php echo ($currentCountry == 'Congo') ? 'selected' : ''; ?>>Congo</option>
                                <option value="Congo, The Democratic Republic of The" <?php echo ($currentCountry == 'Congo, The Democratic Republic of The') ? 'selected' : ''; ?>>Congo, The Democratic Republic of The</option>
                                <option value="Cook Islands" <?php echo ($currentCountry == 'Cook Islands') ? 'selected' : ''; ?>>Cook Islands</option>
                                <option value="Costa Rica" <?php echo ($currentCountry == 'Costa Rica') ? 'selected' : ''; ?>>Costa Rica</option>
                                <option value="Cote D'ivoire" <?php echo ($currentCountry == "Cote D'ivoire") ? 'selected' : ''; ?>>Cote D'ivoire</option>
                                <option value="Croatia" <?php echo ($currentCountry == 'Croatia') ? 'selected' : ''; ?>>Croatia</option>
                                <option value="Cuba" <?php echo ($currentCountry == 'Cuba') ? 'selected' : ''; ?>>Cuba</option>
                                <option value="Cyprus" <?php echo ($currentCountry == 'Cyprus') ? 'selected' : ''; ?>>Cyprus</option>
                                <option value="Czech Republic" <?php echo ($currentCountry == 'Czech Republic') ? 'selected' : ''; ?>>Czech Republic</option>
                                <option value="Denmark" <?php echo ($currentCountry == 'Denmark') ? 'selected' : ''; ?>>Denmark</option>
                                <option value="Djibouti" <?php echo ($currentCountry == 'Djibouti') ? 'selected' : ''; ?>>Djibouti</option>
                                <option value="Dominica" <?php echo ($currentCountry == 'Dominica') ? 'selected' : ''; ?>>Dominica</option>
                                <option value="Dominican Republic" <?php echo ($currentCountry == 'Dominican Republic') ? 'selected' : ''; ?>>Dominican Republic</option>
                                <option value="Ecuador" <?php echo ($currentCountry == 'Ecuador') ? 'selected' : ''; ?>>Ecuador</option>
                                <option value="Egypt" <?php echo ($currentCountry == 'Egypt') ? 'selected' : ''; ?>>Egypt</option>
                                <option value="El Salvador" <?php echo ($currentCountry == 'El Salvador') ? 'selected' : ''; ?>>El Salvador</option>
                                <option value="Equatorial Guinea" <?php echo ($currentCountry == 'Equatorial Guinea') ? 'selected' : ''; ?>>Equatorial Guinea</option>
                                <option value="Eritrea" <?php echo ($currentCountry == 'Eritrea') ? 'selected' : ''; ?>>Eritrea</option>
                                <option value="Estonia" <?php echo ($currentCountry == 'Estonia') ? 'selected' : ''; ?>>Estonia</option>
                                <option value="Eswatini" <?php echo ($currentCountry == 'Eswatini') ? 'selected' : ''; ?>>Eswatini</option>
                                <option value="Ethiopia" <?php echo ($currentCountry == 'Ethiopia') ? 'selected' : ''; ?>>Ethiopia</option>
                                <option value="Fiji" <?php echo ($currentCountry == 'Fiji') ? 'selected' : ''; ?>>Fiji</option>
                                <option value="Finland" <?php echo ($currentCountry == 'Finland') ? 'selected' : ''; ?>>Finland</option>
                                <option value="France" <?php echo ($currentCountry == 'France') ? 'selected' : ''; ?>>France</option>
                                <option value="Gabon" <?php echo ($currentCountry == 'Gabon') ? 'selected' : ''; ?>>Gabon</option>
                                <option value="Gambia" <?php echo ($currentCountry == 'Gambia') ? 'selected' : ''; ?>>Gambia</option>
                                <option value="Georgia" <?php echo ($currentCountry == 'Georgia') ? 'selected' : ''; ?>>Georgia</option>
                                <option value="Germany" <?php echo ($currentCountry == 'Germany') ? 'selected' : ''; ?>>Germany</option>
                                <option value="Ghana" <?php echo ($currentCountry == 'Ghana') ? 'selected' : ''; ?>>Ghana</option>
                                <option value="Gibraltar" <?php echo ($currentCountry == 'Gibraltar') ? 'selected' : ''; ?>>Gibraltar</option>
                                <option value="Greece" <?php echo ($currentCountry == 'Greece') ? 'selected' : ''; ?>>Greece</option>
                                <option value="Greenland" <?php echo ($currentCountry == 'Greenland') ? 'selected' : ''; ?>>Greenland</option>
                                <option value="Grenada" <?php echo ($currentCountry == 'Grenada') ? 'selected' : ''; ?>>Grenada</option>
                                <option value="Guadeloupe" <?php echo ($currentCountry == 'Guadeloupe') ? 'selected' : ''; ?>>Guadeloupe</option>
                                <option value="Guam" <?php echo ($currentCountry == 'Guam') ? 'selected' : ''; ?>>Guam</option>
                                <option value="Guatemala" <?php echo ($currentCountry == 'Guatemala') ? 'selected' : ''; ?>>Guatemala</option>
                                <option value="Guinea" <?php echo ($currentCountry == 'Guinea') ? 'selected' : ''; ?>>Guinea</option>
                                <option value="Guinea-bissau" <?php echo ($currentCountry == 'Guinea-bissau') ? 'selected' : ''; ?>>Guinea-bissau</option>
                                <option value="Guyana" <?php echo ($currentCountry == 'Guyana') ? 'selected' : ''; ?>>Guyana</option>
                                <option value="Haiti" <?php echo ($currentCountry == 'Haiti') ? 'selected' : ''; ?>>Haiti</option>
                                <option value="Honduras" <?php echo ($currentCountry == 'Honduras') ? 'selected' : ''; ?>>Honduras</option>
                                <option value="Hong Kong" <?php echo ($currentCountry == 'Hong Kong') ? 'selected' : ''; ?>>Hong Kong</option>
                                <option value="Hungary" <?php echo ($currentCountry == 'Hungary') ? 'selected' : ''; ?>>Hungary</option>
                                <option value="Iceland" <?php echo ($currentCountry == 'Iceland') ? 'selected' : ''; ?>>Iceland</option>
                                <option value="India" <?php echo ($currentCountry == 'India') ? 'selected' : ''; ?>>India</option>
                                <option value="Indonesia" <?php echo ($currentCountry == 'Indonesia') ? 'selected' : ''; ?>>Indonesia</option>
                                <option value="Iran" <?php echo ($currentCountry == 'Iran') ? 'selected' : ''; ?>>Iran</option>
                                <option value="Iraq" <?php echo ($currentCountry == 'Iraq') ? 'selected' : ''; ?>>Iraq</option>
                                <option value="Ireland" <?php echo ($currentCountry == 'Ireland') ? 'selected' : ''; ?>>Ireland</option>
                                <option value="Israel" <?php echo ($currentCountry == 'Israel') ? 'selected' : ''; ?>>Israel</option>
                                <option value="Italy" <?php echo ($currentCountry == 'Italy') ? 'selected' : ''; ?>>Italy</option>
                                <option value="Jamaica" <?php echo ($currentCountry == 'Jamaica') ? 'selected' : ''; ?>>Jamaica</option>
                                <option value="Japan" <?php echo ($currentCountry == 'Japan') ? 'selected' : ''; ?>>Japan</option>
                                <option value="Jordan" <?php echo ($currentCountry == 'Jordan') ? 'selected' : ''; ?>>Jordan</option>
                                <option value="Kazakhstan" <?php echo ($currentCountry == 'Kazakhstan') ? 'selected' : ''; ?>>Kazakhstan</option>
                                <option value="Kenya" <?php echo ($currentCountry == 'Kenya') ? 'selected' : ''; ?>>Kenya</option>
                                <option value="Kiribati" <?php echo ($currentCountry == 'Kiribati') ? 'selected' : ''; ?>>Kiribati</option>
                                <option value="Korea, Democratic People's Republic of" <?php echo ($currentCountry == 'Korea, Democratic People\'s Republic of') ? 'selected' : ''; ?>>Korea, Democratic People's Republic of</option>
                                <option value="Korea, Republic of" <?php echo ($currentCountry == 'Korea, Republic of') ? 'selected' : ''; ?>>Korea, Republic of</option>
                                <option value="Kuwait" <?php echo ($currentCountry == 'Kuwait') ? 'selected' : ''; ?>>Kuwait</option>
                                <option value="Kyrgyzstan" <?php echo ($currentCountry == 'Kyrgyzstan') ? 'selected' : ''; ?>>Kyrgyzstan</option>
                                <option value="Lao People's Democratic Republic" <?php echo ($currentCountry == 'Lao People\'s Democratic Republic') ? 'selected' : ''; ?>>Lao People's Democratic Republic</option>
                                <option value="Latvia" <?php echo ($currentCountry == 'Latvia') ? 'selected' : ''; ?>>Latvia</option>
                                <option value="Lebanon" <?php echo ($currentCountry == 'Lebanon') ? 'selected' : ''; ?>>Lebanon</option>
                                <option value="Lesotho" <?php echo ($currentCountry == 'Lesotho') ? 'selected' : ''; ?>>Lesotho</option>
                                <option value="Liberia" <?php echo ($currentCountry == 'Liberia') ? 'selected' : ''; ?>>Liberia</option>
                                <option value="Libyan Arab Jamahiriya" <?php echo ($currentCountry == 'Libyan Arab Jamahiriya') ? 'selected' : ''; ?>>Libyan Arab Jamahiriya</option>
                                <option value="Liechtenstein" <?php echo ($currentCountry == 'Liechtenstein') ? 'selected' : ''; ?>>Liechtenstein</option>
                                <option value="Lithuania" <?php echo ($currentCountry == 'Lithuania') ? 'selected' : ''; ?>>Lithuania</option>
                                <option value="Luxembourg" <?php echo ($currentCountry == 'Luxembourg') ? 'selected' : ''; ?>>Luxembourg</option>
                                <option value="Macao" <?php echo ($currentCountry == 'Macao') ? 'selected' : ''; ?>>Macao</option>
                                <option value="Macedonia, The Former Yugoslav Republic of" <?php echo ($currentCountry == 'Macedonia, The Former Yugoslav Republic of') ? 'selected' : ''; ?>>Macedonia, The Former Yugoslav Republic of</option>
                                <option value="Madagascar" <?php echo ($currentCountry == 'Madagascar') ? 'selected' : ''; ?>>Madagascar</option>
                                <option value="Malawi" <?php echo ($currentCountry == 'Malawi') ? 'selected' : ''; ?>>Malawi</option>
                                <option value="Malaysia" <?php echo ($currentCountry == 'Malaysia') ? 'selected' : ''; ?>>Malaysia</option>
                                <option value="Maldives" <?php echo ($currentCountry == 'Maldives') ? 'selected' : ''; ?>>Maldives</option>
                                <option value="Mali" <?php echo ($currentCountry == 'Mali') ? 'selected' : ''; ?>>Mali</option>
                                <option value="Malta" <?php echo ($currentCountry == 'Malta') ? 'selected' : ''; ?>>Malta</option>
                                <option value="Marshall Islands" <?php echo ($currentCountry == 'Marshall Islands') ? 'selected' : ''; ?>>Marshall Islands</option>
                                <option value="Martinique" <?php echo ($currentCountry == 'Martinique') ? 'selected' : ''; ?>>Martinique</option>
                                <option value="Mauritania" <?php echo ($currentCountry == 'Mauritania') ? 'selected' : ''; ?>>Mauritania</option>
                                <option value="Mauritius" <?php echo ($currentCountry == 'Mauritius') ? 'selected' : ''; ?>>Mauritius</option>
                                <option value="Mayotte" <?php echo ($currentCountry == 'Mayotte') ? 'selected' : ''; ?>>Mayotte</option>
                                <option value="Mexico" <?php echo ($currentCountry == 'Mexico') ? 'selected' : ''; ?>>Mexico</option>
                                <option value="Micronesia, Federated States of" <?php echo ($currentCountry == 'Micronesia, Federated States of') ? 'selected' : ''; ?>>Micronesia, Federated States of</option>
                                <option value="Moldova, Republic of" <?php echo ($currentCountry == 'Moldova, Republic of') ? 'selected' : ''; ?>>Moldova, Republic of</option>
                                <option value="Monaco" <?php echo ($currentCountry == 'Monaco') ? 'selected' : ''; ?>>Monaco</option>
                                <option value="Mongolia" <?php echo ($currentCountry == 'Mongolia') ? 'selected' : ''; ?>>Mongolia</option>
                                <option value="Montenegro" <?php echo ($currentCountry == 'Montenegro') ? 'selected' : ''; ?>>Montenegro</option>
                                <option value="Montserrat" <?php echo ($currentCountry == 'Montserrat') ? 'selected' : ''; ?>>Montserrat</option>
                                <option value="Morocco" <?php echo ($currentCountry == 'Morocco') ? 'selected' : ''; ?>>Morocco</option>
                                <option value="Mozambique" <?php echo ($currentCountry == 'Mozambique') ? 'selected' : ''; ?>>Mozambique</option>
                                <option value="Myanmar" <?php echo ($currentCountry == 'Myanmar') ? 'selected' : ''; ?>>Myanmar</option>
                                <option value="Namibia" <?php echo ($currentCountry == 'Namibia') ? 'selected' : ''; ?>>Namibia</option>
                                <option value="Nauru" <?php echo ($currentCountry == 'Nauru') ? 'selected' : ''; ?>>Nauru</option>
                                <option value="Nepal" <?php echo ($currentCountry == 'Nepal') ? 'selected' : ''; ?>>Nepal</option>
                                <option value="Netherlands" <?php echo ($currentCountry == 'Netherlands') ? 'selected' : ''; ?>>Netherlands</option>
                                <option value="Netherlands Antilles" <?php echo ($currentCountry == 'Netherlands Antilles') ? 'selected' : ''; ?>>Netherlands Antilles</option>
                                <option value="New Caledonia" <?php echo ($currentCountry == 'New Caledonia') ? 'selected' : ''; ?>>New Caledonia</option>
                                <option value="New Zealand" <?php echo ($currentCountry == 'New Zealand') ? 'selected' : ''; ?>>New Zealand</option>
                                <option value="Nicaragua" <?php echo ($currentCountry == 'Nicaragua') ? 'selected' : ''; ?>>Nicaragua</option>
                                <option value="Niger" <?php echo ($currentCountry == 'Niger') ? 'selected' : ''; ?>>Niger</option>
                                <option value="Nigeria" <?php echo ($currentCountry == 'Nigeria') ? 'selected' : ''; ?>>Nigeria</option>
                                <option value="Niue" <?php echo ($currentCountry == 'Niue') ? 'selected' : ''; ?>>Niue</option>
                                <option value="Norfolk Island" <?php echo ($currentCountry == 'Norfolk Island') ? 'selected' : ''; ?>>Norfolk Island</option>
                                <option value="Northern Mariana Islands" <?php echo ($currentCountry == 'Northern Mariana Islands') ? 'selected' : ''; ?>>Northern Mariana Islands</option>
                                <option value="Norway" <?php echo ($currentCountry == 'Norway') ? 'selected' : ''; ?>>Norway</option>
                                <option value="Oman" <?php echo ($currentCountry == 'Oman') ? 'selected' : ''; ?>>Oman</option>
                                <option value="Pakistan" <?php echo ($currentCountry == 'Pakistan') ? 'selected' : ''; ?>>Pakistan</option>
                                <option value="Palau" <?php echo ($currentCountry == 'Palau') ? 'selected' : ''; ?>>Palau</option>
                                <option value="Palestinian Territory, Occupied" <?php echo ($currentCountry == 'Palestinian Territory, Occupied') ? 'selected' : ''; ?>>Palestinian Territory, Occupied</option>
                                <option value="Panama" <?php echo ($currentCountry == 'Panama') ? 'selected' : ''; ?>>Panama</option>
                                <option value="Papua New Guinea" <?php echo ($currentCountry == 'Papua New Guinea') ? 'selected' : ''; ?>>Papua New Guinea</option>
                                <option value="Paraguay" <?php echo ($currentCountry == 'Paraguay') ? 'selected' : ''; ?>>Paraguay</option>
                                <option value="Peru" <?php echo ($currentCountry == 'Peru') ? 'selected' : ''; ?>>Peru</option>
                                <option value="Philippines" <?php echo ($currentCountry == 'Philippines') ? 'selected' : ''; ?>>Philippines</option>
                                <option value="Pitcairn" <?php echo ($currentCountry == 'Pitcairn') ? 'selected' : ''; ?>>Pitcairn</option>
                                <option value="Poland" <?php echo ($currentCountry == 'Poland') ? 'selected' : ''; ?>>Poland</option>
                                <option value="Portugal" <?php echo ($currentCountry == 'Portugal') ? 'selected' : ''; ?>>Portugal</option>
                                <option value="Puerto Rico" <?php echo ($currentCountry == 'Puerto Rico') ? 'selected' : ''; ?>>Puerto Rico</option>
                                <option value="Qatar" <?php echo ($currentCountry == 'Qatar') ? 'selected' : ''; ?>>Qatar</option>
                                <option value="Reunion" <?php echo ($currentCountry == 'Reunion') ? 'selected' : ''; ?>>Reunion</option>
                                <option value="Romania" <?php echo ($currentCountry == 'Romania') ? 'selected' : ''; ?>>Romania</option>
                                <option value="Russian Federation" <?php echo ($currentCountry == 'Russian Federation') ? 'selected' : ''; ?>>Russian Federation</option>
                                <option value="Rwanda" <?php echo ($currentCountry == 'Rwanda') ? 'selected' : ''; ?>>Rwanda</option>
                                <option value="Saint Helena" <?php echo ($currentCountry == 'Saint Helena') ? 'selected' : ''; ?>>Saint Helena</option>
                                <option value="Saint Kitts and Nevis" <?php echo ($currentCountry == 'Saint Kitts and Nevis') ? 'selected' : ''; ?>>Saint Kitts and Nevis</option>
                                <option value="Saint Lucia" <?php echo ($currentCountry == 'Saint Lucia') ? 'selected' : ''; ?>>Saint Lucia</option>
                                <option value="Saint Pierre and Miquelon" <?php echo ($currentCountry == 'Saint Pierre and Miquelon') ? 'selected' : ''; ?>>Saint Pierre and Miquelon</option>
                                <option value="Saint Vincent and The Grenadines" <?php echo ($currentCountry == 'Saint Vincent and The Grenadines') ? 'selected' : ''; ?>>Saint Vincent and The Grenadines</option>
                                <option value="Samoa" <?php echo ($currentCountry == 'Samoa') ? 'selected' : ''; ?>>Samoa</option>
                                <option value="San Marino" <?php echo ($currentCountry == 'San Marino') ? 'selected' : ''; ?>>San Marino</option>
                                <option value="Sao Tome and Principe" <?php echo ($currentCountry == 'Sao Tome and Principe') ? 'selected' : ''; ?>>Sao Tome and Principe</option>
                                <option value="Saudi Arabia" <?php echo ($currentCountry == 'Saudi Arabia') ? 'selected' : ''; ?>>Saudi Arabia</option>
                                <option value="Senegal" <?php echo ($currentCountry == 'Senegal') ? 'selected' : ''; ?>>Senegal</option>
                                <option value="Serbia" <?php echo ($currentCountry == 'Serbia') ? 'selected' : ''; ?>>Serbia</option>
                                <option value="Seychelles" <?php echo ($currentCountry == 'Seychelles') ? 'selected' : ''; ?>>Seychelles</option>
                                <option value="Sierra Leone" <?php echo ($currentCountry == 'Sierra Leone') ? 'selected' : ''; ?>>Sierra Leone</option>
                                <option value="Singapore" <?php echo ($currentCountry == 'Singapore') ? 'selected' : ''; ?>>Singapore</option>
                                <option value="Slovakia" <?php echo ($currentCountry == 'Slovakia') ? 'selected' : ''; ?>>Slovakia</option>
                                <option value="Slovenia" <?php echo ($currentCountry == 'Slovenia') ? 'selected' : ''; ?>>Slovenia</option>
                                <option value="Solomon Islands" <?php echo ($currentCountry == 'Solomon Islands') ? 'selected' : ''; ?>>Solomon Islands</option>
                                <option value="Somalia" <?php echo ($currentCountry == 'Somalia') ? 'selected' : ''; ?>>Somalia</option>
                                <option value="South Africa" <?php echo ($currentCountry == 'South Africa') ? 'selected' : ''; ?>>South Africa</option>
                                <option value="South Georgia and The South Sandwich Islands" <?php echo ($currentCountry == 'South Georgia and The South Sandwich Islands') ? 'selected' : ''; ?>>South Georgia and The South Sandwich Islands</option>
                                <option value="Spain" <?php echo ($currentCountry == 'Spain') ? 'selected' : ''; ?>>Spain</option>
                                <option value="Sri Lanka" <?php echo ($currentCountry == 'Sri Lanka') ? 'selected' : ''; ?>>Sri Lanka</option>
                                <option value="Sudan" <?php echo ($currentCountry == 'Sudan') ? 'selected' : ''; ?>>Sudan</option>
                                <option value="Suriname" <?php echo ($currentCountry == 'Suriname') ? 'selected' : ''; ?>>Suriname</option>
                                <option value="Svalbard and Jan Mayen" <?php echo ($currentCountry == 'Svalbard and Jan Mayen') ? 'selected' : ''; ?>>Svalbard and Jan Mayen</option>
                                <option value="Swaziland" <?php echo ($currentCountry == 'Swaziland') ? 'selected' : ''; ?>>Swaziland</option>
                                <option value="Sweden" <?php echo ($currentCountry == 'Sweden') ? 'selected' : ''; ?>>Sweden</option>
                                <option value="Switzerland" <?php echo ($currentCountry == 'Switzerland') ? 'selected' : ''; ?>>Switzerland</option>
                                <option value="Syrian Arab Republic" <?php echo ($currentCountry == 'Syrian Arab Republic') ? 'selected' : ''; ?>>Syrian Arab Republic</option>
                                <option value="Taiwan" <?php echo ($currentCountry == 'Taiwan') ? 'selected' : ''; ?>>Taiwan</option>
                                <option value="Tajikistan" <?php echo ($currentCountry == 'Tajikistan') ? 'selected' : ''; ?>>Tajikistan</option>
                                <option value="Tanzania, United Republic of" <?php echo ($currentCountry == 'Tanzania, United Republic of') ? 'selected' : ''; ?>>Tanzania, United Republic of</option>
                                <option value="Thailand" <?php echo ($currentCountry == 'Thailand') ? 'selected' : ''; ?>>Thailand</option>
                                <option value="Timor-leste" <?php echo ($currentCountry == 'Timor-leste') ? 'selected' : ''; ?>>Timor-leste</option>
                                <option value="Togo" <?php echo ($currentCountry == 'Togo') ? 'selected' : ''; ?>>Togo</option>
                                <option value="Tokelau" <?php echo ($currentCountry == 'Tokelau') ? 'selected' : ''; ?>>Tokelau</option>
                                <option value="Tonga" <?php echo ($currentCountry == 'Tonga') ? 'selected' : ''; ?>>Tonga</option>
                                <option value="Trinidad and Tobago" <?php echo ($currentCountry == 'Trinidad and Tobago') ? 'selected' : ''; ?>>Trinidad and Tobago</option>
                                <option value="Tunisia" <?php echo ($currentCountry == 'Tunisia') ? 'selected' : ''; ?>>Tunisia</option>
                                <option value="Turkey" <?php echo ($currentCountry == 'Turkey') ? 'selected' : ''; ?>>Turkey</option>
                                <option value="Turkmenistan" <?php echo ($currentCountry == 'Turkmenistan') ? 'selected' : ''; ?>>Turkmenistan</option>
                                <option value="Turks and Caicos Islands" <?php echo ($currentCountry == 'Turks and Caicos Islands') ? 'selected' : ''; ?>>Turks and Caicos Islands</option>
                                <option value="Tuvalu" <?php echo ($currentCountry == 'Tuvalu') ? 'selected' : ''; ?>>Tuvalu</option>
                                <option value="Uganda" <?php echo ($currentCountry == 'Uganda') ? 'selected' : ''; ?>>Uganda</option>
                                <option value="Ukraine" <?php echo ($currentCountry == 'Ukraine') ? 'selected' : ''; ?>>Ukraine</option>
                                <option value="United Arab Emirates" <?php echo ($currentCountry == 'United Arab Emirates') ? 'selected' : ''; ?>>United Arab Emirates</option>
                                <option value="United Kingdom" <?php echo ($currentCountry == 'United Kingdom') ? 'selected' : ''; ?>>United Kingdom</option>
                                <option value="United States" <?php echo ($currentCountry == 'United States') ? 'selected' : ''; ?>>United States</option>
                                <option value="United States Minor Outlying Islands" <?php echo ($currentCountry == 'United States Minor Outlying Islands') ? 'selected' : ''; ?>>United States Minor Outlying Islands</option>
                                <option value="Uruguay" <?php echo ($currentCountry == 'Uruguay') ? 'selected' : ''; ?>>Uruguay</option>
                                <option value="Uzbekistan" <?php echo ($currentCountry == 'Uzbekistan') ? 'selected' : ''; ?>>Uzbekistan</option>
                                <option value="Vanuatu" <?php echo ($currentCountry == 'Vanuatu') ? 'selected' : ''; ?>>Vanuatu</option>
                                <option value="Venezuela (Bolivarian Republic of)" <?php echo ($currentCountry == 'Venezuela (Bolivarian Republic of)') ? 'selected' : ''; ?>>Venezuela (Bolivarian Republic of)</option>
                                <option value="Viet Nam" <?php echo ($currentCountry == 'Viet Nam') ? 'selected' : ''; ?>>Viet Nam</option>
                                <option value="Western Sahara" <?php echo ($currentCountry == 'Western Sahara') ? 'selected' : ''; ?>>Western Sahara</option>
                                <option value="Yemen" <?php echo ($currentCountry == 'Yemen') ? 'selected' : ''; ?>>Yemen</option>
                                <option value="Zambia" <?php echo ($currentCountry == 'Zambia') ? 'selected' : ''; ?>>Zambia</option>
                                <option value="Zimbabwe" <?php echo ($currentCountry == 'Zimbabwe') ? 'selected' : ''; ?>>Zimbabwe</option>
                            </select>
                        </p>
                    </div>
                    <div class="info2">
                        <p>Height <input type="number" step="0.1" name="height" value="<?php echo htmlspecialchars($hwData['height'] ?? ''); ?>"></p>
                        <p>Weight <input type="number" step="0.1" name="weight" value="<?php echo htmlspecialchars($hwData['weight'] ?? ''); ?>"></p>
                    </div>
                </div>

                <div class="upload-container">
                    <button type="submit" name="update_all">Update</button>
                </div>
            </form>
        </div>
    

    <div id="others-form" class="others-form">
        <form action="" method="POST" id="othersForm">
        <button class="close-btn" type="button" onclick="closeForm2()">X</button>

        <form action="" method="post">
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
                    <option value="Select favourite exercise" hidden><?php echo empty($currentFavouriteExercise) ? 'Select favourite exercise' : htmlspecialchars($currentFavouriteExercise); ?></option>
                    <?php
                    while ($row = $resultCategories->fetch_assoc()) {
                        $category = $row['category_name'];
                        $selected = ($fav_exercise == $category) ? 'selected' : '';
                        echo "<option value='$category' $selected>$category</option>";
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
                    <option value="Select preffered time of day for workout" hidden><?php echo empty($currentPrefTOD) ? 'Select preffered time of day for workout' : htmlspecialchars($currentPrefTOD); ?></option>
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
                <div class="upload-container">
                    <button type="submit" name="update_others">Update</button>
                </div>
            </div>
        </form>
    </div>

</body>
</html>

<script>
function toggleForm() {
    var form = document.getElementById("settings-form");
    var overlay = document.getElementById("modalOverlay");
    if (form.style.display === "none" || form.style.display === "") {
        form.style.display = "block"; // Show form
        overlay.style.display = "block"; // Show overlay
        document.body.classList.add("modal-open"); // Disable interactions with the page
    } else {
        form.style.display = "none"; // Hide form
        overlay.style.display = "none"; // Hide overlay
        document.body.classList.remove("modal-open"); // Enable interactions with the page
    }
}

function openForm() {
    var form = document.getElementById("others-form");
    var overlay = document.getElementById("modalOverlay");
    if (form.style.display === "none" || form.style.display === "") {
        form.style.display = "block"; 
        overlay.style.display = "block"; 
        document.body.classList.add("modal-open"); 
    } else {
        form.style.display = "none"; 
        overlay.style.display = "none"; 
        document.body.classList.remove("modal-open"); 
    }
}

function closeForm() {
    document.getElementById("settings-form").style.display = "none"; 
    document.getElementById("modalOverlay").style.display = "none"; 
    document.body.classList.remove("modal-open"); 
}

function closeForm2() {
    document.getElementById("others-form").style.display = "none"; 
    document.getElementById("modalOverlay").style.display = "none"; 
    document.body.classList.remove("modal-open"); 
}

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

document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.querySelector("#fileInput");
    const profilePic = document.querySelector("#profilepic-placeholder img"); // More precise selection

    fileInput.addEventListener("change", function (event) {
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();

            reader.onload = function (e) {
                profilePic.src = e.target.result; // Ensures only the correct image is updated
                console.log("Profile picture updated!"); // Debugging message
            };

            reader.readAsDataURL(file);
        }
    });
});
</script>
<?php
include '../../general/footer.php';
?>