<?php
session_start();
include '../general/dbconn.php'; 

$userrole = strtolower($_SESSION['userrole']);
switch ($userrole) {
    case 'manager':
        include '../general/manager-nav.php';
        break;
    case 'admin':
        include '../general/admin-nav.php';
        break;
}

if(isset($_GET['recipe_id'])) {
    $recipeId = intval($_GET['recipe_id']);
} else {
    die('Recipe not found in URL.');
}

$query = "SELECT recipe_id, video, purpose, recipename, difficulty, description, time, ingredient, calories FROM recipe WHERE recipe_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $recipeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recipe = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
$difficulty = $recipe['difficulty'];
$stars = str_repeat("★", $difficulty) . str_repeat("☆", 5 - $difficulty);

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

if(isset($_POST['btnEdit'])){
    $recipename = trim($_POST['recipename']);
    $newVideo = trim($_POST['link']);
    $description = trim($_POST['description']);
    $time = trim($_POST['time']);
    $ingredient = trim($_POST['ingredient']);
    $calories = trim($_POST['calories']);

    if (!empty($newVideo)) {
        $videoID = extractYouTubeID($newVideo);
        if ($videoID == '') {
            die("<script>alert('Invalid YouTube URL format.');window.history.back();</script>");
        }elseif ($newVideo !== $recipe['video']) {
            $newVideo = "https://www.youtube.com/embed/" . $videoID;
        }else{
            $newVideo = $recipe['video'];
        }
    } else {
        $newVideo = $recipe['video']; 
    }

    if (
        $recipename !== $recipe['recipename'] ||
        $description !== $recipe['description'] ||
        $time !== $recipe['time'] ||
        $ingredient !== $recipe['ingredient'] ||
        $calories !== $recipe['calories'] ||
        $newVideo !== $recipe['video']
    ) {
        $query = "UPDATE recipe SET recipename = ?, description = ?, time = ?, ingredient = ?, calories = ?, video = ? WHERE recipe_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $recipename, $description, $time, $ingredient, $calories, $newVideo, $recipeId);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($success) {
            echo "<script>alert('Information updated successfully!');window.location.href = '".$_SERVER['PHP_SELF']."?recipe_id=".$recipeId."';</script>";
        } else {
            echo "<p style='color: white; margin-left: 20px;'>Error updating database: " . mysqli_error($connection) . "</p>";
        }
    } else {
        echo "<script>alert('No changes detected.');window.location.href = '".$_SERVER['PHP_SELF']."?recipe_id=".$recipeId."';</script>";
    }
}

if(isset($_POST['btnDelete'])){
    $deleteId = $_POST['recipe_id'];
    $query = "DELETE FROM recipe WHERE recipe_id = '$deleteId'";
    $deleteRecipe = mysqli_query($connection, $query);

    if($deleteRecipe){
        echo "<script>alert('Recipe deleted successfully!');window.location.href = 'Recipe_Admin.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['recipename']); ?></title>
    <link rel="stylesheet" href="Recipe_Details_Admin.css?v=<?php echo time();?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="mainbody">
    <button class="back" id="btnPrevious" onclick="goback()">⬅️ Previous</button>
    <form action="" method="post" enctype="multipart/form-data">
        <div class="contents">
            <div class="video">
                <iframe 
                    src="https://www.youtube.com/embed/<?php echo extractYouTubeID($recipe['video']); ?>" frameborder="0" allowfullscreen>
                </iframe>
                <div class="newURL">
                    <input type="text" name="link" placeholder="URL link" name="newVideo" value="<?php echo htmlspecialchars($recipe['video']); ?>">
                    <span class="instruction">*Please enter a new URL link to change the recipe video.</span>
                </div>
            </div>
            <div class="name">
                <input type="text" name="recipename" value="<?php echo htmlspecialchars($recipe['recipename']); ?>">
            </div>    
            <div class="difficulty">
                <label for="stars">Difficulty:</label> 
                <span class="stars"><?php echo($stars); ?></span>
            </div>
            <div class="description">
                <textarea class="short-des" name="description"><?php echo htmlspecialchars($recipe['description']); ?></textarea>
            </div>
            <div class="label">
                <label for="label">⏲️Time:</label>
                <input type="text" name="time" value="<?php echo htmlspecialchars($recipe['time']); ?>">
            </div>
            <div class="label">
                <label for="label">🥦Ingredients: </label>
                <input type="text" name="ingredient" value="<?php echo htmlspecialchars($recipe['ingredient']); ?>">
            </div>
            <div class="label">
                <label for="label">🔥Calories: </label>
                <input type="text" name="calories" value="<?php echo htmlspecialchars($recipe['calories']); ?>kcal">
            </div>
            <div class="Button">
                <button class="edit1" type="submit" name="btnEdit">Save Changes</button>
                <button class="edit1" type="submit" name="btnDelete">Delete</button>
                <button class="cancel" onclick="closePopup()">Cancel</button>
            </div>  
            </div>
        </form>
    </div>

    <script>
        function goback(){
            window.location.href = 'Recipe_Admin.php';
        }

        function closePopup() {
            window.location.href = "Recipe_Admin.php";
        }
    </script>
</body>
</html>

<?php
include '../general/footer.php';
?>