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

if(isset($_POST['btnEdit'])){
    $label = $_POST['label'];
    $foodid = $_POST['foodid'];
    if(isset($_FILES['pic']) && $_FILES['pic']['error'] == 0){
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileInfo = pathinfo($_FILES['pic']['name']);
        $fileExt = strtolower($fileInfo['extension']);

        if(in_array($fileExt, $allowed)){
            $pic = file_get_contents($_FILES['pic']['tmp_name']);
            $escapedImage = mysqli_real_escape_string($connection, $pic);
            
            $updateQuery = "UPDATE fooddisplay_admin SET foodTitle = '$label', pic = '$escapedImage' WHERE gifid = '$foodid'";
        }else{
            echo "<script>alert('Invalid file typr. Only JPG, JPEG, PNG and GIF files are allowed.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
            exit();
        }
    }else{
        $updateQuery = "UPDATE fooddisplay_admin SET foodTitle = '$label' WHERE gifid = '$foodid'";
    }
    
    if (mysqli_query($connection, $updateQuery)) {
        // Refresh the user data after saving
        echo "<script>alert('Information udated successfully!');window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
    } else {
        echo "<p style='color: white; margin-left: 20px;>Error updating database: </p>" . mysqli_error($connection);
    }
}    
    
if(isset($_POST['btnEdit2'])){
    $id = $_POST['categoryid'];
    $newCate = $_POST['categoryname'];

    if(isset($_FILES['pic']) && $_FILES['pic']['error'] == 0){
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileInfo = pathinfo($_FILES['pic']['name']);
        $fileExt = strtolower($fileInfo['extension']);

        if(in_array($fileExt, $allowed)){
            $pic = file_get_contents($_FILES['pic']['tmp_name']);
            $escapedImage = mysqli_real_escape_string($connection, $pic);
            $updateQuery2 = "UPDATE categorydisplay_admin SET pic = '$escapedImage' WHERE categoryid = '$id'";
            
        }else{
            echo "<script>alert('Invalid file typr. Only JPG, JPEG, PNG and GIF files are allowed.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
            exit();
        }
    }else{
        $updateQuery2 = "UPDATE categorydisplay_admin SET category = '$newCate' WHERE categoryid = '$id'";
    }

    if (mysqli_query($connection, $updateQuery2)) {
        // Refresh the user data after saving
        echo "<script>alert('Information udated successfully!');window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
    } else {
        echo "<p style='color: white; margin-left: 20px;>Error updating database: </p>" . mysqli_error($connection);
    }
}    

if(isset($_POST['btnAdd'])){
    $category = $_POST['newCategory'];

    if(!empty($category)){
        $query = "SELECT * FROM categorydisplay_admin WHERE category = '$category'";
        $check = mysqli_query($connection, $query);

        if(mysqli_num_rows($check) == 1){
            echo "<script>alert('Category already created. Please enter a new category.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
        }else{
            if(isset($_FILES['pic']) && $_FILES['pic']['error'] == 0){
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $fileInfo = pathinfo($_FILES['pic']['name']);
                $fileExt = strtolower($fileInfo['extension']);
        
                if(in_array($fileExt, $allowed)){
                    $pic = file_get_contents($_FILES['pic']['tmp_name']);
                    $escapedImage = mysqli_real_escape_string($connection, $pic);
                    $addQuery = "INSERT INTO categorydisplay_admin (category, pic) VALUES ('$category', '$escapedImage')";
                    
                    if (mysqli_query($connection, $addQuery)) {
                        // Refresh the user data after saving
                        echo "<script>alert('New category added successfully!');window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
                    } else {
                        echo "<p style='color: white; margin-left: 20px;>Error updating database: </p>" . mysqli_error($connection);
                    }
                }else{
                    echo "<script>alert('Invalid file typr. Only JPG, JPEG, PNG and GIF files are allowed.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
                    exit();
                }
            }else{
                echo "<script>alert('error adding new category.');</script>";
            }
        }
    }
}

if(isset($_POST['btnDelete'])){
    $id = mysqli_real_escape_string($connection, $_POST['categoryid']);
    $categoryName = mysqli_real_escape_string($connection, $_POST['categoryname']);
    $query = "DELETE FROM categorydisplay_admin WHERE categoryid = '$id'; DELETE FROM category WHERE category = '$categoryName';";
    
    if(mysqli_multi_query($connection, $query)){
        echo "<script>alert('Category deleted successfully!');window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
    }
}

if(isset($_POST['btnEdit3'])){
    $food_id = $_POST['food_id'];
    $name = $_POST['food_name'];
    $description = $_POST['description'];
    $categoryNew = $_POST['food_category'];

    if(isset($_FILES['img']) && $_FILES['img']['error'] == 0){
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileInfo = pathinfo($_FILES['img']['name']);
        $fileExt = strtolower($fileInfo['extension']);

        if(in_array($fileExt, $allowed)){
            $img = file_get_contents($_FILES['img']['tmp_name']);
            $escapedImage = mysqli_real_escape_string($connection, $img);
            
            $updateQuery = "UPDATE category SET foodname = '$name', description = '$description', category = '$categoryNew', img = '$escapedImage' WHERE food_id = '$food_id'";
        }else{
            echo "<script>alert('Invalid file typr. Only JPG, JPEG, PNG and GIF files are allowed.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
            exit();
        }
    }else{
        $updateQuery = "UPDATE category SET foodname = '$name', description = '$description', category = '$categoryNew' WHERE food_id = '$food_id'";
    }
    
    if (mysqli_query($connection, $updateQuery)) {
        echo "<script>alert('Information udated successfully!');window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
    } else {
        echo "<p style='color: white; margin-left: 20px';>Error updating database: </p>" . mysqli_error($connection);
    }
}

if (isset($_POST['btnAdd2'])){
    $newCategory = $_POST['newFoodCategory'];
    $fName = $_POST['newFood'];
    $Descrip = $_POST['newDescription'];

    if(!empty($newCategory) && !empty($fName) && !empty($Descrip)){
        $query = "SELECT * FROM category WHERE foodname = '$fName'";
        $check = mysqli_query($connection, $query);

        if(mysqli_num_rows($check) == 1){
            echo "<script>alert('Food already existing. Please add a new food.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
        }else{
            if(isset($_FILES['newPic']) && $_FILES['newPic']['error'] == 0){
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $fileInfo = pathinfo($_FILES['newPic']['name']);
                $fileExt = strtolower($fileInfo['extension']);
        
                if(in_array($fileExt, $allowed)){
                    $newPic = file_get_contents($_FILES['newPic']['tmp_name']);
                    $escapedImage = mysqli_real_escape_string($connection, $newPic);
                    $addQuery = "INSERT INTO category (category, foodname, description, img) VALUES ('$newCategory', '$fName', '$Descrip', '$escapedImage')";
                    
                    if (mysqli_query($connection, $addQuery)) {
                        // Refresh the user data after saving
                        echo "<script>alert('New food added successfully!');window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
                    } else {
                        echo "<p style='color: white; margin-left: 20px;>Error updating database: </p>" . mysqli_error($connection);
                    }
                }else{
                    echo "<script>alert('Invalid file typr. Only JPG, JPEG, PNG and GIF files are allowed.');window.location.href = '".$_SERVER['PHP_SELF']."'</script>";
                    exit();
                }
            }else{
                echo "<script>alert('error adding new food.');</script>";
            }
        }
    }
}

if(isset($_POST['btnDelete2'])){
    $deleteId = $_POST['food_id'];
    $query = "DELETE FROM category WHERE food_id = '$deleteId'";
    $deleteFood = mysqli_query($connection, $query);

    if($deleteFood){
        echo "<script>alert('Food deleted successfully!');window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="Food_Knowledge_Admin.css?v=<?php echo time(); ?>">
</head>
<body>
<div class='container'>
        <table>
            <tr>
                <td>
                    <div class='headpart'>
                    <?php foreach ($foodImgDisplay as $food): ?>
                        <div class='gif-container'>
                            <div class="gif-wrapper">
                                <div class="gif"><img src="showimage.php?gifid=<?php echo $food['gifid']; ?>" alt="pic"></div>
                                <button type="button" class="edit" onclick="openPopup('editPopup', '<?php echo $food['foodTitle']; ?>', '<?php echo $food['gifid']; ?>');"></button>
                                <input class="word" type="text" name="txtlabel" value="<?php echo htmlspecialchars($food['foodTitle']);?>" readonly>
                                <input type="hidden" name="foodid" value="<?php echo $food['gifid']; ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
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
                                    <button type="button" class="edit2" onclick="categoryPopup('edit2Popup', '<?php echo $categoryImg['categoryid']; ?>', '<?php echo $categoryImg['category']; ?>');"></button>
                                </div>
                            <?php endforeach; ?>
                            <div class="add" id="addCategory">
                                <div class="add-icon">+</div>
                                <p>Add New Category</p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr></tr>
        </table>

        <div class="overlay" id="overlay"></div>
        <div class='editContent' id='editPopup' style='display: none;'>
            <h2 align="left">Edit 🖋</h2>
            <form action="" method="post" enctype = "multipart/form-data">
                <table class="editTable">
                    <tr>
                        <input type="hidden" name="foodid" id="foodid">
                        <td><label>Content: </label></td>
                        <img id="editImagePreview" src="" alt="Food Image">
                        <td><input type="file" name="pic"></td>
                    </tr>
                    <tr>
                        <td><label>Food Title: </label></td>
                        <td><input class="label" type="text" name="label" id="editLabel"></td>
                    </tr>
                </table>
                <p class="editBtn">
                    <button type="submit" name="btnEdit">Save Changes</button>
                    <button type="button" onclick="closePopup('editPopup')">Cancel</button>
                </p>
            </form>
        </div>

        <div class="overlay" id="overlay"></div>
        <div class='editContent' id='edit2Popup' style='display: none;'>
            <h2 align="left">Edit 🖋</h2>
            <form action="" method="post" enctype = "multipart/form-data">
                <table class="editTable">
                    <tr>
                        <input type="hidden" name="categoryid" id="categoryid">
                        <td><label>Image: </label></td>
                        <img id="edit2ImagePreview" src="" alt="Category Image">
                        <td><input type="file" name="pic"></td>
                    </tr>
                    <tr>
                        <td><label>Category: </label></td>
                        <td><input class="label" type="text" name="categoryname" id="categoryName"></td>
                    </tr>
                </table>
                <p class="editBtn">
                    <button type="submit" name="btnEdit2">Save Changes</button>
                    <button class="deleteBtn" type="submit" name="btnDelete">Delete</button>
                    <button type="button" onclick="closePopup('edit2Popup')">Cancel</button>
                </p>
            </form>
        </div>

        <div class="overlay" id="overlay"></div>
        <div class='editContent' id='editPopup3' style='display: none;'>
            <h2 align="left">Edit 🖋</h2>
            <form action="" method="post" enctype = "multipart/form-data">
                <table class="editTable">
                    <tr>
                        <input type="hidden" name="food_id" id="food_id">
                        <td><label>Content: </label></td>
                        <img id="img" src="" alt="Food Image" width="200" height="150">
                        <td><input type="file" name="img"></td>
                    </tr>
                    <tr>
                        <td><label>Category: </label></td>
                        <td><input class="label" type="text" name="food_category" id="food_category" required></td>
                    </tr>
                    <tr>
                        <td><label>Food Name: </label></td>
                        <td><input class="label" type="text" name="food_name" id="food_name" required></td>
                    </tr>
                    <tr>
                        <td><label>Calories:</label></td>
                        <td><input class="label" type="text" id="description" name="description" required></td>
                    </tr>
                </table>
                <p class="editBtn">
                    <button type="submit" name="btnEdit3">Save Changes</button>
                    <button class="deleteBtn" type="submit" name="btnDelete2">Delete</button>
                    <button type="button" onclick="closePopup('editPopup3')">Cancel</button>
                </p>
            </form>
        </div>

        <div class="overlay" id="overlay"></div>
        <div class='AddCategory' id='addPopup' style='display: none;'>
            <h2 align="left">Add Category ➕</h2>
            <form action="" method="post" enctype = "multipart/form-data">
                <table class="AddTable">
                    <tr>
                        <td><label>Image: </label></td>
                        <img class="addimg" src="../images/add.png" alt="Category Image">
                        <td><input type="file" name="pic"></td>
                    </tr>
                    <tr>
                        <td>
                            <td><label>Category: </label></td>
                            <td><input class="label" type="text" name="newCategory" required></td>
                        </td>
                    </tr>
                </table>
                <p class="editBtn">
                    <button type="submit" name="btnAdd">Add</button>
                    <button type="button" onclick="closePopup('addPopup')">Cancel</button>
                </p>
            </form>
        </div>

        <div class="overlay" id="overlay"></div>
        <div class='AddFood' id='addPopup2' style='display: none;'>
            <h2 align="left">Add Food ➕</h2>
            <form action="" method="post" enctype = "multipart/form-data">
                <table class="AddTable">
                    <tr>
                        <td><label>Image: </label></td>
                        <img class="addimg" src="../images/add.png" alt="Food Image">
                        <td><input type="file" name="newPic"></td>
                    </tr>
                    <tr>
                        <td><label>Category: </label></td>
                        <td><input class="label" type="text" name="newFoodCategory" required></td>
                    </tr>
                    <tr>
                        <td><label>Food Name: </label></td>
                        <td><input class="label" type="text" name="newFood" required></td>
                    </tr>
                    <tr>
                        <td><label>Calories: </label></td>
                        <td><input class="label" type="text" name="newDescription" required></td>
                    </tr> 
                </table>
                <p class="editBtn">
                    <button type="submit" name="btnAdd2">Add</button>
                    <button type="button" onclick="closePopup('addPopup2')">Cancel</button>
                </p>
            </form>
        </div>

        <div class="function">
            <a href="#" class='all' onclick='refresh()'>All Foods</a>

            <form action="Food_Knowledge_Admin.php" method="get">
                <div class='searchbar'>
                    <input class="searchfield" type="text" name="txtfoodcontent" placeholder="Food Contents" value="<?php echo isset($_GET['txtfoodcontent']) ? htmlspecialchars($_GET['txtfoodcontent']) : ''; ?>" required>
                    <button type="submit" class="search" name="search">🔍</button>
                </div>
            </form>
        </div>

        <div class='list'>
            <div class='row'>
            <?php if (empty($_GET['txtfoodcontent'])) { ?>
            <div class="add2" id="addFood">
                <div class="add-icon">+</div>
                <p>Add New Food</p>
            </div>
            <?php } ?>
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
                                <button class="edit3" onclick="imagePopup('editPopup3', '<?php echo $fooddata['food_id']; ?>', '<?php echo $fooddata['category']; ?>', '<?php echo htmlspecialchars($fooddata['foodname']); ?>', '<?php echo htmlspecialchars($fooddata['description']); ?>');"></button>
                            </div>
                        <?php }
                    }else{
                        if (!empty($_GET['txtfoodcontent'])) {
                            echo "<h2>No matching records found. Please enter a new food name.</h2>";
                
                        }
                    }
                }      
                ?>
            </div>
        </div>

        <script>
            function startCategory(category){
                localStorage.setItem('selectedCategory', category);
                window.location.href = "Food_Knowledge_Admin.php?category=" + encodeURIComponent(category);
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
                window.location.href = "Food_Knowledge_Admin.php";
            }

            function categoryPopup(popupId, Id, category){
                document.getElementById("categoryid").value = Id;
                document.getElementById("categoryName").value = category;
                document.getElementById("edit2ImagePreview").src = "showimage.php?categoryid=" + Id;
                document.getElementById(popupId).style.display = "flex";
                document.getElementById("overlay").style.display = "block";
            }

            function openPopup(popupId, label, foodId){
                document.getElementById("editLabel").value = label;
                document.getElementById("foodid").value = foodId; 
                document.getElementById("editImagePreview").src = "showimage.php?gifid=" + foodId; 
                document.getElementById(popupId).style.display = "flex";
                document.getElementById("overlay").style.display = "block";
            }

            function imagePopup(popupId, foodId ,category, name, description){
                document.getElementById("food_category").value = category;
                document.getElementById("food_id").value = foodId;
                document.getElementById("food_name").value = name;
                document.getElementById("description").value = description;
                document.getElementById("img").src = "showimage.php?foodname=" + encodeURIComponent (name); 
                document.getElementById(popupId).style.display = "flex";
                document.getElementById("overlay").style.display = "block";
            }

            function closePopup(popupId) {
                document.getElementById(popupId).style.display = "none";
                document.getElementById("overlay").style.display = "none";
            }

            document.addEventListener("DOMContentLoaded", function(){
                const addCategoryBtn = document.getElementById("addCategory");
                const addPopup = document.getElementById("addPopup");
                const addFoodBtn = document.getElementById("addFood");
                const addPopup2 = document.getElementById("addPopup2");

                addCategoryBtn.addEventListener("click", function(){
                    addPopup.style.display = "flex";
                    document.getElementById("overlay").style.display = "block";
                });

                addFoodBtn.addEventListener("click", function(){
                    addPopup2.style.display = "flex";
                    document.getElementById("overlay").style.display = "block";
                })
            });

        </script>
    </div>
</body>
</html>
<?php
include '../general/footer.php';
?>