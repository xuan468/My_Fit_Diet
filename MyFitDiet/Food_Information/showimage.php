<?php
include '../general/dbconn.php'; 

if (isset($_GET['categoryid'])) {
    $id = intval($_GET['categoryid']); 
    $query = "SELECT pic FROM categorydisplay_admin WHERE categoryid = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $imageData);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if($imageData){
        header("Content-Type: image/gif"); 
        echo $imageData; 
    }else {
        echo "Image not found.";
    }
    exit;
}

if (isset($_GET['gifid'])) {
    $id = intval($_GET['gifid']); 
    $query = "SELECT pic FROM fooddisplay_admin WHERE gifid = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $imageData);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if($imageData){
        header("Content-Type: image/gif"); 
        echo $imageData; 
    }else {
        echo "Image not found.";
    }
    exit;
}

if(isset($_GET['foodname'])) {
    $foodname = $_GET['foodname'];
    $imageQuery = "SELECT img FROM category WHERE foodname= ?";
    $stmt = mysqli_prepare($connection, $imageQuery);
    mysqli_stmt_bind_param($stmt, "s", $foodname);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $imageData);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if($imageData){
        $info = getimagesizefromstring($imageData);
        header("Content-Type: ".$info['mime']);
        echo $imageData;
    }else {
        echo "Image not found.";
    }
    exit;
}

if (isset($_GET['recipe_id'])) {
    $recipe_id = intval($_GET['recipe_id']); 

    $query = "SELECT img FROM recipe WHERE recipe_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $recipe_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $imageData);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!empty($imageData)) {
        $info = getimagesizefromstring($imageData);
        header("Content-Type: " . $info['mime']);
        echo $imageData;
    } else {
        echo "Image not found.";
    }
    exit;
}
    
?>