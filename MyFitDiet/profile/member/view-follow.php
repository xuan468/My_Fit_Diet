<?php


include '../../general/dbconn.php'; 

$userID = $_SESSION['userid'];

// Fetch users that are following the logged-in user
$sql = "SELECT u.userID, u.Username, u.Profile_pic, u.Country, u.Gender, u.Age, 
               (SELECT COUNT(*) FROM following_user WHERE followID = ? AND followedID = u.userID) AS isFollowing
        FROM user u
        LEFT JOIN following_user f ON f.followID = u.userID AND f.followedID = ?
        WHERE f.followedID = ?";

$stmt = $connection->prepare($sql);
$stmt->bind_param("iii", $userID, $userID, $userID);
$stmt->execute();
$result = $stmt->get_result();

$followers = [];
while ($row = $result->fetch_assoc()) {
    $followers[] = $row;
}

// Fetch users that the logged-in user is following
$sql = "SELECT u.userID, u.Username, u.Profile_pic, u.Country, u.Gender, u.Age, 
               (SELECT COUNT(*) FROM following_user WHERE followID = ? AND followedID = u.userID) AS isFollowing
        FROM user u
        LEFT JOIN following_user f ON f.followedID = u.userID AND f.followID = ?
        WHERE f.followID IS NOT NULL";

$stmt = $connection->prepare($sql);
$stmt->bind_param("ii", $userID, $userID);
$stmt->execute();
$result = $stmt->get_result();

$following = [];
while ($row = $result->fetch_assoc()) {
    $following[] = $row;
}

$stmt->close();
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Followers</title>
    <link rel="stylesheet" href="view-follow.css?v=<?php echo time(); ?>">
</head>
<body>

<button id="showFollower">View Followers</button>

<!-- Modal Structure -->
<div id="followerList" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">X</span>
        <h2>Followers</h2>
        <form id="followerContainer" method="POST" action="view-follower.php">
            <ul>
                <?php if (!empty($followers)): ?>
                    <?php foreach ($followers as $user): ?>
                        <li class="user-item">
                            <img src="<?php echo !empty($user['Profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($user['Profile_pic']) : 'icons/default_profile.png'; ?>" alt="Profile Picture" class="profile-pic">
                            <div class="user-info">
                                <div class="username-gender">
                                    <span class="following-username"><?php echo htmlspecialchars($user['Username']); ?></span>
                                    <img src="../images/<?php echo strtolower($user['Gender']); ?>.png" 
                                         alt="<?php echo htmlspecialchars($user['Gender']); ?>" class="gender-icon">
                                </div>
                                <div class="age-country">
                                    <span class="age"><?php echo htmlspecialchars($user['Age']); ?> years old,</span>&nbsp;
                                    <span class="country"><?php echo htmlspecialchars($user['Country']); ?></span>
                                </div>
                            </div>
                            <a href="view-member.php?profileUserID=<?php echo $user['userID']; ?>" class="view-btn">View</a>
                            <button class="follow-btn <?php echo $user['isFollowing'] ? 'unfollow-btn' : ''; ?>" data-userid="<?php echo $user['userID']; ?>">
                                <?php echo $user['isFollowing'] ? 'Unfollow' : 'Follow'; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>You have no followers currently.</div>
                <?php endif; ?>
            </ul>
        </form>
    </div>
</div>

<button id="showFollowing">View Following</button>

<div id="followingList" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">X</span>
        <h2>Following</h2>
        <form id="followingContainer">
            <ul>
                <?php if (!empty($following)): ?>
                    <?php foreach ($following as $user): ?>
                        <li class="user-item">
                            <img src="<?php echo !empty($user['Profile_pic']) ? 'data:image/jpeg;base64,' . base64_encode($user['Profile_pic']) : 'icons/default_profile.png'; ?>" alt="Profile Picture" class="profile-pic">
                            <div class="user-info">
                                <div class="username-gender">
                                    <span class="following-username"><?php echo htmlspecialchars($user['Username']); ?></span>
                                    <img src="../images/<?php echo strtolower($user['Gender']); ?>.png" 
                                         alt="<?php echo htmlspecialchars($user['Gender']); ?>" class="gender-icon">
                                </div>
                                <div class="age-country">
                                    <span class="age"><?php echo htmlspecialchars($user['Age']); ?> years old,</span>&nbsp;
                                    <span class="country"><?php echo htmlspecialchars($user['Country']); ?></span>
                                </div>
                            </div>
                            <a href="view-member.php?profileUserID=<?php echo $user['userID']; ?>" class="view-btn">View</a>
                            <button class="follow-btn <?php echo $user['isFollowing'] ? 'unfollow-btn' : ''; ?>" data-userid="<?php echo $user['userID']; ?>">
                                <?php echo $user['isFollowing'] ? 'Unfollow' : 'Follow'; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>You are not following anyone currently.</li>
                <?php endif; ?>
            </ul>
        </form>
    </div>
</div>
</body>
</html>

<script>
document.getElementById("showFollower").addEventListener("click", function() {
    document.getElementById("followerList").style.display = "flex";
});

document.getElementById("showFollowing").addEventListener("click", function() {
    document.getElementById("followingList").style.display = "flex";
});

// Close modals when clicking on close buttons
document.querySelectorAll(".close").forEach(button => {
    button.addEventListener("click", function () {
        this.closest(".modal").style.display = "none"; // Hide the modal
        location.reload();
    });
});

// Handle follow/unfollow button click
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".follow-btn").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault(); // Prevent form submission
            let userID = this.getAttribute("data-userid");
            let button = this;

            fetch("follow_action.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "profileUserID=" + encodeURIComponent(userID)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.textContent = data.status === "followed" ? "Unfollow" : "Follow";
                    button.classList.toggle("unfollow-btn", data.status === "followed");
                    
                }
            })
            .catch(error => console.error("Error:", error));
        });
    });
});

</script>


