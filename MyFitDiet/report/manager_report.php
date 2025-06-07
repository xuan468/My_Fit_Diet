<?php
session_start();
include '../general/dbconn.php'; //CHANGE THIS ../????/connection.php
include '../general/manager-nav.php';

$sql = "SELECT Gender, COUNT(*) as count FROM user GROUP BY Gender";
$result = $connection->query($sql);

$genderData = [];
$totalUsers = 0;

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $gender = $row['Gender'];
        $count = $row['count'];
        $genderData[$gender] = [
            'count' => $count,
            'percentage' => null
        ];
        $totalUsers += $count;
    }

    foreach ($genderData as $gender => $data) {
        $genderData[$gender]['percentage'] = round(($data['count'] / $totalUsers) * 100, 2);
    }
} else {
    echo "0 results";
}

$sql = "SELECT Status, COUNT(*) as count FROM user GROUP BY Status";
$result = $connection->query($sql);

$statusData = [];
$totalUsers = 0;

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $status = $row['Status'];
        $count = $row['count'];
        $statusData[$status] = [
            'count' => $count,
            'percentage' => null
        ];
        $totalUsers += $count;
    }

    foreach ($statusData as $status => $data) {
        $statusData[$status]['percentage'] = round(($data['count'] / $totalUsers) * 100, 2);
    }
} else {
    echo "0 results";
}

$sqlAge = "SELECT 
            CASE
                WHEN Age BETWEEN 10 AND 20 THEN '10-20'
                WHEN Age BETWEEN 21 AND 30 THEN '21-30'
                WHEN Age BETWEEN 31 AND 40 THEN '31-40'
                WHEN Age BETWEEN 41 AND 50 THEN '41-50'
                WHEN Age BETWEEN 51 AND 60 THEN '51-60'
                WHEN Age BETWEEN 61 AND 70 THEN '61-70'
                WHEN Age BETWEEN 71 AND 80 THEN '71-80'
                WHEN Age BETWEEN 81 AND 90 THEN '81-90'
                WHEN Age BETWEEN 91 AND 100 THEN '91-100'
                ELSE 'Unknown'
            END AS age_group,
            COUNT(*) as count
          FROM user
          GROUP BY age_group";
$resultAge = $connection->query($sqlAge);

$ageData = [];
$totalAgeUsers = 0;

if ($resultAge->num_rows > 0) {
    while($row = $resultAge->fetch_assoc()) {
        $ageGroup = $row['age_group'];
        $count = $row['count'];
        $ageData[$ageGroup] = [
            'count' => $count,
            'percentage' => null
        ];
        $totalAgeUsers += $count;
    }

    foreach ($ageData as $ageGroup => $data) {
        $ageData[$ageGroup]['percentage'] = round(($data['count'] / $totalAgeUsers) * 100, 2);
    }
} else {
    echo "0 results";
}

$sql = "SELECT Target_fitness_lvl, COUNT(*) as count FROM user GROUP BY Target_fitness_lvl";
$result = $connection->query($sql);

$fitnessLevelData = [];
$totalUsers = 0;

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $fitnessLevel = $row['Target_fitness_lvl'];
        $count = $row['count'];
        $fitnessLevelData[$fitnessLevel] = [
            'count' => $count,
            'percentage' => null
        ];
        $totalUsers += $count;
    }

    foreach ($fitnessLevelData as $fitnessLevel => $data) {
        $fitnessLevelData[$fitnessLevel]['percentage'] = round(($data['count'] / $totalUsers) * 100, 2);
    }
} else {
    echo "0 results";
}

$sqlCountry = "SELECT Country, COUNT(*) as count FROM user GROUP BY Country";
$resultCountry = $connection->query($sqlCountry);

$countryData = [];
$totalCountryUsers = 0;

if ($resultCountry->num_rows > 0) {
    while($row = $resultCountry->fetch_assoc()) {
        $country = $row['Country'];
        $count = $row['count'];
        $countryData[$country] = [
            'count' => $count,
            'percentage' => null
        ];
        $totalCountryUsers += $count;
    }

    foreach ($countryData as $country => $data) {
        $countryData[$country]['percentage'] = round(($data['count'] / $totalCountryUsers) * 100, 2);
    }
} else {
    echo "0 results";
}

$sqlDesiredWorkout = "SELECT Desired_workout, COUNT(*) as count FROM user GROUP BY Desired_workout";
$resultDesiredWorkout = $connection->query($sqlDesiredWorkout);

$desiredWorkoutData = [];
$totalDesiredWorkoutUsers = 0;

if ($resultDesiredWorkout->num_rows > 0) {
    while($row = $resultDesiredWorkout->fetch_assoc()) {
        $workout = $row['Desired_workout'];
        $count = $row['count'];
        $desiredWorkoutData[$workout] = [
            'count' => $count,
            'percentage' => null
        ];
        $totalDesiredWorkoutUsers += $count;
    }

    foreach ($desiredWorkoutData as $workout => $data) {
        $desiredWorkoutData[$workout]['percentage'] = round(($data['count'] / $totalDesiredWorkoutUsers) * 100, 2);
    }
} else {
    echo "0 results";
}

$sqlPrimaryGoal = "SELECT Primary_goal, COUNT(*) as count FROM user GROUP BY Primary_goal";
$resultPrimaryGoal = $connection->query($sqlPrimaryGoal);

$primaryGoalData = [];
$totalPrimaryGoalUsers = 0;

if ($resultPrimaryGoal->num_rows > 0) {
    while($row = $resultPrimaryGoal->fetch_assoc()) {
        $goal = $row['Primary_goal'];
        $count = $row['count'];
        $primaryGoalData[$goal] = [
            'count' => $count,
            'percentage' => null
        ];
        $totalPrimaryGoalUsers += $count;
    }

    foreach ($primaryGoalData as $goal => $data) {
        $primaryGoalData[$goal]['percentage'] = round(($data['count'] / $totalPrimaryGoalUsers) * 100, 2);
    }
} else {
    echo "0 results";
}

$sqlFavExercise = "SELECT Fav_exercise, COUNT(*) as count FROM user GROUP BY Fav_exercise";
$resultFavExercise = $connection->query($sqlFavExercise);

$favExerciseData = [];
$totalFavExerciseUsers = 0;

if ($resultFavExercise->num_rows > 0) {
    while($row = $resultFavExercise->fetch_assoc()) {
        $exercise = $row['Fav_exercise'];
        $count = $row['count'];
        $favExerciseData[$exercise] = [
            'count' => $count,
            'percentage' => null
        ];
        $totalFavExerciseUsers += $count;
    }

    foreach ($favExerciseData as $exercise => $data) {
        $favExerciseData[$exercise]['percentage'] = round(($data['count'] / $totalFavExerciseUsers) * 100, 2);
    }
} else {
    echo "0 results";
}

// Add this code to fetch the rating data
$sqlRating = "SELECT rating, COUNT(*) as count FROM feedbacktbl GROUP BY rating";
$resultRating = $connection->query($sqlRating);

if (!$resultRating) {
    die("Query failed: " . $connection->error); // Debug query failure
}

$ratingData = []; // Initialize as an empty array
$totalRatings = 0; // Initialize total ratings counter

if ($resultRating->num_rows > 0) {
    while($row = $resultRating->fetch_assoc()) {
        $rating = $row['rating'];
        $count = $row['count'];
        $ratingData[$rating] = [
            'count' => $count,
            'percentage' => null
        ];
        $totalRatings += $count;
    }

    // Calculate percentages
    foreach ($ratingData as $rating => $data) {
        $ratingData[$rating]['percentage'] = round(($data['count'] / $totalRatings) * 100, 2);
    }
} else {
    echo "No rating data found."; // Debug no results
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Object</title>
    <link rel="stylesheet" href="css/report.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>

    <header>
        <h1>Report Dashboard</h1>
    </header>

    <!-- <nav>
        <ul>
            <li><a href="../homepage/manager_homepage.php">Home</a></li>
            <li><a href="manager_report.php">Report Summary</a></li>
            <li><a href="#">Manage Member</a></li>
            <li><a href="#">Manage Staff</a></li>
            <li><a href="#">Manage Reviewer</a></li>
        </ul>
    </nav> -->

    <button data-action="showAll" onclick="showAllObjects(this)">Show All Data</button>
    <button data-action="generalStats" onclick="showObjects(['object1', 'object2', 'object3', 'object5'], this)">General Statistics</button>
    <button data-action="indepthStats" onclick="showObjects(['object4', 'object6', 'object7', 'object8'], this)">Indepth User Statistics</button>
    
    <div class="object_container">  
        <div id="object1" class="object active">
            <div>
                <canvas id="genderPieChart"></canvas>
            </div>
        </div>

        <div id="object2" class="object active">
            <div>
                <canvas id="agePieChart"></canvas>
            </div>
        </div>

        <div id="object3" class="object active">
            <div>
            <canvas id="statusPieChart"></canvas>
            </div>
        </div>

        <div id="object4" class="object active">
            <div>
            <canvas id="fitnessLevelPieChart"></canvas>
            </div>
        </div>

        <div id="object5" class="object active">
            <div>
                <canvas id="countryBarChart"></canvas>
            </div>
        </div>

        <div id="object6" class="object active">
            <div>
                <canvas id="desiredWorkoutBarChart"></canvas>
            </div>
        </div>
    </div>

    <div class="object_container2">
        <div id="object7" class="object active">
            <div>
                <canvas id="primaryGoalBarChart"></canvas>
            </div>
        </div>

        <div id="object8" class="object active">
            <div>
                <canvas id="favExerciseBarChart"></canvas>
            </div>
        </div>

        <div id="object9" class="object active">
            <div>
                <h3>Our Reviews</h3>
                <?php if (!empty($ratingData)): ?>
                    <div class="rating-summary">
                        <div class="stars">
                            <?php
                                $averageRating = 0; // Default value if no ratings exist

                                if ($totalRatings > 0 && !empty($ratingData)) {
                                    $sumOfRatings = 0;
                                    foreach ($ratingData as $rating => $data) {
                                        if (isset($data['count']) && is_numeric($rating)) {
                                            $sumOfRatings += $rating * $data['count'];
                                        }
                                    }
                                    $averageRating = round($sumOfRatings / $totalRatings, 1);
                                }
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= floor($averageRating) ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <?php foreach ($ratingData as $stars => $data): ?>
                            <div class="rating-bar">
                                <span><?php echo $stars; ?> Stars: <?php echo $data['count']; ?></span>
                                <div class="bar-background">
                                    <div class="bar-fill" style="width: <?php echo $data['percentage']; ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No rating data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    var genderData = <?php echo json_encode($genderData); ?>;
    var totalUsers = <?php echo $totalUsers; ?>;

    var ageData = <?php echo json_encode($ageData); ?>;
    var totalAgeUsers = <?php echo $totalAgeUsers; ?>;

    var statusData = <?php echo json_encode($statusData); ?>;
    var totalUsers = <?php echo $totalUsers; ?>;

    var fitnessLevelData = <?php echo json_encode($fitnessLevelData); ?>;
    var totalUsers = <?php echo $totalUsers; ?>;

    var countryData = <?php echo json_encode($countryData); ?>;
    var totalCountryUsers = <?php echo $totalCountryUsers; ?>;

    var desiredWorkoutData = <?php echo json_encode($desiredWorkoutData); ?>;
    var totalDesiredWorkoutUsers = <?php echo $totalDesiredWorkoutUsers; ?>;

    var primaryGoalData = <?php echo json_encode($primaryGoalData); ?>;
    var totalPrimaryGoalUsers = <?php echo $totalPrimaryGoalUsers; ?>;

    var favExerciseData = <?php echo json_encode($favExerciseData); ?>;
    var totalFavExerciseUsers = <?php echo $totalFavExerciseUsers; ?>;
    </script>

    <script src="manager_js/report.js"></script>
    <script src="manager_js/gender_pc.js"></script>
    <script src="manager_js/age_pc.js"></script>
    <script src="manager_js/status_pc.js"></script>
    <script src="manager_js/fitnessLevel_pc.js"></script>
    <script src="manager_js/country_bc.js"></script>
    <script src="manager_js/desired_workout_bc.js"></script>
    <script src="manager_js/primary_goal_bc.js"></script>
    <script src="manager_js/fav_exercise_bc.js"></script>
</html>

<?php
include '../general/footer.php';
?>