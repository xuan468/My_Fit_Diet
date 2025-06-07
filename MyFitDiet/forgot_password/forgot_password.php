<?php
session_start();
include '../general/dbconn.php'; // 确保路径正确

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $parents_name = strtolower($_POST['parents_name']); // 统一大小写
    $birthday_date =strtolower($_POST['birthday_date']); // 
    $favorite_food = strtolower($_POST['favorite_food']); // 统一大小写

    // 查询数据库以验证安全信息
    $query = "SELECT userID, email, parents_name, birthday_date, favorite_food FROM user WHERE email = ? AND parents_name = ? AND birthday_date = ? AND favorite_food = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssss", $email, $parents_name, $birthday_date, $favorite_food);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 安全验证通过，允许用户重置密码
        $_SESSION['reset_user'] = $email; // 存储用户名以便在重置页面使用
        echo "<script>alert('Verify Successful!'); window.location.href = '../forgot_password/reset_password.php';</script>";
        exit();
    } else {
        $error = "Security verification failed. Please check your information.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Verification</title>
    <link rel="stylesheet" href="../forgot_password/forgot_password.css">
</head>
<body>
    <div class="container">
        <h2>Security Verification</h2>`
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="parents_name">Parent's Name:</label>
                <input type="text" id="parents_name" name="parents_name" required>
            </div>
            <div class="form-group">
                <label for="birthday_date">Birthday Date:</label>
                <input type="text" id="birthday_date" name="birthday_date" placeholder="dd/mm/yyyy" required>
            </div>
            <div class="form-group">
                <label for="favorite_food">Favorite Food:</label>
                <input type="text" id="favorite_food" name="favorite_food" required>
            </div>
            <button type="submit">Verify</button>
        </form>
    </div>
</body>
</html>