<?php
ini_set('memory_limit', '2G');
session_start();
include '../general/dbconn.php'; 
include '../general/member-nav.php';

$userID = $_SESSION['userid'];
$food_allergies = '';

function getCurrentWeekRange($date = null) {
    $date = $date ?: new DateTime();
    $year = (int) $date->format('o');
    $week = (int) $date->format('W');

    $startOfWeek = new DateTime();
    $startOfWeek->setISODate($year, $week, 1);
    $endOfWeek = clone $startOfWeek;
    $endOfWeek->modify('+6 days');

    return [
        'start' => $startOfWeek->format('Y-m-d'),
        'end' => $endOfWeek->format('Y-m-d'),
    ];
}

$currentWeek = getCurrentWeekRange();
$mealPlans = [];
$dailyTotals = [];

$query = "SELECT create_time, Food_allergies FROM user WHERE userID = '$userID'";
$result = mysqli_query($connection, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $registered_date = new DateTime($row['create_time']);
    $food_allergies = strtolower(trim($row['Food_allergies'])); 
}

$daysToGenerate = 30; 
$startDate = new DateTime(); 
$endDate = clone $startDate;
$endDate->modify("+$daysToGenerate days");

$query = "SELECT DISTINCT meal_date FROM mealplans WHERE user_id = '$userID' AND meal_date BETWEEN '{$registered_date->format('Y-m-d')}' AND '{$endDate->format('Y-m-d')}'";
$result = mysqli_query($connection, $query);
    
$existingMealPlans = [];
while ($row = mysqli_fetch_assoc($result)) {
    $existingMealPlans[$row['meal_date']] = true;
}

$safeRecipes = [];
$query = "SELECT * FROM recipe";
$result = mysqli_query($connection, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ingredients = strtolower($row['ingredient']);
        if (empty($food_allergies) || strpos($ingredients, $food_allergies) === false) {
            $safeRecipes[] = $row;
        }
    }
}

if (!empty($safeRecipes)) {
    shuffle($safeRecipes);
    $mealTypes = ['breakfast', 'lunch', 'dinner'];

    $currentDate = clone $registered_date; 

    while ($currentDate <= $endDate) {
        $meal_date = $currentDate->format('Y-m-d');

        if (!isset($existingMealPlans[$meal_date])) { 
            foreach ($mealTypes as $mealType) {
                if (empty($safeRecipes)) {
                    $result = mysqli_query($connection, "SELECT * FROM recipe");
                    $safeRecipes = mysqli_fetch_all($result, MYSQLI_ASSOC);
                    shuffle($safeRecipes);
                }
                
                $recipe = array_pop($safeRecipes);
                if ($recipe) {
                    $query = "INSERT INTO mealplans (user_id, meal_date, meal_type, recipe_id, servings) 
                            VALUES ('$userID', '$meal_date', '$mealType', '{$recipe['recipe_id']}', 1)";
                    mysqli_query($connection, $query);
                }
            }
        }
        $currentDate->modify('+1 day'); 
    }
}

$query = "
    SELECT mp.meal_date, mp.meal_type, mp.servings, 
           r.recipename, r.img, r.calories, r.carbs, r.fats, r.protein
    FROM mealplans mp
    JOIN recipe r ON mp.recipe_id = r.recipe_id
    WHERE mp.meal_date BETWEEN '{$registered_date->format('Y-m-d')}' AND '{$endDate->format('Y-m-d')}'
    AND mp.user_id = '$userID'
    AND (r.source_table = 'system' OR r.source_table = '$userID')
    ORDER BY mp.meal_date, mp.meal_type";
$result = mysqli_query($connection, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['img'])) {
            $imgData = base64_encode($row['img']);
            $row['img'] = 'data:image/jpeg;base64,' . $imgData;
        } else {
            $row['img'] = '';
        }
        $mealPlans[$row['meal_date']][$row['meal_type']][] = $row;
    }
}

$query = "
    SELECT mp.meal_date, 
           SUM(r.calories * mp.servings) AS total_calories,
           SUM(r.carbs * mp.servings) AS total_carbs,
           SUM(r.fats * mp.servings) AS total_fats,
           SUM(r.protein * mp.servings) AS total_protein
    FROM mealplans mp
    JOIN recipe r ON mp.recipe_id = r.recipe_id
    WHERE mp.meal_date BETWEEN '{$registered_date->format('Y-m-d')}' AND '{$endDate->format('Y-m-d')}'
    AND mp.user_id = '$userID'
    GROUP BY mp.meal_date
    ORDER BY mp.meal_date";
$result1 = mysqli_query($connection, $query);

$query = "
    SELECT mp.meal_date, 
           SUM(r.calories * mp.servings) AS total_calories
    FROM mealplans mp
    JOIN recipe r ON mp.recipe_id = r.recipe_id
    WHERE mp.user_id = '$userID'
    GROUP BY mp.meal_date
    ORDER BY mp.meal_date";
$result = mysqli_query($connection, $query);

$totalCaloriesSum = 0;
$totalDays = 0;

if ($result1) {
    while ($row = mysqli_fetch_assoc($result1)) {
        $total = $row['total_carbs'] + $row['total_fats'] + $row['total_protein'];
        $dailyTotals[$row['meal_date']] = [
            'total_calories' => $row['total_calories'],
            'carbs' => $total > 0 ? ($row['total_carbs'] / $total) * 100 : 0,
            'fats' => $total > 0 ? ($row['total_fats'] / $total) * 100 : 0,
            'protein' => $total > 0 ? ($row['total_protein'] / $total) * 100 : 0,
        ];
    }
}

$weeklyTotals = [];
while ($row = mysqli_fetch_assoc($result)) {
    $date = new DateTime($row['meal_date']);
    $year = $date->format('o');
    $week = $date->format('W');
    $weekKey = "{$year}-{$week}";

    if (!isset($weeklyTotals[$weekKey])) {
        $weeklyTotals[$weekKey] = [
            'total_calories' => 0,
            'total_days' => 0,
            'start_date' => (clone $date)->modify('-' . ($date->format('N') - 1) . ' days')->format('Y-m-d'),
            'end_date' => (clone $date)->modify('+' . (7 - $date->format('N')) . ' days')->format('Y-m-d')
        ];
    }

    $weeklyTotals[$weekKey]['total_calories'] += $row['total_calories'];
    $weeklyTotals[$weekKey]['total_days']++;
}

foreach ($weeklyTotals as $weekKey => $data) {
    $avgCalories = round($data['total_calories'] / $data['total_days'], 2);

    $checkQuery = "
        SELECT COUNT(*) as count FROM weekly_nutrition_summary 
        WHERE user_id = '$userID' 
        AND start_date = '{$data['start_date']}' 
        AND end_date = '{$data['end_date']}'";
    
    $checkResult = mysqli_query($connection, $checkQuery);
    $row = mysqli_fetch_assoc($checkResult);

    if ($row['count'] > 0) {
        $query = "
            UPDATE weekly_nutrition_summary 
            SET avg_calories = $avgCalories 
            WHERE user_id = '$userID' 
            AND start_date = '{$data['start_date']}' 
            AND end_date = '{$data['end_date']}'";
    } else {
        $query = "
            INSERT INTO weekly_nutrition_summary (user_id, start_date, end_date, avg_calories)
            VALUES ('$userID', '{$data['start_date']}', '{$data['end_date']}', $avgCalories)";
    }
    
    mysqli_query($connection, $query);
}

$weeklyCaloriesData = [];
$query = "
    SELECT start_date, end_date, avg_calories 
    FROM weekly_nutrition_summary
    WHERE user_id = '$userID'
    ORDER BY start_date DESC";
$result1 = mysqli_query($connection, $query);
while ($row = mysqli_fetch_assoc($result1)) {
    $weeklyCaloriesData[] = $row;
}

foreach ($dailyTotals as $date => $totals) {
    $query = "
        INSERT INTO daily_nutrition_totals (user_id, meal_date, total_calories, total_carbs, total_fats, total_protein)
        VALUES ('$userID', '$date', {$totals['total_calories']}, {$totals['carbs']}, {$totals['fats']}, {$totals['protein']})
        ON DUPLICATE KEY UPDATE 
            total_calories = VALUES(total_calories),
            total_carbs = VALUES(total_carbs),
            total_fats = VALUES(total_fats),
            total_protein = VALUES(total_protein)";
    mysqli_query($connection, $query);
}

$displayedDate = isset($_GET['date']) && !empty($_GET['date']) ? new DateTime($_GET['date']) : new DateTime();
$weekRange = getCurrentWeekRange($displayedDate);

$query = "SELECT target_calories FROM weekly_goals WHERE user_id = '$userID' AND '{$weekRange['start']}' BETWEEN start_date AND end_date";

$result = mysqli_query($connection, $query);
$currentGoal = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['target_calories'] : 2000;

if(isset($_POST['btnGoal'])){
    $target_calories = $_POST['target_calories'];
    
    $updateQuery = "UPDATE weekly_goals SET target_calories = '$target_calories' WHERE user_id = '$userID' AND '{$weekRange['start']}' BETWEEN start_date AND end_date";

    mysqli_query($connection, $updateQuery);

    if (mysqli_affected_rows($connection) == 0) {
        $insertQuery = "INSERT INTO weekly_goals (user_id, start_date, end_date, target_calories) 
                        VALUES ('$userID', '{$weekRange['start']}', '{$weekRange['end']}', '$target_calories')";
        mysqli_query($connection, $insertQuery);
    }

    echo "<script>alert('Calorie goal updated successfully!');window.location.href = window.location.pathname + '?date={$displayedDate->format('Y-m-d')}';</script>";
}

$recipes = [];
$query = "SELECT recipe_id, recipename, time, ingredient, calories, carbs, protein, fats FROM recipe WHERE source_table = 'system' OR source_table = '$userID' ";
$result = mysqli_query($connection, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recipes[] = $row;
    }
}

if (isset($_POST['btnAdd'])) {
    $recipename = ($_POST['newRecipe']);
    $purpose = $_POST['newPurpose'];
    $difficulty = $_POST['newDifficulty'];
    $description = $_POST['newDescription'];
    $time = $_POST['newTime'];
    $ingredient = $_POST['newIngredient'];
    $calories = $_POST['newCalories'];
    $carbs = $_POST['newCarbs'];
    $protein = $_POST['newProtein'];
    $fats = $_POST['newFats'];
    $video = $_POST['newVideo'];
    
    $mealDate = isset($_POST['meal_date']) ? trim($_POST['meal_date']) : null;
    $mealType = isset($_POST['meal_type']) ? trim($_POST['meal_type']) : null;


    if (!is_numeric($calories) || $calories <= 0 || !is_numeric($carbs) || $carbs <= 0 || !is_numeric($fats) || $fats <= 0 || !is_numeric($protein) || $protein <= 0) {
        echo "<script>alert('Invalid input! Nutrition values must be positive numbers.'); window.history.back();</script>";
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
            } else {
                echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
                exit();
            }

            $addQuery = "INSERT INTO recipe (source_table, img, purpose, recipename, difficulty, description, time, ingredient, calories, carbs, protein, fats, video) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $addQuery);
            mysqli_stmt_bind_param($stmt, "ibssisssdddds", $userID, $pic, $purpose, $recipename, $difficulty, $description, $time, $ingredient, $calories, $carbs, $protein, $fats, $video);
            
            mysqli_stmt_send_long_data($stmt, 1, $pic); 

            if (mysqli_stmt_execute($stmt)) {
                $newRecipeId = mysqli_insert_id($connection);
                if ($mealDate && $mealType) {
                    $updatedQuery = "UPDATE mealplans SET recipe_id = ? WHERE user_id = ? AND meal_type = ? AND meal_date = ?";
                    $stmt = mysqli_prepare($connection, $updatedQuery);
                    mysqli_stmt_bind_param($stmt, "iiss", $newRecipeId, $userID, $mealType, $mealDate);
                    mysqli_stmt_execute($stmt);
                }
                echo "<script>alert('Recipe added successfully!'); window.location.href = window.location.href.split('?')[0];</script>";
            }else {
                echo "<script>alert('Error inserting recipe!');</script>";
            }
        } else {
            echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.'); window.history.back();</script>";
            exit();
        }
    }
}

if (isset($_POST['btnConfirm'])) {
    $selectedRecipeID = $_POST['selectedRecipe'];
    $mealDate = $_POST['meal_date'];
    $mealType = $_POST['meal_type'];

    if (!empty($selectedRecipeID) && !empty($mealDate) && !empty($mealType)) {
        $updateQuery = "UPDATE mealplans SET recipe_id = ? WHERE user_id = ? AND meal_type = ? AND meal_date = ?";
        $stmt = mysqli_prepare($connection, $updateQuery);
        mysqli_stmt_bind_param($stmt, "isss", $selectedRecipeID, $userID, $mealType, $mealDate);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Recipe updated successfully!'); window.location.href = window.location.pathname;</script>";
        } else {
            echo "<script>alert('Error updating recipe: " . mysqli_error($connection) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Missing required data');</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Meal Planner</title>
    <link rel="stylesheet" href="mealplan.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Matemasie&family=New+Amsterdam&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Playwrite+VN:wght@100..400&display=swap" rel="stylesheet">
    <script>
        const mealPlans = <?php echo json_encode($mealPlans); ?>;
        const dailyTotals = <?php echo json_encode($dailyTotals); ?>;
    </script>
    <script>
        const recipes = <?php echo json_encode($recipes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="mealplan.js" defer></script>
</head>
<body>
    <div class="content">
        <header>
            <h1>Weekly Meal Planner</h1>
            <p id="currentWeekRange"></p><br>
            <div id="avgCaloriesDisplay" class="weekly-summary">Loading...</div>
        </header>
        <main>
            <div class="head">
                <div class="circle">
                    <div class="circle-dropdown">
                        <button class="circle-btn" onclick="toggleDropdown()">☰</button>
                        <div class="dropdown-content" id="CdropdownMenu">
                            <a href="Recipe_User.php">Recipe Page</a>
                            <a href="Food_Knowledge_User.php">Food Knowledge</a>
                        </div>
                    </div>
                </div>

                <div class="buttons">
                    <button id="prevButton">&lt; Previous Week</button>
                    <button id="goToToday">Today</button>
                    <input type="date" id="datePicker" class="date-picker">
                    <button id="nextButton">Next Week &gt;</button>
                </div>
                
                <div class="calorie-goal-container">
                    <h2>Set Your Weekly Calorie Goal</h2>
                    <form action="" method="POST">
                        <input type="hidden" name="userID" value="<?php echo ($userID); ?>">
                        <input type="hidden" name="displayed_date" id="displayedDate" value="<?php echo $displayedDate->format('Y-m-d'); ?>">
                        <div class="goal-input">
                            <input type="number" name="target_calories" id="target_calories" value="<?php echo htmlspecialchars($currentGoal); ?>" placeholder="Enter calories" required min="1" oninput="validateCalories(this)">
                        </div>
                        <button type="submit" name="btnGoal">Save Goal</button>
                    </form>
                </div>
            </div>

            <div id="mealPlansContainer" class="week-container"></div>

            <div class="overlay" id="overlay"></div>
                <div class="confirm" id="confirm" style="display: none;">
                    <div class="message">
                        <p>You want to select the existing recipe or create your own recipe?</p>
                        <div class="Button">
                            <button class="edit" onclick="openPopup2('editPopup1')">Edit</button>
                            <button class="create" onclick="openPopup2('create')">Create</button>
                            <button class="cancel" onclick="closePopup('confirm')">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overlay" id="overlay"></div>
            <div class="edit-popup" id="editPopup1" style="display: none;">
                <div class="message">
                    <h2>Recipe Changes</h2>
                    <form method="post">
                    <input type="hidden" name="meal_date" id="editMealDate" value="">
                    <input type="hidden" name="meal_type" id="editMealType" value="">
                    <input type="hidden" name="btnConfirm" value="1">

                    <label for="recipeSelect">Select a Recipe:</label>
                    <select id="recipeSelect" name="selectedRecipe" onchange="updateRecipeDetails()">
                        <option value="">-- Select a Recipe --</option>
                        <?php foreach ($recipes as $recipe): ?>
                            <option value="<?php echo htmlspecialchars($recipe['recipe_id']); ?>">
                                <?php echo htmlspecialchars($recipe['recipename']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="recipeID" id="recipeID">

                    <div class="recipeDetails" id="recipeDetails">
                        <img id="recipeImg" src="" alt="Recipe Image" style="width: 100px; height: 100px;">
                        <p><strong>Name:</strong> <span id="recipeName"></span></p>
                        <p><strong>Time:</strong> <span id="recipeTime"></span> mins</p>
                        <p><strong>Ingredients:</strong> <span id="recipeIngredients"></span></p>
                        <p><strong>Calories:</strong> <span id="recipeCalories"></span> kcal</p>
                        <p><strong>Carbs:</strong> <span id="recipeCarbs"></span> g</p>
                        <p><strong>Fats:</strong> <span id="recipeFats"></span> g</p>
                        <p><strong>Protein:</strong> <span id="recipeProtein"></span> g</p>
                        <p><strong>Servings:</strong> <span id="recipeServings"></span></p>
                    </div>
                    
                    <div class="editBtn">
                        <button type="submit" class="confirmBtn" name="btnConfirm">Confirm</button>
                        <button type="button" class="cancel" onclick="closePopup('editPopup1')">Cancel</button>
                    </div>
                    </form>
                </div>
            </div>

            <div class="overlay" id="overlay"></div>
            <div class='AddRecipe' id='create' style='display: none;'>
                <h2 align="left">Add Recipe ➕</h2>
                <form method="post" enctype = "multipart/form-data">
                    <input type="hidden" id="addMealDate" name="meal_date" value="">
                    <input type="hidden" id="addMealType" name="meal_type" value="">
                    <input type="hidden" name="btnAdd" value="1">

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
                    <button type="button" onclick="closePopup('create')" class="cancel">Cancel</button>
                </p>
                </form>
            </div>
        </main>
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Weekly Meal Planner</p>
        </footer>
    </div>

    <script>
        function toggleDropdown() {
            var dropdown = document.getElementById("CdropdownMenu");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }

        document.addEventListener("click", function (event) {
            var dropdown = document.getElementById("CdropdownMenu");
            var button = document.querySelector(".circle-btn");

            if (!button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = "none";
            }
        });

        function openPopup(type){
            document.getElementById("confirm").style.display = "flex"; 
            document.getElementById("overlay").style.display = "block"; 
        }

        function closePopup(popupID) {
            document.getElementById(popupID).style.display = "none"; 
            document.getElementById("overlay").style.display = "none"; 

            if (popupID === "editPopup1") {
                document.getElementById("recipeSelect").value = "";
                document.getElementById("recipeImg").src = "";
                document.getElementById("recipeName").textContent = "";
                document.getElementById("recipeTime").textContent = "";
                document.getElementById("recipeIngredients").textContent = "";
                document.getElementById("recipeCalories").textContent = "";
                document.getElementById("recipeCarbs").textContent = "";
                document.getElementById("recipeFats").textContent = "";
                document.getElementById("recipeProtein").textContent = "";
                document.getElementById("recipeServings").textContent = "";
            }
        }

        function openPopup2(popupID) {
            closePopup('confirm');
            
            document.getElementById("overlay").style.display = "block";
            document.getElementById(popupID).style.display = "flex";

            let recipeDropdown = document.getElementById("recipeSelect");
                recipeDropdown.innerHTML = '<option value="">-- Select a Recipe --</option>'; 
                recipes.forEach(recipe => {
                    let option = document.createElement("option");
                    option.value = recipe.recipe_id;
                    option.textContent = recipe.recipename;
                    recipeDropdown.appendChild(option);
                });
        }

        document.addEventListener("click", function(event) {
            if (event.target.classList.contains("edit0")) {
                const mealCard = event.target.closest(".meal-card");
                const mealDate = mealCard.dataset.date;
                const mealType = mealCard.dataset.mealType;

                document.getElementById('editMealDate').value = mealDate;
                document.getElementById('editMealType').value = mealType;
                document.getElementById('addMealDate').value = mealDate;
                document.getElementById('addMealType').value = mealType;
            }
        });

        function updateRecipeDetails() {
            const recipeSelect = document.getElementById("recipeSelect");
            const selectedId = recipeSelect.value;
            const selectedRecipe = recipes.find(recipe => recipe.recipe_id == selectedId);

            if (selectedRecipe) {
                document.getElementById("recipeID").textContent = selectedId;
                document.getElementById("recipeImg").src = "showimage.php?recipe_id=" + encodeURIComponent (selectedId);
                document.getElementById("recipeName").textContent = selectedRecipe.recipename;
                document.getElementById("recipeTime").textContent = selectedRecipe.time;
                document.getElementById("recipeIngredients").textContent = selectedRecipe.ingredient;
                document.getElementById("recipeCalories").textContent = selectedRecipe.calories;
                document.getElementById("recipeCarbs").textContent = selectedRecipe.carbs;
                document.getElementById("recipeFats").textContent = selectedRecipe.fats;
                document.getElementById("recipeProtein").textContent = selectedRecipe.protein;
                document.getElementById("recipeServings").textContent = "1"; 
            }
        }
        
    </script>
</body>
</html>

<?php
include '../general/footer.php';
?>