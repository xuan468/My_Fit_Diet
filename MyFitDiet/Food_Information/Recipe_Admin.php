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

$query = "SELECT * FROM recipe WHERE source_table = 'system'";
$recipe = mysqli_query($connection, $query);
$allrecipe = [];
while($row = mysqli_fetch_assoc($recipe)){
    $allrecipe[] = $row;
}

if(isset($_POST['btnEdit1'])){
    $recipeid = $_POST['recipe_id'];
    $recipename = $_POST['recipe_name'];
    $time = $_POST['time'];
    $ingredient = $_POST['ingredient'];
    $calories = $_POST['calories'];

    if (!isset($recipeid) || empty($recipeid)) {
        echo "<script>alert('Recipe ID is missing!');</script>";
        exit();
    }
    
    $updateQuery = "";

    if(isset($_FILES['img']) && $_FILES['img']['error'] == 0){
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileInfo = pathinfo($_FILES['img']['name']);
        $fileExt = strtolower($fileInfo['extension']);

        if(in_array($fileExt, $allowed)){
            $img = file_get_contents($_FILES['img']['tmp_name']);
            $escapedImage = mysqli_real_escape_string($connection, $img);

            $updateQuery = "UPDATE recipe SET recipename = '$recipename', time = '$time', ingredient = '$ingredient', img = '$escapedImage', calories = '$calories' WHERE recipe_id = '$recipeid'";
        }else{
            echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
            exit();
        }
    }else{
        $updateQuery = "UPDATE recipe SET recipename = '$recipename', time = '$time', ingredient = '$ingredient', calories = '$calories' WHERE recipe_id = '$recipeid'";
    }

    if (mysqli_query($connection, $updateQuery)) {
        echo "<script>alert('Information udated successfully!');window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
    } else {
        echo "<p style='color: white; margin-left: 20px;'>Error updating database: </p>" . mysqli_error($connection);
    }
}

if(isset($_POST['btnAdd'])){
    $recipename = trim($_POST['newRecipe']);
    $purpose = $_POST['newPurpose'];
    $difficulty = $_POST['newDifficulty'];
    $description = $_POST['newDescription'];
    $time = $_POST['newTime'];
    $ingredient = $_POST['newIngredient'];
    $calory = $_POST['newCalories'];
    $carbs = $_POST['newCarbs'];
    $protein = $_POST['newProtein'];
    $fats = $_POST['newFats'];
    $video = $_POST['newVideo'];

    if (!is_numeric($calory) && !is_numeric($carbs) && !is_numeric($fats) && !is_numeric($protein)) {
        echo "<script>alert('Invalid input! Please enter a positive number.'); window.history.back();</script>";
        exit(); 
    }
    
    if(!empty($recipename)){
        $query = "SELECT * FROM recipe WHERE recipename = '$recipename'";
        $check = mysqli_query($connection, $query);

        if(mysqli_num_rows($check) == 1){
            echo "<script>alert('Recipe already created. Please enter a new recipe.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
            exit();
        }

        $querySimilar = "SELECT recipename FROM recipe WHERE recipename LIKE '%$recipename%'";
        $resultSimilar = mysqli_query($connection, $querySimilar);

        while ($row = mysqli_fetch_assoc($resultSimilar)) {
            $existingRecipe = $row['recipename'];

            $levenshteinDistance = levenshtein(strtolower($recipename), strtolower($existingRecipe));
            similar_text(strtolower($recipename), strtolower($existingRecipe), $similarityPercentage);

            if ($levenshteinDistance <= 3 || $similarityPercentage >= 80) { 
                echo "<script>alert('Recipe already existing ($existingRecipe). Please create a new recipe.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
                exit();
            }
        }
        if(isset($_FILES['pic']) && $_FILES['pic']['error'] == 0){
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $fileInfo = pathinfo($_FILES['pic']['name']);
            $fileExt = strtolower($fileInfo['extension']);
    
            if(in_array($fileExt, $allowed)){
                $pic = file_get_contents($_FILES['pic']['tmp_name']);
                $escapedImage = mysqli_real_escape_string($connection, $pic);
                $addQuery = "INSERT INTO recipe (source_table, img, purpose, recipename, difficulty, description, time, ingredient, calories, carbs, protein, fats, video) VALUES ('system', '$escapedImage', '$purpose', '$recipename', '$difficulty', '$description', '$time', '$ingredient', '$calories', '$carbs', '$protein', '$fats', '$video')";
                
                if (mysqli_query($connection, $addQuery)) {
                    echo "<script>alert('New recipe added successfully!');window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
                } else {
                    echo "<p style='color: white; margin-left: 20px;'>Error updating database: </p>" . mysqli_error($connection);
                }
            }else{
                echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
                exit();
            }
        }else{
            echo "<script>alert('error adding new recipe.');</script>";
        }
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
    <title>Document</title>
    <link rel="stylesheet" href="Recipe_Admin.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Matemasie&family=New+Amsterdam&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Playwrite+VN:wght@100..400&display=swap" rel="stylesheet">
</head>
<body>
    <div class="head">
        <h2 align="center" class="title">Healthy Cooking Recipes and the right Nutrition</h2>
            <div class='search-container'>
            <button class="back" id="btnPrevious" style="display: none;" onclick="goback()">⬅️ Previous</button>
                <form action="Recipe_Admin.php" method="get" class="searchbar">
                    <input class="searchfield" type="text" name="txtcontent" placeholder="Recipe Contents" value="<?php echo isset($_GET['txtcontent']) ? htmlspecialchars($_GET['txtcontent']) : ''; ?>" required>
                    <button type="submit" class="search" name="search">🔍</button>
                </form>
            </div>
            <table align="center">
                <tr>
                    <td style="text-align: center;">
                        <?php
                        $query = "";

                        if (!empty($_GET['txtcontent'])) {  
                            $content = mysqli_real_escape_string($connection, trim($_GET['txtcontent']));
                            $query = "SELECT recipe_id, recipename, time, ingredient, calories FROM recipe WHERE recipename LIKE '%$content%' OR time LIKE '%$content%' OR ingredient LIKE '%$content%' OR calories LIKE '%$content%' AND source_table = 'system' ORDER BY recipe_id";
                        }else{
                            $query = "SELECT recipe_id, recipename, time, ingredient, calories FROM recipe WHERE source_table = 'system'";
                        }
                            
                        if(!empty($query)){
                            $result = mysqli_query($connection, $query);
                        }
                        ?>

                        <div class="recommendation">
                        <div class="add" id="addRecipe">
                            <div class="add-icon">+</div>
                            <p>Add New Recipe</p>
                        </div>
                        <?php
                            if($result && mysqli_num_rows($result) > 0){
                                while ($recipedata = mysqli_fetch_assoc($result)) {?>
                                    <div class="recipe-wrapper">
                                        <div class="content">
                                            <a href="Recipe_Details_Admin.php?recipe_id=<?php echo $recipedata['recipe_id']; ?>">
                                                <div class="text-container">
                                                    <input type="hidden" name="recipe_id" value="<?php echo ($recipedata['recipe_id']); ?>">
                                                    <p class="label1" name="recipename"><?php echo htmlspecialchars($recipedata['recipename']);?></p>
                                                    <p class="label" name="time">⏲️<?php echo htmlspecialchars($recipedata['time']);?></p>
                                                    <p class="label2" name="ingredient">🥦<?php echo htmlspecialchars($recipedata['ingredient']);?></p>
                                                    <p class="label" name="calories">🔥<?php echo htmlspecialchars($recipedata['calories']);?>kcal</p>
                                                </div>
                                                <img class="img" src="showimage.php?recipe_id=<?php echo $recipedata['recipe_id']; ?>" alt="Food Image"><br>
                                            </a>
                                            <button class="edit1" name="editCall" onclick="editPopup('editPopup1', '<?php echo($recipedata['recipe_id']); ?>', '<?php echo htmlspecialchars($recipedata['recipename']); ?>', '<?php echo htmlspecialchars($recipedata['time']); ?>', '<?php echo htmlspecialchars($recipedata['ingredient']); ?>', '<?php echo htmlspecialchars($recipedata['calories']); ?>')" ></button>
                                        </div>
                                    </div>
                                    <?php }
                                }?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p class="title2">
                            Easy-To-Follow <br>
                            Tailored-To-Your-Goals
                        </p>
                    </td>
                </tr>
            </table>
    </div>

    <div class="overlay" id="overlay"></div>
    <div class='AddRecipe' id='addPopup' style='display: none;'>
        <h2 align="left">Add Recipe ➕</h2>
        <form action="" method="post" enctype = "multipart/form-data">
            <table class="AddTable">
                <tr>
                    <td>
                        <label>Image:</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <img class="addimg" src="../images/add.png" alt="Category Image">
                        <input class="media" type="file" name="pic">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Purpose:</label>
                        <input class="label" type="text" name="newPurpose" required>
                    </td>
                    <td>
                        <label>Time:</label>
                        <input class="label" type="text" name="newTime" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Recipe Name:</label>
                        <input class="label" type="text" name="newRecipe" required>
                    </td>
                    <td>
                        <label>Carbohydrate:</label>
                        <input class="label" type="text" name="newCarbs" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Difficulty:</label> <br>
                        <select name="newDifficulty" class="label" required>
                                <option value="None">None</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                        </select>
                    </td>
                    <td>
                        <label>Calories:</label>
                        <input class="label" type="text" name="newCalories" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Ingredient:</label>
                        <input class="label" type="text" name="newIngredient" required>
                    </td>
                    <td>
                        <label>Protein:</label>
                        <input class="label" type="text" name="newProtein" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Video:</label>
                        <input class="label" type="text" name="newVideo" placeholder="URL link" required>
                    </td>
                    <td>
                        <label>Fats:</label>
                        <input class="label" type="text" name="newFats"  required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Description:</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <textarea class="label" name="newDescription" required></textarea>
                    </td>
                </tr>
            </table>
        <p class="editBtn">
            <button type="submit" name="btnAdd" class="addBtn">Add</button>
            <button type="button" onclick="closePopup('addPopup')" class="cancel">Cancel</button>
        </p>
        </form>
    </div>

    <div class="overlay" id="overlay"></div>
    <div class='editContent' id='editPopup1' style='display: none;'>
        <h2 align="left">Edit 🖋</h2>
        <form action="" method="post" enctype = "multipart/form-data">
            <table class="editTable">
                <tr>
                    <input type="hidden" name="recipe_id" id="recipe_id">
                    <td><label>Content: </label></td>
                    <img id="img" src="" alt="Recipe Image" width="200" height="150">
                    <td><input type="file" name="img"></td>
                </tr>
                <tr>
                    <td><label>Name: </label></td>
                    <td><input class="label" type="text" name="recipe_name" id="recipe_name" required></td>
                </tr>
                <tr>
                    <td><label>Times: </label></td>
                    <td><input class="label" type="text" name="time" id="time" required></td>
                </tr>
                <tr>
                    <td><label>Ingredients:</label></td>
                    <td><textarea class="label" id="ingredient" name="ingredient" required></textarea></td>
                </tr>
                <tr>
                    <td><label>Calories:</label></td>
                    <td><input class="label" type="text" id="calories" name="calories" required></td>
                </tr>
            </table>
            <p class="editBtn">
                <button type="submit" name="btnEdit1" class="btnEdit">Save Changes</button>
                <button class="deleteBtn" type="submit" name="btnDelete">Delete</button>
                <button type="button" onclick="closePopup('editPopup1')" class="cancel">Cancel</button>
            </p>
        </form>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('txtcontent')) {
                document.getElementById("btnPrevious").style.display = "inline-block";
                document.getElementById("addRecipe").style.display = "none";
            }
        });

        function goback(){
            window.location.href = 'Recipe_Admin.php';
        }

        function editPopup(popupId, recipeId, recipename, time, ingredient, calories){
            document.getElementById("recipe_id").value = recipeId;
            document.getElementById("recipe_name").value = recipename;
            document.getElementById("time").value = time;
            document.getElementById("ingredient").value = ingredient;
            document.getElementById("calories").value = calories;
            document.getElementById("img").src = "showimage.php?recipe_id=" + encodeURIComponent (recipeId);
            document.getElementById(popupId).style.display = "flex";
            document.getElementById("overlay").style.display = "block";
        }

        function closePopup(popupId) {
            document.getElementById(popupId).style.display = "none";
            document.getElementById("overlay").style.display = "none";
        }

        document.addEventListener("DOMContentLoaded", function(){
            const addRecipeBtn = document.getElementById("addRecipe");
            const addPopup = document.getElementById("addPopup");

            addRecipeBtn.addEventListener("click", function(){
                addPopup.style.display = "flex";
                document.getElementById("overlay").style.display = "block";
            });
        });
    </script>
</body>
</html>

<?php
include '../general/footer.php';
?>