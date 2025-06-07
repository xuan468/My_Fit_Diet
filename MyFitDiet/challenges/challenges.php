<?php
ob_start(); 
session_start();
include '../general/member-nav.php';
include '../general/dbconn.php'; // Database connection

// if (!isset($_SESSION['userid'])) {
//     header('Location: ../login/login.php');
//     exit();
// }

$userid = $_SESSION['userid'];
// Initialize arrays for challenges
$ongoingChallenges = [];
$availableChallenges = [];
$completedChallenges = [];
$historyChallenges = [];

// Query for ongoing challenges
$sql_ongoing = "SELECT c.challengeid, c.challengeName, c.description, c.type, c.difficulty, c.img, c.score, ucid, uc.progress, uc.startDate, uc.endDate
                FROM userchallenges uc
                JOIN challenges c ON uc.challengeid = c.challengeid
                WHERE uc.userid = '$userid' AND uc.status = 'ongoing'"; // Progress less than 7 indicates the challenge is ongoing
$result_ongoing = mysqli_query($connection, $sql_ongoing);
// Fetch ongoing challenges and encode images as base64
while ($row = mysqli_fetch_assoc($result_ongoing)) {
    // Convert BLOB data to Base64 encoding
    $row['img'] = 'data:image/jpeg;base64,' . base64_encode($row['img']); // Assuming the image is JPEG format
    $ongoingChallenges[] = $row;
}

// Query for available challenges
$sql_available = "SELECT c.challengeid, c.challengeName, c.type, c.description, c.difficulty, c.img, c.score
        FROM challenges c
        WHERE NOT EXISTS (
        SELECT 1 FROM userchallenges uc WHERE uc.challengeid = c.challengeid AND uc.userid = '$userid' AND uc.status = 'ongoing')"; // Select challenges the user hasn't started yet
$result_available = mysqli_query($connection, $sql_available);
// Fetch available challenges and encode images as base64
while ($row = mysqli_fetch_assoc($result_available)) {
    // Convert BLOB data to Base64 encoding
    $row['img'] = 'data:image/jpeg;base64,' . base64_encode($row['img']); // Assuming the image is JPEG format
    $availableChallenges[] = $row;
}

// Query for completed challenges
$sql_completed = "SELECT c.challengeid, c.challengeName, c.description, c.type, c.difficulty, c.img, c.score, ucid
                FROM userchallenges uc
                JOIN challenges c ON uc.challengeid = c.challengeid
                WHERE uc.userid = '$userid' AND uc.status = 'success'";
$result_completed = mysqli_query($connection, $sql_completed);
while ($row = mysqli_fetch_assoc($result_completed)) {
    $row['img'] = 'data:image/jpeg;base64,' . base64_encode($row['img']); // Assuming JPEG
    $completedChallenges[] = $row;
}

$sql_history = "SELECT c.challengeid, c.challengeName, c.description, c.type, c.difficulty, c.img, c.score, uc.status
                FROM userchallenges uc
                JOIN challenges c ON uc.challengeid = c.challengeid
                WHERE uc.userid = '$userid' AND uc.status != 'ongoing'";
$result_history = mysqli_query($connection, $sql_history);
while ($row = mysqli_fetch_assoc($result_history)) {
    $row['img'] = 'data:image/jpeg;base64,' . base64_encode($row['img']); // Assuming JPEG
    $historyChallenges[] = $row;
}

$sql_level = "
    SELECT 
        COALESCE(SUM(c.score), 0) AS totalScore,
        COALESCE(l.level, 1) AS userLevel,
        COALESCE((l.endPoint - SUM(c.score)), 100) AS pointsToNextLevel
    FROM userchallenges uc
    LEFT JOIN challenges c ON uc.challengeid = c.challengeid
    LEFT JOIN challengelevel l ON (
        SELECT COALESCE(SUM(c2.score), 0)
        FROM userchallenges uc2
        JOIN challenges c2 ON uc2.challengeid = c2.challengeid
        WHERE uc2.userid = '$userid' AND uc2.status = 'success'
    ) BETWEEN l.startPoint AND l.endPoint
    WHERE uc.userid = '$userid' AND uc.status = 'success'
    GROUP BY l.level, l.startPoint, l.endPoint
";
$result_level = mysqli_query($connection, $sql_level);
$levelData = mysqli_fetch_assoc($result_level);
$totalScore = $levelData['totalScore'] ?? 0; // If there is no success record, the total score is 0
$userLevel = $levelData['userLevel'] ?? 1;
$pointsToNextLevel = $levelData['pointsToNextLevel'] ?? 100; // Default value when no challenges completed
$totalScore = $levelData['totalScore'] ?? 0;



// Handle requests to start or leave a challenge
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $challengeid = mysqli_real_escape_string($connection, $_POST['challengeid']);
    $action = mysqli_real_escape_string($connection, $_POST['action']);
    $ucid = mysqli_real_escape_string($connection, $_POST['ucid']);
    
    if ($action == 'confirmLeave') {
        // User leaves the challenge
        $sql = "UPDATE userchallenges SET status = 'fail' WHERE ucid='$ucid' AND userid='$userid'";
        
    } else if ($action == 'start') {
        // Check if the challenge exists
        $check_sql = "SELECT * FROM challenges WHERE challengeid='$challengeid'";
        $check_result = mysqli_query($connection, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Challenge exists, proceed to start
            $startDate = date('Y-m-d'); // Current date as start date
            $endDate = date('Y-m-d', strtotime('+6 days')); // End date is 7 days later, adjust as needed
            $sql = "
                INSERT INTO userchallenges (userid, challengeid, progress, startDate, endDate, status)
                VALUES ('$userid', '$challengeid', 0, '$startDate', '$endDate','ongoing')"; // Initial progress is 0
        } else {
            // Challenge does not exist, notify user
            header("Location: challenges.php?status=error&message=Challenge no longer exists");
            exit;
        }
    } else if ($action == 'checkin') {
        $currentDate = date('Y-m-d');
        $endDate = $_POST['endDate'];
        $ucid = $_POST['ucid'];
        if ($currentDate > $endDate) {
            // Challenge failed, update status
            $sql_fail = "UPDATE userchallenges SET status='fail' WHERE ucid='$ucid'";
            mysqli_query($connection, $sql_fail);
            echo "<script>
                    alert('Your challenge has failed. Try again next time!');
                    window.location.href = 'challenges.php';
                </script>";
            exit;
        } else {
            header("Location: checkin.php?ucid=$ucid");
            exit;
        }

        
    }

    // Execute query and handle success or error
    if (!empty($sql)) {
        if (mysqli_query($connection, $sql)) {
            header("Location: challenges.php?status=success");
        } else {
            header("Location: challenges.php?status=error&message=" . mysqli_error($connection));
        }
        exit;
    }
}

// Close database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Challenges | Achieve Your Goals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="challenges.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="app-container">
        <!-- Header Section -->
        <header class="app-header">
            <div class="header-content">
                <h1>Fitness Challenges</h1>
                <button id="profileBtn" class="profile-btn">
                    <i class="fas fa-trophy"></i>
                    <span class="badge"><?php echo $totalScore; ?> pts</span>
                </button>
            </div>
            
            <!-- User Progress Card -->
            <div class="progress-card">
                <div class="progress-info">
                    <div class="level-display">
                        <span class="level-label">Level</span>
                        <span class="level-value"><?php echo $userLevel; ?></span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-labels">
                            <span><?php echo $totalScore; ?> pts</span>
                            <span><?php echo $totalScore + $pointsToNextLevel; ?> pts</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: <?php echo ($totalScore / ($totalScore + $pointsToNextLevel)) * 100; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="challenges-container">
            <!-- Ongoing Challenges Section -->
            <section class="challenges-section">
                <div class="section-header">
                    <h2>Your Active Challenges</h2>
                    <span class="section-count"><?php echo count($ongoingChallenges); ?></span>
                </div>
                
                <?php if (!empty($ongoingChallenges)): ?>
                <div class="challenges-grid">
                    <?php foreach ($ongoingChallenges as $challenge): ?>
                    <div class="challenge-card active" data-difficulty="<?= htmlspecialchars($challenge['difficulty']); ?>">
                        <div class="card-header">
                            <div class="challenge-progress">
                                <div class="circular-progress">
                                    <svg class="progress-ring" width="60" height="60">
                                        <circle class="progress-ring-circle" stroke-width="4" fill="transparent" r="26" cx="30" cy="30"/>
                                        <circle class="progress-ring-fill" stroke-width="4" fill="transparent" r="26" cx="30" cy="30" 
                                                stroke-dasharray="<?= 2 * pi() * 26 ?>" 
                                                stroke-dashoffset="<?= (1 - ($challenge['progress']/7)) * 2 * pi() * 26 ?>"/>
                                    </svg>
                                    <span class="progress-percent"><?= round(($challenge['progress']/7)*100) ?>%</span>
                                </div>
                                <span class="days-remaining">
                                    <?php 
                                    $endDate = new DateTime($challenge['endDate']);
                                    $today = new DateTime();
                                    if ($today > $endDate) {
                                        echo 'Challenge ended';
                                    } else {
                                        $interval = $today->diff($endDate);
                                        echo ($interval->days + 1) . ' days left';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="challenge-image" style="background-image: url('<?= $challenge['img']; ?>')"></div>
                            <div class="points-badge">+<?= $challenge['score'] ?> pts</div>
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($challenge['challengeName']); ?></h3>
                            <p class="challenge-description"><?= htmlspecialchars($challenge['description']); ?></p>
                            <div class="difficulty-badge"><?= ucfirst($challenge['difficulty']) ?></div>
                        </div>
                        <div class="card-footer">
                            <form method="POST" action="challenges.php" class="inline-form">
                                <input type="hidden" name="challengeid" value="<?= htmlspecialchars($challenge['challengeid']); ?>">
                                <input type="hidden" name="action" value="checkin">
                                <input type="hidden" name="endDate" value="<?= htmlspecialchars($challenge['endDate']); ?>">
                                <input type="hidden" name="ucid" value="<?= htmlspecialchars($challenge['ucid']); ?>">
                                <button type="submit" class="btn primary">
                                    <i class="fas fa-check-circle"></i> Check In
                                </button>
                            </form>
                            <button class="btn secondary leave-btn" data-ucid="<?= htmlspecialchars($challenge['ucid']); ?>">
                                <i class="fas fa-times"></i> Leave
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <img src="assets/no-challenges.svg" alt="No active challenges">
                    <p>You don't have any active challenges</p>
                    <button class="btn primary browse-btn">Browse Challenges</button>
                </div>
                <?php endif; ?>
            </section>

            <!-- Available Challenges Section -->
            <section class="challenges-section">
                <div class="section-header">
                    <h2>Available Challenges</h2>
                    <div class="filter-controls">
                        <select id="difficultyFilter" class="filter-select">
                            <option value="all">All Difficulties</option>
                            <option value="lightly">Lightly</option>
                            <option value="moderately">Moderately</option>
                            <option value="highly">Highly</option>
                        </select>
                    </div>
                </div>
                
                <div class="challenges-grid">
                    <?php foreach ($availableChallenges as $challenge): ?>
                    <div class="challenge-card" data-difficulty="<?= htmlspecialchars($challenge['difficulty']); ?>">
                        <div class="card-header">
                            <div class="challenge-image" style="background-image: url('<?= $challenge['img']; ?>')"></div>
                            <div class="points-badge">+<?= $challenge['score'] ?> pts</div>
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($challenge['challengeName']); ?></h3>
                            <p class="challenge-description"><?= htmlspecialchars($challenge['description']); ?></p>
                            <div class="difficulty-badge"><?= ucfirst($challenge['difficulty']) ?></div>
                        </div>
                        <div class="card-footer">
                            <form method="POST" action="challenges.php" class="inline-form">
                                <input type="hidden" name="challengeid" value="<?= htmlspecialchars($challenge['challengeid']); ?>">
                                <input type="hidden" name="action" value="start">
                                <button type="submit" class="btn primary full-width">
                                    <i class="fas fa-play"></i> Start Challenge
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Achievement Sidebar -->
    <div class="achievements-sidebar">
        <div class="sidebar-header">
            <h3>Your Achievements</h3>
            <button class="close-sidebar"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="sidebar-tabs">
            <button class="tab-btn active" data-tab="completed">Completed</button>
            <button class="tab-btn" data-tab="history">History</button>
        </div>
        
        <div class="tab-content active" id="completed">
            <?php if (!empty($completedChallenges)): ?>
                <?php foreach ($completedChallenges as $challenge): ?>
                <div class="achievement-card">
                    <div class="achievement-badge">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="achievement-details">
                        <h4><?= htmlspecialchars($challenge['challengeName']); ?></h4>
                        <p>Completed • +<?= $challenge['score'] ?> pts</p>
                    </div>
                    <div class="achievement-image" style="background-image: url('<?= $challenge['img']; ?>')"></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-achievements">
                    <i class="fas fa-trophy"></i>
                    <p>Complete challenges to earn achievements</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="history">
            <?php if (!empty($historyChallenges)): ?>
                <?php foreach ($historyChallenges as $challenge): ?>
                <div class="history-card">
                    <div class="history-status <?= $challenge['status'] === 'success' ? 'success' : 'failed' ?>">
                        <i class="fas fa-<?= $challenge['status'] === 'success' ? 'check' : 'times' ?>"></i>
                    </div>
                    <div class="history-details">
                        <h4><?= htmlspecialchars($challenge['challengeName']); ?></h4>
                        <p><?= ucfirst($challenge['status']) ?> • <?= $challenge['score'] ?> pts</p>
                    </div>
                    <div class="history-image" style="background-image: url('<?= $challenge['img']; ?>')"></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-achievements">
                    <i class="fas fa-history"></i>
                    <p>Your challenge history will appear here</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Leave Challenge Modal -->
    <div class="modal-overlay" id="leaveModal">
        <div class="modal-container">
            <button class="modal-close"><i class="fas fa-times"></i></button>
            <div class="modal-header">
                <h3>Leave Challenge?</h3>
                <p>Are you sure you want to leave this challenge?</p>
            </div>
            <div class="modal-body">
                <div class="progress-summary">
                    <div class="progress-display">
                        <div class="leave-circular-progress">
                            <svg class="progress-ring" width="80" height="80">
                                <circle class="progress-ring-circle" stroke-width="4" fill="transparent" r="36" cx="40" cy="40"/>
                                <circle class="progress-ring-fill" stroke-width="4" fill="transparent" r="36" cx="40" cy="40"/>
                            </svg>
                            <span class="progress-percent">0%</span>
                        </div>
                    </div>
                    <div class="challenge-info">
                        <h4 id="modalChallengeName"></h4>
                        <p>You'll lose all progress and potential rewards</p>
                        <div class="reward-loss">
                            <i class="fas fa-coins"></i>
                            <span id="modalPointsLoss">-0 points</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn secondary modal-cancel">Cancel</button>
                <form method="POST" action="challenges.php" class="inline-form">
                    <input type="hidden" name="challengeid" id="modalChallengeId">
                    <input type="hidden" name="action" value="confirmLeave">
                    <input type="hidden" name="ucid" id="modalUcid">
                    <button type="submit" class="btn danger">Leave Challenge</button>
                </form>
            </div>
        </div>
    </div>

    <script src="challenges.js?v=<?php echo time(); ?>"></script>
</body>
</html>
<?php
include '../general/footer.php';
ob_end_flush(); 
?>