<?php
session_start();
include '../general/dbconn.php'; 
include '../general/member-nav.php';

$selectedCategory = isset($_GET['category']) ? $_GET['category'] : "";

$foodDisplayQuery = "SELECT * FROM fooddisplay_admin";
$resultss = mysqli_query($connection, $foodDisplayQuery);
$foodImgDisplay = [];
while ($row = mysqli_fetch_assoc($resultss)) {
    $foodImgDisplay[] = $row;
}

$categoryDisplayQuery = "SELECT * FROM categorydisplay_admin";
$categoryDisResult = mysqli_query($connection, $categoryDisplayQuery);
$categoryImgDisplay = [];
while ($row = mysqli_fetch_assoc($categoryDisResult)) {
    $categoryImgDisplay[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="Food_Knowledge_User.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="container">
    <table>
            <tr>
                <td>
                    <div class='headpart'>
                        <div class='gif-container'>
                        <?php foreach ($foodImgDisplay as $food): ?>
                            <div class="gif-wrapper">
                                <a href="dietplans.php" class="gif"><img src="showimage.php?gifid=<?php echo $food['gifid']; ?>" alt="pic"></a>
                                <input class="word" type="text" name="txtlabel" value="<?php echo htmlspecialchars($food['foodTitle']);?>" readonly>
                                <input type="hidden" name="foodid" value="<?php echo $food['gifid']; ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td> 
                    <div class='bottompart'>
                        <div class='category'>
                        <?php foreach ($categoryImgDisplay as $categoryImg): ?>
                            <div class='category-wrapper'>
                                <input type="hidden" name="categoryid" value="<?php echo $categoryImg['categoryid']; ?>">
                                <a href="#" onclick="startCategory('<?php echo $categoryImg['category']; ?>')"
                                class="<?php echo ($selectedCategory == $categoryImg['category']) ? 'selected-category' : ''; ?>">
                                    <img src="showimage.php?categoryid=<?php echo $categoryImg['categoryid']; ?>" alt="Category Picture">
                                </a>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <tr></tr>
        </table>

        <div class="function">
            <a href="#" class='all' onclick='refresh()'>All Foods</a>

            <form action="Food_Knowledge_User.php" method="get">
                <div class='searchbar'>
                    <input class="searchfield" type="text" name="txtfoodcontent" placeholder="Food Contents" value="<?php echo isset($_GET['txtfoodcontent']) ? htmlspecialchars($_GET['txtfoodcontent']) : ''; ?>" required>
                    <button type="submit" class="search" name="search">🔍</button>
                </div>
            </form>

        </div>

        <div class='list'>
            <div class='row'>
                <?php 
                $query = ""; 

                if (!empty($_GET['txtfoodcontent'])) {  
                    $foodcontent = mysqli_real_escape_string($connection, trim($_GET['txtfoodcontent']));
                    $query = "SELECT * FROM category WHERE foodname LIKE '%$foodcontent%' OR description LIKE '%$foodcontent%' OR category LIKE '%$foodcontent%' GROUP BY foodname ORDER BY MIN(food_id)";
                } elseif (!empty($_GET['category'])) {  
                    $category = mysqli_real_escape_string($connection, $_GET['category']);
                    $query = "SELECT * FROM category WHERE category = '$category'";
                } else{
                    $query = "SELECT * FROM category GROUP BY foodname";
                }

                if(!empty($query)){
                    $result = mysqli_query($connection, $query);
                    $category = isset($_GET['category']) ? mysqli_real_escape_string($connection, $_GET['category']) : '';

                    if($result && mysqli_num_rows($result) > 0){
                        while ($fooddata = mysqli_fetch_assoc($result)) {?>
                            <div class='content'>
                                <img src="showimage.php?foodname=<?php echo urlencode($fooddata['foodname']); ?>" class="image"> <br>
                                <span class="text1"><?php echo htmlspecialchars($fooddata['foodname']);?></span>
                                <p class="text2"><?php echo htmlspecialchars($fooddata['description']);?>kcal</p>
                            </div>
                        <?php }
                    }else{
                        echo "<h2>No matching records found. Please enter a new food name.</h2>";
                    }
                }
                ?>
            </div>
        </div>
</div>

    <script>
        function startCategory(category){
            localStorage.setItem('selectedCategory', category);
            window.location.href = "Food_Knowledge_User.php?category=" + encodeURIComponent(category);
        }

        document.addEventListener("DOMContentLoaded", function() {
            let selectedCategory = localStorage.getItem('selectedCategory');
            if (selectedCategory) {
                let activeElement = document.querySelector("." + selectedCategory);
                if (activeElement) {
                    activeElement.classList.add("selected-category");
                }
            }
        });

        function refresh(){
            localStorage.removeItem('selectedCategory');  
            window.location.href = "Food_Knowledge_User.php";
        }
    </script>
</body>
</html>
<?php
include '../general/footer.php';
?>