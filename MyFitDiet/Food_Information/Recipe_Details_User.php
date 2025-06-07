<?php
session_start();
include '../general/dbconn.php'; 
include '../general/member-nav.php';

if(!isset($_SESSION['userid'])){
    die("User not logged in.");
}

$user_id = $_SESSION['userid'];
$recipeId = isset($_REQUEST['recipe_id']) ? intval($_REQUEST['recipe_id']) : 0;


if ($recipeId <= 0) {
    die("Invalid recipe ID.". var_export($_GET['recipe_id'], true));
}

$query = "SELECT recipe_id, video, purpose, recipename, difficulty, description, time, ingredient, calories FROM recipe WHERE recipe_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $recipeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recipe = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

function extractYouTubeID($url) {
    parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
    if (isset($queryParams['v'])) {
        return $queryParams['v']; 
    } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1]; 
    }
    return ''; 
}

if(!$recipe){
    echo "Recipe not found.";
    exit;
}

$difficulty = $recipe['difficulty'];
$stars = str_repeat("★", $difficulty) . str_repeat("☆", 5 - $difficulty);

$favQuery = "SELECT * FROM fav_recipe WHERE userID = ? AND recipe_id = ?";
$favStmt = $connection->prepare($favQuery);
$favStmt->bind_param("ii", $user_id, $recipeId);
$favStmt->execute();
$favResult = $favStmt->get_result();
$is_favorited = $favResult->num_rows > 0; 
$favStmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['recipename']); ?></title>
    <link rel="stylesheet" href="Recipe_Details_User.css?v=<?php echo time();?>">
</head>
<body>
    <div class="mainbody">
    <button class="back" id="btnPrevious" onclick="goback()">⬅️ Previous</button>
        <div class="contents">
        <button id="favBtn" class="favourite-icon" data-recipe-id="<?php echo $recipeId; ?>" style="color: <?php echo $is_favorited ? 'red' : 'black'; ?>;">&#10084;</button>
            <div class="video">
                <iframe 
                    src="https://www.youtube.com/embed/<?php echo extractYouTubeID($recipe['video']); ?>" frameborder="0" allowfullscreen>
                </iframe>
            </div>
            <div class="name">
                <p><?php echo htmlspecialchars($recipe['recipename']); ?></p>
            </div>    
            <div class="difficulty">
                <label for="stars">Difficulty:</label> 
                <span class="stars"><?php echo($stars); ?></span>
            </div>
            <div class="description">
                <p class="short-des"><?php echo htmlspecialchars($recipe['description']); ?></p>
            </div>
            <div class="label">
                <label for="label">⏲️Time:</label>
                <p class="input"><?php echo htmlspecialchars($recipe['time']); ?></p>
            </div>
            <div class="label">
                <label for="label">🥦Ingredients: </label>
                <p class="input"><?php echo htmlspecialchars($recipe['ingredient']); ?></p>
            </div>
            <div class="label">
                <label for="label">🔥Calories: </label>
                <p class="input" name="calories"><?php echo htmlspecialchars($recipe['calories']); ?>kcal</p>
            </div>
        </div>
    </div>

    <script>
        function goback(){
            window.location.href = 'Recipe_User.php';
        }

        document.addEventListener("DOMContentLoaded", function () {
            let favButton = document.getElementById("favBtn");
            if (favButton) {
                favButton.addEventListener("click", function () {
                    let recipeId = favButton.getAttribute("data-recipe-id");
                    let isFavorited = favButton.style.color === "red";
                    let action = isFavorited ? "remove" : "add";

                    fetch("fav_handler.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "recipe_id=" + encodeURIComponent(recipeId) + "&action=" + encodeURIComponent(action)
                    })
                    .then(response => response.text())
                    .then(data => {
                        console.log("Server response:", data);
                        if (data.trim() === "Success") {
                            favButton.style.color = isFavorited ? "black" : "red";
                        } else {
                            console.error("Failed to update favorite status:", data);
                            alert("Error: " + data);
                        }
                    })
                    .catch(error => {
                        console.error("Request error:", error);
                        alert("Network error: Please try again.");
                    });
                });
            } else {
                console.error("Favorite button not found!");
            }
        });

    </script>
</body>
</html>

<?php
include '../general/footer.php';
?>