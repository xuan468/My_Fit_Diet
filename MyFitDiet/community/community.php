<?php
ob_start();
// session_start();
include '../general/dbconn.php'; 
include '../general/member-nav.php';

$userid = $_SESSION['userid']; 

$sql_user = "SELECT Username, Profile_pic FROM user WHERE userID = $userid";
$result_user = mysqli_query($connection, $sql_user);

$current_user = [];

if ($row = mysqli_fetch_assoc($result_user)) {
    $current_user = $row;
    $current_user['Profile_pic'] = !empty($row['Profile_pic']) 
        ? 'data:image/jpeg;base64,' . base64_encode($row['Profile_pic']) 
        : 'default_avatar.jpg';
}

// Get top challenges EXCLUDING those the current user is already in
$sql_top_challenges = "SELECT c.challengeid, c.challengeName, c.description, c.img, c.score,
                      COUNT(uc.ucid) as participants
                      FROM challenges c
                      JOIN userchallenges uc ON c.challengeid = uc.challengeid
                      WHERE uc.status = 'ongoing'
                      AND c.challengeid NOT IN (
                          SELECT challengeid 
                          FROM userchallenges 
                          WHERE userid = $userid AND status = 'ongoing'
                      )
                      GROUP BY c.challengeid
                      ORDER BY participants DESC
                      LIMIT 5";

$result_challenges = mysqli_query($connection, $sql_top_challenges);
$top_challenges = [];

while ($row = mysqli_fetch_assoc($result_challenges)) {
    $row['img'] = !empty($row['img']) 
        ? 'data:image/jpeg;base64,' . base64_encode($row['img']) 
        : 'default_challenge.jpg';
    $top_challenges[] = $row;
}

// Get Explore's posts
$sql_explore = "SELECT p.*, u.Username, u.Profile_pic 
                FROM community p 
                JOIN user u ON p.userid = u.userID 
                WHERE p.status = 'active' 
                ORDER BY p.date DESC";
$result_explore = mysqli_query($connection, $sql_explore);
$explore_post = [];
while ($row = mysqli_fetch_assoc($result_explore)) {
    if (!empty($row['Profile_pic'])) {
        $row['Profile_pic'] = 'data:image/jpeg;base64,' . base64_encode($row['Profile_pic']); 
    } else {
        $row['Profile_pic'] = 'default_avatar.jpg'; // Set a default avatar
    }
    $explore_post[] = $row;
}

// Get Following's posts
$sql_following = "SELECT p.*, u.Username, u.Profile_pic 
                  FROM community p 
                  JOIN user u ON p.userid = u.userID 
                  JOIN following_user f ON p.userid = f.followedID 
                  WHERE f.followID = '$userid' AND p.status = 'active' 
                  ORDER BY p.date DESC";
$result_following = mysqli_query($connection, $sql_following);
while ($row = mysqli_fetch_assoc($result_following)) {
    if (!empty($row['Profile_pic'])) {
        $row['Profile_pic'] = 'data:image/jpeg;base64,' . base64_encode($row['Profile_pic']); 
    } else {
        $row['Profile_pic'] = 'default_avatar.jpg'; 
    }
    $following_post[] = $row;
}

// Get all comments
$sql_comments = "SELECT c.*, u.Username, u.Profile_pic 
                 FROM communitycomment c 
                 JOIN user u ON c.userid = u.userID 
                 WHERE c.status = 'active' 
                 ORDER BY c.createdAt ASC";
$result_comments = mysqli_query($connection, $sql_comments);
$comments = [];
while ($row = mysqli_fetch_assoc($result_comments)) {
    if (!empty($row['Profile_pic'])) {
        $row['Profile_pic'] = 'data:image/jpeg;base64,' . base64_encode($row['Profile_pic']); 
    } else {
        $row['Profile_pic'] = 'default_avatar.jpg';
    }
    $comments[$row['postid']][] = $row;
}

// Get the number of likes for each post
$sql_likes = "SELECT postid, COUNT(*) AS like_count 
              FROM post_likes 
              GROUP BY postid";
$result_likes = mysqli_query($connection, $sql_likes);
$likes = [];
while ($row = mysqli_fetch_assoc($result_likes)) {
    $likes[$row['postid']] = $row['like_count'];
}

// Get the current user's like status for each post
$sql_user_likes = "SELECT postid FROM post_likes WHERE userid = $userid";
$result_user_likes = mysqli_query($connection, $sql_user_likes);
$user_likes = [];
while ($row = mysqli_fetch_assoc($result_user_likes)) {
    $user_likes[] = $row['postid'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comment'], $_POST['postid'])) {
        $postid = $_POST['postid'];
        $comment = mysqli_real_escape_string($connection, $_POST['comment']);
        $insert_comment = "INSERT INTO communitycomment (userid, postid, comment, createdAt, status) 
                           VALUES ('$userid', '$postid', '$comment', NOW(), 'active')";
        mysqli_query($connection, $insert_comment);
        header("Location: community.php");
        exit;
    } elseif (isset($_POST['caption'])) { // Processing new posts
        $caption = mysqli_real_escape_string($connection, $_POST['caption']);
        $image = null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $image = addslashes(file_get_contents($_FILES['image']['tmp_name']));
        }
        $insert_post = "INSERT INTO community (userid, img, caption, date, status) 
                        VALUES ('$userid', '$image', '$caption', NOW(), 'active')";
        mysqli_query($connection, $insert_post);
        header("Location: community.php");
        exit;
    } else if (isset($_POST['challengeid'])){
        $challengeid = $_POST['challengeid'];
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
            mysqli_query($connection, $sql);
            header("Location: ../challenges/challenges.php");
        } else {
            // Challenge does not exist, notify user
            header("Location: challenges.php?status=error&message=Challenge no longer exists");
            exit;
        }
    }
    
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community</title>
    <link rel="stylesheet" href="community.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script defer src="community.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <div class="community-container">
        <div class="modules-sidebar">
        <div class="module-card" onclick="location.href='../schedules/schedule.php'">
            <div class="module-icon">💪</div>
            <div class="module-title">Workout Plan</div>
            <div class="module-desc">View your personalized training program</div>
        </div>
        
        <div class="module-card" onclick="location.href='../Food_Information/dietplans.php'">
            <div class="module-icon">🥗</div>
            <div class="module-title">Diet Plan</div>
            <div class="module-desc">Get today's recommended meals</div>
        </div>
        
        <div class="module-card" onclick="location.href='../feedback/feedbacks.php'">
            <div class="module-icon">📝</div>
            <div class="module-title">Feedback</div>
            <div class="module-desc">Help us improve our service</div>
        </div>
        
        <div class="module-card" onclick="location.href='../report/member_report.php'">
            <div class="module-icon">📊</div>
            <div class="module-title">Progress Summary</div>
            <div class="module-desc">View your monthly fitness data</div>
        </div>
    </div>

        <div class="container">
            <div class="nav">
                <button class="tab active" data-tab="explore">🌍 Explore</button>
                <button class="tab" data-tab="following">👥 Following</button>
            </div>

            <div class="post-form">
                <form method="POST" action="community.php" enctype="multipart/form-data">
                    <textarea name="caption" placeholder="What's on your mind?" required></textarea>
                    
                    <div class="file-upload">
                        <label for="file-input" class="file-label">
                            <i class="fas fa-image"></i> Choose Image
                        </label>
                        <input type="file" name="image" id="file-input" accept="image/jpeg, image/png, image/gif, image/webp">
                        <span class="file-name">No file chosen</span>
                    </div>

                    <div class="image-preview">
                        <img id="preview-image" src="#" alt="Image Preview" style="display: none;">
                        <button id="cancel-upload" style="display: none;">Cancel</button>
                    </div>

                    <button type="submit">Post</button>
                </form>
            </div>

            <div id="explore" class="post-feed active">
                <div class="post-container">
                    <?php foreach ($explore_post as $post): ?>
                        <div class="post">
                            <div class="meta">
                                <img src="<?= $post['Profile_pic'] ?: 'default.jpg' ?>" class="avatar" data-userid="<?= $post['userid'] ?>">
                                <div class="meta-info">
                                    <strong><?= htmlspecialchars($post['Username']) ?></strong>
                                    <span class="post-date"><?= date('d-m-Y H:i', strtotime($post['date'])) ?></span>
                                </div>   
                            </div>
                            <p><?= htmlspecialchars($post['caption']) ?></p>
                            <?php if ($post['img']): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($post['img']) ?>" class="post-image">
                            <?php endif; ?>
                            <!-- comment -->
                            <div class="comment-section">
                                <div class="like-section">
                                    <button class="like-btn <?= in_array($post['postid'], $user_likes) ? 'liked' : '' ?>" 
                                            data-postid="<?= $post['postid'] ?>">
                                        ❤️ <span class="like-count"><?= $likes[$post['postid']] ?? 0 ?></span>
                                    </button>
                                </div>
                                <?php if (!empty($comments[$post['postid']])): ?>
                                    <?php $comment_count = count($comments[$post['postid']]); ?>
                                    <?php foreach (array_slice($comments[$post['postid']], 0, 2) as $comment): ?>
                                        <div class="comment">
                                            <img src="<?= htmlspecialchars($comment['Profile_pic'] ?? 'default-avatar.jpg') ?>" alt="User Avatar" class="avatar" data-userid="<?= $comment['userid'] ?>">
                                            <div class="comment-content">
                                                <strong><?= htmlspecialchars($comment['Username']) ?>:</strong> 
                                                <?= htmlspecialchars($comment['comment']) ?>
                                                <span class="comment-date"><?= date('d-m-Y H:i', strtotime($comment['createdAt'])) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($comment_count > 2): ?>
                                        <div id="comments-<?= $post['postid'] ?>" class="hidden-comments" style="display: none;">
                                            <?php foreach (array_slice($comments[$post['postid']], 2) as $comment): ?>
                                                <div class="comment">
                                                    <img src="<?= htmlspecialchars($comment['Profile_pic'] ?? 'default-avatar.jpg') ?>" alt="User Avatar" class="avatar" data-userid="<?= $comment['userid'] ?>">
                                                    <div class="comment-content">
                                                        <strong><?= htmlspecialchars($comment['Username']) ?>:</strong> 
                                                        <?= htmlspecialchars($comment['comment']) ?>
                                                        <span class="comment-date"><?= date('d-m-Y H:i', strtotime($comment['createdAt'])) ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button class="show-comments" data-postid="<?= $post['postid'] ?>">Show all comments (<?= $comment_count ?>)</button>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <form method="POST" action="community.php">
                                    <img src="<?= htmlspecialchars($current_user['Profile_pic']) ?>" alt="User Avatar" class="avatar" data-userid="<?= $userid ?>">
                                    <input type="hidden" name="postid" value="<?= $post['postid'] ?>">
                                    <input type="text" name="comment" placeholder="Write a comment..." required>
                                    <button type="submit">➤</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="following" class="post-feed">
                <div class="post-container">
                    <?php foreach ($following_post as $post):  ?>
                        <div class="post">
                            <div class="meta">
                                <img src="<?= $post['Profile_pic'] ?: 'default.jpg' ?>" class="avatar" data-userid="<?= $post['userid'] ?>">
                                <div class="meta-info">
                                    <strong><?= htmlspecialchars($post['Username']) ?></strong>
                                    <span class="post-date"><?= date('d-m-Y H:i', strtotime($post['date'])) ?></span>
                                </div>
                            </div>
                            <p><?= htmlspecialchars($post['caption']) ?></p>
                            <?php if ($post['img']): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($post['img']) ?>" class="post-image">
                            <?php endif; ?>

                            <!-- comment -->
                            <div class="comment-section">
                                <div class="like-section">
                                    <button class="like-btn <?= in_array($post['postid'], $user_likes) ? 'liked' : '' ?>" 
                                            data-postid="<?= $post['postid'] ?>">
                                        ❤️ <span class="like-count"><?= $likes[$post['postid']] ?? 0 ?></span>
                                    </button>
                                </div>
                                <?php if (!empty($comments[$post['postid']])): ?>
                                    <?php $comment_count = count($comments[$post['postid']]); ?>
                                    <?php foreach (array_slice($comments[$post['postid']], 0, 2) as $comment): ?>
                                        <div class="comment">
                                            <img src="<?= htmlspecialchars($comment['Profile_pic'] ?? 'default-avatar.jpg') ?>" alt="User Avatar" class="avatar" data-userid="<?= $comment['userid'] ?>">
                                            <div class="comment-content">
                                                <strong><?= htmlspecialchars($comment['Username']) ?>:</strong> 
                                                <?= htmlspecialchars($comment['comment']) ?>
                                                <span class="comment-date"><?= date('d-m-Y H:i', strtotime($comment['createdAt'])) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($comment_count > 2): ?>
                                        <div id="comments-<?= $post['postid'] ?>" class="hidden-comments" style="display: none;">
                                            <?php foreach (array_slice($comments[$post['postid']], 2) as $comment): ?>
                                                <div class="comment">
                                                    <img src="<?= htmlspecialchars($comment['Profile_pic'] ?? 'default-avatar.jpg') ?>" alt="User Avatar" class="avatar" data-userid="<?= $comment['userid'] ?>">
                                                    <div class="comment-content">
                                                        <strong><?= htmlspecialchars($comment['Username']) ?>:</strong> 
                                                        <?= htmlspecialchars($comment['comment']) ?>
                                                        <span class="comment-date"><?= date('Y-m-d H:i', strtotime($comment['createdAt'])) ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button class="show-comments" data-postid="<?= $post['postid'] ?>">Show all comments (<?= $comment_count ?>)</button>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <form method="POST" action="community.php">
                                    <img src="<?= htmlspecialchars($current_user['Profile_pic']) ?>" alt="User Avatar" class="avatar" data-userid="<?= $userid ?>">
                                    <input type="hidden" name="postid" value="<?= $post['postid'] ?>">
                                    <input type="text" name="comment" placeholder="Write a comment..." required>
                                    <button type="submit">➤</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>                               
        <div class="challenges-sidebar">
            <div class="sidebar-header">
                <h3>🔥Hot Challenges</h3>
                <small>The most participated challenges</small>
            </div>
            <div class="challenges-list">
                <?php foreach ($top_challenges as $challenge): ?>
                    <div class="challenge-card">
                        <?php if ($challenge['img']): ?>
                            <img src="<?= $challenge['img'] ?>" alt="<?= htmlspecialchars($challenge['challengeName']) ?>">
                        <?php endif; ?>
                        <div class="challenge-title"><?= htmlspecialchars($challenge['challengeName']) ?></div>
                        <div class="challenge-stats">
                            <span>🏆 <?= $challenge['score'] ?>score</span>
                            <span>👥 <?= $challenge['participants'] ?>person participated</span>
                        </div>
                        <form method="POST" action="community.php" style="display:inline;">
                            <input type="hidden" name="challengeid" value="<?= htmlspecialchars($challenge['challengeid']); ?>">
                            <button type="submit" class="join-btn">Start</button> <!-- Check-in button -->
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div> 
    </div>
</body>
</html>
<?php
include '../general/footer.php';
?>