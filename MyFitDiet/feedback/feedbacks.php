<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../general/dbconn.php';

if (isset($_SESSION['userrole'])) {
    $userrole = strtolower($_SESSION['userrole']);
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Your'; 

    if ($userrole === "manager" || $userrole === "admin") {
        $userid = $_SESSION['userroleid']; 
    } elseif ($userrole === "member") {
        $userid = $_SESSION['userid'];
    }
}

$containerClass = ($userrole !== 'member') ? 'reviews-container admin' : 'reviews-container';

switch ($userrole) {
    case 'manager':
        include '../general/manager-nav.php';
        break;
    case 'admin':
        include '../general/admin-nav.php';
        break;
    case 'member':
        include '../general/member-nav.php';
        break;
}

$formSubmitted = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($connection, $_POST['username']);
    $feedback_emoji = mysqli_real_escape_string($connection, $_POST['emoji']);
    $feedback_categories = mysqli_real_escape_string($connection, $_POST['categories']);
    $feedback_text = mysqli_real_escape_string($connection, $_POST['feedback']);
    $rating = mysqli_real_escape_string($connection, $_POST['rating']);

    $stmt = mysqli_prepare($connection, "INSERT INTO feedbacktbl (username, userID, feedback_emoji, feedback_categories, feedback_text, rating) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sisssi', $username, $userid, $feedback_emoji, $feedback_categories, $feedback_text, $rating);

    if (mysqli_stmt_execute($stmt)) {
        $formSubmitted = true;
        header("Location: feedbacks.php?success=1");
        exit();
    } else {
        $errorMessage = 'Error: ' . mysqli_error($connection);
    }

    mysqli_stmt_close($stmt);
}

$reviews = [];
$totalRatings = 0;
$totalReviews = 0;
$ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

$query = "SELECT f.feedback_id, f.userID, f.feedback_categories, f.feedback_text, f.rating, f.user_status, f.created_at,
                 r.reply_text, r.userID AS admin_userID, r.created_at AS reply_date,
                 u.Username AS user_name, u.Status AS user_status,
                 s.username AS staff_name, s.role AS staff_role
          FROM feedbacktbl f
          JOIN user u ON f.userID = u.userID
          LEFT JOIN feedback_replies r ON f.feedback_id = r.feedback_id
          LEFT JOIN staff s ON r.userID = s.userroleID 
          WHERE f.user_status = 'active'
          ORDER BY f.feedback_id, r.created_at";

$result = mysqli_query($connection, $query);
if ($result) {
    $processed_feedback_ids = []; 
    while ($row = mysqli_fetch_assoc($result)) {
        $feedback_id = $row['feedback_id'];

        if (!isset($processed_feedback_ids[$feedback_id])) {
            $rating = (int) $row['rating'];
            $totalRatings += $rating;
            $totalReviews++;

            if (isset($ratingCounts[$rating])) {
                $ratingCounts[$rating]++;
            }

            $processed_feedback_ids[$feedback_id] = true;
        }

        $staff_name = $row['staff_name'] ?: 'Unknown';
        $staff_role = ucfirst($row['staff_role']);   

        if (!isset($reviews[$feedback_id])) {
            $user_status = strtolower($row['user_status']);
            $username = ($user_status == 'block') ? "User has been blocked" : $row['user_name'];

            $reviews[$feedback_id] = [
                'feedback_id' => $feedback_id,
                'username' => $username,
                'date' => $row['created_at'],
                'rating' => $rating,
                'categories' => $row['feedback_categories'] ?: 'None',
                'text' => $row['feedback_text'] ?: 'No feedback provided',
                'user_status' => $row['user_status'],
                'replies' => []
            ];
        }

        if (!empty($row['reply_text'])) {
            $reviews[$feedback_id]['replies'][] = [
                'staff_role' => $staff_role, 
                'staff_name' => $staff_name, 
                'reply_text' => $row['reply_text'],
                'reply_date' => $row['reply_date']
            ];
        }
    }
}

$averageRating = $totalReviews > 0 ? round($totalRatings / $totalReviews, 1) : 0;

$header_query = "SELECT position, image_data FROM header_images ORDER BY position ASC";
$header_result = $connection->query($header_query);
$headerImages = [];
while ($row = $header_result->fetch_assoc()) {
    $headerImages[$row['position']] = base64_encode($row['image_data']);
}

mysqli_close($connection);
ob_end_flush();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Form</title>
    <link rel="stylesheet" href="feedbacks.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../general/Navigation.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
        <div class="collage-container">
            <?php if($i = 6): ?>
                <!-- Collage of images -->
                <div class="collage-item">
                    <img src="data:image/jpeg;base64,<?php echo $headerImages[$i] ?? ''; ?>" alt="Feedback Image <?php echo $i; ?>">
                    <?php if (strtolower($_SESSION['userrole']) !== 'member'): ?>
                        <img src="../images/edit.jpg" class="edit-icon" onclick="openImageUpload(<?php echo $i; ?>)">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div id="uploadForm" class="modals">
                <div class="modal-contents">
                    <span class="close" onclick="closeImageUpload()">&times;</span>  
                    <h2>Upload Image</h2>
                    <input type="hidden" id="positionInput">
                    <label for="imageFile" class="file-label">Choose an image</label>  
                    <input type="file" id="imageFile" accept="image/*" class="file-input">                    
                    <div class="button-group">
                        <button class="confirm-btn" onclick="uploadImage()">Confirm</button>
                        <button class="cancel-btn" onclick="closeImageUpload()">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Feedback Form Section -->
    
    <div class="page-layout">
        <div class="feedback-container">
            <?php if (strtolower($_SESSION['userrole']) === 'member'): ?>
                <h1>Your Feedback</h1>
                <p>We would like your feedback to improve our website.</p>
                
                <!-- Feedback form with POST method -->
                <form action="feedbacks.php" method="POST" id="feedbackForm">
                    <span>Name</span>
                    <div class="username">
                        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>

                    <span>What is your opinion of this page?</span>
                    <div class="emojis" id="emojiSelection">
                        <img src="../images/icons8-sad-face-48.png" alt="Sad" data-emoji="Sad" onclick="selectEmoji(this)">
                        <img src="../images/icons8-confused-face-48.png" alt="Confused" data-emoji="Confused" onclick="selectEmoji(this)">
                        <img src="../images/icons8-neutral-face-48.png" alt="Neutral" data-emoji="Neutral" onclick="selectEmoji(this)">
                        <img src="../images/icons8-smiling-face-48.png" alt="Happy" data-emoji="Happy" onclick="selectEmoji(this)">
                        <img src="../images/icons8-star-struck-48.png" alt="Very Happy" data-emoji="Very Happy" onclick="selectEmoji(this)">
                    </div>

                    <span>Please select your feedback category below.</span>
                    <div class="category-buttons" id="categorySelection">
                        <button type="button" onclick="toggleCategory(this)" data-category="Website Design">Website Design</button>
                        <button type="button" onclick="toggleCategory(this)" data-category="Content Quality">Content Quality</button>
                        <button type="button" onclick="toggleCategory(this)" data-category="User Experience">User Experience</button>
                        <button type="button" onclick="toggleCategory(this)" data-category="Navigation">Navigation</button>
                        <button type="button" onclick="toggleCategory(this)" data-category="Other">Other</button>
                    </div>

                    <textarea id="feedbackText" name="feedback" placeholder="Please leave your feedback below:"></textarea>

                    <span>Rate your experience:</span>
                    <div class="star-rating" id="starRating">
                        <span class="star" data-value="1">&#9734;</span>
                        <span class="star" data-value="2">&#9734;</span>
                        <span class="star" data-value="3">&#9734;</span>
                        <span class="star" data-value="4">&#9734;</span>
                        <span class="star" data-value="5">&#9734;</span>
                    </div>
                    <input type="hidden" id="ratingInput" name="rating"> <!-- Hidden save information to the server(data-value) -->

                    <input type="hidden" id="emojiInput" name="emoji"> <!-- Hidden save information to server(data-emoji) -->
                    <input type="hidden" id="categoryInput" name="categories"> <!-- Hidden save information to server(data-category",") -->

                    <button type="submit" class="submit-button">Send</button>
                </form>
            <?php endif; ?>
        </div>
        <!-- Right hand side -->
        <div class="<?php echo $containerClass; ?>">
            <h2>Our Reviews</h2>
            <p>Of course we utilize our own service to constantly gather feedback</p>

            <!-- Rating section -->
            <div class="rating-summary">
                <h3><?php echo $averageRating; ?> Out of 5 Stars</h3>
                <div class="stars">
                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= floor($averageRating)) {
                            echo '★'; 
                        } else {
                            echo '☆'; 
                        }
                    }
                    ?>
                </div>

                <?php foreach ($ratingCounts as $stars => $count): ?> 
                    <div class="rating-bar">
                        <span><?php echo $stars; ?> Stars: <?php echo $count; ?></span>
                        <div class="bar-background">
                            <div class="bar-fill" style="width: <?php echo ($totalReviews > 0 ? ($count / $totalReviews) * 100 : 0); ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <!-- Customer comment -->
            <div class="customer-reviews">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <h4>
                        <?php if ($review['username'] == "User has been blocked"): ?>
                            <span class="status-block">🚫 User has been blocked</span>
                        <?php else: ?>
                            <?php echo $review['username']; ?>
                        <?php endif; ?> 
                            <span class="status <?php echo strtolower($review['user_status']); ?>">
                                <?php echo ucfirst($review['user_status']); ?>
                            </span>
                            - <?php echo date('F j, Y', strtotime($review['date'])); ?>
                        </h4>
                        <div class="stars">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <p><strong>Categories:</strong> <?php echo $review['categories']; ?></p>
                        <p><?php echo $review['text']; ?></p>

                        <!-- Admin & Manager Reply -->
                        <?php if(!empty($review['replies'])): ?>
                            <div class="admin-replies">
                                <?php foreach ($review['replies'] as $reply): ?>
                                    <div class="admin-reply">
                                        <p><strong><?php echo $reply['staff_role']; ?> <?php echo $reply['staff_name']; ?> replied:</strong> <?php echo $reply['reply_text']; ?></p>
                                        <br>
                                        <span class="reply-date"><?php echo date('F j, Y', strtotime($reply['reply_date'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <!-- Admin & Manager Reply Form -->
                        <?php if (strtolower($_SESSION['userrole']) === 'admin' || strtolower($_SESSION['userrole']) === 'manager'): ?>
                            <form action="reply_feedback.php" method="POST" class="reply-form" ;>
                                <input type="hidden" name="feedback_id" value="<?php echo $review['feedback_id']; ?>">
                                <textarea name="reply_text" placeholder="Reply to this comment..." required></textarea>
                                <button class="reply-button" onclick="submitReply()">Reply</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <script src="feedbacks.js?v=<?php echo time(); ?>"></script>
</body>
</html>
<?php
include '../general/footer.php';
?>