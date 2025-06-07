<?php
ob_start();
session_start();
include '../general/dbconn.php'; // Include database connection file
include '../general/member-nav.php';
$userid = $_SESSION['userid'];

// If starting a new challenge, do not retrieve challenge details immediately
if (isset($_POST['btnstart'])) {
    // Check if the challenge exists
    $challengeid = $_POST['challengeid'];
    $check_sql = "SELECT * FROM challenges WHERE challengeid='$challengeid'";
    $check_result = mysqli_query($connection, $check_sql);
    if (mysqli_num_rows($check_result) > 0) {
        // Challenge exists, proceed to start
        $start = date('Y-m-d'); // Current date as start date
        $end = date('Y-m-d', strtotime('+6 days')); // End date is 7 days later, adjust as needed
        $sql = "
            INSERT INTO userchallenges (userid, challengeid, progress, startDate, endDate, status)
            VALUES ('$userid', '$challengeid', 0, '$start', '$end','ongoing')"; // Initial progress is 0
        if (mysqli_query($connection, $sql)) {
            $ucid = mysqli_insert_id($connection); // Get the ucid just inserted
            header("Location: checkin.php?ucid=$ucid");
            exit; 
        } else {
            header("Location: challenges.php?status=error&message=Failed to start challenge");
            exit;
        }
    } else {
        // Challenge does not exist, notify user
        header("Location: challenges.php?status=error&message=Challenge no longer exists");
        exit;
    }
}

$ucid = $_POST['ucid'] ?? $_GET['ucid'];

// Retrieve challenge details from the database
$sql_challenge = "SELECT c.challengeid, c.challengeName, c.description, c.difficulty, c.img, uc.challengeid, uc.progress, uc.startDate, uc.endDate
    FROM userchallenges uc
    JOIN challenges c ON uc.challengeid = c.challengeid
    WHERE uc.ucid = $ucid AND uc.userid = '$userid'";
$result = mysqli_query($connection, $sql_challenge);
$challenge = mysqli_fetch_assoc($result);

if (!$challenge) {
    die('Error: Challenge not found.');
}

$challenge['img'] = 'data:image/jpeg;base64,' . base64_encode($challenge['img']); // Assuming the image is in JPEG format
// Calculate current day number
$currentDate = new DateTime();
$startDate = new DateTime($challenge['startDate']);
$endDate = new DateTime($challenge['endDate']);
$progress = $challenge['progress'];
$challengeid = $challenge['challengeid'];
// Calculate check-in date range for the challenge
$dateInterval = DateInterval::createFromDateString('1 day');
$datePeriod = new DatePeriod($startDate, $dateInterval, 7); // Create a 7-day period

if (!isset($ucid) || $ucid === '') {
    die('Error: UCID is missing.');
}

if ($endDate->format('Y-m-d') < $currentDate->format('Y-m-d')) {
    $status = ($totalCheckInDay == 7) ? 'success' : 'fail';
    $sql_status = "UPDATE userchallenges SET status = '$status' WHERE ucid = $ucid";
    mysqli_query($connection, $sql_status);
    if ($status === 'success') {
        echo "<script>
                alert('Congratulations! Your challenge was successful! You can view the details in the history.');
                window.location.href = 'challenges.php';
              </script>";
    } else {
        echo "<script>
                alert('Your challenge has failed. Try again next time!');
                window.location.href = 'challenges.php';
              </script>";
    }
    exit;
}

// Get the user's check-in record
$sql_checkins = "SELECT checkin_date FROM user_checkin WHERE ucid=$ucid";
$checkins_result = mysqli_query($connection, $sql_checkins);
$checkin_dates = [];
while ($row = mysqli_fetch_assoc($checkins_result)) {
    $checkin_dates[] = $row['checkin_date'];
}

if ($_SERVER['REQUEST_METHOD'] === "POST"){
    if (isset($_POST['checkInDate'])) {
        $checkInDate = $_POST['checkInDate'];
        $sql_insert = "INSERT INTO user_checkin (ucid, checkin_date) VALUES ($ucid, '$checkInDate')";
        mysqli_query($connection, $sql_insert);

        $sql_checkins = "SELECT checkin_date FROM user_checkin WHERE ucid = $ucid";
        $checkins_result = mysqli_query($connection, $sql_checkins);
        $checkin_dates = [];
        while($row = mysqli_fetch_assoc($checkins_result)){
            $checkin_dates[] = $row['checkin_date'];
        }
        $totalCheckInDay = count($checkin_dates);
        
        $sql_progress = "UPDATE userchallenges SET progress = $totalCheckInDay WHERE ucid = $ucid";
        mysqli_query($connection, $sql_progress);
        
        if ($checkInDate === $endDate->format('Y-m-d')) {
            $status = ($totalCheckInDay == 7) ? 'success' : 'fail';
            $sql_status = "UPDATE userchallenges SET status = '$status' WHERE ucid = $ucid";
            mysqli_query($connection, $sql_status);
            if ($status === 'success') {
                echo "<script>
                        alert('Congratulations! Your challenge was successful! You can view the details in the history.');
                        window.location.href = 'challenges.php';
                      </script>";
            } else {
                echo "<script>
                        alert('Your challenge has failed. Try again next time!');
                        window.location.href = 'challenges.php';
                      </script>";
            }
            exit;
        }
        
        header("Location: checkin.php?ucid=$ucid");
        exit;
    }
}


// Get suggested challenges with the same difficulty level
$sql_suggested = "SELECT c.challengeid, c.challengeName, c.description, c.difficulty, c.img
                  FROM challenges c 
                  WHERE c.difficulty = '{$challenge['difficulty']}' 
                  AND c.challengeid != $challengeid
                  AND NOT EXISTS (
                      SELECT 1 FROM userchallenges uc 
                      WHERE uc.challengeid = c.challengeid 
                      AND uc.userid = '$userid' AND uc.status = 'ongoing')";
$suggestedResult = mysqli_query($connection, $sql_suggested);

// Get ongoing challenges for the user
$sql_ongoing = "SELECT c.challengeid, c.challengeName, c.description, c.difficulty, c.img, uc.ucid
                FROM userchallenges uc 
                JOIN challenges c ON uc.challengeid = c.challengeid 
                WHERE uc.userid = '$userid' AND c.challengeid != $challengeid AND uc.status = 'ongoing'";
$ongoingResult = mysqli_query($connection, $sql_ongoing);

$sql_comments = "SELECT u.Username, u.Profile_pic, c.comment, c.createdAt, c.status
                 FROM challengecomments c
                 JOIN user u ON c.userid = u.userID
                 WHERE c.challengeid = '$challengeid' AND c.status = 'active'
                 ORDER BY c.createdAt DESC";
$commentsResult = mysqli_query($connection, $sql_comments);

$comments = [];
while ($row = mysqli_fetch_assoc($commentsResult)) {
    $row['Profile_pic'] = 'data:image/jpeg;base64,' . base64_encode($row['Profile_pic']); // Convert image to Base64
    $comments[] = $row;
}

// Initialize arrays for suggested and ongoing challenges
$suggestedChallenges = [];
$ongoingChallenges = [];

// Process suggested challenges and convert BLOB data to Base64
while ($row = mysqli_fetch_assoc($suggestedResult)) {
    $row['img'] = 'data:image/jpeg;base64,' . base64_encode($row['img']); // Convert image to Base64
    $suggestedChallenges[] = $row; // Add suggested challenge to the array
}

// Process ongoing challenges and convert BLOB data to Base64
while ($row = mysqli_fetch_assoc($ongoingResult)) {
    $row['img'] = 'data:image/jpeg;base64,' . base64_encode($row['img']); // Convert image to Base64
    $ongoingChallenges[] = $row; // Add ongoing challenge to the array
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $comment = mysqli_real_escape_string($connection, $_POST['comment']);
    $sql_insert = "INSERT INTO challengecomments (userid, challengeid, comment, createdAt, status)
                   VALUES ('$userid', '$challengeid', '$comment', NOW(), 'active')";
    if (!mysqli_query($connection, $sql_insert)) {
        die("Error: " . mysqli_error($connection));
    }
    // Reload the page to display the new comment
    header("Location: checkin.php?ucid=$ucid");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check In | Challenge Tracker</title>
    <link rel="stylesheet" href="checkin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Challenge Header -->
        <div class="challenge-header">
            <div class="challenge-card" style="--challenge-image: url('<?= htmlspecialchars($challenge['img']); ?>'); --progress-width: <?= ($challenge['progress'] / 7) * 100 ?>%">
                <h1 class="challenge-title"><?= htmlspecialchars($challenge['challengeName']); ?></h1>
                <p><?= htmlspecialchars($challenge['description']); ?></p>
                
                <div class="challenge-meta">
                    <div class="meta-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span><?= htmlspecialchars(ucfirst($challenge['difficulty'])); ?> Difficulty</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-day"></i>
                        <span>Started: <?= date("M j, Y", strtotime($challenge['startDate'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-flag-checkered"></i>
                        <span>Ends: <?= date("M j, Y", strtotime($challenge['endDate'])); ?></span>
                    </div>
                </div>
                
                <div class="progress-container">
                    <div class="progress-text">
                        <span>Your Progress</span>
                        <span><?= htmlspecialchars($challenge['progress']); ?>/7 Days</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Check-in Section -->
        <section class="checkin-section">
            <h2 class="section-title">
                <i class="fas fa-calendar-check"></i>
                Daily Check-in
            </h2>
            
            <form method="POST" action="checkin.php?ucid=<?= htmlspecialchars($ucid); ?>">
                <div class="checkin-grid">
                    <?php
                        $dayCount = 1;
                        foreach ($datePeriod as $date) :
                            if ($dayCount > 7) break;
                            $dateString = $date->format('M j');
                            $isChecked = in_array($date->format('Y-m-d'), $checkin_dates);
                            $isToday = ($date->format('Y-m-d') === $currentDate->format('Y-m-d'));
                            $isPast = ($date->format('Y-m-d') < $currentDate->format('Y-m-d'));
                    ?>
                    <div class="checkin-day <?= $isChecked ? 'checked' : ($isToday ? 'today' : ($isPast ? 'past' : '')) ?>">
                        <input type="radio" id="day<?= htmlspecialchars($dayCount) ?>" name="checkInDate" 
                               value="<?= htmlspecialchars($date->format('Y-m-d')) ?>" 
                               <?= ($isChecked || !$isToday) ? 'disabled' : '' ?>>
                        <label for="day<?= htmlspecialchars($dayCount) ?>">
                            <span class="day-number"><?= htmlspecialchars($dayCount) ?></span>
                            <span class="day-date"><?= htmlspecialchars($dateString) ?></span>
                            <?php if($isChecked): ?>
                                <i class="fas fa-check-circle" style="color: var(--white); margin-top: 5px;"></i>
                            <?php endif; ?>
                        </label>
                    </div>
                    <?php
                        $dayCount++;
                        endforeach;
                    ?>
                </div>
                
                <div class="checkin-submit">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        Submit Check-in
                    </button>
                </div>
            </form>
        </section>
        
        <!-- Comments Section -->
        <section class="comments-section">
            <h2 class="section-title">
                <i class="fas fa-comments"></i>
                Community Feedback
            </h2>
            
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <img src="<?= htmlspecialchars($comment['Profile_pic']); ?>" alt="User Avatar" class="comment-avatar">
                        <div class="comment-content">
                            <div class="comment-header">
                                <span class="comment-user"><?= htmlspecialchars($comment['Username']); ?></span>
                                <span class="comment-time"><?= date("M j, Y g:i a", strtotime($comment['createdAt'])); ?></span>
                            </div>
                            <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" action="checkin.php?ucid=<?= htmlspecialchars($ucid); ?>" class="comment-form">
                <textarea name="comment" class="comment-input" placeholder="Share your experience with this challenge..." rows="3" required></textarea>
                <input type="hidden" name="ucid" value="<?= htmlspecialchars($ucid); ?>">
                <button type="submit" name="submit_comment" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Post Comment
                </button>
            </form>
        </section>
        
        <!-- Challenges Sections -->
        <section class="challenges-section">
            <!-- Suggested Challenges -->
            <div class="challenges-card">
                <h2 class="section-title">
                    <i class="fas fa-lightbulb"></i>
                    Recommended Challenges
                </h2>
                
                <div class="challenges-list">
                    <?php foreach ($suggestedChallenges as $suggested): ?>
                        <div class="challenge-item" style="background-image: url('<?= $suggested['img']; ?>');">
                            <div class="challenge-overlay">
                                <div class="challenge-name"><?= htmlspecialchars($suggested['challengeName']); ?></div>
                                <div class="challenge-difficulty">
                                    <i class="fas fa-bolt"></i>
                                    <?= htmlspecialchars(ucfirst($suggested['difficulty'])); ?>
                                </div>
                                <form method="POST" action="checkin.php" class="challenge-action">
                                    <input type="hidden" name="challengeid" value="<?= htmlspecialchars($suggested['challengeid']); ?>">
                                    <button type="submit" name="btnstart" class="btn btn-primary btn-sm">
                                        <i class="fas fa-play"></i>
                                        Start
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Ongoing Challenges -->
            <div class="challenges-card">
                <h2 class="section-title">
                    <i class="fas fa-tasks"></i>
                    Your Active Challenges
                </h2>
                
                <div class="challenges-list">
                    <?php foreach ($ongoingChallenges as $ongoing): ?>
                        <div class="challenge-item" style="background-image: url('<?= $ongoing['img']; ?>');">
                            <div class="challenge-overlay">
                                <div class="challenge-name"><?= htmlspecialchars($ongoing['challengeName']); ?></div>
                                <div class="challenge-difficulty">
                                    <i class="fas fa-bolt"></i>
                                    <?= htmlspecialchars(ucfirst($ongoing['difficulty'])); ?>
                                </div>
                                <form method="POST" action="checkin.php" class="challenge-action">
                                    <input type="hidden" name="ucid" value="<?= htmlspecialchars($ongoing['ucid']); ?>">
                                    <button type="submit" name="btncheckIn" class="btn btn-primary btn-sm">
                                        <i class="fas fa-check-circle"></i>
                                        Check In
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
<?php
include '../general/footer.php';
ob_flush();
?>