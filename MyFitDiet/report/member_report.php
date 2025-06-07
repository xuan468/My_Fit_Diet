<?php
session_start();
include '../general/member-nav.php';

if (!isset($_SESSION['userid'])) {
    header("Location: ../logindraft/login.php"); //CHANGE THIS ../????/connection.php
    exit();
}

$userid = $_SESSION['userid'];

include '../general/dbconn.php';

$sqlHeightWeight = "SELECT height, weight, DATE(update_time) as update_date FROM height_weight WHERE userID = ? ORDER BY update_time ASC";
$stmtHeightWeight = $connection->prepare($sqlHeightWeight);
$stmtHeightWeight->bind_param("i", $userid);
$stmtHeightWeight->execute();
$resultHeightWeight = $stmtHeightWeight->get_result();

$labelsHeightWeight = [];
$heightData = [];
$weightData = [];

while ($row = $resultHeightWeight->fetch_assoc()) {
    $labelsHeightWeight[] = $row['update_date'];
    $heightData[] = $row['height'];
    $weightData[] = $row['weight'];
}
$stmtHeightWeight->close();

$sqlNutrition = "SELECT start_date, end_date, avg_calories FROM weekly_nutrition_summary WHERE user_id = ? ORDER BY start_date ASC";
$stmtNutrition = $connection->prepare($sqlNutrition);
$stmtNutrition->bind_param("i", $userid);
$stmtNutrition->execute();
$resultNutrition = $stmtNutrition->get_result();

$labelsNutrition = [];
$avgCaloriesData = [];

while ($row = $resultNutrition->fetch_assoc()) {
    $labelsNutrition[] = $row['start_date'];
    $avgCaloriesData[] = $row['avg_calories'];
}

$stmtNutrition->close();

$sqlDailyNutrition = "SELECT meal_date, total_calories, total_carbs, total_fats, total_protein FROM daily_nutrition_totals WHERE user_id = ? ORDER BY meal_date ASC";
$stmtDailyNutrition = $connection->prepare($sqlDailyNutrition);
$stmtDailyNutrition->bind_param("i", $userid);
$stmtDailyNutrition->execute();
$resultDailyNutrition = $stmtDailyNutrition->get_result();

$labelsDailyNutrition = [];
$caloriesData = [];
$carbsData = [];
$fatsData = [];
$proteinData = [];

while ($row = $resultDailyNutrition->fetch_assoc()) {
    $labelsDailyNutrition[] = $row['meal_date'];
    $caloriesData[] = $row['total_calories'];
    $carbsData[] = $row['total_carbs'];
    $fatsData[] = $row['total_fats'];
    $proteinData[] = $row['total_protein'];
}

$stmtDailyNutrition->close();

$sqlUserChallenges = "SELECT challengeid FROM userchallenges WHERE userid = ? AND status = 'ongoing'";
$stmtUserChallenges = $connection->prepare($sqlUserChallenges);
$stmtUserChallenges->bind_param("i", $userid);
$stmtUserChallenges->execute();
$resultUserChallenges = $stmtUserChallenges->get_result();

$challengeIds = [];
while ($row = $resultUserChallenges->fetch_assoc()) {
    $challengeIds[] = $row['challengeid'];
}

$stmtUserChallenges->close();

$challengeIdsStr = implode(",", $challengeIds);
$sqlChallenges = "SELECT challengeid, challengeName, score FROM challenges WHERE challengeid IN ($challengeIdsStr)";
$resultChallenges = $connection->query($sqlChallenges);

$challenges = [];
while ($row = $resultChallenges->fetch_assoc()) {
    $challenges[] = $row;
}

$sqlLevels = "SELECT levelid, startPoint, endPoint FROM challengelevel ORDER BY levelid ASC";
$resultLevels = $connection->query($sqlLevels);

$levels = [];
while ($row = $resultLevels->fetch_assoc()) {
    $levels[] = $row;
}

$challengeProgress = [];
foreach ($challenges as $challenge) {
    $totalScore = $challenge['score'];
    $progress = 0;
    $currentLevel = null;

    foreach ($levels as $level) {
        if ($totalScore >= $level['startPoint'] && $totalScore <= $level['endPoint']) {
            $currentLevel = $level;
            $progress = ($totalScore - $level['startPoint']) / ($level['endPoint'] - $level['startPoint']) * 100;
            break;
        }
    }

    $challengeProgress[] = [
        'challengeid' => $challenge['challengeid'],
        'challengeName' => $challenge['challengeName'],
        'progress' => $progress,
        'currentLevel' => $currentLevel
    ];
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Report</title>
    <link rel="stylesheet" href="css/report.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <header>
        <h1>Member Report Dashboard</h1>
    </header>

    <!-- <nav>
        <ul>
            <li><a href="../homepage/member_homepage.php">Home</a></li>
            <li><a href="member_report.php">report summary</a></li>
        </ul>
    </nav> -->

    <div class="button-container">
        <button data-action="showAll" onclick="showAllObjects(this)">Show All Data</button>
        <button data-action="generalStats" onclick="showObjects(['object1'], this)">Height & Weight</button>
        <button data-action="nutritionStats" onclick="showObjects(['object2','object3'], this)">Nutrition status</button>
        <button data-action="progressStats" onclick="showObjects(['object4'], this)">Challenge Progress</button>
    </div>

    <div class="object_container">
        <div id="object1" class="object">
            <div>
                <canvas id="heightWeightLineChart"></canvas>
            </div>
        </div>
        <div id="object2" class="object">
            <div>
                <canvas id="weeklyNutritionLineChart"></canvas>
            </div>
        </div>
        <div id="object3" class="object">
            <div>
                <canvas id="dailyNutritionLineChart"></canvas>
            </div>
        </div>
    </div>
    <div class="object_container3">
        <div id="object4" class="object">
        <div id="challengeProgressCharts" style="display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 20px;"></div>
    </div>
</div>

    <script>
    var labels = <?php echo json_encode($labelsHeightWeight); ?>;
    var heightData = <?php echo json_encode($heightData); ?>;
    var weightData = <?php echo json_encode($weightData); ?>;

    var labelsNutrition = <?php echo json_encode($labelsNutrition); ?>;
    var avgCaloriesData = <?php echo json_encode($avgCaloriesData); ?>;

    var labelsDailyNutrition = <?php echo json_encode($labelsDailyNutrition); ?>;
    var caloriesData = <?php echo json_encode($caloriesData); ?>;
    var carbsData = <?php echo json_encode($carbsData); ?>;
    var fatsData = <?php echo json_encode($fatsData); ?>;
    var proteinData = <?php echo json_encode($proteinData); ?>;

    var currentLevel = <?php echo json_encode($currentLevel); ?>;
    var progress = <?php echo json_encode($progress); ?>;
    var levels = <?php echo json_encode($levels); ?>;

    var challengeProgress = <?php echo json_encode($challengeProgress); ?>;
    </script>

    <script src="manager_js/report.js"></script>
    <script src="member_js/height_weight_lc.js"></script>
    <script src="member_js/weekly_nutrition_lc.js"></script>
    <script src="member_js/daily_nutrition_lc.js"></script>
    <script src="member_js/progress_chart.js"></script>
</body>
</html>

<?php
include '../general/footer.php';
?>