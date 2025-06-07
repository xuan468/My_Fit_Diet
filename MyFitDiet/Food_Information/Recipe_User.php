<?php
session_start();
include '../general/dbconn.php'; 
include '../general/member-nav.php';

$query = "SELECT purpose FROM recipe WHERE source_table = 'system' GROUP BY purpose";
$result = mysqli_query($connection, $query);
$filter = [];
while($list = mysqli_fetch_assoc($result)){
    $filter[] = $list;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="Recipe_User.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Matemasie&family=New+Amsterdam&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Playwrite+VN:wght@100..400&display=swap">
</head>
<body>
    <div class="head">
        <h2 align="center" class="title">Healthy Cooking Recipes and the right Nutrition</h2>
        <button class="show" id="btnShow">Load More Recipes</button>
            <div class='search-container'>
                <button class="back" id="btnPrevious" style="display: none;" onclick="goback()">⬅️ Previous</button>
                <div class="filter-list" id="filter-list">
                    <div class="filter-dropdown">
                        <button class="filter" id="filter" onclick="toggleDropdown()">Filter ▼</button>
                        <div class="filter-list-content" id="filter-content">
                            <ul>
                                <li><a href="Recipe_User.php?showall=true" onclick="saveLoadState()">Show All</a></li>
                            <?php foreach ($filter as $filter_content): ?>
                                <li><a href="Recipe_User.php?purpose=<?php echo urlencode($filter_content['purpose']); ?>" onclick="saveLoadState()"><?php echo htmlspecialchars($filter_content['purpose']); ?></a></li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <form action="Recipe_User.php" method="get" class="searchbar">
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
                        }elseif(!empty($_GET['purpose'])){
                            $selectedPurpose = mysqli_real_escape_string($connection, $_GET['purpose']);
                            $query = "SELECT recipe_id, recipename, time, ingredient, calories FROM recipe WHERE purpose = '$selectedPurpose' AND source_table = 'system' ORDER BY recipe_id";
                        }elseif(!empty($_GET['showall'])) { 
                            $query = "SELECT recipe_id, recipename, time, ingredient, calories FROM recipe WHERE source_table = 'system' ORDER BY recipe_id"; 
                        }else{
                            $query = "SELECT recipe_id, recipename, time, ingredient, calories FROM recipe WHERE source_table = 'system'";
                        }
                            
                        if(!empty($query)){
                            $result = mysqli_query($connection, $query);
                            $num_rows = mysqli_num_rows($result);
                            $centerClass = 'style="justify-content:center; display:flex; flex-wrap:wrap;"'; 
                        }
                        ?>

                        <div class="recommendation" <?php echo $centerClass; ?>>

                        <?php
                            if($result && mysqli_num_rows($result) > 0){
                                while ($recipedata = mysqli_fetch_assoc($result)) {?>
                                    <div class="recipe-wrapper">
                                        <a href="Recipe_Details_User.php?recipe_id=<?php echo $recipedata['recipe_id']; ?>">
                                            <div class="content">
                                                <div class="text-container">
                                                    <p class="label1" name="recipename"><?php echo htmlspecialchars($recipedata['recipename']);?></p>
                                                    <p class="label" name="time">⏲️<?php echo htmlspecialchars($recipedata['time']);?></p>
                                                    <p class="label2" name="ingredient">🥦<?php echo htmlspecialchars($recipedata['ingredient']);?></p>
                                                    <p class="label" name="calories">🔥<?php echo htmlspecialchars($recipedata['calories']);?>kcal</p>
                                                </div>
                                                <img class="img" src="showimage.php?recipe_id=<?php echo $recipedata['recipe_id']; ?>&type=image" alt="Food Image"><br>
                                            </div>
                                        </a>
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

    <script>
        function toggleDropdown() {
            var dropdown = document.getElementById("filter-content");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }

        document.addEventListener("click", function (event) {
            var dropdown = document.getElementById("filter-content");
            var button = document.querySelector(".filter");

            if (!button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = "none";
            }
        });     

        document.addEventListener("DOMContentLoaded", function() {
            let recipes = document.querySelectorAll('.recipe-wrapper');
            let Showbtn = document.getElementById("btnShow");
            let isLoadMoreClicked = sessionStorage.getItem("loadMoreClicked");

            function showAllRecipes() {
                recipes.forEach(recipe => {
                    recipe.style.display = "flex";
                });
                Showbtn.style.display = "none";
            }

            if (isLoadMoreClicked === "true") {
                showAllRecipes();
            } else {
                recipes.forEach((recipe, index) => {
                    if (index < 4) {
                        recipe.style.display = "flex";
                    } else {
                        recipe.style.display = "none";
                    }
                });
            }

            Showbtn.addEventListener("click", function() {
                showAllRecipes();
                sessionStorage.setItem("loadMoreClicked", "true");
            });

            let urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('txtcontent')) {
                document.getElementById("btnPrevious").style.display = "inline-block";

                recipes.forEach(recipe => {
                    recipe.style.display = "flex";
                });

                Showbtn.style.display = "none";
            }
        });

        function saveLoadState() {
            sessionStorage.setItem("loadMoreClicked", "true");
        }

        function goback(){
            window.location.href = 'Recipe_User.php';
        }
    </script>
</body>
</html>

<?php
include '../general/footer.php';
?>